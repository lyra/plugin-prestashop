<?php

/**
 * Copyright © Lyra Network and contributors.
 * This file is part of PayZen plugin for Prestashop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
 */

use Lyranetwork\Payzen\Sdk\Refund\Processor as RefundProcessor;
use Lyranetwork\Payzen\Sdk\Form\Response as PayzenResponse;

class PayzenRefundProcessor implements RefundProcessor
{
    protected $payzen;
    protected $context;

    public function __construct()
    {
        $this->payzen = new Payzen();
        $this->context = $this->payzen->getContext();
    }

    public function doOnError($errorCode, $message)
    {
        // Allow offline refund and display warning message.
        $this->context->cookie->payzenRefundWarn = $message;
    }

    /**
     * Action to do after sucessful refund process.
     *
     * @throws Exception
     */
    public function doOnSuccess($operationResponse, $operationType)
    {
        // Retrieve Order from its Id.
        $cartId = (int) $operationResponse['orderDetails']['orderId'];
        $orderId = Order::getOrderByCartId($cartId);

        if (! $orderId) {
            return;
        }

        $order = new Order($orderId);

        // Retrieve order currency.
        $orderCurrency = new Currency((int) $order->id_currency);
        $currency = Lyranetwork\Payzen\Sdk\Form\Api::findCurrencyByAlphaCode($orderCurrency->iso_code);

        // Total amount paid by the client.
        $orderAmount = Tools::ps_round($order->total_paid, $currency->getDecimals());
        $orderAmountInCents = $currency->convertAmountToInteger($orderAmount);

        $orderDetails = OrderDetail::getList($orderId);
        $orderRefundedAmount = 0;
        foreach($orderDetails as $orderDetail){
            // Retrieve the amount already refunded in PrestaShop.
            if (version_compare(_PS_VERSION_, '1.6', '<=')) {
                $orderRefundedAmount += Tools::ps_round($orderDetail["product_quantity_refunded"] * $orderDetail["total_price_tax_incl"], $currency->getDecimals());
            } else {
                $orderRefundedAmount += Tools::ps_round($orderDetail["total_refunded_tax_incl"], $currency->getDecimals());
            }
        }

        $orderRefundedAmountInCents = $currency->convertAmountToInteger($orderRefundedAmount);

        // Sum of refund request amount and amount already refunded in PrestaShop.
        $refundAmount = $operationResponse['amount'] + $orderRefundedAmountInCents;

        // Amount refunded on the Back Office.
        $transRefundedAmount = 0;
        if (isset($operationResponse['refundedAmount']) && $operationResponse['refundedAmount']) {
            $transRefundedAmount = $operationResponse['refundedAmount'];
        }

        if (isset($operationResponse['refundedAmountMulti']) && $operationResponse['refundedAmountMulti']) {
            $refundAmount = $operationResponse['refundedAmountMulti'] + $orderRefundedAmountInCents;
        }

        if ($operationType == 'frac_update') {
            if ($transRefundedAmount == $refundAmount && $refundAmount == $orderAmountInCents) {
                $this->context->cookie->payzenSplitPaymentUpdateRefundStatus = "True";
                $order->setCurrentState((int) Configuration::get('PAYZEN_OS_REFUNDED'));
            } elseif(! ($transRefundedAmount == $refundAmount && $transRefundedAmount < $orderAmountInCents)){
                $msg = sprintf($this->translate('Refund of split payment is not supported. Please, consider making necessary changes in %1$s Back Office.'), 'PayZen');

                throw new \Exception($msg);
            }

            return;
        }

        $responseData = PayzenTools::convertRestResult($operationResponse);
        $response = new PayzenResponse($responseData, null, null, null);

        // Save refund transaction in PrestaShop.
        $this->payzen->createMessage($order, $response);
        $this->payzen->savePayment($order, $response, $operationType === 'cancel');

        $isManualUpdateRefundStatus = isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses) && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True');
        if (!$isManualUpdateRefundStatus && $refundAmount == $orderAmountInCents) {
            $order->setCurrentState((int) Configuration::get('PAYZEN_OS_REFUNDED'));
        }
    }

    /**
     * Action to do after failed refund process.
     *
     */
    public function doOnFailure($errorCode, $message)
    {
        $this->context->cookie->payzenRefundWarn = $message;
        if (isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses)
            && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True')) {
            unset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses);
        }

        $this->doOnError($errorCode, $message);
    }

    /**
     * Log informations.
     *
     */
    public function log($message, $level)
    {
        switch ($level) {
            case "ERROR":
                PayzenTools::getLogger()->logError($message);
                break;

            case "WARNING":
                PayzenTools::getLogger()->logWarning($message);
                break;

            case "INFO":
                PayzenTools::getLogger()->logInfo($message);
                break;

            default:
                PayzenTools::getLogger()->log($message);
                break;
        }
    }

    /**
     * Translate given message.
     *
     */
    public function translate($message)
    {
        return $this->payzen->l($message);
    }
}