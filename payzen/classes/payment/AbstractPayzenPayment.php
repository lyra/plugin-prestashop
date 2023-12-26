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
use Lyranetwork\Payzen\Sdk\Form\Request as PayzenRequest;

abstract class AbstractPayzenPayment
{
    const PAYZEN_CART_MAX_NB_PRODUCTS = 85;

    protected $prefix;
    protected $tpl_name;
    protected $logo;
    protected $name;

    protected $currencies = array();
    protected $countries = array();

    protected $needs_cart_data = false;
    protected $force_local_cart_data = false;
    protected $allow_backend_payment = false;

    protected $needs_shipping_method_data = false;

    protected $module;
    protected $context;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->module = Module::getInstanceByName('payzen');
        $this->context = Context::getContext();
    }

    public function isAvailable($cart)
    {
        if (! $this->checkActive()) {
            return false;
        }

        if ($this->isFromBackend() && ! $this->allow_backend_payment) {
            return false;
        }

        // Cart errors.
        if (! Validate::isLoadedObject($cart) || ($cart->nbProducts() <= 0)) {
            return false;
        }

        if (! $this->checkAmountRestriction($cart)) {
            return false;
        }

        if (! $this->checkCurrency($cart)) {
            return false;
        }

        if (! $this->checkCountry($cart)) {
            return false;
        }

        return true;
    }

    protected function checkActive()
    {
        return Configuration::get($this->prefix . 'ENABLED') === 'True';
    }

    protected function checkAmountRestriction($cart)
    {
        $config_options = @unserialize(Configuration::get($this->prefix . 'AMOUNTS'));
        if (! is_array($config_options) || empty($config_options)) {
            return true;
        }

        $customer_group = (int) Customer::getDefaultGroupId($cart->id_customer);

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

        if (! is_numeric($min_amount)) {
            $min_amount = $all_min_amount;
        }

        if (! is_numeric($max_amount)) {
            $max_amount = $all_max_amount;
        }

        $amount = $cart->getOrderTotal();

        if ((is_numeric($min_amount) && $amount < $min_amount) || (is_numeric($max_amount) && $amount > $max_amount)) {
            return false;
        }

        return true;
    }

    protected function checkCurrency($cart)
    {
        if (! is_array($this->currencies) || empty($this->currencies)) {
            return true;
        }

        // Check if submodule is available for some currencies.
        $cart_currency = new Currency((int) $cart->id_currency);
        if (in_array($cart_currency->iso_code, $this->currencies)) {
            return true;
        }

        return false;
    }

    protected function checkCountry($cart)
    {
        $billing_address = new Address((int) $cart->id_address_invoice);
        $billing_country = new Country((int) $billing_address->id_country);

        // Submodule country restriction.
        $submoduleAvailableCountries = true;
        if (is_array($this->countries) && ! empty($this->countries)) {
            $submoduleAvailableCountries = in_array($billing_country->iso_code, $this->countries);
        }

        // Backend restriction on countries.
        $backendAllowAllCountries = Configuration::get($this->prefix . 'COUNTRY') === '1' ? true : false;
        $backendAllowSpecificCountries = ! Configuration::get($this->prefix . 'COUNTRY_LST') ?
            array() : explode(';', Configuration::get($this->prefix . 'COUNTRY_LST'));

        if ($backendAllowAllCountries) {
            if ($submoduleAvailableCountries) {
                return true;
            }
        } elseif (in_array($billing_country->iso_code, $backendAllowSpecificCountries) && $submoduleAvailableCountries) {
            return true;
        }

        return false;
    }

    public function validate($cart, $data = array())
    {
        $errors = array();

        // Cart errors.
        if (! Validate::isLoadedObject($cart)) {
            $errors[] = $this->l('Shopping cart not found.');
        } elseif ($cart->nbProducts() <= 0) {
            $errors[] = $this->l('Empty cart detected before order processing.');
        }

        return $errors;
    }

    public function getTplName()
    {
        return $this->tpl_name;
    }

    public function getLogo()
    {
        return _MODULE_DIR_ . 'payzen/views/img/' . $this->logo;
    }

    public function getTplVars($cart)
    {
        return array(
            'payzen_title' => $this->getTitle((int) $cart->id_lang),
            'payzen_logo' => $this->getLogo()
        );
    }

    public function getPaymentOption($cart)
    {
        $class_name = '\PrestaShop\PrestaShop\Core\Payment\PaymentOption';
        $option = new $class_name();
        $option->setCallToActionText($this->getTitle((int) $cart->id_lang))
            ->setModuleName('payzen');

        if (file_exists(_PS_MODULE_DIR_ . 'payzen/views/img/' . $this->logo)) {
            $option->setLogo($this->getLogo());
        }

        if (! $this->hasForm()) {
            $option->setAction($this->context->link->getModuleLink('payzen', 'redirect', array(), true));

            $inputs = array(
                array('type' => 'hidden', 'name' => 'payzen_payment_type', 'value' => $this->name)
            );
            $option->setInputs($inputs);
        }

        return $option;
    }

    public function getTitle($lang)
    {
        $title = Configuration::get($this->prefix . 'TITLE', $lang);
        if (! $title) {
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
        // Update shop info in cart to avoid errors when shopping cart is shared.
        $shop = $this->context->shop;
        if ($shop->getGroup()->share_order && ($cart->id_shop != $shop->id)) {
            $cart->id_shop = $shop->id;
            $cart->id_shop_group = $shop->id_shop_group;
            $cart->save();
        }

        /* @var $billing_country Address */
        $billing_address = new Address((int) $cart->id_address_invoice);
        $billing_country = new Country((int) $billing_address->id_country);

        /* @var $delivery_address Address */
        $colissimo_address = PayzenTools::getColissimoDeliveryAddress($cart); // Get SoColissimo delivery address.
        if ($colissimo_address instanceof Address) {
            $delivery_address = $colissimo_address;
        } else {
            $delivery_address = new Address((int) $cart->id_address_delivery);
        }

        $delivery_country = new Country((int) $delivery_address->id_country);

        PayzenTools::getLogger()->logInfo("Form data generation for cart #{$cart->id} with {$this->name} submodule.");

        /* @var $request PayzenRequest */
        $request = new PayzenRequest();

        $contrib = PayzenTools::getContrib();
        if (defined('_PS_HOST_MODE_')) {
            $contrib = str_replace('PrestaShop', 'PrestaShop_Cloud', $contrib);
        }

        $request->set('contrib', $contrib);

        foreach (PayzenTools::getAdminParameters() as $param) {
            if (isset($param['name'])) {
                $id_lang = null;
                if (in_array($param['key'], PayzenTools::$multi_lang_fields)) {
                    $id_lang = (int) $cart->id_lang;
                }

                $value = Configuration::get($param['key'], $id_lang);

                if (($param['name'] !== 'theme_config') || ($value !== 'RESPONSIVE_MODEL=')) {
                    // Set payment gateway params only.
                    $request->set($param['name'], $value);
                }
            }
        }

        // Detect default language.
        /* @var $language Language */
        $language = Language::getLanguage((int) $cart->id_lang);
        $language_iso_code = $language['language_code'] ?
            Tools::substr($language['language_code'], 0, 2) : $language['iso_code'];
        $language_iso_code = Tools::strtolower($language_iso_code);
        if (! PayzenApi::isSupportedLanguage($language_iso_code)) {
            $language_iso_code = Configuration::get('PAYZEN_DEFAULT_LANGUAGE');
        }

        // Detect store currency.
        $cart_currency = new Currency((int) $cart->id_currency);
        $currency = PayzenApi::findCurrencyByAlphaCode($cart_currency->iso_code);

        // Amount rounded to currency decimals.
        $amount = Tools::ps_round($cart->getOrderTotal(), $currency->getDecimals());

        $request->set('amount', $currency->convertAmountToInteger($amount));
        $request->set('currency', $currency->getNum());
        $request->set('language', $language_iso_code);
        $request->set('order_id', $cart->id);

        /* @var $cust Customer */
        $cust = new Customer((int) $cart->id_customer);

        // Customized fields for Brazil payment means.
        if(PayzenTools::$plugin_features['brazil']) {
            $customer_address = array_merge((array) $cust, (array) $billing_address);

            $address_number = '0';
            $config_number = Configuration::get('PAYZEN_NUMBER');
            if (! empty($config_number)) {
                $parts = explode('.', $config_number);
                $data_array = json_decode(json_encode($customer_address), true);
                if (! empty($data_array[$parts[1]])) {
                    $address_number = $data_array[$parts[1]];
                }
            }

            $document = '';
            $config_document = Configuration::get('PAYZEN_DOCUMENT');
            if (! empty($config_document)) {
                $parts = explode('.', $config_document);
                $data_array = json_decode(json_encode($customer_address), true);
                if (! empty($data_array[$parts[1]])) {
                    $document = $data_array[$parts[1]];
                }
            }

            $document = PayzenTools::formatDocument($document);

            $neighborhood = '-';
            $config_neighborhood = Configuration::get('PAYZEN_NEIGHBORHOOD');
            if (! empty($config_neighborhood)) {
                $parts = explode('.', $config_neighborhood);
                $data_array = json_decode(json_encode($customer_address), true);
                if (! empty($data_array[$parts[1]])) {
                    $neighborhood = $data_array[$parts[1]];
                }
            }

            $request->set('cust_district', $neighborhood);
            $request->set('cust_address_number', $address_number);
            $request->set('cust_national_id', $document);
        }

        // Customer data.
        $request->set('cust_email', $cust->email);
        $request->set('cust_id', $cust->id);

        $cust_title = new Gender((int) $cust->id_gender);
        $request->set('cust_title', $cust_title->name ? $cust_title->name[$this->context->language->id] : "");

        $phone = $billing_address->phone ? $billing_address->phone : $billing_address->phone_mobile;
        $cell_phone = $billing_address->phone_mobile ? $billing_address->phone_mobile : $billing_address->phone;

        $request->set('cust_first_name', $billing_address->firstname);
        $request->set('cust_last_name', $billing_address->lastname);
        $request->set('cust_legal_name', $billing_address->company ? $billing_address->company : null);
        $request->set('cust_address', $billing_address->address1 . ' ' . $billing_address->address2);
        $request->set('cust_zip', $billing_address->postcode);
        $request->set('cust_city', $billing_address->city);
        $request->set('cust_phone', $phone);
        $request->set('cust_cell_phone', $cell_phone);
        $request->set('cust_country', $billing_country->iso_code);
        if ($billing_address->id_state) {
            $state = new State((int) $billing_address->id_state);
            $request->set('cust_state', $state->iso_code);
        }

        if (! $cart->isVirtualCart() && ($delivery_address instanceof Address)) {
            $request->set('ship_to_first_name', $delivery_address->firstname);
            $request->set('ship_to_last_name', $delivery_address->lastname);
            $request->set('ship_to_legal_name', $delivery_address->company ? $delivery_address->company : null);
            $request->set('ship_to_street', $delivery_address->address1);
            $request->set('ship_to_street2', $delivery_address->address2);
            $request->set('ship_to_zip', $delivery_address->postcode);
            $request->set('ship_to_city', $delivery_address->city);
            $request->set('ship_to_phone_num', $delivery_address->phone_mobile ? $delivery_address->phone_mobile : $delivery_address->phone);
            $request->set('ship_to_country', $delivery_country->iso_code);
            if ($delivery_address->id_state) {
                $state = new State((int) $delivery_address->id_state);
                $request->set('ship_to_state', $state->iso_code);
            }
        }

        // Prepare cart data to send to gateway.
        $this->setCartData($cart, $currency, $request);

        // Set misc optional params as possible.
        $request->set(
            'shipping_amount',
            $currency->convertAmountToInteger($cart->getOrderTotal(false, Cart::ONLY_SHIPPING))
        );

        // Avoid recalculating tax amount, not correct in many cases, just send tax amount as returned by PrestaShop.
        $tax_amount = $cart->getOrderTotal(true) - $cart->getOrderTotal(false);
        $tax_amount_in_cents = ($tax_amount <= 0) ? 0 : $currency->convertAmountToInteger($tax_amount);

        $request->set('tax_amount', $tax_amount_in_cents);

        // VAT amount for colombian payment means.
        $request->set('totalamount_vat', $tax_amount_in_cents);

        // Set information about delivery mode.
        $this->setAdvancedShippingData($cart, $delivery_address, $request);

        // Override capture delay if defined in submodule.
        if (is_numeric(Configuration::get($this->prefix . 'DELAY'))) {
            $request->set('capture_delay', Configuration::get($this->prefix . 'DELAY'));
        }

        // Override validation mode if defined in submodule.
        if (Configuration::get($this->prefix . 'VALIDATION') !== '-1') {
            $request->set('validation_mode', Configuration::get($this->prefix . 'VALIDATION'));
        }

        $request->addExtInfo('module_id', $this->name);

        // Activate 3DS?
        $threeds_mpi = null;
        $threeds_min_amount_options = @unserialize(Configuration::get('PAYZEN_3DS_MIN_AMOUNT'));
        if (is_array($threeds_min_amount_options) && ! empty($threeds_min_amount_options)) {
            $customer_group = (int) Customer::getDefaultGroupId($cart->id_customer);

            $all_min_amount = $threeds_min_amount_options[0]['min_amount']; // Value configured for all groups.

            $min_amount = null;
            foreach ($threeds_min_amount_options as $key => $value) {
                if (empty($value) || $key === 0) {
                    continue;
                }

                if ($key === $customer_group) {
                    $min_amount = $value['min_amount'];
                    break;
                }
            }

            if (! $min_amount) {
                $min_amount = $all_min_amount;
            }

            if ($min_amount && ($amount < $min_amount)) {
                $threeds_mpi = '2';
            }
        }

        $request->set('threeds_mpi', $threeds_mpi);

        // Return URL.
        $request->set('url_return', $this->context->link->getModuleLink('payzen', 'submit', array(), true));

        return $request;
    }

    public function isFromBackend()
    {
        if (isset($this->context->cookie->payzenBackendPayment)) {
            return $this->context->cookie->payzenBackendPayment;
        }

        $return = false;

        $adminCookie = new Cookie('psAdmin');
        if (isset($adminCookie->id_employee) && ! empty($adminCookie->id_employee) && Tools::getIsset('recover_cart')) {
            $this->context->cookie->payzenBackendPayment = true;
            $return = true;
        }

        return $return;
    }

    private function setCartData($cart, $currency, &$payzen_request)
    {
        $products = $cart->getProducts(true);

        if (! $this->needs_cart_data && (count($products) > self::PAYZEN_CART_MAX_NB_PRODUCTS)) {
            return;
        }

        if (! $this->needs_cart_data && ($this->force_local_cart_data || (Configuration::get('PAYZEN_SEND_CART_DETAIL') !== 'True'))) {
            return;
        }

        // Prepare cart data to send to gateway.
        if (Configuration::get('PAYZEN_COMMON_CATEGORY') !== 'CUSTOM_MAPPING') {
            $category = Configuration::get('PAYZEN_COMMON_CATEGORY');
        } else {
            $oney_categories = @unserialize(Configuration::get('PAYZEN_CATEGORY_MAPPING'));
        }

        $product_label_regex_not_allowed = '#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ ]#ui';

        foreach ($products as $product) {
            if (!isset($category)) {
                // Build query to get product default category.
                $sql = 'SELECT `id_category_default` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' .
                    (int) $product['id_product'];

                $db_category = Db::getInstance()->getValue($sql);
                $category = $oney_categories[$db_category];
            }

            $price_in_cents = $currency->convertAmountToInteger($product['price']);
            $qty = (int) $product['cart_quantity'];

            $payzen_request->addProduct(
                Tools::substr(preg_replace($product_label_regex_not_allowed, ' ', $product['name']), 0, 255),
                $price_in_cents,
                $qty,
                $product['id_product'],
                $category,
                number_format($product['rate'], 4, '.', '')
           );
        }
    }

    private function setAdvancedShippingData($cart, $delivery_address, &$payzen_request)
    {
        if (Configuration::get('PAYZEN_SEND_SHIP_DATA') !== 'True' && ! $this->needs_shipping_method_data) {
            return;
        }

        // For Oney, some parameters must contains different data.
        $isOney34 = $this instanceof PayzenOney34Payment;

        // Oney delivery options defined in admin panel.
        $shipping_options = @unserialize(Configuration::get('PAYZEN_ONEY_SHIP_OPTIONS'));

        // Retrieve carrier ID from cart.
        if (isset($cart->id_carrier) && $cart->id_carrier > 0) {
            $carrier_id = $cart->id_carrier;
        } else {
            $delivery_option_list = $cart->getDeliveryOptionList();

            $delivery_option = $cart->getDeliveryOption();
            $carrier_key = $delivery_option[(int) $cart->id_address_delivery];
            $carrier_list = $delivery_option_list[(int) $cart->id_address_delivery][$carrier_key]['carrier_list'];

            foreach (array_keys($carrier_list) as $id) {
                $carrier_id = $id;
                break;
            }
        }

        $not_allowed_chars = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ -]#ui";
        $address_not_allowed_chars = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '.,-]#ui";
        $relay_point_name = null;

        // Set shipping params.
        if ($cart->isVirtualCart() || ! isset($carrier_id) || ! is_array($shipping_options) || empty($shipping_options)) {
            // No shipping options or virtual cart.
            $payzen_request->set('ship_to_type', 'ETICKET');
            $payzen_request->set('ship_to_speed', 'EXPRESS');
        } elseif (self::isSupportedRelayPoint($carrier_id)) {
            // Specific supported relay point carrier.
            $payzen_request->set('ship_to_type', 'RELAY_POINT');
            $payzen_request->set('ship_to_speed', 'STANDARD');

            $address = '';
            $city = '';
            $zipcode = '';
            $country = 'FR';

            switch (true) {
                case self::isTntRelayPoint($carrier_id):
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . "tnt_carrier_drop_off` WHERE `id_cart` = '" . (int) $cart->id . "'";
                    $row = Db::getInstance()->getRow($sql);

                    if (! $row) {
                        break;
                    }

                    $address = $isOney34 ? $row['address'] : $row['name'] . ' ' . $row['address']; // Relay point address.
                    $relay_point_name = $isOney34 ? $row['name'] : null; // Relay point name.
                    $city = $row['city'];
                    $zipcode = $row['zipcode'];

                    break;

                case self::isNewMondialRelay($carrier_id):
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_selected_relay` s WHERE s.`id_cart` = ' . (int) $cart->id;
                    $row = Db::getInstance()->getRow($sql);

                    if (! $row) {
                        break;
                    }

                    // Relay point address.
                    $address =  $isOney34 ? $row['selected_relay_adr3'] : $row['selected_relay_adr1'] . ' ' . $row['selected_relay_adr3'];
                    $relay_point_name = $isOney34 ? $row['selected_relay_adr1'] : null; // Relay point name.
                    $city = $row['selected_relay_city'];
                    $zipcode = $row['selected_relay_postcode'];
                    $country = $row['selected_relay_country_iso'];
                    break;

                case self::isMondialRelay($carrier_id):
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mr_selected` s WHERE s.`id_cart` = ' . (int) $cart->id;
                    $row = Db::getInstance()->getRow($sql);

                    if (! $row) {
                        break;
                    }

                    // Relay point address.
                    $address = $isOney34 ? $row['MR_Selected_LgAdr3'] : $row['MR_Selected_LgAdr1'] . ' ' . $row['MR_Selected_LgAdr3'];
                    $relay_point_name = $isOney34 ? $row['MR_Selected_LgAdr1'] : null; // Relay point name.
                    $city = $row['MR_Selected_Ville'];
                    $zipcode = $row['MR_Selected_CP'];
                    $country = $row['MR_Selected_Pays'];

                    break;

                case self::isDpdFranceRelais($carrier_id):
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dpdfrance_shipping` WHERE `id_cart` = ' . (int) $cart->id;
                    $row = Db::getInstance()->getRow($sql);

                    if (! $row) {
                        break;
                    }

                    $address = $row['address1'] . ' ' . $row['address2'];
                    $address = $isOney34 ? $address : $row['company'] . ' ' . $address; // Relay point address.
                    $relay_point_name = $isOney34 ? $row['company'] : null; // Relay point name.
                    $city = $row['city'];
                    $zipcode = $row['postcode'];

                    $ps_country = new Country((int) $row['id_country']);
                    $country = $ps_country->iso_code;

                    break;

                case (self::isSoColissimoLiberteRelay($carrier_id)):
                    // Get relay point internal id.
                    $sql = 'SELECT prid FROM `' . _DB_PREFIX_ . 'socolissimo_delivery_info`
                            WHERE id_cart = ' . (int) $cart->id;

                    $prid = Db::getInstance()->getValue($sql);
                    if (! $prid) {
                        break;
                    }

                    // Get relay point address.
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'so_retrait`
                            WHERE id = ' . (int) $prid;

                    $relaypoint_address = Db::getInstance()->getRow($sql);
                    if (! $relaypoint_address) {
                        break;
                    }

                    $address = $relaypoint_address['adresse1'] . (isset($relaypoint_address['adresse2']) ? ' ' . $relaypoint_address['adresse2'] : '');
                    $address = $isOney34 ? $address : $relaypoint_address['libelle'] . ' ' . $address; // Relay point address.
                    $relay_point_name = $isOney34 ? $relaypoint_address['libelle'] : null; // Relay point name.

                    $city = $relaypoint_address['commune'];
                    $zipcode = $relaypoint_address['code_postal'];
                    $country = $payzen_request->get('ship_to_country');

                    break;

                case (self::isColissimoRelay($carrier_id) && $delivery_address->company):
                    $address = $delivery_address->address1 . ' ' . $delivery_address->address2;
                    $address = $isOney34 ? $address : $delivery_address->company . ' ' . $address; // Relay point address.
                    $relay_point_name = $isOney34 ? $delivery_address->company : null; // Relay point name.

                    // Already set address.
                    $city = $payzen_request->get('ship_to_city');
                    $zipcode = $payzen_request->get('ship_to_zip');
                    $country = $payzen_request->get('ship_to_country');

                    break;

                case (self::isChronoPostRelay($carrier_id)):
                    $sql = 'SELECT id_pr FROM `' . _DB_PREFIX_ . 'chrono_cart_relais` WHERE id_cart = ' . (int) $cart->id;
                    $id_pr = Db::getInstance()->getValue($sql);

                    if (! $id_pr) {
                        break;
                    }

                    $relaypoint_address = $this->getChronopostRelayPointAddress($id_pr);

                    $address = $relaypoint_address->adresse1 . ' ' . $relaypoint_address->adresse2;
                    $address = $isOney34 ? $address : $relaypoint_address->nomEnseigne . ' ' . $address; // Relay point address.
                    $relay_point_name = $isOney34 ? $relaypoint_address->nomEnseigne : null; // Relay point name.

                    $city = $relaypoint_address->localite;
                    $zipcode = $relaypoint_address->codePostal;
                    $country = $payzen_request->get('ship_to_country');

                    break;

                    // Can implement more specific relay point carriers logic here.

                default:
                    break;
            }

            // Override shipping address.
            $payzen_request->set('ship_to_street', preg_replace($address_not_allowed_chars, ' ', $address));
            $payzen_request->set('ship_to_street2', null);
            $payzen_request->set('ship_to_zip', $zipcode);
            $payzen_request->set('ship_to_city', preg_replace($not_allowed_chars, ' ', $city));
            $payzen_request->set('ship_to_state', null);
            $payzen_request->set('ship_to_country', $country);

            $delivery_company = preg_replace($not_allowed_chars, ' ', $address . ' ' . $zipcode . ' ' . $city);
            $payzen_request->set('ship_to_delivery_company_name', $delivery_company);
        } else {
            // Other cases.
            $delivery_type = isset($shipping_options[$carrier_id]) ? $shipping_options[$carrier_id]['type'] : 'PACKAGE_DELIVERY_COMPANY';
            $delivery_speed = isset($shipping_options[$carrier_id]) ? $shipping_options[$carrier_id]['speed'] : 'STANDARD';
            $payzen_request->set('ship_to_type', $delivery_type);
            $payzen_request->set('ship_to_speed', $delivery_speed);

            // Get delivery company name.
            $delivery_option_list = $cart->getDeliveryOptionList();

            $delivery_option = $cart->getDeliveryOption();
            $carrier_key = $delivery_option[(int) $cart->id_address_delivery];
            $carrier_list = $delivery_option_list[(int) $cart->id_address_delivery][$carrier_key]['carrier_list'];
            $company_name = $carrier_list[$carrier_id]['instance']->name;

            if ($delivery_type === 'RECLAIM_IN_SHOP') {
                $shop_name = preg_replace($not_allowed_chars, ' ', Configuration::get('PS_SHOP_NAME'));

                $payzen_request->set('ship_to_street', $shop_name . ' ' . $shipping_options[$carrier_id]['address']);
                $payzen_request->set('ship_to_street2', null);
                $payzen_request->set('ship_to_zip', $shipping_options[$carrier_id]['zip']);
                $payzen_request->set('ship_to_city', $shipping_options[$carrier_id]['city']);
                $payzen_request->set('ship_to_country', 'FR');

                $company_name = $shop_name . ' ' . $shipping_options[$carrier_id]['address'] . ' ' .
                    $shipping_options[$carrier_id]['zip'] . ' ' . $shipping_options[$carrier_id]['city'];
            }

            // Enable delay select for rows with speed equals PRIORITY.
            if ($shipping_options[$carrier_id]['speed'] === 'PRIORITY') {
                $payzen_request->set('ship_to_delay', $shipping_options[$carrier_id]['delay']);
            }

            $payzen_request->set('ship_to_delivery_company_name', preg_replace($not_allowed_chars, ' ', $company_name));
        }

        if ($isOney34) {
            // Modify address to send it to Oney.
            if ($payzen_request->get('ship_to_street')) { // If there is a delivery address.
                $payzen_request->set('ship_to_status', 'PRIVATE'); // By default PrestaShop doesn't manage customer type.

                $address = $payzen_request->get('ship_to_street') . ' ' . $payzen_request->get('ship_to_street2');

                $payzen_request->set('ship_to_street', preg_replace($address_not_allowed_chars, ' ', $address));
                $payzen_request->set('ship_to_street2', $relay_point_name);

                // Send FR even address is in DOM-TOM unless form is rejected.
                $payzen_request->set('ship_to_country', 'FR');
            }

            // By default PrestaShop doesn't manage customer type.
            $payzen_request->set('cust_status', 'PRIVATE');

            // Send FR even address is in DOM-TOM unless form is rejected.
            $payzen_request->set('cust_country', 'FR');
        }
    }

    private static function isSupportedRelayPoint($carrier_id)
    {
        return self::isTntRelayPoint($carrier_id) || self::isNewMondialRelay($carrier_id)
            || self::isMondialRelay($carrier_id) || self::isDpdFranceRelais($carrier_id)
            || self::isColissimoRelay($carrier_id) || self::isChronoPostRelay($carrier_id)
            || self::isSoColissimoLiberteRelay($carrier_id);
    }

    private static function isTntRelayPoint($carrier_id)
    {
        if (! Configuration::get('TNT_CARRIER_JD_ID')) {
            return false;
        }

        return (Configuration::get('TNT_CARRIER_JD_ID') == $carrier_id);
    }

    private static function isNewMondialRelay($carrier_id)
    {
        if (! Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE')) {
            return false;
        }

        $sql = 'SELECT `id_mondialrelay_carrier_method` FROM `' . _DB_PREFIX_ . 'mondialrelay_carrier_method` WHERE `id_carrier` = ' . (int) $carrier_id;
        $id_method = Db::getInstance()->getValue($sql);

        return ! empty($id_method);
    }

    private static function isMondialRelay($carrier_id)
    {
        if (! Configuration::get('MONDIAL_RELAY')) {
            return false;
        }

        $sql = 'SELECT `id_mr_method` FROM `' . _DB_PREFIX_ . 'mr_method` WHERE `id_carrier` = ' . (int) $carrier_id;
        $id_method = Db::getInstance()->getValue($sql);

        return ! empty($id_method);
    }

    private static function isDpdFranceRelais($carrier_id)
    {
        if (! Configuration::get('DPDFRANCE_RELAIS_CARRIER_ID')) {
            return false;
        }

        return (Configuration::get('DPDFRANCE_RELAIS_CARRIER_ID') == $carrier_id);
    }

    private static function isColissimoRelay($carrier_id)
    {
        // SoColissimo not available.
        if (! Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return false;
        }

        // SoColissimo is selected as shipping method.
        return (Configuration::get('SOCOLISSIMO_CARRIER_ID') == $carrier_id);
    }

    private static function isSoColissimoLiberteRelay($carrier_id)
    {
        // SoColissimo Liberté relay points not available.
        if (! Configuration::get('SOLIBERTE_BPR_ID') && ! Configuration::get('SOLIBERTE_A2P_ID') &&
            ! Configuration::get('SOLIBERTE_CIT_ID')) {
                return false;
        }

        // SoColissimo Liberté is selected as shipping method.
        return (Configuration::get('SOLIBERTE_BPR_ID') == $carrier_id)
            || (Configuration::get('SOLIBERTE_A2P_ID') == $carrier_id)
            || (Configuration::get('SOLIBERTE_CIT_ID') == $carrier_id);
    }

    private static function isChronoPostRelay($carrier_id)
    {
        if (file_exists($fileName = _PS_MODULE_DIR_ . 'chronopost' . DIRECTORY_SEPARATOR . 'chronopost.php')) {
            require_once($fileName);

            return Chronopost::isRelais($carrier_id);
        } else {
            return false;
        }
    }

    private function getChronopostRelayPointAddress($chronopostRelayId)
    {
        include_once _PS_MODULE_DIR_ . 'chronopost' . DIRECTORY_SEPARATOR . 'libraries'  . DIRECTORY_SEPARATOR . 'PointRelaisServiceWSService.php';

        // Fetch BT object.
        $ws = new PointRelaisServiceWSService();
        $p = new rechercheBtAvecPFParIdChronopostA2Pas();
        $p->id = $chronopostRelayId;

        return $ws->rechercheBtAvecPFParIdChronopostA2Pas($p)->return;
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

    public function setCookieValidPaymentByAlias($identifier, $customer)
    {
        $isValidIdentifier = false;
        if ($identifier && $customer->id) {
            try {
                $isValidIdentifier = $this->module->checkIdentifier($identifier, $customer->email);
            } catch (\Exception $e) {
                PayzenTools::getLogger()->logError(
                    "Saved identifier for customer {$customer->email} couldn't be verified on gateway. Error occurred: {$e->getMessage()}"
                );

                // Unable to validate alias online, we cannot disable feature.
                $isValidIdentifier = true;
            }
        }

        $cookieName = $this->getValidAliasCookieName();
        $this->context->cookie->$cookieName = $isValidIdentifier;

        return $isValidIdentifier;
    }

    public function isValidSavedAlias()
    {
        $cookieName = $this->getValidAliasCookieName();
        return $this->isOneClickActive() && isset($this->context->cookie->$cookieName) && $this->context->cookie->$cookieName;
    }

    public function isOneClickActive()
    {
        return false;
    }

    protected function getValidAliasCookieName()
    {
        return 'is' . ucfirst($this->name) . 'ValidAlias';
    }

    protected static function getCcTypeImageSrc($card)
    {
        return PayzenTools::getDefault('LOGO_URL') . strtolower($card) . '.png';
    }
}
