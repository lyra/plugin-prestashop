<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (! defined('_PS_VERSION_')) {
    exit;
}

use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;
use Lyranetwork\Payzen\Sdk\Rest\Api as PayzenRest;

class PayzenStandardPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_STD_';
    protected $tpl_name = 'payment_std.tpl';
    protected $logo = 'standard.png';
    protected $name = 'standard';

    protected $allow_backend_payment = true;

    public function getTplVars($cart, $force_customer_wallet = false)
    {
        $vars = parent::getTplVars($cart);

        $embedded = ($this->isEmbedded() || $force_customer_wallet);

        // For 1.6 versions get if js files loaded at end.
        if ($embedded && version_compare(_PS_VERSION_, '1.7', '<') && (bool) Configuration::get('PS_JS_DEFER')) {
            // kr_public_key.
            $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
            if ($pub_key = $test_mode ? Configuration::get('PAYZEN_PUBKEY_TEST') : Configuration::get('PAYZEN_PUBKEY_PROD')) {
                $vars['payzen_set_std_rest_kr_public_key'] = $pub_key;
            }

            // Return url.
            if ($return_url = $this->context->link->getModuleLink('payzen', 'rest', array(), true)) {
                $vars['payzen_set_std_rest_return_url'] = $return_url;
            }

            // REST placeholders config.
            $language = Language::getLanguage((int) $this->context->cart->id_lang);
            $rest_placeholders = @unserialize(Configuration::get('PAYZEN_STD_REST_PLACEHLDR'));

            // kr-placeholder-pan.
            if ($pan_label = $rest_placeholders['pan'][$language['id_lang']]) {
                $vars['payzen_set_std_rest_kr_placeholder_pan'] = $pan_label;
            }

            // kr-placeholder-expiry.
            if ($expiry_label = $rest_placeholders['expiry'][$language['id_lang']]) {
                $vars['payzen_set_std_rest_kr_placeholder_expiry'] = $expiry_label;
            }

            // kr-placeholder-security-code.
            if ($cvv_label = $rest_placeholders['cvv'][$language['id_lang']]) {
                $vars['payzen_set_std_rest_kr_placeholder_security_code'] = $cvv_label;
            }

            // kr-label-do-register.
            if ($register_card_label = Configuration::get('PAYZEN_STD_REST_LBL_REGIST', $language['id_lang'])) {
                $vars['payzen_set_std_rest_kr_label_do_register'] = $register_card_label;
            }
        }

        // Current language or default if not supported.
        $language = Language::getLanguage((int) $cart->id_lang);
        $language_iso_code = Tools::strtolower($language['iso_code']);
        if (! PayzenApi::isSupportedLanguage($language_iso_code)) {
            $language_iso_code = Configuration::get('PAYZEN_DEFAULT_LANGUAGE');
        }

        $vars['payzen_set_std_rest_language'] = $language_iso_code;

        if ($this->isFromBackend()) {
            $vars['payzen_std_card_data_mode'] = '1';
            return $vars;
        }

        $controller = $this->context->controller;

        $entry_mode = ($controller instanceof PayzenWalletModuleFrontController) ? PayzenTools::MODE_SMARTFORM : $this->getEntryMode();
        $vars['payzen_std_card_data_mode'] = $entry_mode;
        $vars['payzen_std_rest_popin_mode'] = ($controller instanceof PayzenWalletModuleFrontController) ? false : Configuration::get('PAYZEN_STD_REST_POPIN_MODE');
        $vars['payzen_std_rest_theme'] = Configuration::get('PAYZEN_STD_REST_THEME');
        $vars['payzen_std_smartform_compact_mode'] = Configuration::get('PAYZEN_STD_SF_COMPACT_MODE');
        $vars['payzen_std_smartform_payment_means_grouping_threshold'] = Configuration::get('PAYZEN_STD_SF_THRESHOLD') ?
            Configuration::get('PAYZEN_STD_SMARTFORM_PAYMENT_MEANS_GROUPING_THRESHOLD') : 'False';
        $vars['payzen_std_display_title'] = $this->displayTitle();

        // Payment by identifier.
        $vars['payzen_is_valid_std_identifier'] = false;
        $vars['payzen_saved_payment_mean'] = '';

        $vars['payzen_rest_form_token'] = '';
        $vars['payzen_rest_identifier_token'] = '';

        if ($this->isValidSavedAlias()) {
            $vars['payzen_is_valid_std_identifier'] = true;
            $customers_config = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));
            $vars['payzen_saved_payment_mean'] = isset($customers_config[$cart->id_customer][$this->name]['m']) ?
                $customers_config[$cart->id_customer][$this->name]['m'] : '';
        }

        if ($entry_mode === PayzenTools::MODE_LOCAL_TYPE /* Card type on website. */) {
            $vars['payzen_avail_cards'] = $this->getPaymentCards();
        } elseif ($entry_mode === PayzenTools::MODE_IFRAME /* Iframe mode. */) {
            $vars['payzen_can_cancel_iframe'] = (Configuration::get($this->prefix . 'CANCEL_IFRAME') === 'True');
            $this->tpl_name = 'payment_std_iframe.tpl';
        } elseif ($embedded /* REST API. */) {
            $form_token = $this->getFormToken($cart);

            if ($form_token) {
                // REST API params.
                $vars['payzen_rest_form_token'] = $form_token;
                $vars['payzen_rest_identifier_token'] = $form_token;

                if (Configuration::get('PAYZEN_STD_USE_WALLET') == 'True') {
                    $vars['payzen_is_valid_std_identifier'] = false;
                } else {
                    // Customer has an identifier and it is valid.
                    $identifier_token = $this->getFormToken($cart, true);
                    if ($identifier_token) {
                        $vars['payzen_rest_identifier_token'] = $identifier_token;
                    }
                }

                $this->tpl_name = 'payment_std_rest.tpl';
            } else {
                // Form token not generated by platform, force payment using default mode.
                $vars['payzen_std_card_data_mode'] = '1';
            }
        }

        return $vars;
    }

    private function getPaymentCards()
    {
        // Get selected card types.
        $cards = Configuration::get($this->prefix . 'PAYMENT_CARDS');
        if (! empty($cards)) {
            $cards = explode(';', $cards);
        } else {
            // No card type selected, display all supported cards.
            $cards = array_keys(PayzenTools::getSupportedCardTypes());
        }

        // Retrieve card labels.
        $avail_cards = array();
        foreach (PayzenApi::getSupportedCardTypes() as $code => $label) {
            if (in_array($code, $cards)) {
                $card = array(
                    'label' => $label,
                    'logo' => self::getCcTypeImageSrc($code)
                );

                $avail_cards[$code] = $card;
            }
        }

        return $avail_cards;
    }

    public function getEntryMode()
    {
        // Get data entry mode.
        return Configuration::get($this->prefix . 'CARD_DATA_MODE');
    }

    private function displayTitle()
    {
        if (! $this->isSmartform()) {
            return 'True';
        }

        if ($this->isSmartform() && Configuration::get('PAYZEN_STD_REST_POPIN_MODE') === 'True') {
            return 'True';
        }

        if (sizeOf(PayzenTools::getActivePaymentMethods()) > 1) {
            return 'True';
        }

        return Configuration::get('PAYZEN_STD_SF_DISPLAY_TITLE');
    }

    private function checkSsl()
    {
        return Configuration::get('PS_SSL_ENABLED') && Tools::usingSecureMode();
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // Set payment_src to MOTO for backend payments.
        if (isset($this->context->cookie->payzenBackendPayment)) {
            $request->set('payment_src', 'MOTO');
            unset($this->context->cookie->payzenBackendPayment);

            $request->set('payment_cards', Configuration::get($this->prefix . 'PAYMENT_CARDS'));

            return $request;
        }

        if (isset($data['iframe_mode']) && $data['iframe_mode']) {
            $request->set('action_mode', 'IFRAME');

            // Hide logos below payment fields.
            $request->set('theme_config', $request->get('theme_config') . '3DS_LOGOS=false;');

            // Enable automatic redirection.
            $request->set('redirect_enabled', '1');
            $request->set('redirect_success_timeout', '0');
            $request->set('redirect_error_timeout', '0');

            $return_url = $request->get('url_return');
            $sep = strpos($return_url, '?') === false ? '?' : '&';
            $request->set('url_return', $return_url . $sep . 'content_only=1');
        }

        if (isset($data['card_type']) && $data['card_type']) {
            // Override payment_cards parameter.
            $request->set('payment_cards', $data['card_type']);

            if ($data['card_type'] === 'BANCONTACT') {
                // May not disable 3DS for Bancontact Mistercash.
                $request->set('threeds_mpi', null);
            }
        } else {
            $cards = Configuration::get($this->prefix . 'PAYMENT_CARDS');
            $request->set('payment_cards', $cards);
        }

        // Payment by alias.
        $customer = new Customer((int) $cart->id_customer);

        if ($this->isOneClickActive() && $customer->id) {
            $isCustomerWallet = $this->isEmbedded() && (Configuration::get('PAYZEN_STD_USE_WALLET') == 'True') || $this->accountCustomerwallet();
            if ($isCustomerWallet) {
                $controller = $this->context->controller;
                if ($controller instanceof PayzenWalletModuleFrontController) {
                    $request->set('amount', 0);
                    $request->set('order_id', null);
                    $request->addExtInfo('from_account', true);
                }

                $request->set('page_action', 'CUSTOMER_WALLET');
            } elseif ((! isset($data['force_identifier']) || $data['force_identifier']) && $this->isValidSavedAlias()) {
                // Customer has an identifier and it is valid.
                $customers_config = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));
                $saved_identifier = isset($customers_config[$cart->id_customer][$this->name]['n']) ? $customers_config[$cart->id_customer][$this->name]['n'] : '';
                $request->set('identifier', $saved_identifier);

                $use_identifier = isset($data['payment_by_identifier']) ? $data['payment_by_identifier'] === '1' : false;
                if (! $use_identifier) {
                    // Customer choose to not use alias.
                    $request->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Card data entry on payment page, let's ask customer for data registration.
                PayzenTools::getLogger()->logInfo('Customer ' . $request->get('cust_email') . ' will be asked for card data registration on payment page.');
                $request->set('page_action', 'ASK_REGISTER_PAY');
            }
        }

        return $request;
    }

    public function getFormToken($cart, $use_identifier = false)
    {
        $request = $this->prepareRequest($cart, array(
            'force_identifier' => $use_identifier,
            'payment_by_identifier' => $use_identifier ? '1' : '0'
        ));

        $strong_auth = $this->getEscapedVar($request, 'threeds_mpi') === '2' ? 'DISABLED' : 'AUTO';
        $currency = PayzenApi::findCurrencyByNumCode($this->getEscapedVar($request, 'currency'));
        $cart_id = $this->getEscapedVar($request, 'order_id');
        $cust_mail = $this->getEscapedVar($request, 'cust_email');

        $params = array(
            'orderId' => $cart_id,
            'customer' => array(
                'email' => $cust_mail,
                'reference' => $this->getEscapedVar($request, 'cust_id'),
                'billingDetails' => array(
                    'language' => $this->getEscapedVar($request, 'language'),
                    'title' => $this->getEscapedVar($request, 'cust_title'),
                    'firstName' => $this->getEscapedVar($request, 'cust_first_name'),
                    'lastName' => $this->getEscapedVar($request, 'cust_last_name'),
                    'category' => $this->getEscapedVar($request, 'cust_status'),
                    'address' => $this->getEscapedVar($request, 'cust_address'),
                    'zipCode' => $this->getEscapedVar($request, 'cust_zip'),
                    'city' => $this->getEscapedVar($request, 'cust_city'),
                    'state' => $this->getEscapedVar($request, 'cust_state'),
                    'phoneNumber' => $this->getEscapedVar($request, 'cust_phone'),
                    'country' => $this->getEscapedVar($request, 'cust_country')
                ),
                'shippingDetails' => array(
                    'firstName' => $this->getEscapedVar($request, 'ship_to_first_name'),
                    'lastName' => $this->getEscapedVar($request, 'ship_to_last_name'),
                    'category' => $this->getEscapedVar($request, 'ship_to_status'),
                    'address' => $this->getEscapedVar($request, 'ship_to_street'),
                    'address2' => $this->getEscapedVar($request, 'ship_to_street2'),
                    'zipCode' => $this->getEscapedVar($request, 'ship_to_zip'),
                    'city' => $this->getEscapedVar($request, 'ship_to_city'),
                    'state' => $this->getEscapedVar($request, 'ship_to_state'),
                    'phoneNumber' => $this->getEscapedVar($request, 'ship_to_phone_num'),
                    'country' => $this->getEscapedVar($request, 'ship_to_country'),
                    'deliveryCompanyName' => $this->getEscapedVar($request, 'ship_to_delivery_company_name'),
                    'shippingMethod' => $this->getEscapedVar($request, 'ship_to_type'),
                    'shippingSpeed' => $this->getEscapedVar($request, 'ship_to_speed')
                ),
                'shoppingCart' => array(
                    'cartItemInfo' => $this->getCartData($request)
                )
            ),
            'transactionOptions' => array(
                'cardOptions' => array(
                    'captureDelay' => $this->getEscapedVar($request, 'capture_delay'), // In case of Smartform, only payment means supporting capture delay will be shown.
                    'paymentSource' => 'EC'
                )
            ),
            'contrib' => $this->getEscapedVar($request, 'contrib'),
            'strongAuthentication' => $strong_auth,
            'currency' => $currency->getAlpha3(),
            'amount' => $this->getEscapedVar($request, 'amount'),
            'metadata' => array(
                'module_id' => $this->name
            )
        );

        if ($request->get('page_action') == 'CUSTOMER_WALLET') {
            $params['metadata']['is_customer_wallet'] = true;
            if ($request->get('vads_ext_info_from_account')) {
                $params['metadata']['from_account'] = true;
            }
        }

        $validationMode = Configuration::get('PAYZEN_STD_VALIDATION');
        if ($validationMode !== "") {
            $validationMode = ($validationMode === '-1') ? Configuration::get('PAYZEN_VALIDATION_MODE') : $validationMode;

            if ($validationMode !== "") {
                $params['transactionOptions']['cardOptions']['manualValidation'] = ($validationMode === '1') ? 'YES' : 'NO';
            }
        }

        // Set Number of attempts in case of rejected payment.
        if (Configuration::get($this->prefix . 'REST_ATTEMPTS') !== null) {
            $params['transactionOptions']['cardOptions']['retry'] = Configuration::get($this->prefix . 'REST_ATTEMPTS');
        }

        if ($use_identifier) {
            if ($saved_identifier = $this->getEscapedVar($request, 'identifier')) {
                PayzenTools::getLogger()->logInfo("Customer {$cust_mail} has an identifier. Use it for payment of cart #{$cart_id}.");

                $params['paymentMethodToken'] = $saved_identifier;
            } else {
                return false;
            }
        }

        $params['formAction'] = $this->getEscapedVar($request, 'page_action');

        if ($this->isSmartform()) {
            // Filter payment means when creating the payment token.
            $params['paymentMethods'] = $this->getPaymentMeansForSmartform($cart);
        }

        $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
        $key = $test_mode ? Configuration::get('PAYZEN_PRIVKEY_TEST') : Configuration::get('PAYZEN_PRIVKEY_PROD');
        $site_id = Configuration::get('PAYZEN_SITE_ID');

        $return = false;
        if ($request->get('vads_ext_info_from_account')) {
            $webservice = 'CreateToken';
            $metadata = "user $cust_mail.";
        } else {
            $webservice = 'CreatePayment';
            $metadata = "cart #$cart_id.";
        }

        $data = json_encode($params);
        PayzenTools::getLogger()->logInfo("Creating form token for cart #{$cart_id} with parameters: {$data}");

        try {
            $client = new PayzenRest(Configuration::get('PAYZEN_REST_SERVER_URL'), $site_id, $key);
            $result = $client->post('V4/Charge/'. $webservice, $data);

            if ($result['status'] !== 'SUCCESS') {
                PayzenTools::getLogger()->logError("Error while creating payment form token for {$metadata}: " . $result['answer']['errorMessage']
                    . ' (' . $result['answer']['errorCode'] . ').');

                if (isset($result['answer']['detailedErrorMessage']) && ! empty($result['answer']['detailedErrorMessage'])) {
                    PayzenTools::getLogger()->logError('Detailed message: ' . $result['answer']['detailedErrorMessage']
                        . ' (' . $result['answer']['detailedErrorCode'] . ').');
                }
            } else {
                // Payment form token created successfully.
                PayzenTools::getLogger()->logInfo("Form token created successfully for {$metadata}");
                $return = $result['answer']['formToken'];
            }
        } catch (Exception $e) {
            PayzenTools::getLogger()->logError("{$e->getMessage() }" . ($e->getCode() > 0 ? ' (' . $e->getCode() . ').' : ''));
        }

        return $return;
    }

    private function getEscapedVar($request, $var)
    {
        $value = $request->get($var);

        if (empty($value)) {
            return null;
        }

        return $value;
    }

    private function getCartData($request)
    {
        $nbProducts = $this->getEscapedVar($request, "nb_products");
        if (! $nbProducts) {
            return array();
        }

        $products = array();
        for($index = 0; $index < $nbProducts; ++$index) {
            $product = array(
                "productLabel" => $this->getEscapedVar($request, "product_label" . $index),
                "productType" => $this->getEscapedVar($request, "product_type" . $index),
                "productRef" => $this->getEscapedVar($request, "product_ref" . $index),
                "productQty" => $this->getEscapedVar($request, "product_qty" . $index),
                "productAmount" => $this->getEscapedVar($request, "product_amount" . $index),
                "productVat" => $this->getEscapedVar($request, "product_vat" . $index)
            );

            array_push($products, $product);
        }

        return $products;
    }

    public function hasForm()
    {
        if ($this->getEntryMode() === PayzenTools::MODE_FORM) {
            return false;
        }

        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment by credit card');
    }

    /**
     * Check if the embedded payment fields option is choosen.
     *
     * @return boolean
     */
    public function isEmbedded()
    {
        if ($this->isFromBackend()) {
            return false;
        }

        $embedded = array(
            PayzenTools::MODE_EMBEDDED,
            PayzenTools::MODE_SMARTFORM,
            PayzenTools::MODE_SMARTFORM_EXT_WITH_LOGOS,
            PayzenTools::MODE_SMARTFORM_EXT_WITHOUT_LOGOS
        );

        return in_array($this->getEntryMode(), $embedded);
    }

    /**
     * Check if the Smartform payment fields option is choosen.
     *
     * @return boolean
     */
    public function isSmartform()
    {
        if ($this->isFromBackend()) {
            return false;
        }

        $smartform = array(
            PayzenTools::MODE_SMARTFORM,
            PayzenTools::MODE_SMARTFORM_EXT_WITH_LOGOS,
            PayzenTools::MODE_SMARTFORM_EXT_WITHOUT_LOGOS
        );

        return in_array($this->getEntryMode(), $smartform);
    }

    public function isOneClickActive()
    {
        if ($this->isFromBackend()) {
            return false;
        }

        return Configuration::get($this->prefix . '1_CLICK_PAYMENT') === 'True';
    }

    private function getPaymentMeansForSmartform($cart)
    {
        $paymentCards = Configuration::get('PAYZEN_STD_PAYMENT_CARDS');

        // If "ALL" is selected, let the gateway manage the payment means to display.
        if (empty($paymentCards)) {
            return array();
        }

        // Get standard payments means.
        $stdPaymentMeans = explode(';', $paymentCards);

        // Get other payment means that are embedded.
        $otherEmbeddedPaymentMeans = array();

        if (Configuration::get('PAYZEN_OTHER_ENABLED') === 'True') {
            $otherPaymentMeans = @unserialize(Configuration::get('PAYZEN_OTHER_PAYMENT_MEANS'));
            $amount = $cart->getOrderTotal();
            $billing_address = new Address((int) $cart->id_address_invoice);
            $billing_country = new Country((int) $billing_address->id_country);

            foreach ($otherPaymentMeans as $key => $option) {
                $countries = isset($option['countries']) ? $option['countries'] : array(); // Authorized countries for this option.

                if (isset($option['embedded']) && ($option['embedded'] === 'True')
                    && ! (! empty($option['min_amount']) && $option['min_amount'] != 0 && $amount < $option['min_amount'])
                    && ! (! empty($option['max_amount']) && $option['max_amount'] != 0 && $amount > $option['max_amount'])
                    && (empty($countries) || in_array($billing_country->iso_code, $countries))) {
                        array_push($otherEmbeddedPaymentMeans, $option['code']);
                }
            }
        }

        // Merge standard and other payment means.
        return array_merge($stdPaymentMeans, $otherEmbeddedPaymentMeans);
    }
}
