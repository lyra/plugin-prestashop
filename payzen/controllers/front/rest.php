<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class PayzenRestModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $current_cart;
    private $logger;

    public function __construct()
    {
        parent::__construct();

        $this->logger = PayzenTools::getLogger();
    }

    public function getToken()
    {
        $cart = $this->context->cart;

        $payment = new PayzenStandardPayment();
        $token = $payment->getFormToken($cart);

        if ($token) {
            $json = array('token' => $token);

            if (Tools::getValue('refreshIdentifierToken')) {
                $identifierToken = $payment->getFormToken($cart, true);
                $json['identifierToken'] = $identifierToken;
            }
        } else {
           $json = array(
               'status' => 'error',
               'message' => $this->l('Error when creating token.')
            );
        }

        die(Tools::jsonEncode($json));
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Tools::getValue('refreshToken')) {
            $this->logger->logInfo('Cart has been updated, let\'s refresh form token.');
            $this->getToken();

            return;
        }

        $this->logger->logInfo("User return to shop process starts.");

        if (! $this->checkRestReturnValidity()) {
            $this->logger->logError('Invalid return request received, redirect to home page. Content: ' . print_r($_POST, true));
            Tools::redirectLink('index.php');
        }

        $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
        $sha_key = $test_mode ? Configuration::get('PAYZEN_RETKEY_TEST') : Configuration::get('PAYZEN_RETKEY_PROD');

        // Use direct post content to avoid stipslashes from json data.
        $data = $_POST;

        // Check the authenticity of the request.
        if (! PayzenTools::checkHash($data, $sha_key)) {
            $ip = Tools::getRemoteAddr();
            $this->logger->logError("{$ip} tries to access module/payzen/rest page without valid signature with parameters: " . print_r($data, true));

            Tools::redirectLink('index.php');
        }

        $answer = json_decode($data['kr-answer'], true);
        if (! is_array($answer)) {
            $this->logger->logError('Invalid return request received, redirect to home page. Content of kr-answer: ' . $data['kr-answer']);
            Tools::redirectLink('index.php');
        }

        // Wrap payment result to use traditional order creation tunnel.
        $data = PayzenTools::convertRestResult($answer);

        require_once _PS_MODULE_DIR_ . 'payzen/classes/PayzenResponse.php';

        /** @var PayzenResponse $response */
        $response = new PayzenResponse($data, null, null, null);

        $cart_id = (int) $response->get('order_id');
        $cart = new Cart($cart_id);

        // Get order ID by cart ID.
        $order_id = Order::getOrderByCartId($cart_id);

        if ($order_id == false) {
            // Order has not been processed yet.

            $new_state = (int) Payzen::nextOrderState($response);

            if ($response->isAcceptedPayment()) {
                $this->logger->logWarning("Payment for cart #$cart_id has been processed by client return! This means the IPN URL did not work.");
                $this->logger->logInfo("Payment accepted for cart #$cart_id. New order state is $new_state.");

                $order = $this->module->saveOrder($cart, $new_state, $response);

                // Redirect to success page.
                $this->redirectSuccess($order, true);
            } else {
                // Payment KO.

                $save_on_failure = (Configuration::get('PAYZEN_FAILURE_MANAGEMENT') === PayzenTools::ON_FAILURE_SAVE);
                if ($save_on_failure) {
                    // Save on failure option is selected: save order and go to history page.
                    $this->logger->logWarning("Payment for order #$cart_id has been processed by client return! This means the IPN URL did not work.");
                    $this->logger->logInfo("Save on failure option is selected: save failed order for cart #$cart_id. New order state is $new_state.");

                    $order = $this->module->saveOrder($cart, $new_state, $response);

                    $this->logger->logInfo("Redirect to history page, cart ID: #$cart_id.");
                    Tools::redirect('index.php?controller=history');
                } else {
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
                            $page .= '&cgv=1&id_carrier=' . $cart->id_carrier;
                        }
                    }

                    Tools::redirect('index.php?controller=' . $page);
                }
            }
        } else {
            // Order already registered.
            $order = new Order((int) $order_id);

            if ($response->isAcceptedPayment()) {
                // Just display a confirmation message.
                $this->logger->logInfo("Payment success confirmed for cart #$cart_id.");
                $this->redirectSuccess($order);
            } else {
                // Just redirect to order history page.
                $this->logger->logInfo("Payment failure confirmed for cart #$cart_id.");
                Tools::redirect('index.php?controller=history');
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
            $link .= '&error=yes';
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

        Tools::redirect($link);
    }

    private function checkRestReturnValidity()
    {
        return Tools::getIsset('kr-hash') && Tools::getIsset('kr-hash-algorithm') && Tools::getIsset('kr-answer');
    }
}
