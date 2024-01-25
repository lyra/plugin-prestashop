<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

/**
 * Instant payment notification file. Wait for payment gateway confirmation, then validate order.
 */
use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;
use Lyranetwork\Payzen\Sdk\Form\Response as PayzenResponse;

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.inc.php';
require_once dirname(__FILE__) . '/payzen.php';

// Module logger object.
$logger = PayzenTools::getLogger();

$save_on_failure = true;
$header_error_500 = 'HTTP/1.1 500 Internal Server Error';

if (PayzenTools::checkRestIpnValidity()) {
    // Use direct post content to avoid stipslashes from json data.
    $data = $_POST;

    $answer = json_decode($data['kr-answer'], true);
    if (! is_array($answer)) {
        $logger->logError('Invalid REST IPN request received. Content of kr-answer: ' . $data['kr-answer']);

        header($header_error_500, true, 500);
        die('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
    }

    // Ignore IPN response with "ABANDONED" status.
    if ($answer['orderStatus'] === 'ABANDONED') {
        $cartId = $answer['orderDetails']['orderId'];
        $logger->logWarning('Server call on cancelation' . ($cartId ? ' for cart #' . $cartId : '') . '. No order will be created.');

        die('<span style="display:none">KO-Payment abandoned.' . "\n" . '</span>');
    }

    $save_on_failure &= isset($answer['orderCycle']) && ($answer['orderCycle'] === 'CLOSED');

    // Wrap payment result to use traditional order creation tunnel.
    $data = PayzenTools::convertRestResult($answer);

    $cart_id = (int) $data['vads_order_id'];

    $logger->logInfo("Server call process starts for cart #$cart_id.");

    // Shopping cart object.
    $cart = new Cart($cart_id);

    // Rebuild context.
    try {
        PayzenTools::rebuildContext($cart);
    } catch (Exception $e) {
        $logger->logError($e->getMessage() . ' Cart ID: #' . $cart->id);

        header($header_error_500, true, 500);
        die('<span style="display:none">KO-' . $e->getMessage(). "\n" . '</span>');
    }

    $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
    $sha_key = $test_mode ? Configuration::get('PAYZEN_PRIVKEY_TEST') : Configuration::get('PAYZEN_PRIVKEY_PROD');

    if (! PayzenTools::checkHash($_POST, $sha_key)) {
        $ip = Tools::getRemoteAddr();
        $logger->logError("{$ip} tries to access validation.php page without valid signature with data: " . print_r($_POST, true));

        header($header_error_500, true, 500);
        die('<span style="display:none">KO-An error occurred while computing the signature.' . "\n" . '</span>');
    }

    /** @var PayzenResponse $response */
    $response = new PayzenResponse($data, null, null, null);
} elseif (PayzenTools::checkFormIpnValidity()) {
    $cart_id = (int) Tools::getValue('vads_order_id');

    $logger->logInfo("Server call process starts for cart #$cart_id.");

    // Shopping cart object.
    $cart = new Cart($cart_id);

    // Rebuild context.
    try {
        PayzenTools::rebuildContext($cart);
    } catch (Exception $e) {
        $logger->logError($e->getMessage() . " Cart ID: #{$cart->id}.");

        header($header_error_500, true, 500);
        die('<span style="display:none">KO-' . $e->getMessage(). "\n" . '</span>');
    }

    /** @var PayzenResponse $response */
    $response = new PayzenResponse(
        $_POST,
        Configuration::get('PAYZEN_MODE'),
        Configuration::get('PAYZEN_KEY_TEST'),
        Configuration::get('PAYZEN_KEY_PROD'),
        Configuration::get('PAYZEN_SIGN_ALGO')
    );

    // Check the authenticity of the request.
    if (! $response->isAuthentified()) {
        $ip = Tools::getRemoteAddr();
        $logger->logError("{$ip} tries to access validation.php page without valid signature with data: " . print_r($_POST, true));
        $logger->logError('Signature algorithm selected in module settings must be the same as one selected in gateway Back Office.');

        header($header_error_500, true, 500);
        die($response->getOutputForGateway('auth_fail'));
    }
} else {
    $logger->logError('Invalid IPN request received. Content: ' . print_r($_POST, true));

    header($header_error_500, true, 500);
    die('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
}

// Module main object.
$payzen = new Payzen();

// Search order in db.
$order_id = Order::getOrderByCartId($cart_id);

if (! $order_id) {
    // Order has not been processed yet.
    $new_state = (int) Payzen::nextOrderState($response);

    if ($response->isAcceptedPayment()) {
        $logger->logInfo("Payment accepted for cart #$cart_id. New order state is $new_state.");

        $order = $payzen->saveOrder($cart, $new_state, $response);

        if (Payzen::hasAmountError($order)) {
            // Amount paid not equals initial amount.
            $msg = "Error: amount paid {$order->total_paid_real} is not equal to initial amount {$order->total_paid}.";
            $msg .= " Order is in a failed state, cart #$cart_id.";
            $logger->logWarning($msg);

            header($header_error_500, true, 500);
            die($response->getOutputForGateway('amount_error'));
        } else {
            // Response to server.
            die($response->getOutputForGateway('payment_ok'));
        }
    } else {
        // Payment KO.
        $logger->logInfo("Payment failed for cart #$cart_id.");

        $save_on_failure &= (Configuration::get('PAYZEN_FAILURE_MANAGEMENT') === PayzenTools::ON_FAILURE_SAVE);
        if ($save_on_failure || Payzen::isOney($response)) {
            // Save on failure option is selected or Oney payment.
            $msg = Payzen::isOney($response) ? 'Oney payment' : 'Save on failure option is selected';
            $logger->logInfo("$msg: save failed order for cart #$cart_id. New order state is $new_state.");
            $order = $payzen->saveOrder($cart, $new_state, $response);

            die($response->getOutputForGateway('payment_ko'));
        } else {
            die($response->getOutputForGateway('payment_ko_bis'));
        }
    }
} else {
    // Order already registered.
    $logger->logInfo("Order #$order_id already registered for cart #$cart_id.");

    // Ignore IPN on cancelation for already registered orders.
    if ($response->getTransStatus() === 'ABANDONED') {
        $logger->logWarning('Server call on cancelation for cart #' . $cart_id . '. No order will be updated.');

        die('<span style="display:none">KO-Payment abandoned.' . "\n" . '</span>');
    }

    $order = new Order((int) $order_id);
    $old_state = (int) $order->getCurrentState();

    $logger->logInfo("The current state for order corresponding to cart #$cart_id is ($old_state).");

    // Check if  it is a partial payment.
    $is_partial_payment = false;

    $currency = PayzenApi::findCurrency($response->get('currency'));
    $decimals = $currency->getDecimals();
    $paid_total = $currency->convertAmountToFloat($response->get('amount'));

    // Check if this is a partial payment.
    if (number_format($order->total_paid_real, $decimals) !== number_format($paid_total, $decimals)) {
        $is_partial_payment = true;
    }

    $outofstock = Payzen::isOutOfStock($order);
    $new_state = (int) Payzen::nextOrderState($response, $outofstock, $old_state, $is_partial_payment);

    // Final states.
    $consistent_states = array(
        'PS_OS_OUTOFSTOCK_PAID', // Override paid state since PrestaShop 1.6.1.
        'PAYZEN_OS_PAYMENT_OUTOFSTOCK', // Paid state for PrestaShop < 1.6.1.
        'PS_OS_PAYMENT',
        'PAYZEN_OS_TRANS_PENDING',
        'PAYZEN_OS_REFUNDED',
        'PS_OS_CANCELED'
    );

    // If the payment is not the first in sequence, do not update order state.
    $first_payment = ($response->get('sequence_number') === '1')
        || Payzen::isFirstSequenceInOrderPayments($order, $response->get('trans_id'), $response->get('sequence_number'));

    if (($old_state === $new_state) || ! $first_payment) {
        // No changes, just display a confirmation message.
        $logger->logInfo("No state change for order associated with cart #$cart_id, order remains in state ({$old_state}).");

        // Do not create payment if it is cancelled partial debit payment OR order is in final status PAYZEN_OS_REFUNDED.
        $force_stop_payment_creation = (Configuration::get('PAYZEN_OS_REFUNDED') == $old_state) ||
                                       (($response->isCancelledPayment() || ($response->getTransStatus() === 'CANCELLED'))
                                       && ($response->get('operation_type') === 'DEBIT') && $is_partial_payment);

        $payzen->savePayment($order, $response, $force_stop_payment_creation);
        $payzen->createMessage($order, $response);

        if ($response->isAcceptedPayment()) {
            $msg = 'payment_ok_already_done';
        } else {
            $msg = 'payment_ko_already_done';
        }

        die($response->getOutputForGateway($msg));
    } elseif (Payzen::isPaidOrder($order) &&
        (! Payzen::isStateInArray($new_state, $consistent_states) || ($response->get('url_check_src') === 'PAY'))) {
        // Order cannot move from final paid state to not completed states.
        $logger->logInfo("Order is successfully registered for cart #$cart_id but platform returns a payment error, transaction status is {$response->getTransStatus()}.");

        header($header_error_500, true, 500);
        die($response->getOutputForGateway('payment_ko_on_order_ok'));
    } elseif (! $old_state || Payzen::isStateInArray($old_state, Payzen::getManagedStates())) {
        if (($old_state === Configuration::get('PS_OS_ERROR')) && $response->isAcceptedPayment() &&
            Payzen::hasAmountError($order)) {
            // Amount paid not equals amount.
            $msg = "Error: amount paid {$order->total_paid_real} is not equal to initial amount {$order->total_paid}.";
            $msg .= " Order is in a failed state, cart #$cart_id.";
            $logger->logWarning($msg);

            header($header_error_500, true, 500);
            die($response->getOutputForGateway('amount_error'));
        }

        if (! $old_state) {
            $logger->logWarning("Current order state for cart #$cart_id is empty! Something went wrong. Try to set it anyway.");
        }

        $payzen->setOrderState($order, $new_state, $response);

        $logger->logInfo("Order is successfully updated for cart #$cart_id.");
        die($response->getOutputForGateway($response->isAcceptedPayment() ? 'payment_ok' : 'payment_ko'));
    } else {
        $logger->logWarning("Unknown order state ID ($old_state) for cart #$cart_id. Managed by merchant.");
        die($response->getOutputForGateway('ok', 'Unknown order status.'));
    }
}
