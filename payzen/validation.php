<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  payment
 * @package   payzen
 */

/**
 * Instant payment notification file. Wait for PayZen payment confirmation, then validate order.
 */

require_once dirname(dirname(dirname(__FILE__))).'/config/config.inc.php';

if (($cart_id = (int)Tools::getValue('vads_order_id')) && Tools::getValue('vads_hash')) {
    /* module main object */
    require_once(dirname(__FILE__).'/payzen.php');
    $payzen = new Payzen();

    /* module logger object */
    $logger = PayzenTools::getLogger();
    $logger->logInfo("Server call process starts for cart #$cart_id.");

    /* shopping cart object */
    $cart = new Cart($cart_id);

    /* cart errors */
    $trans_id = htmlspecialchars(Tools::getValue('vads_trans_id'), ENT_COMPAT, 'UTF-8');
    if (!Validate::isLoadedObject($cart)) {
        $logger->logError("Cart #$cart_id not found in database.");
        die('<span style="display:none">KO-'.$trans_id."=Impossible de retrouver la commande\n</span>");
    } elseif ($cart->nbProducts() <= 0) {
        $logger->logError("Cart #$cart_id was emptied before redirection.");
        die('<span style="display:none">KO-'.$trans_id."=Le panier a été vidé avant la redirection\n</span>");
    }

    /* rebuild context */
    if (isset($cart->id_shop)) {
        $_GET['id_shop'] = $cart->id_shop;
        Context::getContext()->shop = Shop::initialize();
    }

    Context::getContext()->customer = new Customer((int)$cart->id_customer);
    Context::getContext()->cart = $cart = new Cart((int)$cart_id); // reload cart to take into account customer group

    $address = new Address((int)$cart->id_address_invoice);
    Context::getContext()->country = new Country((int)$address->id_country);
    Context::getContext()->language = new Language((int)$cart->id_lang);
    Context::getContext()->currency = new Currency((int)$cart->id_currency);
    Context::getContext()->link = new Link();

    require_once _PS_MODULE_DIR_.'payzen/classes/PayzenResponse.php';

    /** @var PayzenResponse $response */
    $response = new PayzenResponse(
        $_POST,
        Configuration::get('PAYZEN_MODE'),
        Configuration::get('PAYZEN_KEY_TEST'),
        Configuration::get('PAYZEN_KEY_PROD'),
        Configuration::get('PAYZEN_SIGN_ALGO')
    );

    /* check the authenticity of the request */
    if (!$response->isAuthentified()) {
        $ip = Tools::getRemoteAddr();
        $logger->logError("{$ip} tries to access validation.php page without valid signature with parameters: ".print_r($_POST, true));
        //$logger->logError('Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.');

        die($response->getOutputForPlatform('auth_fail'));
    }

    /* search order in db */
    $order_id = Order::getOrderByCartId($cart_id);

    if ($order_id == false) {
        /* order has not been processed yet */

        $new_state = (int)Payzen::nextOrderState($response);

        if ($response->isAcceptedPayment()) {
            $logger->logInfo("Payment accepted for cart #$cart_id. New order state is $new_state.");

            $order = $payzen->saveOrder($cart, $new_state, $response);

            if (Payzen::hasAmountError($order)) {
                /* amount paid not equals initial amount. */
                $msg = "Error: amount paid {$order->total_paid_real} not equals initial amount {$order->total_paid}.";
                $msg .= " Order is in a failed state, cart #$cart_id.";
                $logger->logWarning($msg);

                die($response->getOutputForPlatform('ko', 'Le montant payé est différent du montant intial'));
            } else {
                /* response to server */
                die($response->getOutputForPlatform('payment_ok'));
            }
        } else {
            /* payment KO */
            $logger->logInfo("Payment failed for cart #$cart_id.");

            $save_on_failure = (Configuration::get('PAYZEN_FAILURE_MANAGEMENT') == PayzenTools::ON_FAILURE_SAVE);
            if ($save_on_failure || Payzen::isOney($response)) {
                /* save on failure option is selected or Oney payment */
                $msg = Payzen::isOney($response) ? 'FacilyPay Oney payment' : 'Save on failure option is selected';
                $logger->logInfo("$msg : save failed order for cart #$cart_id. New order state is $new_state.");
                $order = $payzen->saveOrder($cart, $new_state, $response);
            }

            die($response->getOutputForPlatform('payment_ko'));
        }
    } else {
        /* order already registered */
        $logger->logInfo("Order #$order_id already registered for cart #$cart_id.");

        $order = new Order((int)$order_id);
        $old_state = (int)$order->getCurrentState();

        $logger->logInfo("The current state for order corresponding to cart #$cart_id is ($old_state).");

        // check if a total refun of order was made
        $total_refund = false;

        if ($response->get('operation_type') === 'CREDIT') {
            $currency = PayzenApi::findCurrency($response->get('currency'));
            $decimals = $currency->getDecimals();
            $paid_total = $currency->convertAmountToFloat($response->get('amount'));

            if (number_format($order->total_paid_real, $decimals) == number_format($paid_total, $decimals)) {
                $total_refund = true;
            }
        }

        $outofstock = Payzen::isOutOfStock($order);
        $new_state = (int)Payzen::nextOrderState($response, $total_refund, $outofstock);

        // if the payment is not the first in sequence, do not update order state
        $first_payment = ($response->get('sequence_number') == '1');

        if (($old_state === $new_state) || !$first_payment) {
            /* no changes, just display a confirmation message */
            $logger->logInfo("No state change for order associated with cart #$cart_id, order remains in state ({$old_state}).");

            $payzen->savePayment($order, $response);
            $payzen->createMessage($order, $response);

            if ($response->isAcceptedPayment()) {
                $msg = 'payment_ok_already_done';
            } else {
                $msg = 'payment_ko_already_done';
            }

            die($response->getOutputForPlatform($msg));
        } elseif (!$old_state || Payzen::isStateInArray($old_state, Payzen::getManagedStates())) {
            if (($old_state == Configuration::get('PS_OS_ERROR')) && $response->isAcceptedPayment() && Payzen::hasAmountError($order)) {
                /* amount paid not equals initial amount. */
                $logger->logWarning(
                    "Error: amount paid {$order->total_paid_real} not equals initial amount {$order->total_paid}. Order is in a failed state, cart #$cart_id."
                );
                die($response->getOutputForPlatform('ko', 'Le montant payé est différent du montant intial'));
            }

            if (!$old_state) {
                $logger->logWarn("Old order state for cart #$cart_id is empty! Something went wrong. Try to set it anyway.");
            }

            $payzen->setOrderState($order, $new_state, $response);

            $logger->logInfo("Order is successfully updated for cart #$cart_id.");
            die($response->getOutputForPlatform($response->isAcceptedPayment() ? 'payment_ok' : 'payment_ko'));
        } else {
            $logger->logWarning("Unknown order state ID ($old_state) for cart #$cart_id. Managed by merchant.");
            die($response->getOutputForPlatform('ok', 'Statut de commande inconnu'));
        }
    }
}
