<?php
/**
 * PayZen V2-Payment Module version 1.4.7 for PrestaShop 1.4. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (! class_exists('PayzenRequest', false)) {

    /**
     * Class managing and preparing request parameters and HTML rendering of request.
     */
    class PayzenRequest
    {

        /**
         * The fields to send to the PayZen platform.
         *
         * @var array[string][PayzenField]
         * @access private
         */
        private $requestParameters;

        /**
         * Certificate to use in TEST mode.
         *
         * @var string
         * @access private
         */
        private $keyTest;

        /**
         * Certificate to use in PRODUCTION mode.
         *
         * @var string
         * @access private
         */
        private $keyProd;

        /**
         * URL of the payment page.
         *
         * @var string
         * @access private
         */
        private $platformUrl;

        /**
         * Set to true to send the vads_redirect_* parameters.
         *
         * @var boolean
         * @access private
         */
        private $redirectEnabled;

        /**
         * Algo used to sign forms.
         *
         * @var string
         * @access private
         */
        private $algo = PayzenApi::ALGO_SHA1;

        /**
         * The original data encoding.
         *
         * @var string
         * @access private
         */
        private $encoding;

        /**
         * The list of categories for payment with Accord bank.
         * To be sent with the products detail if you use this payment mean.
         */
        public static $ACCORD_CATEGORIES = array(
            'FOOD_AND_GROCERY',
            'AUTOMOTIVE',
            'ENTERTAINMENT',
            'HOME_AND_GARDEN',
            'HOME_APPLIANCE',
            'AUCTION_AND_GROUP_BUYING',
            'FLOWERS_AND_GIFTS',
            'COMPUTER_AND_SOFTWARE',
            'HEALTH_AND_BEAUTY',
            'SERVICE_FOR_INDIVIDUAL',
            'SERVICE_FOR_BUSINESS',
            'SPORTS',
            'CLOTHING_AND_ACCESSORIES',
            'TRAVEL',
            'HOME_AUDIO_PHOTO_VIDEO',
            'TELEPHONY'
        );

        public function __construct($encoding = 'UTF-8')
        {
            // initialize encoding
            $this->encoding = in_array(strtoupper($encoding), PayzenApi::$SUPPORTED_ENCODINGS) ?
                strtoupper($encoding) : 'UTF-8';

            // parameters' regular expressions
            $ans = '[^<>]'; // Any character (except the dreadful "<" and ">")
            $an63 = '#^[A-Za-z0-9]{0,63}$#u';
            $ans255 = '#^' . $ans . '{0,255}$#u';
            $ans127 = '#^' . $ans . '{0,127}$#u';
            $supzero = '[1-9]\d*';
            $regex_payment_cfg = '#^(SINGLE|MULTI:first=\d+;count=' . $supzero . ';period=' . $supzero . ')$#u';
            // AAAAMMJJhhmmss
            $regex_trans_date = '#^\d{4}(1[0-2]|0[1-9])(3[01]|[1-2]\d|0[1-9])(2[0-3]|[0-1]\d)([0-5]\d){2}$#u';
            $regex_sub_effect_date = '#^\d{4}(1[0-2]|0[1-9])(3[01]|[1-2]\d|0[1-9])$#u';
            $regex_mail = '#^[^@]+@[^@]+\.\w{2,4}$#u'; // TODO plus restrictif
            $regex_params = '#^([^&=]+=[^&=]*)?(&[^&=]+=[^&=]*)*$#u'; // name1=value1&name2=value2...
            $regex_ship_type = '#^RECLAIM_IN_SHOP|RELAY_POINT|RECLAIM_IN_STATION|PACKAGE_DELIVERY_COMPANY|ETICKET$#u';
            $regex_payment_option = '#^[a-zA-Z0-9]{0,32}$|^COUNT=([1-9][0-9]{0,2})?;RATE=[0-9]{0,4}(\\.[0-9]{1,4})?;DESC=.{0,64};?$#';

            // defining all parameters and setting formats and default values
            $this->addField('signature', 'Signature', '#^[0-9a-f]{40}$#u', true);

            $this->addField('vads_action_mode', 'Action mode', '#^INTERACTIVE|SILENT$#u', true, 11);
            $this->addField('vads_amount', 'Amount', '#^' . $supzero . '$#u', true);
            $this->addField('vads_available_languages', 'Available languages', '#^(|[A-Za-z]{2}(;[A-Za-z]{2})*)$#u', false, 2);
            $this->addField('vads_capture_delay', 'Capture delay', '#^\d*$#u');
            $this->addField('vads_card_number', 'Card number', '#^\d{13,19}$#u');
            $this->addField('vads_contracts', 'Contracts', $ans255);
            $this->addField('vads_contrib', 'Contribution', $ans255);
            $this->addField('vads_ctx_mode', 'Mode', '#^TEST|PRODUCTION$#u', true);
            $this->addField('vads_currency', 'Currency', '#^\d{3}$#u', true, 3);
            $this->addField('vads_cust_address', 'Customer address', $ans255);
            $this->addField('vads_cust_antecedents', 'Customer history', '#^NONE|NO_INCIDENT|INCIDENT$#u');
            $this->addField('vads_cust_cell_phone', 'Customer cell phone', $an63, false, 63);
            $this->addField('vads_cust_city', 'Customer city', '#^' . $ans . '{0,63}$#u', false, 63);
            $this->addField('vads_cust_country', 'Customer country', '#^[A-Za-z]{2}$#u', false, 2);
            $this->addField('vads_cust_email', 'Customer email', $regex_mail, false, 127);
            $this->addField('vads_cust_first_name', 'Customer first name', $an63, false, 63);
            $this->addField('vads_cust_id', 'Customer id', $an63, false, 63);
            $this->addField('vads_cust_last_name', 'Customer last name', $an63, false, 63);
            $this->addField('vads_cust_legal_name', 'Customer legal name', '#^' . $ans . '{0,100}$#u', false, 100);
            $this->addField('vads_cust_name', 'Customer name', $ans127, false, 127);
            $this->addField('vads_cust_phone', 'Customer phone', $an63, false, 63);
            $this->addField('vads_cust_state', 'Customer state/region', '#^' . $ans . '{0,63}$#u', false, 63);
            $this->addField('vads_cust_status', 'Customer status (private or company)', '#^PRIVATE|COMPANY$#u', false, 7);
            $this->addField('vads_cust_title', 'Customer title', '#^' . $ans . '{0,63}$#u', false, 63);
            $this->addField('vads_cust_zip', 'Customer zip code', $an63, false, 63);
            $this->addField('vads_cvv', 'Card verification number', '#^\d{3,4}$#u');
            $this->addField('vads_expiry_month', 'Month of card expiration', '#^\d[0-2]{1}$#u');
            $this->addField('vads_expiry_year', 'Year of card expiration', '#^20[0-9]{2}$#u');
            $this->addField('vads_identifier', 'Identifier', '#^'.$ans.'{0,50}$#u', false, 50);
            $this->addField('vads_insurance_amount', 'The amount of insurance', '#^' . $supzero . '$#u', false, 12);
            $this->addField('vads_language', 'Language', '#^[A-Za-z]{2}$#u', false, 2);
            $this->addField('vads_nb_products', 'Number of products', '#^' . $supzero . '$#u', false);
            $this->addField('vads_order_id', 'Order id', '#^[A-za-z0-9]{0,12}$#u', false, 12);
            $this->addField('vads_order_info', 'Order info', $ans255);
            $this->addField('vads_order_info2', 'Order info 2', $ans255);
            $this->addField('vads_order_info3', 'Order info 3', $ans255);
            $this->addField('vads_page_action', 'Page action', '#^PAYMENT$#u', true, 7);
            $this->addField('vads_payment_cards', 'Payment cards', '#^([A-Za-z0-9\-_]+;)*[A-Za-z0-9\-_]*$#u', false, 127);
            $this->addField('vads_payment_config', 'Payment config', $regex_payment_cfg, true);
            $this->addField('vads_payment_option_code', 'Payment option to use', $regex_payment_option, false);
            $this->addField('vads_payment_src', 'Payment source', '#^$#u', false, 0);
            $this->addField('vads_redirect_error_message', 'Redirection error message', $ans255, false);
            $this->addField('vads_redirect_error_timeout', 'Redirection error timeout', $ans255, false);
            $this->addField('vads_redirect_success_message', 'Redirection success message', $ans255, false);
            $this->addField('vads_redirect_success_timeout', 'Redirection success timeout', $ans255, false);
            $this->addField('vads_return_get_params', 'GET return parameters', $regex_params, false);
            $this->addField('vads_return_mode', 'Return mode', '#^NONE|GET|POST$#u', false, 4);
            $this->addField('vads_return_post_params', 'POST return parameters', $regex_params, false);
            $this->addField('vads_ship_to_city', 'Shipping city', '#^' . $ans . '{0,63}$#u', false, 63);
            $this->addField('vads_ship_to_country', 'Shipping country', '#^[A-Za-z]{2}$#u', false, 2);
            $this->addField('vads_ship_to_delay', 'Delay of shipping', '#^INFERIOR_EQUALS|SUPERIOR|IMMEDIATE|ALWAYS$#u', false, 15);
            $this->addField('vads_ship_to_delivery_company_name', 'Name of the delivery company', $ans127, false, 127);
            $this->addField('vads_ship_to_first_name', 'Shipping first name', $an63, false, 63);
            $this->addField('vads_ship_to_last_name', 'Shipping last name', $an63, false, 63);
            $this->addField('vads_ship_to_legal_name', 'Shipping legal name', '#^' . $ans . '{0,100}$#u', false, 100);
            $this->addField('vads_ship_to_name', 'Shipping name', '#^' . $ans . '{0,127}$#u', false, 127);
            $this->addField('vads_ship_to_phone_num', 'Shipping phone', $ans255, false, 63);
            $this->addField('vads_ship_to_speed', 'Speed of the shipping method', '#^STANDARD|EXPRESS|PRIORITY$#u', false, 8);
            $this->addField('vads_ship_to_state', 'Shipping state', $an63, false, 63);
            $this->addField('vads_ship_to_status', 'Shipping status (private or company)', '#^PRIVATE|COMPANY$#u', false, 7);
            $this->addField('vads_ship_to_street', 'Shipping street', $ans127, false, 127);
            $this->addField('vads_ship_to_street2', 'Shipping street (2)', $ans127, false, 127);
            $this->addField('vads_ship_to_type', 'Type of the shipping method', $regex_ship_type, false, 24);
            $this->addField('vads_ship_to_zip', 'Shipping zip code', $an63, false, 63);
            $this->addField('vads_shipping_amount', 'The amount of shipping', '#^' . $supzero . '$#u', false, 12);
            $this->addField('vads_shop_name', 'Shop name', $ans127);
            $this->addField('vads_shop_url', 'Shop URL', '#^https?://(\w+(:\w*)?@)?(\S+)(:[0-9]+)?[\w\#!:.?+=&%@`~;,|!\-/]*$#u');
            $this->addField('vads_site_id', 'Shop ID', '#^\d{8}$#u', true, 8);
            $this->addField('vads_tax_amount', 'The amount of tax', '#^' . $supzero . '$#u', false, 12);
            $this->addField('vads_tax_rate', 'The rate of tax', '#^\d{1,2}\.\d{1,4}$#u', false, 6);
            $this->addField('vads_theme_config', 'Theme configuration', '#^[^;=]+=[^;=]*(;[^;=]+=[^;=]*)*;?$#u');
            $this->addField('vads_totalamount_vat', 'The total amount of VAT', '#^' . $supzero . '$#u', false, 12);
            $this->addField('vads_threeds_mpi', 'Enable / disable 3D Secure', '#^[0-2]$#u', false);
            $this->addField('vads_trans_date', 'Transaction date', $regex_trans_date, true, 14);
            $this->addField('vads_trans_id', 'Transaction ID', '#^[0-8]\d{5}$#u', true, 6);
            $this->addField('vads_url_cancel', 'Cancel URL', $ans127, false, 127);
            $this->addField('vads_url_error', 'Error URL', $ans127, false, 127);
            $this->addField('vads_url_referral', 'Referral URL', $ans127, false, 127);
            $this->addField('vads_url_refused', 'Refused URL', $ans127, false, 127);
            $this->addField('vads_url_return', 'Return URL', $ans127, false, 127);
            $this->addField('vads_url_success', 'Success URL', $ans127, false, 127);
            $this->addField('vads_user_info', 'User info', $ans255);
            $this->addField('vads_validation_mode', 'Validation mode', '#^[01]?$#u', false, 1);
            $this->addField('vads_version', 'Platform version', '#^V2$#u', true, 2);

            // Subscription payment fields
            $this->addField('vads_sub_amount', 'Subscription amount', '#^' . $supzero . '$#u');
            $this->addField('vads_sub_currency', 'Subscription currency', '#^\d{3}$#u', false, 3);
            $this->addField('vads_sub_desc', 'Subscription description', $ans255);
            $this->addField('vads_sub_effect_date', 'Subscription effect date', $regex_sub_effect_date);
            $this->addField('vads_sub_init_amount', 'Subscription initial amount', '#^' . $supzero . '$#u');
            $this->addField('vads_sub_init_amount_number', 'subscription initial amount number', '#^\d+$#u');

            // set some default values
            $this->set('vads_version', 'V2');
            $this->set('vads_page_action', 'PAYMENT');
            $this->set('vads_action_mode', 'INTERACTIVE');
            $this->set('vads_payment_config', 'SINGLE');

            $timestamp = time();
            $this->set('vads_trans_id', PayzenApi::generateTransId($timestamp));
            $this->set('vads_trans_date', gmdate('YmdHis', $timestamp));
        }

        /**
         * Shortcut function used in constructor to build requestParameters.
         *
         * @param string $name
         * @param string $label
         * @param string $regex
         * @param boolean $required
         * @param mixed $value
         * @return boolean
         */
        private function addField($name, $label, $regex, $required = false, $length = 255, $value = null)
        {
            $this->requestParameters[$name] = new PayzenField($name, $label, $regex, $required, $length);

            if ($value !== null) {
                return $this->set($name, $value);
            }

            return true;
        }

        /**
         * Shortcut for setting multiple values with one array.
         *
         * @param array[string][mixed] $parameters
         * @return boolean
         */
        public function setFromArray($parameters)
        {
            $ok = true;
            foreach ($parameters as $name => $value) {
                $ok &= $this->set($name, $value);
            }

            return $ok;
        }

        /**
         * General getter that retrieves a request parameter with its name.
         * Adds "vads_" to the name if necessary.
         * Example : <code>$site_id = $request->get('site_id');</code>
         *
         * @param string $name
         * @return mixed
         */
        public function get($name)
        {
            if (! $name || ! is_string($name)) {
                return null;
            }

            // shortcut notation compatibility
            $name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;

            if ($name == 'vads_key_test') {
                return $this->keyTest;
            } elseif ($name == 'vads_key_prod') {
                return $this->keyProd;
            } elseif ($name == 'vads_platform_url') {
                return $this->platformUrl;
            } elseif ($name == 'vads_redirect_enabled') {
                return $this->redirectEnabled;
            } elseif (key_exists($name, $this->requestParameters)) {
                return $this->requestParameters[$name]->getValue();
            } else {
                return null;
            }
        }

        /**
         * Set a request parameter with its name and the provided value.
         * Adds "vads_" to the name if necessary.
         * Example : <code>$request->set('site_id', '12345678');</code>
         *
         * @param string $name
         * @param mixed $value
         * @return boolean
         */
        public function set($name, $value)
        {
            if (! $name || ! is_string($name)) {
                return false;
            }

            // shortcut notation compatibility
            $name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;

            if (is_string($value)) {
                // trim value before set
                $value = trim($value);

                // convert the parameters' values if they are not encoded in UTF-8
                if ($this->encoding !== 'UTF-8') {
                    $value = iconv($this->encoding, 'UTF-8', $value);
                }

                // delete < and > characters from $value and replace multiple spaces by one
                $value = preg_replace(array('#[<>]+#u', '#\s+#u'), array('', ' '), $value);
            }

            // search appropriate setter
            if ($name == 'vads_key_test') {
                return $this->setCertificate($value, 'TEST');
            } elseif ($name == 'vads_key_prod') {
                return $this->setCertificate($value, 'PRODUCTION');
            } elseif ($name == 'vads_platform_url') {
                return $this->setPlatformUrl($value);
            } elseif ($name == 'vads_redirect_enabled') {
                return $this->setRedirectEnabled($value);
            } elseif ($name == 'vads_sign_algo') {
                return $this->setSignAlgo($value);
            } elseif (key_exists($name, $this->requestParameters)) {
                return $this->requestParameters[$name]->setValue($value);
            } else {
                return false;
            }
        }

        /**
         * Set multi payment configuration.
         *
         * @param $total_in_cents total order amount in cents
         * @param $first_in_cents amount of the first payment in cents
         * @param $count total number of payments
         * @param $period number of days between 2 payments
         * @return boolean
         */
        public function setMultiPayment($total_in_cents = null, $first_in_cents = null, $count = 3, $period = 30)
        {
            $result = true;

            if (is_numeric($count) && $count > 1 && is_numeric($period) && $period > 0) {
                // default values for first and total
                $total_in_cents = ($total_in_cents === null) ? $this->get('amount') : $total_in_cents;
                $first_in_cents = ($first_in_cents === null) ? round($total_in_cents / $count) : $first_in_cents;

                // check parameters
                if (is_numeric($total_in_cents) && $total_in_cents > $first_in_cents
                    && $total_in_cents > 0 && is_numeric($first_in_cents) && $first_in_cents > 0) {
                    // set value to payment_config
                    $payment_config = 'MULTI:first=' . $first_in_cents . ';count=' . $count . ';period=' . $period;
                    $result &= $this->set('amount', $total_in_cents);
                    $result &= $this->set('payment_config', $payment_config);
                }
            }

            return $result;
        }

        /**
         * Set target URL of the payment form.
         *
         * @param string $url
         * @return boolean
         */
        public function setPlatformUrl($url)
        {
            if (preg_match('#^https?://([^/]+/)+$#u', $url)) {
                $this->platformUrl = $url;
                return true;
            } else {
                return false;
            }
        }

        /**
         * Enable/disable vads_redirect_* parameters.
         *
         * @param mixed $enabled false, 0, null, negative integer or 'false' to disable
         * @return boolean
         */
        public function setRedirectEnabled($enabled)
        {
            $this->redirectEnabled = ($enabled && $enabled != '0' && strtolower($enabled) != 'false');
            return true;
        }

        /**
         * Set TEST or PRODUCTION certificate.
         *
         * @param string $key
         * @param string $mode
         * @return boolean
         */
        public function setCertificate($key, $mode)
        {
            if ($mode == 'TEST') {
                $this->keyTest = $key;
            } elseif ($mode == 'PRODUCTION') {
                $this->keyProd = $key;
            } else {
                return false;
            }

            return true;
        }

        /**
         * Set signature algorithm.
         *
         * @param string $algo
         * @return boolean
         */
        public function setSignAlgo($algo)
        {
            if (in_array($algo, PayzenApi::$SUPPORTED_ALGOS)) {
                $this->algo = $algo;
                return true;
            }

            return false;
        }

        /**
         * Add a product info as request parameters.
         *
         * @param string $label
         * @param int $amount
         * @param int $qty
         * @param string $ref
         * @param string $type
         * @param float vat
         * @return boolean
         */
        public function addProduct($label, $amount, $qty, $ref, $type = null, $vat = null)
        {
            $index = $this->get('nb_products') ? $this->get('nb_products') : 0;
            $ok = true;

            // add product info as request parameters
            $ok &= $this->addField('vads_product_label' . $index, 'Product label', '#^[^<>"+-]{0,255}$#u', false, 255, $label);
            $ok &= $this->addField('vads_product_amount' . $index, 'Product amount', '#^[1-9]\d*$#u', false, 12, $amount);
            $ok &= $this->addField('vads_product_qty' . $index, 'Product quantity', '#^[1-9]\d*$#u', false, 255, $qty);
            $ok &= $this->addField('vads_product_ref' . $index, 'Product reference', '#^[A-Za-z0-9]{0,64}$#u', false, 64, $ref);
            $ok &= $this->addField('vads_product_type' . $index, 'Product type', '#^' . implode('|', self::$ACCORD_CATEGORIES) . '$#u', false, 30, $type);
            $ok &= $this->addField('vads_product_vat' . $index, 'Product tax rate', '#^((\d{1,12})|(\d{1,2}\.\d{1,4}))$#u', false, 12, $vat);

            // increment the number of products
            $ok &= $this->set('nb_products', $index + 1);

            return $ok;
        }

        /**
         * Add extra info as a request parameter.
         *
         * @param string $key
         * @param string $value
         * @return boolean
         */
        public function addExtInfo($key, $value)
        {
            return $this->addField('vads_ext_info_' . $key, 'Extra info ' . $key, '#^.{0,255}$#u', false, 255, $value);
        }

        /**
         * Return certificate according to current mode, false if mode was not set.
         *
         * @return string|boolean
         */
        private function getCertificate()
        {
            switch ($this->requestParameters['vads_ctx_mode']->getValue()) {
                case 'TEST':
                    return $this->keyTest;

                case 'PRODUCTION':
                    return $this->keyProd;

                default:
                    return false;
            }
        }

        /**
         * Generate signature from a list of PayzenField.
         *
         * @param array[string][PayzenField] $fields already filtered fields list
         * @param bool $hashed
         * @return string
         */
        private function generateSignature($fields, $hashed = true)
        {
            $params = array();
            foreach ($fields as $field) {
                $params[$field->getName()] = $field->getValue();
            }

            return PayzenApi::sign($params, $this->getCertificate(), $this->algo, $hashed);
        }

        /**
         * Unset the value of optionnal fields if they are invalid.
         */
        public function clearInvalidOptionnalFields()
        {
            $fields = $this->getRequestFields();
            foreach ($fields as $field) {
                if (! $field->isValid() && ! $field->isRequired()) {
                    $field->setValue(null);
                }
            }
        }

        /**
         * Check all payment fields.
         *
         * @param array[string] $errors will be filled with the names of invalid fields
         * @return boolean
         */
        public function isRequestReady(&$errors = null)
        {
            $errors = is_array($errors) ? $errors : array();

            foreach ($this->getRequestFields() as $field) {
                if (! $field->isValid()) {
                    $errors[] = $field->getName();
                }
            }

            return count($errors) == 0;
        }

        /**
         * Return the list of fields to send to the payment platform.
         *
         * @return array[string][PayzenField] a list of PayzenField
         */
        public function getRequestFields()
        {
            $fields = $this->requestParameters;

            // filter redirect_* parameters if redirect is disabled
            if (! $this->redirectEnabled) {
                $redirect_fields = array(
                    'vads_redirect_success_timeout',
                    'vads_redirect_success_message',
                    'vads_redirect_error_timeout',
                    'vads_redirect_error_message'
                );

                foreach ($redirect_fields as $field_name) {
                    unset($fields[$field_name]);
                }
            }

            foreach ($fields as $field_name => $field) {
                if (! $field->isFilled() && ! $field->isRequired()) {
                    unset($fields[$field_name]);
                }
            }

            // compute signature
            $fields['signature']->setValue($this->generateSignature($fields));

            // return the list of fields
            return $fields;
        }

        /**
         * Return the URL of the payment page with urlencoded parameters (GET-like URL).
         *
         * @return string
         */
        public function getRequestUrl()
        {
            $fields = $this->getRequestFields();

            $url = $this->platformUrl . '?';
            foreach ($fields as $field) {
                if (! $field->isFilled()) {
                    continue;
                }

                $url .= $field->getName() . '=' . rawurlencode($field->getValue()) . '&';
            }
            $url = substr($url, 0, - 1); // remove last &
            return $url;
        }

        /**
         * Return the HTML form to send to the payment platform.
         *
         * @param string $form_add
         * @param string $input_type
         * @param string $input_add
         * @param string $btn_type
         * @param string $btn_value
         * @param string $btn_add
         * @return string
         */
        public function getRequestHtmlForm(
            $form_add = '',
            $input_type = 'hidden',
            $input_add = '',
            $btn_type = 'submit',
            $btn_value = 'Pay',
            $btn_add = '',
            $escape = true
        ) {
            $html = '';
            $html .= '<form action="' . $this->platformUrl . '" method="POST" ' . $form_add . '>';
            $html .= "\n";
            $html .= $this->getRequestHtmlFields($input_type, $input_add, $escape);
            $html .= '<input type="' . $btn_type . '" value="' . $btn_value . '" ' . $btn_add . '/>';
            $html .= "\n";
            $html .= '</form>';

            return $html;
        }

        /**
         * Return the HTML inputs of fields to send to the payment page.
         *
         * @param string $input_type
         * @param string $input_add
         * @return string
         */
        public function getRequestHtmlFields($input_type = 'hidden', $input_add = '', $escape = true)
        {
            $fields = $this->getRequestFields();

            $html = '';
            $format = '<input name="%s" value="%s" type="' . $input_type . '" ' . $input_add . "/>\n";
            foreach ($fields as $field) {
                if (! $field->isFilled()) {
                    continue;
                }

                // convert special chars to HTML entities to avoid data truncation
                if ($escape) {
                    $value = htmlspecialchars($field->getValue(), ENT_QUOTES, 'UTF-8');
                }

                $html .= sprintf($format, $field->getName(), $value);
            }
            return $html;
        }

        /**
         * Return the html fields to send to the payment page as a key/value array.
         *
         * @param bool $for_log
         * @return array[string][string]
         */
        public function getRequestFieldsArray($for_log = false, $escape = true)
        {
            $fields = $this->getRequestFields();

            $sensitive_data = array('vads_card_number', 'vads_cvv', 'vads_expiry_month', 'vads_expiry_year');

            $result = array();
            foreach ($fields as $field) {
                if (! $field->isFilled()) {
                    continue;
                }

                $value = $field->getValue();
                if ($for_log && in_array($field->getName(), $sensitive_data)) {
                    $value = str_repeat('*', strlen($value));
                }

                // convert special chars to HTML entities to avoid data truncation
                if ($escape) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }

                $result[$field->getName()] = $value;
            }

            return $result;
        }
    }
}

