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
     */
    public function doOnSuccess($operationResponse, $operationType)
    {
        $cartId = (int) $operationResponse['orderDetails']['orderId'];
        $orderId = Order::getOrderByCartId($cartId);
        $order = new Order($orderId);

        $responseData = PayzenTools::convertRestResult($operationResponse);
        $response = new PayzenResponse($responseData, null, null, null);

        // Save refund transaction in PrestaShop.
        $this->payzen->createMessage($order, $response);
        $this->payzen->savePayment($order, $response, $operationType === 'cancel');

        $transAmount = $order->total_paid;

        $orderCurrency = new Currency((int) $order->id_currency);
        $currency = Lyranetwork\Payzen\Sdk\Form\Api::findCurrencyByAlphaCode($orderCurrency->iso_code);
        $transAmount = Tools::ps_round($transAmount, $currency->getDecimals());
        $amountInCents = $currency->convertAmountToInteger($transAmount);
        $refundedAmount = $operationResponse['amount'];

        $isManualUpdateRefundStatus = isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses) && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True');
        if (!$isManualUpdateRefundStatus && $refundedAmount == $amountInCents) {
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
    public function Translate($message)
    {
        return $this->payzen->l($message);
    }
}