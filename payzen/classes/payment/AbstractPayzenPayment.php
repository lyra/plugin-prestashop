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

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class AbstractPayzenPayment
{
    const PAYZEN_CART_MAX_NB_PRODUCTS = 85;

    protected $prefix;
    protected $tpl_name;
    protected $logo;
    protected $name;

    protected $currencies = array();

    public function isAvailable($cart)
    {
        if (!$this->checkActive()) {
            return false;
        }

        if (!$this->checkAmountRestriction($cart)) {
            return false;
        }

        if (!$this->checkCurrency($cart)) {
            return false;
        }

        return true;
    }

    private function checkActive()
    {
        return Configuration::get($this->prefix . 'ENABLED') == 'True';
    }

    private function checkAmountRestriction($cart)
    {
        $config_options = @unserialize(Configuration::get($this->prefix . 'AMOUNTS'));
        if (!is_array($config_options) || empty($config_options)) {
            return true;
        }

        $customer_group = (int)Customer::getDefaultGroupId($cart->id_customer);

        $all_min_amount = $config_options[0]['min_amount'];
        $all_max_amount = $config_options[0]['max_amount'];

        $min_amount = null;
        $max_amount = null;
        foreach ($config_options as $key => $value) {
            if (empty($value) || $key === 0) {
                continue;
            }

            if ($key === $customer_group) {
                $min_amount = $value['min_amount'];
                $max_amount = $value['max_amount'];

                break;
            }
        }

        if (!$min_amount) {
            $min_amount = $all_min_amount;
        }
        if (!$max_amount) {
            $max_amount = $all_max_amount;
        }

        if (($min_amount && $cart->getOrderTotal() < $min_amount)
            || ($max_amount && $cart->getOrderTotal() > $max_amount)) {
            return false;
        }

        return true;
    }

    private function checkCurrency($cart)
    {
        if (!is_array($this->currencies) || empty($this->currencies)) {
            return true;
        }

        // check if sub-module is available for some currencies
        $cart_currency = new Currency((int)$cart->id_currency);
        if (in_array($cart_currency->iso_code, $this->currencies)) {
            return true;
        }

        return false;
    }

    protected function proposeOney($data = array())
    {
        return false;
    }

    public function validate($cart, $data = array())
    {
        $errors = array();
        return $errors;
    }

    public function getTplName()
    {
        return $this->tpl_name;
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function getTplVars($cart)
    {
        return array(
            'payzen_title' => $this->getTitle((int)$cart->id_lang),
            'payzen_logo' => _MODULE_DIR_.'payzen/views/img/'.$this->getLogo()
        );
    }

    public function getPaymentOption($cart)
    {
        $class_name = '\PrestaShop\PrestaShop\Core\Payment\PaymentOption';
        $option = new $class_name();
        $option->setCallToActionText($this->getTitle((int)$cart->id_lang))
                ->setModuleName('payzen');
//                 ->setLogo(_MODULE_DIR_.'payzen/views/img/'.$this->getLogo());

        if (!$this->hasForm()) {
            $option->setAction(Context::getContext()->link->getModuleLink('payzen', 'redirect', array(), true));

            $inputs = array(
                array('type' => 'hidden', 'name' => 'payzen_payment_type', 'value' => $this->name)
            );
            $option->setInputs($inputs);
        }

        return $option;
    }

    public function getTitle($lang)
    {
        $title = Configuration::get($this->prefix.'TITLE', $lang);
        if (!$title) {
            $title = $this->getDefaultTitle();
        }

        return $title;
    }

    public function hasForm()
    {
        return false;
    }

    abstract protected function getDefaultTitle();

    /**
     * Generate form fields to post to the payment gateway.
     *
     * @param Cart $cart
     * @param array[string][string] $data
     * @return array[string][string]
     */
    public function prepareRequest($cart, $data = array())
    {
        // update shop info in cart to avoid errors when shopping cart is shared
        $shop = Context::getContext()->shop;
        if ($shop->getGroup()->share_order && ($cart->id_shop != $shop->id)) {
            $cart->id_shop = $shop->id;
            $cart->id_shop_group = $shop->id_shop_group;
            $cart->save();
        }

        /* @var $billing_country Address */
        $billing_address = new Address((int)$cart->id_address_invoice);
        $billing_country = new Country((int)$billing_address->id_country);

        /* @var $delivery_address Address */
        $colissimo_address = PayzenTools::getColissimoDeliveryAddress($cart); // get SoColissimo delivery address
        if ($colissimo_address instanceof Address) {
            $delivery_address = $colissimo_address;
        } else {
            $delivery_address = new Address((int)$cart->id_address_delivery);
        }
        $delivery_country = new Country((int)$delivery_address->id_country);

        PayzenTools::getLogger()->logInfo("Form data generation for cart #{$cart->id} with {$this->name} sub-module.");

        require_once _PS_MODULE_DIR_.'payzen/classes/PayzenRequest.php';
        /* @var $request PayzenRequest */
        $request = new PayzenRequest();

        $contrib = 'PrestaShop1.5-1.7_1.9.0/'._PS_VERSION_.'/'.PHP_VERSION;
        if (defined('_PS_HOST_MODE_')) {
            $contrib = str_replace('PrestaShop', 'PrestaShop_Cloud', $contrib);
        }
        $request->set('contrib', $contrib);

        foreach (PayzenTools::getAdminParameters() as $param) {
            if (isset($param['name'])) {
                $id_lang = null;
                if (in_array($param['key'], PayzenTools::$multi_lang_fields)) {
                    $id_lang = (int)$cart->id_lang;
                }

                // set PayZen payment params only
                $request->set($param['name'], Configuration::get($param['key'], $id_lang));
            }
        }

        // detect default language
        /* @var $language Language */
        $language = Language::getLanguage((int)$cart->id_lang);
        $language_iso_code = $language['language_code'] ?
            Tools::substr($language['language_code'], 0, 2) : $language['iso_code'];
        $language_iso_code = Tools::strtolower($language_iso_code);
        if (!PayzenApi::isSupportedLanguage($language_iso_code)) {
            $language_iso_code = Configuration::get('PAYZEN_DEFAULT_LANGUAGE');
        }

        // detect store currency
        $cart_currency = new Currency((int)$cart->id_currency);
        $currency = PayzenApi::findCurrencyByAlphaCode($cart_currency->iso_code);

        // amount rounded to currency decimals
        $amount = Tools::ps_round($cart->getOrderTotal(), $currency->getDecimals());

        $request->set('amount', $currency->convertAmountToInteger($amount));
        $request->set('currency', $currency->getNum());
        $request->set('language', $language_iso_code);
        $request->set('order_id', $cart->id);

        /* @var $cust Customer */
        $cust = new Customer((int)$cart->id_customer);

        // customer data
        $request->set('cust_email', $cust->email);
        $request->set('cust_id', $cust->id);

        $cust_title = new Gender((int)$cust->id_gender);
        $request->set('cust_title', $cust_title->name[Context::getContext()->language->id]);

        $request->set('cust_first_name', $billing_address->firstname);
        $request->set('cust_last_name', $billing_address->lastname);
        $request->set('cust_legal_name', $billing_address->company ? $billing_address->company : null);
        $request->set('cust_address', $billing_address->address1.' '.$billing_address->address2);
        $request->set('cust_zip', $billing_address->postcode);
        $request->set('cust_city', $billing_address->city);
        $request->set('cust_phone', $billing_address->phone);
        $request->set('cust_cell_phone', $billing_address->phone_mobile);
        $request->set('cust_country', $billing_country->iso_code);
        if ($billing_address->id_state) {
            $state = new State((int)$billing_address->id_state);
            $request->set('cust_state', $state->iso_code);
        }

        if (!$cart->isVirtualCart() && ($delivery_address instanceof Address)) {
            $request->set('ship_to_first_name', $delivery_address->firstname);
            $request->set('ship_to_last_name', $delivery_address->lastname);
            $request->set('ship_to_legal_name', $delivery_address->company ? $delivery_address->company : null);
            $request->set('ship_to_street', $delivery_address->address1);
            $request->set('ship_to_street2', $delivery_address->address2);
            $request->set('ship_to_zip', $delivery_address->postcode);
            $request->set('ship_to_city', $delivery_address->city);
            $request->set('ship_to_phone_num', $delivery_address->phone);
            $request->set('ship_to_country', $delivery_country->iso_code);
            if ($delivery_address->id_state) {
                $state = new State((int)$delivery_address->id_state);
                $request->set('ship_to_state', $state->iso_code);
            }
        }

        // prepare cart data to send to gateway
        if (Configuration::get('PAYZEN_COMMON_CATEGORY') != 'CUSTOM_MAPPING') {
            $category = Configuration::get('PAYZEN_COMMON_CATEGORY');
        } else {
            $oney_categories = @unserialize(Configuration::get('PAYZEN_CATEGORY_MAPPING'));
        }

        $subtotal = 0;
        $products = $cart->getProducts(true);
        if (count($products) <= self::PAYZEN_CART_MAX_NB_PRODUCTS || $this->proposeOney($data)) {
            $product_label_regex_not_allowed = '#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ ]#ui';

            foreach ($products as $product) {
                if (!isset($category)) {
                    // build query to get product default category
                    $sql = 'SELECT `id_category_default` FROM `'._DB_PREFIX_.'product` WHERE `id_product` = '
                        .(int)$product['id_product'];
                    $db_category = Db::getInstance()->getValue($sql);

                    $category = $oney_categories[$db_category];
                }

                $price_in_cents = $currency->convertAmountToInteger($product['price']);
                $qty = (int)$product['cart_quantity'];

                $request->addProduct(
                    Tools::substr(preg_replace($product_label_regex_not_allowed, ' ', $product['name']), 0, 255),
                    $price_in_cents,
                    $qty,
                    $product['id_product'],
                    $category
                );

                $subtotal += $price_in_cents * $qty;
            }
        }

        // set misc optional params as possible
        $request->set(
            'shipping_amount',
            $currency->convertAmountToInteger($cart->getOrderTotal(false, Cart::ONLY_SHIPPING))
        );

        // recalculate tax_amount to avoid rounding problems
        $tax_amount_in_cents = $request->get('amount') - $subtotal - $request->get('shipping_amount');
        if ($tax_amount_in_cents < 0) {
            // when order is discounted
            $tax_amount = $cart->getOrderTotal() - $cart->getOrderTotal(false);
            $tax_amount_in_cents = ($tax_amount <= 0) ? 0 : $currency->convertAmountToInteger($tax_amount);
        }

        $request->set('tax_amount', $tax_amount_in_cents);

        if (Configuration::get('PAYZEN_SEND_SHIP_DATA') == 'True' || $this->proposeOney($data)) {
            // set information about delivery mode
            $this->setAdditionalData($cart, $delivery_address, $request, $this->proposeOney($data));
        }

        // override capture delay if defined in sub-module
        if (is_numeric(Configuration::get($this->prefix.'DELAY'))) {
            $request->set('capture_delay', Configuration::get($this->prefix.'DELAY'));
        }

        //override validation mode if defined in sub-module
        if (Configuration::get($this->prefix.'VALIDATION') != '-1') {
            $request->set('validation_mode', Configuration::get($this->prefix.'VALIDATION'));
        }

        $request->set('order_info', $this->getTitle((int)$cart->id_lang));

        // activate 3-DS ?
        $threeds_min_amount = Configuration::get('PAYZEN_3DS_MIN_AMOUNT');
        $threeds_mpi = null;
        if ($threeds_min_amount != '' && $amount < $threeds_min_amount) {
            $threeds_mpi = '2';
        }

        $request->set('threeds_mpi', $threeds_mpi);

        // return URL
        $request->set('url_return', Context::getContext()->link->getModuleLink('payzen', 'submit', array(), true));

        return $request;
    }

    private function setAdditionalData($cart, $delivery_address, &$payzen_request, $use_oney = false)
    {
        // Oney delivery options defined in admin panel
        $shipping_options = @unserialize(Configuration::get('PAYZEN_ONEY_SHIP_OPTIONS'));

        // retrieve carrier ID from cart
        if (isset($cart->id_carrier) && $cart->id_carrier > 0) {
            $carrier_id = $cart->id_carrier;
        } else {
            $delivery_option_list = $cart->getDeliveryOptionList();

            $delivery_option = $cart->getDeliveryOption();
            $carrier_key = $delivery_option[(int)$cart->id_address_delivery];
            $carrier_list = $delivery_option_list[(int)$cart->id_address_delivery][$carrier_key]['carrier_list'];

            foreach (array_keys($carrier_list) as $id) {
                $carrier_id = $id;
                break;
            }
        }

        $not_allowed_chars = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui";
        $address_not_allowed_chars = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '.,-]#ui";

        // set shipping params

        if ($cart->isVirtualCart() || !isset($carrier_id) || !is_array($shipping_options) || empty($shipping_options)) {
            // no shipping options or virtual cart
            $payzen_request->set('ship_to_type', 'ETICKET');
            $payzen_request->set('ship_to_speed', 'EXPRESS');

            $shop = Shop::getShop($cart->id_shop);
            $payzen_request->set('ship_to_delivery_company_name', preg_replace($not_allowed_chars, ' ', $shop['name']));
        } elseif (self::isSupportedRelayPoint($carrier_id)) {
            // specific supported relay point carrier
            $payzen_request->set('ship_to_type', 'RELAY_POINT');
            $payzen_request->set('ship_to_speed', 'STANDARD');

            $address = '';
            $city = '';
            $zipcode = '';
            $country = 'FR';

            switch (true) {
                case self::isTntRelayPoint($carrier_id):
                    $sql = 'SELECT * FROM `'._DB_PREFIX_."tnt_carrier_drop_off` WHERE `id_cart` = '".(int)$cart->id."'";
                    $row = Db::getInstance()->getRow($sql);

                    // relay point name + address
                    $address = $row['name'].' '.$row['address'];
                    $city = $row['city'];
                    $zipcode = $row['zipcode'];

                    break;
                case self::isMondialRelay($carrier_id):
                    $sql = 'SELECT * FROM `'._DB_PREFIX_.'mr_selected` s WHERE s.`id_cart` = '.(int)$cart->id;
                    $row = Db::getInstance()->getRow($sql);

                    // relay point name + address
                    $address = $row['MR_Selected_LgAdr1'].' '.$row['MR_Selected_LgAdr3'];
                    $city = $row['MR_Selected_Ville'];
                    $zipcode = $row['MR_Selected_CP'];
                    $country = $row['MR_Selected_Pays'];

                    break;
                case self::isDpdFranceRelais($carrier_id):
                    $sql = 'SELECT * FROM `'._DB_PREFIX_.'dpdfrance_shipping` WHERE `id_cart` = '.(int)$cart->id;
                    $row = Db::getInstance()->getRow($sql);

                    // relay point name + address
                    $address = $row['company'].' '.$row['address1'].' '.$row['address2'];
                    $city = $row['city'];
                    $zipcode = $row['postcode'];

                    $ps_country = new Country((int)$row['id_country']);
                    $country = $ps_country->iso_code;

                    break;

                case (self::isColissimoRelay($carrier_id) && $delivery_address->company /* relay point */):
                    // relay point name + address
                    $address = $delivery_address->company.' '.$delivery_address->address1.' '.$delivery_address->address2;

                    // already set address
                    $city = $payzen_request->get('ship_to_city');
                    $zipcode = $payzen_request->get('ship_to_zip');
                    $country = $payzen_request->get('ship_to_country');
                    break;

                // can implement more specific relay point carriers logic here.
            }

            // override shipping address
            $payzen_request->set('ship_to_street', preg_replace($address_not_allowed_chars, ' ', $address));
            $payzen_request->set('ship_to_street2', null); // not sent to FacilyPay Oney
            $payzen_request->set('ship_to_zip', $zipcode);
            $payzen_request->set('ship_to_city', preg_replace($not_allowed_chars, ' ', $city));
            $payzen_request->set('ship_to_state', null);
            $payzen_request->set('ship_to_country', $country);

            $delivery_company = preg_replace($not_allowed_chars, ' ', $address.' '.$zipcode.' '.$city);
            $payzen_request->set('ship_to_delivery_company_name', $delivery_company);
        } else {
            // other cases
            $delivery_type = $shipping_options[$carrier_id]['type'];
            $payzen_request->set('ship_to_type', $delivery_type);
            $payzen_request->set('ship_to_speed', $shipping_options[$carrier_id]['speed']);

            $company_name = $shipping_options[$carrier_id]['label'];

            if ($delivery_type === 'RECLAIM_IN_SHOP') {
                $shop = Shop::getShop($cart->id_shop);
                $shop_name = preg_replace($not_allowed_chars, ' ', $shop['name']);

                $payzen_request->set('ship_to_street', $shop_name.' '.$shipping_options[$carrier_id]['address']);
                $payzen_request->set('ship_to_street2', null); // not sent to FacilyPay Oney
                $payzen_request->set('ship_to_zip', $shipping_options[$carrier_id]['zip']);
                $payzen_request->set('ship_to_city', $shipping_options[$carrier_id]['city']);
                $payzen_request->set('ship_to_country', 'FR');

                $company_name = $shop_name.' '.$shipping_options[$carrier_id]['address'].' '.
                    $shipping_options[$carrier_id]['zip'].' '.$shipping_options[$carrier_id]['city'];

                if ($shipping_options[$carrier_id]['speed'] === 'PRIORITY') {
                    $payzen_request->set('ship_to_delay', $shipping_options[$carrier_id]['delay']);
                }
            }

            $payzen_request->set('ship_to_delivery_company_name', $company_name);
        }

        if ($use_oney) {
            // modify address to send it to Oney

            if ($payzen_request->get('ship_to_street')) { // if there is a delivery address
                $payzen_request->set('ship_to_status', 'PRIVATE'); // by default PrestaShop doesn't manage customer type

                $address = $payzen_request->get('ship_to_street').' '.$payzen_request->get('ship_to_street2');

                $payzen_request->set('ship_to_street', preg_replace($address_not_allowed_chars, ' ', $address));
                $payzen_request->set('ship_to_street2', null); // not sent to FacilyPay Oney

                $payzen_request->set('ship_to_country', 'FR');
            }

            // by default PrestaShop doesn't manage customer type
            $payzen_request->set('cust_status', 'PRIVATE');

            // send FR even address is in DOM-TOM unless form is rejected
            $payzen_request->set('cust_country', 'FR');
        }
    }

    private static function isSupportedRelayPoint($carrier_id)
    {
        return self::isTntRelayPoint($carrier_id) || self::isMondialRelay($carrier_id)
            || self::isDpdFranceRelais($carrier_id) || self::isColissimoRelay($carrier_id);
    }

    private static function isTntRelayPoint($carrier_id)
    {
        if (!Configuration::get('TNT_CARRIER_JD_ID')) {
            return false;
        }

        return (Configuration::get('TNT_CARRIER_JD_ID') == $carrier_id);
    }

    private static function isMondialRelay($carrier_id)
    {
        if (!Configuration::get('MONDIAL_RELAY')) {
            return false;
        }

        $sql = 'SELECT `id_mr_method` FROM `'._DB_PREFIX_.'mr_method` WHERE `id_carrier` = '.(int)$carrier_id;
        $id_method = Db::getInstance()->getValue($sql);

        return !empty($id_method);
    }

    private static function isDpdFranceRelais($carrier_id)
    {
        if (!Configuration::get('DPDFRANCE_RELAIS_CARRIER_ID')) {
            return false;
        }

        return (Configuration::get('DPDFRANCE_RELAIS_CARRIER_ID') == $carrier_id);
    }

    private static function isColissimoRelay($carrier_id)
    {
        // SoColissimo not available
        if (!Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return false;
        }

        // SoColissimo is not selected as shipping method
        return (Configuration::get('SOCOLISSIMO_CARRIER_ID') == $carrier_id);
    }

    /**
     * Shortcut for module translation function.
     *
     * @param string $text
     * @return localized text
     */
    protected function l($string)
    {
        /* @var Payzen */
        $payzen = Module::getInstanceByName('payzen');
        return $payzen->l($string, Tools::strtolower(get_class($this)));
    }
}