if (! class_exists('PayzenResponse', false)) {

    /**
     * Class representing the result of a transaction (sent by the IPN URL or by the client return).
     */
    class PayzenResponse
    {
        const TYPE_RESULT = 'result';
        const TYPE_AUTH_RESULT = 'auth_result';
        const TYPE_WARRANTY_RESULT = 'warranty_result';
        const TYPE_RISK_CONTROL = 'risk_control';
        const TYPE_RISK_ASSESSMENT = 'risk_assessment';

        /**
         * Raw response parameters array.
         *
         * @var array[string][string]
         */
        private $rawResponse = array();

        /**
         * Certificate used to check the signature.
         *
         * @see PayzenApi::sign
         * @var string
         */
        private $certificate;

        /**
         * Algorithm used to check the signature.
         *
         * @see PayzenApi::sign
         * @var string
         */
        private $algo = PayzenApi::ALGO_SHA1;

        /**
         * Value of vads_result.
         *
         * @var string
         */
        private $result;

        /**
         * Value of vads_extra_result.
         *
         * @var string
         */
        private $extraResult;

        /**
         * Value of vads_auth_result
         *
         * @var string
         */
        private $authResult;

        /**
         * Value of vads_warranty_result
         *
         * @var string
         */
        private $warrantyResult;

        /**
         * Transaction status (vads_trans_status)
         *
         * @var string
         */
        private $transStatus;

        /**
         * Constructor for PayzenResponse class.
         * Prepare to analyse check URL or return URL call.
         *
         * @param array[string][string] $params
         * @param string $ctx_mode
         * @param string $key_test
         * @param string $key_prod
         * @param string $algo
         */
        public function __construct($params, $ctx_mode, $key_test, $key_prod, $algo = PayzenApi::ALGO_SHA1)
        {
            $this->rawResponse = PayzenApi::uncharm($params);
            $this->certificate = trim(($ctx_mode == 'PRODUCTION') ? $key_prod : $key_test);

            if (in_array($algo, PayzenApi::$SUPPORTED_ALGOS)) {
                $this->algo = $algo;
            }

            // payment results
            $this->result = self::findInArray('vads_result', $this->rawResponse, null);
            $this->extraResult = self::findInArray('vads_extra_result', $this->rawResponse, null);
            $this->authResult = self::findInArray('vads_auth_result', $this->rawResponse, null);
            $this->warrantyResult = self::findInArray('vads_warranty_result', $this->rawResponse, null);

            $this->transStatus = self::findInArray('vads_trans_status', $this->rawResponse, null);
        }

        /**
         * Check response signature.
         * @return bool
         */
        public function isAuthentified()
        {
            return $this->getComputedSignature() == $this->getSignature();
        }

        /**
         * Return the signature computed from the received parameters, for log/debug purposes.
         * @param bool $hashed
         * @return string
         */
        public function getComputedSignature($hashed = true)
        {
            return PayzenApi::sign($this->rawResponse, $this->certificate, $this->algo, $hashed);
        }

        /**
         * Check if the payment was successful (waiting confirmation or captured).
         * @return bool
         */
        public function isAcceptedPayment()
        {
            $confirmedStatuses = array(
                'AUTHORISED',
                'AUTHORISED_TO_VALIDATE',
                'CAPTURED',
                'CAPTURE_FAILED', /* capture will be redone */
                'ACCEPTED'
            );

            return in_array($this->transStatus, $confirmedStatuses) || $this->isPendingPayment();
        }

        /**
         * Check if the payment is waiting confirmation (successful but the amount has not been
         * transfered and is not yet guaranteed).
         * @return bool
         */
        public function isPendingPayment()
        {
            $pendingStatuses = array(
                'INITIAL',
                'WAITING_AUTHORISATION',
                'WAITING_AUTHORISATION_TO_VALIDATE',
                'UNDER_VERIFICATION',
                'WAITING_FOR_PAYMENT'
            );

            return in_array($this->transStatus, $pendingStatuses);
        }

        /**
         * Check if the payment process was interrupted by the client.
         * @return bool
         */
        public function isCancelledPayment()
        {
            $cancelledStatuses = array('NOT_CREATED', 'ABANDONED');
            return in_array($this->transStatus, $cancelledStatuses);
        }

        /**
         * Check if the payment is to validate manually in the PayZen Back Office.
         * @return bool
         */
        public function isToValidatePayment()
        {
            $toValidateStatuses = array('WAITING_AUTHORISATION_TO_VALIDATE', 'AUTHORISED_TO_VALIDATE');
            return in_array($this->transStatus, $toValidateStatuses);
        }

        /**
         * Check if the payment is suspected to be fraudulent.
         * @return bool
         */
        public function isSuspectedFraud()
        {
            // at least one control failed ...
            $riskControl = $this->getRiskControl();
            if (in_array('WARNING', $riskControl) || in_array('ERROR', $riskControl)) {
                return true;
            }

            // or there was an alert from risk assessment module
            $riskAssessment = $this->getRiskAssessment();
            if (in_array('INFORM', $riskAssessment)) {
                return true;
            }

            return false;
        }

        /**
         * Return the risk control result.
         * @return array[string][string]
         */
        public function getRiskControl()
        {
            $riskControl = $this->get('risk_control');
            if (!isset($riskControl) || !trim($riskControl)) {
                return array();
            }

            // get a URL-like string
            $riskControl = str_replace(';', '&', $riskControl);

            $result = array();
            parse_str($riskControl, $result);

            return $result;
        }

        /**
         * Return the risk assessment result.
         * @return array[string]
         */
        public function getRiskAssessment()
        {
            $riskAssessment = $this->get('risk_assessment_result');
            if (!isset($riskAssessment) || !trim($riskAssessment)) {
                return array();
            }

            return explode(';', $riskAssessment);
        }

        /**
         * Return the value of a response parameter.
         * @param string $name
         * @return string
         */
        public function get($name)
        {
            // manage shortcut notations by adding 'vads_'
            $name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;

            return @$this->rawResponse[$name];
        }

        /**
         * Shortcut for getting ext_info_* fields.
         * @param string $key
         * @return string
         */
        public function getExtInfo($key)
        {
            return $this->get("ext_info_$key");
        }

        /**
         * Return the expected signature received from platform.
         * @return string
         */
        public function getSignature()
        {
            return @$this->rawResponse['signature'];
        }

        /**
         * Return the paid amount converted from cents (or currency equivalent) to a decimal value.
         * @return float
         */
        public function getFloatAmount()
        {
            $currency = PayzenApi::findCurrencyByNumCode($this->get('currency'));
            return $currency->convertAmountToFloat($this->get('amount'));
        }

        /**
         * Return the payment response result.
         * @return string
         */
        public function getResult()
        {
            return $this->result;
        }

        /**
         * Return the payment response extra result.
         * @return string
         */
        public function getExtraResult()
        {
            return $this->extraResult;
        }

        /**
         * Return the payment response authentication result.
         * @return string
         */
        public function getAuthResult()
        {
            return $this->authResult;
        }

        /**
         * Return the payment response warranty result.
         * @return string
         */
        public function getWarrantyResult()
        {
            return $this->warrantyResult;
        }

        /**
         * Return all the payment response results as array.
         * @return array[string][string]
         */
        public function getAllResults()
        {
            return array(
                'result' => $this->result,
                'extra_result' => $this->extraResult,
                'auth_result' => $this->authResult,
                'warranty_result' => $this->warrantyResult
            );
        }

        /**
         * Return the payment transaction status.
         * @return string
         */
        public function getTransStatus()
        {
            return $this->transStatus;
        }

        /**
         * Return the response message translated to the payment langauge.
         * @param $result_type string
         * @return string
         */
        public function getMessage($result_type = self::TYPE_RESULT)
        {
            $text = '';

            $text .= self::translate($this->get($result_type), $result_type, $this->get('language'), true);
            if ($result_type === self::TYPE_RESULT && $this->get($result_type) === '30' /* form error */) {
                $text .= ' ' . self::extraMessage($this->extraResult);
            }

            return $text;
        }

        /**
         * Return the complete response message translated to the payment langauge.
         * @param $result_type string
         * @return string
         */
        public function getCompleteMessage($sep = ' ')
        {
            $text = $this->getMessage(self::TYPE_RESULT);
            $text .= $sep . $this->getMessage(self::TYPE_AUTH_RESULT);
            $text .= $sep . $this->getMessage(self::TYPE_WARRANTY_RESULT);

            return $text;
        }

        /**
         * Return a short description of the payment result, useful for logging.
         * @return string
         */
        public function getLogMessage()
        {
            $text = '';

            $text .= self::translate($this->result, self::TYPE_RESULT, 'en', true);
            if ($this->result === '30' /* form error */) {
                $text .= ' ' . self::extraMessage($this->extraResult);
            }

            $text .= ' ' . self::translate($this->authResult, self::TYPE_AUTH_RESULT, 'en', true);
            $text .= ' ' . self::translate($this->warrantyResult, self::TYPE_WARRANTY_RESULT, 'en', true);

            return $text;
        }

        /**
         * @deprecated Deprecated since version 1.2.1. Use <code>PayzenResponse::getLogMessage()</code>
         * or <code>PayzenResponse::getMessage()</code> instead.
         */
        public function getLogString()
        {
            return $this->getMessage();
        }

        /**
         * @deprecated Deprecated since version 1.2.0. Use <code>PayzenResponse::getOutputForPlatform()</code> instead.
         */
        public function getOutputForGateway($case = '', $extra_message = '', $original_encoding = 'UTF-8')
        {
            return $this->getOutputForPlatform($case, $extra_message, $original_encoding);
        }

        /**
         * Return a formatted string to output as a response to the notification URL call.
         *
         * @param string $case shortcut code for current situations. Most useful : payment_ok, payment_ko, auth_fail
         * @param string $extra_message some extra information to output to the payment platform
         * @param string $original_encoding some extra information to output to the payment platform
         * @return string
         */
        public function getOutputForPlatform($case = '', $extra_message = '', $original_encoding = 'UTF-8')
        {
            // predefined response messages according to case
            $cases = array(
                'payment_ok' => array(true, 'Accepted payment, order has been updated.'),
                'payment_ko' => array(true, 'Payment failure, order has been cancelled.'),
                'payment_ko_bis' => array(true, 'Payment failure.'),
                'payment_ok_already_done' => array(true, 'Accepted payment, already registered.'),
                'payment_ko_already_done' => array(true, 'Payment failure, already registered.'),
                'order_not_found' => array(false, 'Order not found.'),
                'payment_ko_on_order_ok' => array(false, 'Order status does not match the payment result.'),
                'auth_fail' => array(false, 'An error occurred while computing the signature.'),
                'empty_cart' => array(false, 'Empty cart detected before order processing.'),
                'unknown_status' => array(false, 'Unknown order status.'),
                'amount_error' => array(false, 'Total paid is different from order amount.'),
                'ok' => array(true, ''),
                'ko' => array(false, '')
            );

            $success = key_exists($case, $cases) ? $cases[$case][0] : false;
            $message = key_exists($case, $cases) ? $cases[$case][1] : '';

            if (! empty($extra_message)) {
                $message .= ' ' . $extra_message;
            }

            $message = str_replace("\n", ' ', $message);

            // set original CMS encoding to convert if necessary response to send to platform
            $encoding = in_array(strtoupper($original_encoding), PayzenApi::$SUPPORTED_ENCODINGS) ?
                strtoupper($original_encoding) : 'UTF-8';
            if ($encoding !== 'UTF-8') {
                $message = iconv($encoding, 'UTF-8', $message);
            }

            $content = $success ? 'OK-' : 'KO-';
            $content .= "$message\n";

            $response = '';
            $response .= '<span style="display:none">';
            $response .= htmlspecialchars($content, ENT_COMPAT, 'UTF-8');
            $response .= '</span>';
            return $response;
        }

        /**
         * Return a translated short description of the payment result for a specified language.
         * @param string $result
         * @param string $result_type
         * @param string $lang
         * @param boolean $appendCode
         * @return string
         */
        public static function translate($result, $result_type = self::TYPE_RESULT, $lang = 'en', $appendCode = false)
        {
            // if language is not supported, use the domain default language
            if (!key_exists($lang, self::$RESPONSE_TRANS)) {
                $lang = 'en';
            }

            $translations = self::$RESPONSE_TRANS[$lang];
            $text = self::findInArray($result ? $result : 'empty', $translations[$result_type], $translations['unknown']);

            if ($text && $appendCode) {
                $text = self::appendResultCode($text, $result);
            }

            return $text;
        }

        public static function appendResultCode($message, $result_code)
        {
            if ($result_code) {
                $message .= ' (' . $result_code . ')';
            }

            return $message . '.';
        }

        public static function extraMessage($extra_result)
        {
            $error = self::findInArray($extra_result, self::$FORM_ERRORS, 'OTHER');
            return self::appendResultCode($error, $extra_result);
        }

        public static function findInArray($key, $array, $default)
        {
            if (is_array($array) && key_exists($key, $array)) {
                return $array[$key];
            }

            return $default;
        }

        /**
         * Associative array containing human-readable translations of response codes.
         *
         * @var array
         * @access private
         */
        public static $RESPONSE_TRANS = array(
            'fr' => array(
                'unknown' => 'Inconnu',

                'result' => array(
                    'empty' => '',
                    '00' => 'Paiement réalisé avec succès',
                    '02' => 'Le marchand doit contacter la banque du porteur',
                    '05' => 'Action refusé',
                    '17' => 'Annulation',
                    '30' => 'Erreur de format de la requête',
                    '96' => 'Erreur technique'
                ),
                'auth_result' => array(
                    'empty' => '',
                    '00' => 'Transaction approuvée ou traitée avec succès',
                    '02' => 'Contacter l\'émetteur de carte',
                    '03' => 'Accepteur invalide',
                    '04' => 'Conserver la carte',
                    '05' => 'Ne pas honorer',
                    '07' => 'Conserver la carte, conditions spéciales',
                    '08' => 'Approuver après identification',
                    '12' => 'Transaction invalide',
                    '13' => 'Montant invalide',
                    '14' => 'Numéro de porteur invalide',
                    '30' => 'Erreur de format',
                    '31' => 'Identifiant de l\'organisme acquéreur inconnu',
                    '33' => 'Date de validité de la carte dépassée',
                    '34' => 'Suspicion de fraude',
                    '41' => 'Carte perdue',
                    '43' => 'Carte volée',
                    '51' => 'Provision insuffisante ou crédit dépassé',
                    '54' => 'Date de validité de la carte dépassée',
                    '56' => 'Carte absente du fichier',
                    '57' => 'Transaction non permise à ce porteur',
                    '58' => 'Transaction interdite au terminal',
                    '59' => 'Suspicion de fraude',
                    '60' => 'L\'accepteur de carte doit contacter l\'acquéreur',
                    '61' => 'Montant de retrait hors limite',
                    '63' => 'Règles de sécurité non respectées',
                    '68' => 'Réponse non parvenue ou reçue trop tard',
                    '90' => 'Arrêt momentané du système',
                    '91' => 'Emetteur de cartes inaccessible',
                    '96' => 'Mauvais fonctionnement du système',
                    '94' => 'Transaction dupliquée',
                    '97' => 'Echéance de la temporisation de surveillance globale',
                    '98' => 'Serveur indisponible routage réseau demandé à nouveau',
                    '99' => 'Incident domaine initiateur'
                ),
                'warranty_result' => array(
                    'empty' => 'Garantie de paiement non applicable',
                    'YES' => 'Le paiement est garanti',
                    'NO' => 'Le paiement n\'est pas garanti',
                    'UNKNOWN' => 'Suite à une erreur technique, le paiment ne peut pas être garanti'
                ),
                'risk_control' => array (
                    'CARD_FRAUD' => 'Contrôle du numéro de carte',
                    'SUSPECT_COUNTRY' => 'Contrôle du pays émetteur de la carte',
                    'IP_FRAUD' => 'Contrôle de l\'adresse IP',
                    'CREDIT_LIMIT' => 'Contrôle de l\'encours',
                    'BIN_FRAUD' => 'Contrôle du code BIN',
                    'ECB' => 'Contrôle e-carte bleue',
                    'COMMERCIAL_CARD' => 'Contrôle carte commerciale',
                    'SYSTEMATIC_AUTO' => 'Contrôle carte à autorisation systématique',
                    'INCONSISTENT_COUNTRIES' => 'Contrôle de cohérence des pays (IP, carte, adresse de facturation)',
                    'NON_WARRANTY_PAYMENT' => 'Contrôle le transfert de responsabilité',
                    'SUSPECT_IP_COUNTRY' => 'Contrôle Pays de l\'IP'
                ),
                'risk_assessment' => array(
                    'ENABLE_3DS' => '3D Secure activé',
                    'DISABLE_3DS' => '3D Secure désactivé',
                    'MANUAL_VALIDATION' => 'La transaction est créée en validation manuelle',
                    'REFUSE' => 'La transaction est refusée',
                    'RUN_RISK_ANALYSIS' => 'Appel à un analyseur de risques externes',
                    'INFORM' => 'Une alerte est remontée'
                )
            ),

            'en' => array(
                'unknown' => 'Unknown',

                'result' => array(
                    'empty' => '',
                    '00' => 'Action successfully completed',
                    '02' => 'The merchant must contact the cardholder\'s bank',
                    '05' => 'Action rejected',
                    '17' => 'Action canceled',
                    '30' => 'Request format error',
                    '96' => 'Technical issue'
                ),
                'auth_result' => array(
                    'empty' => '',
                    '00' => 'Approved or successfully processed transaction',
                    '02' => 'Contact the card issuer',
                    '03' => 'Invalid acceptor',
                    '04' => 'Keep the card',
                    '05' => 'Do not honor',
                    '07' => 'Keep the card, special conditions',
                    '08' => 'Confirm after identification',
                    '12' => 'Invalid transaction',
                    '13' => 'Invalid amount',
                    '14' => 'Invalid cardholder number',
                    '30' => 'Format error',
                    '31' => 'Unknown acquirer company ID',
                    '33' => 'Expired card',
                    '34' => 'Fraud suspected',
                    '41' => 'Lost card',
                    '43' => 'Stolen card',
                    '51' => 'Insufficient balance or exceeded credit limit',
                    '54' => 'Expired card',
                    '56' => 'Card absent from the file',
                    '57' => 'Transaction not allowed to this cardholder',
                    '58' => 'Transaction not allowed to this cardholder',
                    '59' => 'Suspected fraud',
                    '60' => 'Card acceptor must contact the acquirer',
                    '61' => 'Withdrawal limit exceeded',
                    '63' => 'Security rules unfulfilled',
                    '68' => 'Response not received or received too late',
                    '90' => 'Temporary shutdown',
                    '91' => 'Unable to reach the card issuer',
                    '96' => 'System malfunction',
                    '94' => 'Duplicate transaction',
                    '97' => 'Overall monitoring timeout',
                    '98' => 'Server not available, new network route requested',
                    '99' => 'Initiator domain incident'
                ),
                'warranty_result' => array(
                    'empty' => 'Payment guarantee not applicable',
                    'YES' => 'The payment is guaranteed',
                    'NO' => 'The payment is not guaranteed',
                    'UNKNOWN' => 'Due to a technical error, the payment cannot be guaranteed'
                ),
                'risk_control' => array (
                    'CARD_FRAUD' => 'Card number control',
                    'SUSPECT_COUNTRY' => 'Card country control',
                    'IP_FRAUD' => 'IP address control',
                    'CREDIT_LIMIT' => 'Card outstanding control',
                    'BIN_FRAUD' => 'BIN code control',
                    'ECB' => 'E-carte bleue control',
                    'COMMERCIAL_CARD' => 'Commercial card control',
                    'SYSTEMATIC_AUTO' => 'Systematic authorization card control',
                    'INCONSISTENT_COUNTRIES' => 'Countries consistency control (IP, card, shipping address)',
                    'NON_WARRANTY_PAYMENT' => 'Transfer of responsibility control',
                    'SUSPECT_IP_COUNTRY' => 'IP country control'
                ),
                'risk_assessment' => array(
                    'ENABLE_3DS' => '3D Secure enabled',
                    'DISABLE_3DS' => '3D Secure disabled',
                    'MANUAL_VALIDATION' => 'The transaction has been created via manual validation',
                    'REFUSE' => 'The transaction is refused',
                    'RUN_RISK_ANALYSIS' => 'Call for an external risk analyser',
                    'INFORM' => 'A warning message appears'
                )
            ),

            'es' => array(
                'unknown' => 'Desconocido',

                'result' => array(
                    'empty' => '',
                    '00' => 'Accion procesada con exito',
                    '02' => 'El mercante debe contactar el banco del portador',
                    '05' => 'Accion rechazada',
                    '17' => 'Accion cancelada',
                    '30' => 'Error de formato de solicitutd',
                    '96' => 'Problema technico'
                ),
                'auth_result' => array(
                    'empty' => '',
                    '00' => 'Transaccion aceptada o procesada con exito',
                    '02' => 'Contact el emisor de la tarjeta',
                    '03' => 'Adquirente invalido',
                    '04' => 'Retener tarjeta',
                    '05' => 'No honrar',
                    '07' => 'Retener tarjeta, condiciones especiales',
                    '08' => 'Confirmar despues identificacion',
                    '12' => 'Transaccion invalida',
                    '13' => 'Importe invalido',
                    '14' => 'Numero de portador invalido',
                    '30' => 'Error de formato',
                    '31' => 'Identificador adquirente desconocido',
                    '33' => 'Tarjeta caducada',
                    '34' => 'Fraude sospechado',
                    '41' => 'Tarjeta perdida',
                    '43' => 'Tarjeta robada',
                    '51' => 'Saldo insuficiente o limite de credito sobrepasado',
                    '54' => 'Tarjeta caducada',
                    '56' => 'Tarjeta ausente del archivo',
                    '57' => 'Transaccion no permitida a este portador',
                    '58' => 'Transaccion no permitida a este portador',
                    '59' => 'Fraude sospechado',
                    '60' => 'El aceptador de la tarjeta debe contactar el adquirente',
                    '61' => 'Limite de retirada sobrepasada',
                    '63' => 'Reglas de suguridad no cumplidas',
                    '68' => 'Respuesta no recibiba o recibida demasiado tarde',
                    '90' => 'Interrupcion temporera',
                    '91' => 'No se puede contactar el emisor de tarjeta',
                    '96' => 'Malfunction del sistema',
                    '94' => 'Transaccion duplicada',
                    '97' => 'Supervision timeout',
                    '98' => 'Servidor no disonible, nueva ruta pedida',
                    '99' => 'Incidente de dominio iniciador'
                ),
                'warranty_result' => array(
                    'empty' => 'Garantia de pago no aplicable',
                    'YES' => 'El pago es garantizado',
                    'NO' => 'El pago no es garantizado',
                    'UNKNOWN' => 'Debido a un problema tecnico, el pago no puede ser garantizado'
                ),
                'risk_control' => array (
                    'CARD_FRAUD' => 'Control de numero de tarjeta',
                    'SUSPECT_COUNTRY' => 'Control de pais de tarjeta',
                    'IP_FRAUD' => 'Control de direccion IP',
                    'CREDIT_LIMIT' => 'Control de saldo de vivo de tarjeta',
                    'BIN_FRAUD' => 'Control de codigo BIN',
                    'ECB' => 'Control de E-carte bleue',
                    'COMMERCIAL_CARD' => 'Control de tarjeta comercial',
                    'SYSTEMATIC_AUTO' => 'Control de tarjeta a autorizacion sistematica',
                    'INCONSISTENT_COUNTRIES' => 'Control de coherencia de pais (IP, tarjeta, direccion de envio)',
                    'NON_WARRANTY_PAYMENT' => 'Control de transferencia de responsabilidad',
                    'SUSPECT_IP_COUNTRY' => 'Control del pais de la IP'
                ),
                'risk_assessment' => array(
                    'ENABLE_3DS' => '3D Secure activado',
                    'DISABLE_3DS' => '3D Secure desactivado',
                    'MANUAL_VALIDATION' => 'La transaccion ha sido creada con validacion manual',
                    'REFUSE' => 'La transaccion ha sido rechazada',
                    'RUN_RISK_ANALYSIS' => 'Llamada a un analisador de riesgos exterior',
                    'INFORM' => 'Un mensaje de advertencia aparece'
                )
            ),

            'pt' => array (
                'unknown' => 'Desconhecido',

                'result' => array (
                    'empty' => '',
                    '00' => 'Pagamento realizado com sucesso',
                    '02' => 'O comerciante deve contactar o banco do portador',
                    '05' => 'Pagamento recusado',
                    '17' => 'Cancelamento',
                    '30' => 'Erro no formato dos dados',
                    '96' => 'Erro técnico durante o pagamento'
                ),
                'auth_result' => array (
                    'empty' => '',
                    '00' => 'Transação aprovada ou tratada com sucesso',
                    '02' => 'Contactar o emissor do cartão',
                    '03' => 'Recebedor inválido',
                    '04' => 'Conservar o cartão',
                    '05' => 'Não honrar',
                    '07' => 'Conservar o cartão, condições especiais',
                    '08' => 'Aprovar após identificação',
                    '12' => 'Transação inválida',
                    '13' => 'Valor inválido',
                    '14' => 'Número do portador inválido',
                    '30' => 'Erro no formato',
                    '31' => 'Identificação do adquirente desconhecido',
                    '33' => 'Data de validade do cartão ultrapassada',
                    '34' => 'Suspeita de fraude',
                    '41' => 'Cartão perdido',
                    '43' => 'Cartão roubado',
                    '51' => 'Saldo insuficiente ou limite excedido',
                    '54' => 'Data de validade do cartão ultrapassada',
                    '56' => 'Cartão ausente do arquivo',
                    '57' => 'Transação não permitida para este portador',
                    '58' => 'Transação proibida no terminal',
                    '59' => 'Suspeita de fraude',
                    '60' => 'O recebedor do cartão deve contactar o adquirente',
                    '61' => 'Valor de saque fora do limite',
                    '63' => 'Regras de segurança não respeitadas',
                    '68' => 'Nenhuma resposta recebida ou recebida tarde demais',
                    '90' => 'Parada momentânea do sistema',
                    '91' => 'Emissor do cartão inacessível',
                    '96' => 'Mau funcionamento do sistema',
                    '94' => 'Transação duplicada',
                    '97' => 'Limite do tempo de monitoramento global',
                    '98' => 'Servidor indisponível nova solicitação de roteamento',
                    '99' => 'Incidente no domínio iniciador'
                ),
                'warranty_result' => array (
                    'empty' => 'Garantia de pagamento não aplicável',
                    'YES' => 'O pagamento foi garantido',
                    'NO' => 'O pagamento não foi garantido',
                    'UNKNOWN' => 'Devido à un erro técnico, o pagamento não pôde ser garantido'
                ),
                'risk_control' => array (
                    'CARD_FRAUD' => 'Card number control',
                    'SUSPECT_COUNTRY' => 'Card country control',
                    'IP_FRAUD' => 'IP address control',
                    'CREDIT_LIMIT' => 'Card outstanding control',
                    'BIN_FRAUD' => 'BIN code control',
                    'ECB' => 'E-carte bleue control',
                    'COMMERCIAL_CARD' => 'Commercial card control',
                    'SYSTEMATIC_AUTO' => 'Systematic authorization card control',
                    'INCONSISTENT_COUNTRIES' => 'Countries consistency control (IP, card, shipping address)',
                    'NON_WARRANTY_PAYMENT' => 'Transfer of responsibility control',
                    'SUSPECT_IP_COUNTRY' => 'IP country control'
                ),
                'risk_assessment' => array(
                    'ENABLE_3DS' => '3D Secure enabled',
                    'DISABLE_3DS' => '3D Secure disabled',
                    'MANUAL_VALIDATION' => 'The transaction has been created via manual validation',
                    'REFUSE' => 'The transaction is refused',
                    'RUN_RISK_ANALYSIS' => 'Call for an external risk analyser',
                    'INFORM' => 'A warning message appears'
                )
            ),

            'de' => array (
                'unknown' => 'Unbekannt',

                'result' => array (
                    'empty' => '',
                    '00' => 'Zahlung mit Erfolg durchgeführt',
                    '02' => 'Der Händler muss die Bank des Karteninhabers kontaktieren',
                    '05' => 'Zahlung zurückgewiesen',
                    '17' => 'Stornierung',
                    '30' => 'Fehler im Format der Anfrage',
                    '96' => 'Technischer Fehler bei der Zahlung'
                ),
                'auth_result' => array (
                    'empty' => '',
                    '00' => 'Zahlung durchgeführt oder mit Erfolg bearbeitet',
                    '02' => 'Kartenausgebende Bank kontaktieren',
                    '03' => 'Ungültiger Annehmer',
                    '04' => 'Karte aufbewahren',
                    '05' => 'Nicht einlösen',
                    '07' => 'Karte aufbewahren, Sonderbedingungen',
                    '08' => 'Nach Identifizierung genehmigen',
                    '12' => 'Ungültige Transaktion',
                    '13' => 'Ungültiger Betrag',
                    '14' => 'Ungültige Nummer des Karteninhabers',
                    '30' => 'Formatfehler',
                    '31' => 'ID des Annehmers unbekannt',
                    '33' => 'Gültigkeitsdatum der Karte überschritten',
                    '34' => 'Verdacht auf Betrug',
                    '41' => 'Verlorene Karte',
                    '43' => 'Gestohlene Karte',
                    '51' => 'Deckung unzureichend oder Kredit überschritten',
                    '54' => 'Gültigkeitsdatum der Karte überschritten',
                    '56' => 'Karte nicht in der Datei enthalten',
                    '57' => 'Transaktion diesem Karteninhaber nicht erlaubt',
                    '58' => 'Transaktion diesem Terminal nicht erlaubt',
                    '59' => 'Verdacht auf Betrug',
                    '60' => 'Der Kartenannehmer muss den Acquirer kontaktieren',
                    '61' => 'Betrag der Abhebung überschreitet das Limit',
                    '63' => 'Sicherheitsregelen nicht respektiert',
                    '68' => 'Antwort nicht oder zu spät erhalten',
                    '90' => 'Momentane Systemunterbrechung',
                    '91' => 'Kartenausgeber nicht erreichbar',
                    '96' => 'Fehlverhalten des Systems',
                    '94' => 'Kopierte Transaktion',
                    '97' => 'Fälligkeit der Verzögerung der globalen Überwachung',
                    '98' => 'Server nicht erreichbar, Routen des Netzwerkes erneut angefragt',
                    '99' => 'Vorfall der urhebenden Domain'
                ),
                'warranty_result' => array (
                    'empty' => 'Zahlungsgarantie nicht anwendbar',
                    'YES' => 'Die Zahlung ist garantiert',
                    'NO' => 'Die Zahlung ist nicht garantiert',
                    'UNKNOWN' => 'Die Zahlung kann aufgrund eines technischen Fehlers nicht gewährleistet werden'
                ),
                'risk_control' => array (
                    'CARD_FRAUD' => 'Card number control',
                    'SUSPECT_COUNTRY' => 'Card country control',
                    'IP_FRAUD' => 'IP address control',
                    'CREDIT_LIMIT' => 'Card outstanding control',
                    'BIN_FRAUD' => 'BIN code control',
                    'ECB' => 'E-carte bleue control',
                    'COMMERCIAL_CARD' => 'Commercial card control',
                    'SYSTEMATIC_AUTO' => 'Systematic authorization card control',
                    'INCONSISTENT_COUNTRIES' => 'Countries consistency control (IP, card, shipping address)',
                    'NON_WARRANTY_PAYMENT' => 'Transfer of responsibility control',
                    'SUSPECT_IP_COUNTRY' => 'IP country control'
                ),
                'risk_assessment' => array(
                    'ENABLE_3DS' => '3D Secure enabled',
                    'DISABLE_3DS' => '3D Secure disabled',
                    'MANUAL_VALIDATION' => 'The transaction has been created via manual validation',
                    'REFUSE' => 'The transaction is refused',
                    'RUN_RISK_ANALYSIS' => 'Call for an external risk analyser',
                    'INFORM' => 'A warning message appears'
                )
            )
        );

        public static $FORM_ERRORS = array(
            '00' => 'SIGNATURE',
            '01' => 'VERSION',
            '02' => 'SITE_ID',
            '03' => 'TRANS_ID',
            '04' => 'TRANS_DATE',
            '05' => 'VALIDATION_MODE',
            '06' => 'CAPTURE_DELAY',
            '07' => 'PAYMENT_CONFIG',
            '08' => 'PAYMENT_CARDS',
            '09' => 'AMOUNT',
            '10' => 'CURRENCY',
            '11' => 'CTX_MODE',
            '12' => 'LANGUAGE',
            '13' => 'ORDER_ID',
            '14' => 'ORDER_INFO',
            '15' => 'CUST_EMAIL',
            '16' => 'CUST_ID',
            '17' => 'CUST_TITLE',
            '18' => 'CUST_NAME',
            '19' => 'CUST_ADDRESS',
            '20' => 'CUST_ZIP',
            '21' => 'CUST_CITY',
            '22' => 'CUST_COUNTRY',
            '23' => 'CUST_PHONE',
            '24' => 'URL_SUCCESS',
            '25' => 'URL_REFUSED',
            '26' => 'URL_REFERRAL',
            '27' => 'URL_CANCEL',
            '28' => 'URL_RETURN',
            '29' => 'URL_ERROR',
            '30' => 'IDENTIFIER',
            '31' => 'CONTRIB',
            '32' => 'THEME_CONFIG',
            '33' => 'URL_CHECK',
            '34' => 'REDIRECT_SUCCESS_TIMEOUT',
            '35' => 'REDIRECT_SUCCESS_MESSAGE',
            '36' => 'REDIRECT_ERROR_TIMEOUT',
            '37' => 'REDIRECT_ERROR_MESSAGE',
            '38' => 'RETURN_POST_PARAMS',
            '39' => 'RETURN_GET_PARAMS',
            '40' => 'CARD_NUMBER',
            '41' => 'CARD_EXP_MONTH',
            '42' => 'CARD_EXP_YEAR',
            '43' => 'CARD_CVV',
            '44' => 'CARD_CVV_AND_BIRTH',
            '46' => 'PAGE_ACTION',
            '47' => 'ACTION_MODE',
            '48' => 'RETURN_MODE',
            '49' => 'ABSTRACT_INFO',
            '50' => 'SECURE_MPI',
            '51' => 'SECURE_ENROLLED',
            '52' => 'SECURE_CAVV',
            '53' => 'SECURE_ECI',
            '54' => 'SECURE_XID',
            '55' => 'SECURE_CAVV_ALG',
            '56' => 'SECURE_STATUS',
            '60' => 'PAYMENT_SRC',
            '61' => 'USER_INFO',
            '62' => 'CONTRACTS',
            '63' => 'RECURRENCE',
            '64' => 'RECURRENCE_DESC',
            '65' => 'RECURRENCE_AMOUNT',
            '66' => 'RECURRENCE_REDUCED_AMOUNT',
            '67' => 'RECURRENCE_CURRENCY',
            '68' => 'RECURRENCE_REDUCED_AMOUNT_NUMBER',
            '69' => 'RECURRENCE_EFFECT_DATE',
            '70' => 'EMPTY_PARAMS',
            '71' => 'AVAILABLE_LANGUAGES',
            '72' => 'SHOP_NAME',
            '73' => 'SHOP_URL',
            '74' => 'OP_COFINOGA',
            '75' => 'OP_CETELEM',
            '76' => 'BIRTH_DATE',
            '77' => 'CUST_CELL_PHONE',
            '79' => 'TOKEN_ID',
            '80' => 'SHIP_TO_NAME',
            '81' => 'SHIP_TO_STREET',
            '82' => 'SHIP_TO_STREET2',
            '83' => 'SHIP_TO_CITY',
            '84' => 'SHIP_TO_STATE',
            '85' => 'SHIP_TO_ZIP',
            '86' => 'SHIP_TO_COUNTRY',
            '87' => 'SHIP_TO_PHONE_NUM',
            '88' => 'CUST_STATE',
            '89' => 'REQUESTOR',
            '90' => 'PAYMENT_TYPE',
            '91' => 'EXT_INFO',
            '92' => 'CUST_STATUS',
            '93' => 'SHIP_TO_STATUS',
            '94' => 'SHIP_TO_TYPE',
            '95' => 'SHIP_TO_SPEED',
            '96' => 'SHIP_TO_DELIVERY_COMPANY_NAME',
            '97' => 'PRODUCT_LABEL',
            '98' => 'PRODUCT_TYPE',
            '100' => 'PRODUCT_REF',
            '101' => 'PRODUCT_QTY',
            '102' => 'PRODUCT_AMOUNT',
            '103' => 'PAYMENT_OPTION_CODE',
            '104' => 'CUST_FIRST_NAME',
            '105' => 'CUST_LAST_NAME',
            '106' => 'SHIP_TO_FIRST_NAME',
            '107' => 'SHIP_TO_LAST_NAME',
            '108' => 'TAX_AMOUNT',
            '109' => 'SHIPPING_AMOUNT',
            '110' => 'INSURANCE_AMOUNT',
            '111' => 'PAYMENT_ENTRY',
            '112' => 'CUST_ADDRESS_NUMBER',
            '113' => 'CUST_DISTRICT',
            '114' => 'SHIP_TO_STREET_NUMBER',
            '115' => 'SHIP_TO_DISTRICT',
            '116' => 'SHIP_TO_USER_INFO',
            '117' => 'RISK_PRIMARY_WARRANTY',
            '117' => 'DONATION',
            '99' => 'OTHER',
            '118' => 'STEP_UP_DATA',
            '201' => 'PAYMENT_AUTH_CODE',
            '202' => 'PAYMENT_CUST_CONTRACT_NUM',
            '888' => 'ROBOT_REQUEST',
            '999' => 'SENSITIVE_DATA'
        );
    }
}

