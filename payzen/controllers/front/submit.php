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
 * This controller manages return from payment gateway.
 */
use Lyranetwork\Payzen\Sdk\Form\Response as PayzenResponse;

class PayzenSubmitModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    private $currentCart;
    private $logger;

    public function __construct()
    {
        parent::__construct();

        $this->logger = PayzenTools::getLogger();
    }

    public function postProcess()
    {
        $cart_id = Tools::getValue('vads_order_id');
        $this->currentCart = new Cart((int) $cart_id);

        $this->logger->logInfo("User return to shop process starts for cart #$cart_id.");

        // Page to redirect to if errors.
        $page = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';

        // Check cart errors.
        if (! Validate::isLoadedObject($this->currentCart) || $this->currentCart->nbProducts() <= 0) {
            $this->logger->logWarning("Cart is empty, redirect to cart page. Cart ID: $cart_id.");

            $this->payzenRedirect('index.php?controller=' . $page);
        }

        if (! $this->currentCart->id_customer || ! $this->currentCart->id_address_delivery ||
            ! $this->currentCart->id_address_invoice || ! $this->module->active) {
            $this->logger->logWarning("No address selected for customer or module disabled, redirect to first checkout step. Cart ID: $cart_id.");

            if (version_compare(_PS_VERSION_, '1.7', '<') && ! Configuration::get('PS_ORDER_PROCESS_TYPE')) {
              $page .= '&step=1'; // Not one page checkout, goto first checkout step.
            }

            $this->payzenRedirect('index.php?controller=' . $page);
        }

        $this->processPaymentReturn();
    }

    private function processPaymentReturn()
    {
        /** @var PayzenResponse $response */
        $response = new PayzenResponse(
            $_REQUEST,
            Configuration::get('PAYZEN_MODE'),
            Configuration::get('PAYZEN_KEY_TEST'),
            Configuration::get('PAYZEN_KEY_PROD'),
            Configuration::get('PAYZEN_SIGN_ALGO')
        );

        // Check the authenticity of the request.
        if (! $response->isAuthentified()) {
            $ip = Tools::getRemoteAddr();
            $this->logger->logError("{$ip} tries to access module/payzen/submit page without valid signature with parameters: " . print_r($_REQUEST, true));
            $this->logger->logError('Signature algorithm selected in module settings must be the same as one selected in gateway Back Office.');

            Tools::redirectLink('index.php');
        }

        $cart_id = $this->currentCart->id;

        // Rebuild context.
        try {
            PayzenTools::rebuildContext($this->currentCart);
        } catch (Exception $e) {
            $this->logger->logError("Error while rebuilding context for cart #{$cart_id}: {$e->getMessage()}.");
            Tools::redirectLink('index.php');
        }

        // Search order in db.
        $order_id = PayzenTools::getOrderByCartId($cart_id);

        if (! $order_id) {
            // Order has not been processed yet.
            $new_state = (int) Payzen::nextOrderState($response);

            if ($response->isAcceptedPayment()) {
                $this->logger->logWarning("Payment for cart #$cart_id has been processed by client return! This means the IPN URL did not work.");
                $this->logger->logInfo("Payment accepted for cart #$cart_id. New order state is $new_state.");

                $order = $this->module->saveOrder($this->currentCart, $new_state, $response);

                // Redirect to success page.
                $this->redirectSuccess($order, true);
            } else {
                // Payment KO.
                $save_on_failure = (Configuration::get('PAYZEN_FAILURE_MANAGEMENT') === PayzenTools::ON_FAILURE_SAVE);
                if ($save_on_failure || Payzen::isOney($response)) {
                    // Save on failure option is selected or Oney payment: save order and go to history page.
                    $this->logger->logWarning("Payment for order #$cart_id has been processed by client return! This means the IPN URL did not work.");

                    $msg = Payzen::isOney($response) ? 'Oney payment' : 'Save on failure option is selected';
                    $this->logger->logInfo("$msg: save failed order for cart #$cart_id. New order state is $new_state.");

                    $order = $this->module->saveOrder($this->currentCart, $new_state, $response);

                    $this->logger->logInfo("Redirect to history page, cart ID: #$cart_id.");
                    $this->payzenRedirect('index.php?controller=history');
                } else {
                    $this->context->cookie->id_cart = $cart_id;

                    // Option 2 choosen: get back to checkout process.
                    $this->logger->logInfo("Payment failed, redirect to order checkout page, cart ID: #$cart_id.");

                    if (! $response->isCancelledPayment()) {
                        // ... and show message if not cancelled.
                        $this->context->cookie->payzenPayErrors = $this->module->l('Your payment was not accepted. Please, try to re-order.', 'submit');
                    }

                    $page = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
                    if (version_compare(_PS_VERSION_, '1.7', '<') && ! Configuration::get('PS_ORDER_PROCESS_TYPE')) {
                        $page .= '&step=3';

                        if (version_compare(_PS_VERSION_, '1.5.1', '<')) {
                            $page .= '&cgv=1&id_carrier=' . $this->currentCart->id_carrier;
                        }
                    }

                    if ($response->get('payment_src') === 'MOTO') {
                        $page .= "&recover_cart=$cart_id&token_cart=" . md5(_COOKIE_KEY_ . 'recover_cart_' . $cart_id);
                    }

                    $this->payzenRedirect('index.php?controller=' . $page);
                }
            }
        } else {
            // Order already registered.
            $this->logger->logInfo("Order already registered for cart #$cart_id.");

            $order = new Order((int) $order_id);
            $old_state = (int) $order->getCurrentState();

            $this->logger->logInfo("The current state for order corresponding to cart #$cart_id is ($old_state).");

            $outofstock = Payzen::isOutOfStock($order);
            $new_state = (int) Payzen::nextOrderState($response, $outofstock);

            // For PrestaShop < 1.6.1, expect either original "Out of stock" state or ours to check order consistency.
            $bc_os_state_valid = $outofstock;
            if (! Configuration::get('PS_OS_OUTOFSTOCK_PAID')) {
                $bc_os_state_valid &= Payzen::isStateInArray($new_state, array('PS_OS_OUTOFSTOCK', 'PAYZEN_OS_PAYMENT_OUTOFSTOCK'));
            }

            if (($old_state === $new_state) || $bc_os_state_valid) {
                // No changes, just display a confirmation message.
                $this->logger->logInfo("No changes for order associated with cart #$cart_id, order remains in state ($old_state).");

                if ($response->isAcceptedPayment()) {
                    // Just display a confirmation message.
                    $this->logger->logInfo("Payment success confirmed for cart #$cart_id.");
                    $this->redirectSuccess($order);
                } else {
                    // Just redirect to order history page.
                    $this->logger->logInfo("Payment failure confirmed for cart #$cart_id.");
                    $this->payzenRedirect('index.php?controller=history');
                }
            } elseif (Payzen::isStateInArray($old_state, Payzen::getManagedStates())) {
                if (($old_state == Configuration::get('PS_OS_ERROR')) && $response->isAcceptedPayment() && Payzen::hasAmountError($order)) {
                    // Amount paid not equals initial amount.
                    $this->logger->logWarning(
                        "Error: amount paid {$order->total_paid_real} not equals initial amount {$order->total_paid}. Order is in a failed state, cart #$cart_id."
                    );
                    $this->redirectSuccess($order);
                } else {
                    // Order is in a pending state, payment is not pending: error case.
                    $this->logger->logWarning("Error: inconsistent order state ($old_state) and payment result ({$response->getTransStatus()}), cart ID: #$cart_id.");
                    $this->payzenRedirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $this->module->id .
                        '&id_order=' . $order->id . '&key=' . $order->secure_key . '&error=yes'
                    );
                }
            } else {
                $this->logger->logWarning("Unknown order state ID ($old_state) for cart #$cart_id. Managed by merchant.");

                if ($response->isAcceptedPayment()) {
                    // Redirect to success page.
                    $this->logger->logInfo("Payment success for cart #$cart_id. Redirect to success page.");
                    $this->redirectSuccess($order);
                } else {
                    $this->logger->logInfo("Payment failure for cart #$cart_id. Redirect to history page.");
                    $this->payzenRedirect('index.php?controller=history');
                }
            }
        }
    }

    private function redirectSuccess($order, $check = false)
    {
        // Display a confirmation message.
        $link = 'index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id .
             '&id_order=' . $order->id . '&key=' . $order->secure_key;

        // Amount paid not equals initial amount. Error!
        if (Payzen::hasAmountError($order)) {
            $link .= '&amount_error=yes';
        }

        if (Configuration::get('PAYZEN_MODE') === 'TEST') {
            if ($check) {
                // TEST mode (user is the webmaster): IPN did not work, so we display a warning.
                $link .= '&check_url_warn=yes';
            }

            if (PayzenTools::$plugin_features['prodfaq']) {
                $link .= '&prod_info=yes';
            }
        }

        $this->payzenRedirect($link);
    }

    private function payzenRedirect($url)
    {
        Tools::redirect($url);
    }
}