if (! class_exists('PayzenApi', false)) {

    /**
     * Utility class for managing parameters checking, inetrnationalization, signature building and more.
     */
    class PayzenApi
    {

        const ALGO_SHA1 = 'SHA-1';
        const ALGO_SHA256 = 'SHA-256';

        public static $SUPPORTED_ALGOS = array(self::ALGO_SHA1, self::ALGO_SHA256);

        /**
         * The list of encodings supported by the API.
         *
         * @var array[string]
         */
        public static $SUPPORTED_ENCODINGS = array(
            'UTF-8',
            'ASCII',
            'Windows-1252',
            'ISO-8859-15',
            'ISO-8859-1',
            'ISO-8859-6',
            'CP1256'
        );

        /**
         * Generate a trans_id.
         * To be independent from shared/persistent counters, we use the number of 1/10 seconds since midnight
         * which has the appropriatee format (000000-899999) and has great chances to be unique.
         *
         * @param int $timestamp
         * @return string the generated trans_id
         */
        public static function generateTransId($timestamp = null)
        {
            if (! $timestamp) {
                $timestamp = time();
            }

            $parts = explode(' ', microtime());
            $id = ($timestamp + $parts[0] - strtotime('today 00:00')) * 10;
            $id = sprintf('%06d', $id);

            return $id;
        }

        /**
         * Returns an array of languages accepted by the PayZen payment platform.
         *
         * @return array[string][string]
         */
        public static function getSupportedLanguages()
        {
            return array(
                'de' => 'German',
                'en' => 'English',
                'zh' => 'Chinese',
                'es' => 'Spanish',
                'fr' => 'French',
                'it' => 'Italian',
                'ja' => 'Japanese',
                'nl' => 'Dutch',
                'pl' => 'Polish',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'sv' => 'Swedish',
                'tr' => 'Turkish'
            );
        }

        /**
         * Returns true if the entered language (ISO code) is supported.
         *
         * @param string $lang
         * @return boolean
         */
        public static function isSupportedLanguage($lang)
        {
            foreach (array_keys(self::getSupportedLanguages()) as $code) {
                if ($code == strtolower($lang)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Return the list of currencies recognized by the PayZen platform.
         *
         * @return array[int][PayzenCurrency]
         */
        public static function getSupportedCurrencies()
        {
            $currencies = array(
                array('AUD', '036', 2), array('KHR', '116', 0), array('CAD', '124', 2), array('CNY', '156', 1),
                array('CZK', '203', 2), array('DKK', '208', 2), array('HKD', '344', 2), array('HUF', '348', 2),
                array('INR', '356', 2), array('IDR', '360', 2), array('JPY', '392', 0), array('KRW', '410', 0),
                array('KWD', '414', 3), array('MYR', '458', 2), array('MXN', '484', 2), array('MAD', '504', 2),
                array('NZD', '554', 2), array('NOK', '578', 2), array('PHP', '608', 2), array('RUB', '643', 2),
                array('SGD', '702', 2), array('ZAR', '710', 2), array('SEK', '752', 2), array('CHF', '756', 2),
                array('THB', '764', 2), array('TND', '788', 3), array('GBP', '826', 2), array('USD', '840', 2),
                array('TWD', '901', 2), array('TRY', '949', 2), array('EUR', '978', 2), array('PLN', '985', 2),
                array('BRL', '986', 2)
            );

            $payzen_currencies = array();

            foreach ($currencies as $currency) {
                $payzen_currencies[] = new PayzenCurrency($currency[0], $currency[1], $currency[2]);
            }

            return $payzen_currencies;
        }

        /**
         * Return a currency from its 3-letters ISO code.
         *
         * @param string $alpha3
         * @return PayzenCurrency
         */
        public static function findCurrencyByAlphaCode($alpha3)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var PayzenCurrency $currency
                 */
                if ($currency->getAlpha3() == $alpha3) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Returns a currency form its numeric ISO code.
         *
         * @param int $numeric
         * @return PayzenCurrency
         */
        public static function findCurrencyByNumCode($numeric)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var PayzenCurrency $currency
                 */
                if ($currency->getNum() == $numeric) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Return a currency from its 3-letters or numeric ISO code.
         *
         * @param string $code
         * @return PayzenCurrency
         */
        public static function findCurrency($code)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var PayzenCurrency $currency
                 */
                if ($currency->getNum() == $code || $currency->getAlpha3() == $code) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Returns currency numeric ISO code from its 3-letters code.
         *
         * @param string $alpha3
         * @return int
         */
        public static function getCurrencyNumCode($alpha3)
        {
            $currency = self::findCurrencyByAlphaCode($alpha3);
            return ($currency instanceof PayzenCurrency) ? $currency->getNum() : null;
        }

        /**
         * Returns an array of card types accepted by the PayZen payment platform.
         *
         * @return array[string][string]
         */
        public static function getSupportedCardTypes()
        {
            return array(
                'CB' => 'CB', 'E-CARTEBLEUE' => 'e-Carte Bleue', 'MAESTRO' => 'Maestro', 'MASTERCARD' => 'MasterCard',
                'VISA' => 'Visa', 'VISA_ELECTRON' => 'Visa Electron', 'VPAY' => 'V PAY', 'AMEX' => 'American Express',
                'ACCORD_STORE' => 'Carte enseigne Accord', 'ACCORD_STORE_SB' => 'Carte enseigne Accord - Sandbox',
                'ALINEA' => 'Carte enseigne Alinéa', 'ALINEA_CDX' => 'Carte cadeau Alinéa',
                'ALINEA_CDX_SB' => 'Carte cadeau Alinéa - Sandbox', 'ALINEA_SB' => 'Carte enseigne Alinéa - Sandbox',
                'ALIPAY' => 'Alipay', 'ALLOBEBE_CDX' => 'Carte cadeau AlloBébé', 'ALLOBEBE_CDX_SB' => 'Carte cadeau AlloBébé - Sandbox',
                'AUCHAN' => 'Carte enseigne Auchan', 'AUCHAN_SB' => 'Carte enseigne Auchan - Sandbox', 'AURORE_MULTI' => 'Carte Aurore',
                'BANCONTACT' => 'Bancontact Mistercash', 'BIZZBEE_CDX' => 'Carte cadeau BizzBee',
                'BIZZBEE_CDX_SB' => 'Carte cadeau BizzBee - Sandbox', 'BOULANGER' => 'Carte enseigne Boulanger',
                'BOULANGER_SB' => 'Carte enseigne Boulanger - Sandbox', 'BRICE_CDX' => 'Carte cadeau Brice',
                'BRICE_CDX_SB' => 'Carte cadeau Brice - Sandbox', 'COFINOGA' => 'Carte Cofinoga Be Smart',
                'CONECS' => 'Titre-Restaurant Dématérialisé Conecs', 'CONECS_APETIZ' => 'Titre-Restaurant Dématérialisé Apetiz',
                'CONECS_CHQ_DEJ' => 'Titre-Restaurant Dématérialisé Chèque Déjeuner',
                'CONECS_SODEXO' => 'Titre-Restaurant Dématérialisé Sodexo', 'CONECS_EDENRED' => 'Ticket Restaurant',
                'DINERS' => 'Carte Diners Club', 'DISCOVER' => 'Carte Discover', 'E_CV' => 'e-Chèque-Vacances', 'ECCARD' => 'Euro-Cheque card',
                'EDENRED_EC' => 'Ticket Eco Chèque Edenred', 'EDENRED_TC' => 'Ticket Culture Edenred',
                'EDENRED_TR' => 'Ticket Restaurant Edenred', 'ELV' => 'Prélèvement Bancaire Hobex',
                'EPS' => 'EPS Online Überweisung', 'EPS_GIROPAY' => 'EPS Online Überweisung',
                'FULLCB_3X' => 'Paiement en 3x sans frais par BNPP PF', 'FULLCB_4X' => 'Paiement en 4x sans frais par BNPP PF',
                'GIROPAY' => 'Giropay', 'IDEAL' => 'iDEAL', 'ILLICADO' => 'Cartes Cadeau Illicado',
                'ILLICADO_SB' => 'Cartes Cadeau Illicado - Sandbox - Sandbox', 'JCB' => 'JCB',
                'JOUECLUB_CDX' => 'Carte cadeau JouéClub', 'JOUECLUB_CDX_SB' => 'Carte cadeau JouéClub - Sandbox',
                'KLARNA' => 'Klarna Internet Banking', 'LEROY-MERLIN' => 'Carte enseigne Leroy-Merlin',
                'LEROY-MERLIN_SB' => 'Carte enseigne Leroy-Merlin - Sandbox', 'MASTERPASS' => 'MasterPass',
                'MULTIBANCO' => 'Multibanco', 'NORAUTO' => 'Carte enseigne Norauto', 'NORAUTO_SB' => 'Carte enseigne Norauto - Sandbox',
                'ONEY' => 'FacilyPay Oney', 'ONEY_SANDBOX' => 'FacilyPay Oney - Sandbox', 'PAYDIREKT' => 'PayDirekt',
                'PAYLIB' => 'Wallet Paylib', 'PAYPAL' => 'PayPal', 'PAYPAL_SB' => 'PayPal - Sandbox',
                'PAYSAFECARD' => 'Carte prépayée paysafecard', 'PICWIC' => 'Carte enseigne PicWic',
                'PICWIC_SB' => 'Carte enseigne PicWic - Sandbox', 'POSTFINANCE' => 'PostFinance',
                'POSTFINANCE_EFIN' => 'PostFinance E-finance', 'SCT' => 'Virement SEPA Credit Transfer',
                'SDD' => 'Prélèvement SEPA Direct Debit', 'SOFICARTE' => 'Carte Soficarte',
                'SOFORT_BANKING' => 'Sofort', 'TRUFFAUX_CDX' => 'Carte Cadeau Truffaut', 'UNION_PAY' => 'UnionPay',
                'VILLAVERDE' => 'Carte enseigne Villaverde', 'VILLAVERDE_SB' => 'Carte enseigne Villaverde - Sandbox',
                'WECHAT' => 'WeChat Pay', 'MYBANK' => 'MyBank', 'PRZELEWY24' => 'Przelewy24'
            );
        }

        /**
         * Compute a PayZen signature. Parameters must be in UTF-8.
         *
         * @param array[string][string] $parameters payment platform request/response parameters
         * @param string $key shop certificate
         * @param string $algo signature algorithm
         * @param boolean $hashed set to false to get the unhashed signature
         * @return string
         */
        public static function sign($parameters, $key, $algo, $hashed = true)
        {
            ksort($parameters);

            $sign = '';
            foreach ($parameters as $name => $value) {
                if (substr($name, 0, 5) == 'vads_') {
                    $sign .= $value . '+';
                }
            }

            $sign .= $key;

            if (! $hashed) {
                return $sign;
            }

            switch ($algo) {
                case self::ALGO_SHA1:
                    return sha1($sign);
                case self::ALGO_SHA256:
                    return base64_encode(hash_hmac('sha256', $sign, $key, true));
                default:
                    throw new \InvalidArgumentException("Unsupported algorithm passed : {$algo}.");
            }
        }

        /**
         * PHP is not yet a sufficiently advanced technology to be indistinguishable from magic...
         * so don't use magic_quotes, they mess up with the platform response analysis.
         *
         * @param array $potentially_quoted_data
         * @return mixed
         */
        public static function uncharm($potentially_quoted_data)
        {
            if (get_magic_quotes_gpc()) {
                $sane = array();
                foreach ($potentially_quoted_data as $k => $v) {
                    $sane_key = stripslashes($k);
                    $sane_value = is_array($v) ? self::uncharm($v) : stripslashes($v);
                    $sane[$sane_key] = $sane_value;
                }
            } else {
                $sane = $potentially_quoted_data;
            }

            return $sane;
        }
    }
}

if (! class_exists('PayzenField', false)) {

    /**
     * Class representing a form field to send to the payment platform.
     */
    class PayzenField
    {

        /**
         * field name.
         * Matches the HTML input attribute.
         *
         * @var string
         */
        private $name;

        /**
         * field label in English, may be used by translation systems.
         *
         * @var string
         */
        private $label;

        /**
         * field length.
         * Matches the HTML input size attribute.
         *
         * @var int
         */
        private $length;

        /**
         * PCRE regular expression the field value must match.
         *
         * @var string
         */
        private $regex;

        /**
         * Whether the form requires the field to be set (even to an empty string).
         *
         * @var boolean
         */
        private $required;

        /**
         * field value.
         * Null or string.
         *
         * @var string
         */
        private $value = null;

        /**
         * Constructor.
         *
         * @param string $name
         * @param string $label
         * @param string $regex
         * @param boolean $required
         * @param int length
         */
        public function __construct($name, $label, $regex, $required = false, $length = 255)
        {
            $this->name = $name;
            $this->label = $label;
            $this->regex = $regex;
            $this->required = $required;
            $this->length = $length;
        }

        /**
         * Checks the current value.
         *
         * @return boolean
         */
        public function isValid()
        {
            if ($this->value === null && $this->required) {
                return false;
            }

            if ($this->value !== null && !preg_match($this->regex, $this->value)) {
                return false;
            }

            return true;
        }

        /**
         * Setter for value.
         *
         * @param mixed $value
         * @return boolean
         */
        public function setValue($value)
        {
            $value = ($value === null) ? null : (string) $value;
            // we save value even if invalid but we return "false" as warning
            $this->value = $value;

            return $this->isValid();
        }

        /**
         * Return the current value of the field.
         *
         * @return string
         */
        public function getValue()
        {
            return $this->value;
        }

        /**
         * Is the field required in the payment request ?
         *
         * @return boolean
         */
        public function isRequired()
        {
            return $this->required;
        }

        /**
         * Return the name (HTML attribute) of the field.
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * Return the english human-readable name of the field.
         *
         * @return string
         */
        public function getLabel()
        {
            return $this->label;
        }

        /**
         * Return the length of the field value.
         *
         * @return int
         */
        public function getLength()
        {
            return $this->length;
        }

        /**
         * Has a value been set ?
         *
         * @return boolean
         */
        public function isFilled()
        {
            return ! is_null($this->value);
        }
    }
}

if (! class_exists('PayzenCurrency', false)) {

    /**
     * Class representing a currency, used for converting alpha/numeric ISO codes and float/integer amounts.
     */
    class PayzenCurrency
    {

        private $alpha3;
        private $num;
        private $decimals;

        public function __construct($alpha3, $num, $decimals = 2)
        {
            $this->alpha3 = $alpha3;
            $this->num = $num;
            $this->decimals = $decimals;
        }

        public function convertAmountToInteger($float)
        {
            $coef = pow(10, $this->decimals);

            $amount = $float * $coef;
            return (int) (string) $amount; // cast amount to string (to avoid rounding) than return it as int
        }

        public function convertAmountToFloat($integer)
        {
            $coef = pow(10, $this->decimals);

            return ((float) $integer) / $coef;
        }

        public function getAlpha3()
        {
            return $this->alpha3;
        }

        public function getNum()
        {
            return $this->num;
        }

        public function getDecimals()
        {
            return $this->decimals;
        }
    }
}
