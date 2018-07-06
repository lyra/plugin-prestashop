<?php
/**
 * PayZen V2-Payment Module version 1.10.1 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  payment
 * @package   payzen
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayzenTools
{
    const ORDER_ID_REGEX = '#^[a-zA-Z0-9]{1,9}$#';
    const CUST_ID_REGEX = '#^[a-zA-Z0-9]{1,8}$#';
    const PRODUCT_REF_REGEX = '#^[a-zA-Z0-9]{1,64}$#';

    const ON_FAILURE_RETRY = 'retry';
    const ON_FAILURE_SAVE = 'save';

    const EMPTY_CART = 'empty';
    const KEEP_CART = 'keep';

    /* fields lists */
    public static $multi_lang_fields = array(
        'PAYZEN_REDIRECT_SUCCESS_M', 'PAYZEN_REDIRECT_ERROR_M',
        'PAYZEN_STD_TITLE', 'PAYZEN_MULTI_TITLE', 'PAYZEN_ONEY_TITLE', 'PAYZEN_ANCV_TITLE',
        'PAYZEN_SEPA_TITLE', 'PAYZEN_SOFORT_TITLE', 'PAYZEN_PAYPAL_TITLE', 'PAYZEN_CHOOZEO_TITLE',
        'PAYZEN_FULLCB_TITLE'
    );
    public static $amount_fields = array();
    public static $group_amount_fields = array(
        'PAYZEN_STD_AMOUNTS', 'PAYZEN_MULTI_AMOUNTS', 'PAYZEN_ANCV_AMOUNTS',
        'PAYZEN_ONEY_AMOUNTS', 'PAYZEN_SEPA_AMOUNTS', 'PAYZEN_SOFORT_AMOUNTS',
        'PAYZEN_PAYPAL_AMOUNTS', 'PAYZEN_CHOOZEO_AMOUNTS', 'PAYZEN_CHOOZEO_OPTIONS',
        'PAYZEN_FULLCB_AMOUNTS', 'PAYZEN_3DS_MIN_AMOUNT'
    );
    public static $address_regex = array(
        'oney' => array(
            'name' => "#^[A-ZÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,63}$#ui",
            'street' => "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '.,-]{1,127}$#ui",
            'zip' => '#^[0-9]{5}$#',
            'city' => "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,127}$#ui",
            'country' => '#^FR|GP|MQ|GF|RE|YT$#i',
            'phone' => '#^[0-9]{10}$#'
        ),
        'fullcb' => array(
            'name' => "#^[A-Za-z0-9àâçèéêîôùû]+([ \-']?[A-Za-z0-9àâçèéêîôùû]+)*$#",
            'street' => '#^[^;]*$#',
            'zip' => '#^[0-9]{5}$#',
            'city' => '#^[^;]*$#',
            'country' => '#^FR$#',
            'phone' => '#^(0|33)[0-9]{9}$#'
        )
    );
    public static $plugin_features = array(
        'qualif' => false,
        'acquis' => true,
        'prodfaq' => true,
        'restrictmulti' => false,
        'shatwo' => true,

        'multi' => true,
        'choozeo' => false,
        'oney' => true,
        'ancv' => true,
        'sepa' => true,
        'sofort' => true,
        'paypal' => true,
        'fullcb' => true
    );

    public static function checkAddress($address, $type, $payment)
    {
        /* @var Payzen */
        $payzen = Module::getInstanceByName('payzen');

        $regex = self::$address_regex[$payment];
        $invalid_msg = $payzen->l('The field %1$s of your %2$s is invalid.', 'payzentools');
        $empty_msg = $payzen->l('The field %1$s of your %2$s is mandatory.', 'payzentools');

        $address_type = $type == 'billing' ? $payzen->l('billing address', 'payzentools') :
            $payzen->l('delivery address', 'payzentools');

        $errors = array();

        if (empty($address->lastname)) {
            $errors[] = sprintf($empty_msg, $payzen->l('Last name', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['name'], $address->lastname)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Last name', 'payzentools'), $address_type);
        }

        if (empty($address->firstname)) {
            $errors[] = sprintf($empty_msg, $payzen->l('First name', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['name'], $address->firstname)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('First name', 'payzentools'), $address_type);
        }

        if (!empty($address->phone) && !preg_match($regex['phone'], $address->phone)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Phone', 'payzentools'), $address_type);
        }

        if (!empty($address->phone_mobile) && !preg_match($regex['phone'], $address->phone_mobile)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Phone mobile', 'payzentools'), $address_type);
        }

        if (empty($address->address1)) {
            $errors[] = sprintf($empty_msg, $payzen->l('Address', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['street'], $address->address1)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Address', 'payzentools'), $address_type);
        }

        if (!empty($address->address2) && !preg_match($regex['street'], $address->address2)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Address2', 'payzentools'), $address_type);
        }

        if (empty($address->postcode)) {
            $errors[] = sprintf($empty_msg, $payzen->l('Zip code', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['zip'], $address->postcode)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Zip code', 'payzentools'), $address_type);
        }

        if (empty($address->city)) {
            $errors[] = sprintf($empty_msg, $payzen->l('City', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['city'], $address->city)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('City', 'payzentools'), $address_type);
        }

        $country = new Country((int)$address->id_country);
        if (empty($country->iso_code)) {
            $errors[] = sprintf($empty_msg, $payzen->l('Country', 'payzentools'), $address_type);
        } elseif (!preg_match($regex['country'], $country->iso_code)) {
            $errors[] = sprintf($invalid_msg, $payzen->l('Country', 'payzentools'), $address_type);
        }

        return $errors;
    }

    /**
     * Return the list of configuration parameters with their payzen names and default values.
     *
     * @return array
     */
    public static function getAdminParameters()
    {
        // NB : keys are 32 chars max
        $params = array(
                array('key' => 'PAYZEN_ENABLE_LOGS', 'default' => 'True', 'label' => 'Logs'),

                array('key' => 'PAYZEN_SITE_ID', 'name' => 'site_id', 'default' => '12345678', 'label' => 'Site ID'),
                array('key' => 'PAYZEN_KEY_TEST', 'name' => 'key_test', 'default' => '1111111111111111',
                    'label' => 'Certificate in test mode'),
                array('key' => 'PAYZEN_KEY_PROD', 'name' => 'key_prod', 'default' => '2222222222222222',
                    'label' => 'Certificate in production mode'),
                array('key' => 'PAYZEN_MODE', 'name' => 'ctx_mode', 'default' => 'TEST', 'label' => 'Mode'),
                array('key' => 'PAYZEN_SIGN_ALGO', 'name' => 'sign_algo', 'default' => 'SHA-256',
                    'label' => 'Signature algorithm'),
                array('key' => 'PAYZEN_PLATFORM_URL', 'name' => 'platform_url',
                    'default' => 'https://secure.payzen.eu/vads-payment/', 'label' => 'Payment page URL'),

                array('key' => 'PAYZEN_DEFAULT_LANGUAGE', 'default' => 'fr', 'label' => 'Default language'),
                array('key' => 'PAYZEN_AVAILABLE_LANGUAGES', 'name' => 'available_languages', 'default' => '',
                    'label' => 'Available languages'),
                array('key' => 'PAYZEN_DELAY', 'name' => 'capture_delay', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_VALIDATION_MODE', 'name' => 'validation_mode', 'default' => '',
                    'label' => 'Payment validation'),

                array('key' => 'PAYZEN_THEME_CONFIG', 'name' => 'theme_config', 'default' => '',
                    'label' => 'Theme configuration'),
                array('key' => 'PAYZEN_SHOP_NAME', 'name' => 'shop_name', 'default' => '', 'label' => 'Shop name'),
                array('key' => 'PAYZEN_SHOP_URL', 'name' => 'shop_url', 'default' => '', 'label' => 'Shop URL'),

                array('key' => 'PAYZEN_3DS_MIN_AMOUNT', 'default' => '', 'label' => 'Disable 3DS by customer group'),

                array('key' => 'PAYZEN_REDIRECT_ENABLED', 'name' => 'redirect_enabled', 'default' => 'False',
                    'label' => 'Automatic redirection'),
                array('key' => 'PAYZEN_REDIRECT_SUCCESS_T', 'name' => 'redirect_success_timeout', 'default' => '5',
                        'label' => 'Redirection timeout on success'),
                array('key' => 'PAYZEN_REDIRECT_SUCCESS_M', 'name' => 'redirect_success_message',
                    'default' => array(
                        'en' => 'Redirection to shop in few seconds...',
                        'fr' => 'Redirection vers la boutique dans quelques instants...',
                        'de' => 'Weiterleitung zum Shop in Kürze...'
                    ),
                    'label' => 'Redirection message on success'),
                array('key' => 'PAYZEN_REDIRECT_ERROR_T', 'name' => 'redirect_error_timeout', 'default' => '5',
                    'label' => 'Redirection timeout on failure'),
                array('key' => 'PAYZEN_REDIRECT_ERROR_M', 'name' => 'redirect_error_message',
                    'default' => array(
                        'en' => 'Redirection to shop in few seconds...',
                        'fr' => 'Redirection vers la boutique dans quelques instants...',
                        'de' => 'Weiterleitung zum Shop in Kürze...'
                    ),
                    'label' => 'Redirection message on failure'),
                array('key' => 'PAYZEN_RETURN_MODE', 'name' => 'return_mode', 'default' => 'GET',
                    'label' => 'Return mode'),
                array('key' => 'PAYZEN_FAILURE_MANAGEMENT', 'default' => self::ON_FAILURE_RETRY,
                    'label' => 'Payment failed management'),
                array('key' => 'PAYZEN_CART_MANAGEMENT', 'default' => self::EMPTY_CART, 'label' => 'Cart management'),

                array('key' => 'PAYZEN_COMMON_CATEGORY', 'default' => 'FOOD_AND_GROCERY',
                    'label' => 'Category mapping'),
                array('key' => 'PAYZEN_CATEGORY_MAPPING', 'default' => array(), 'label' => 'Category mapping'),
                array('key' => 'PAYZEN_SEND_SHIP_DATA', 'default' => 'False',
                    'label' => 'Always send advanced shipping data'),
                array('key' => 'PAYZEN_ONEY_SHIP_OPTIONS', 'default' => array(), 'label' => 'Shipping options'),

                array('key' => 'PAYZEN_STD_TITLE',
                    'default' => array(
                        'en' => 'Payment by credit card',
                        'fr' => 'Paiement par carte bancaire',
                        'de' => 'Zahlung mit EC-/Kreditkarte'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_STD_ENABLED', 'default' => 'True', 'label' => 'Activation'),
                array('key' => 'PAYZEN_STD_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_STD_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_STD_PAYMENT_CARDS', 'default' => '', 'label' => 'Card Types'),
                array('key' => 'PAYZEN_STD_PROPOSE_ONEY', 'default' => 'False', 'label' => 'Propose FacilyPay Oney'),
                array('key' => 'PAYZEN_STD_AMOUNTS', 'default' => array(), 'label' => 'One-time payment - Customer group amount restriction'),
                array('key' => 'PAYZEN_STD_CARD_DATA_MODE', 'default' => '1', 'label' => 'Card data entry mode'),

                array('key' => 'PAYZEN_MULTI_TITLE',
                    'default' => array(
                        'en' => 'Payment by credit card in installments',
                        'fr' => 'Paiement par carte bancaire en plusieurs fois',
                        'de' => 'Ratenzahlung mit EC-/Kreditkarte'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_MULTI_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_MULTI_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_MULTI_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_MULTI_PAYMENT_CARDS', 'default' => '', 'label' => 'Card Types'),
                array('key' => 'PAYZEN_MULTI_CARD_MODE', 'default' => '1', 'label' => 'Card selection mode'),
                array('key' => 'PAYZEN_MULTI_AMOUNTS', 'default' => array(), 'label' => 'Payment in installments - Customer group amount restriction'),
                array('key' => 'PAYZEN_MULTI_OPTIONS', 'default' => array(), 'label' => 'Payment in installments - Payment options'),

                array('key' => 'PAYZEN_ONEY_TITLE',
                    'default' => array(
                        'en' => 'Payment with FacilyPay Oney',
                        'fr' => 'Paiement avec FacilyPay Oney',
                        'de' => 'Zahlung via FacilyPay Oney'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_ONEY_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_ONEY_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_ONEY_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_ONEY_AMOUNTS', 'default' => array(), 'label' => 'FacilyPay Oney payment - Customer group amount restriction'),
                array('key' => 'PAYZEN_ONEY_ENABLE_OPTIONS', 'default' => 'False',
                    'label' => 'Enable options selection'),
                array('key' => 'PAYZEN_ONEY_OPTIONS', 'default' => array(), 'label' => 'FacilyPay Oney payment - Payment options'),

                array('key' => 'PAYZEN_FULLCB_TITLE',
                    'default' => array(
                        'en' => 'Payment with FullCB',
                        'fr' => 'Paiement avec FullCB',
                        'de' => 'Zahlung via FullCB'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_FULLCB_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_FULLCB_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_FULLCB_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_FULLCB_AMOUNTS',
                    'default' => array(
                        array('min_amount' => '100', 'max_amount' => '1500')
                    ),
                    'label' => 'FullCB payment - Customer group amount restriction'),
                array('key' => 'PAYZEN_FULLCB_ENABLE_OPTS', 'default' => 'False',
                    'label' => 'Enable options selection'),
                array('key' => 'PAYZEN_FULLCB_OPTIONS',
                    'default' => array(
                        'FULLCB3X' => array(
                            'label' => self::convertIsoArrayToIdArray(
                                array('en' => 'Payment in 4 times', 'fr' => 'Paiement en 3 fois', 'de' => 'Zahlung in 3 mal')
                            ),
                            'min_amount' => '',
                            'max_amount' => '',
                            'rate' => '1.4',
                            'cap' => '9',
                            'count' => '3'
                        ),
                        'FULLCB4X' => array(
                            'label' => self::convertIsoArrayToIdArray(
                                array('en' => 'Payment in 4 times', 'fr' => 'Paiement en 4 fois', 'de' => 'Zahlung in 4 mal')
                            ),
                            'min_amount' => '',
                            'max_amount' => '',
                            'rate' => '2.1',
                            'cap' => '12',
                            'count' => '4'
                        )
                    ),
                    'label' => 'FullCB payment - Payment options'),

                array('key' => 'PAYZEN_ANCV_TITLE',
                    'default' => array(
                        'en' => 'Payment with ANCV',
                        'fr' => 'Paiement avec ANCV',
                        'de' => 'Zahlung via ANCV'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_ANCV_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_ANCV_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_ANCV_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_ANCV_AMOUNTS', 'default' => array(), 'label' => 'ANCV payment - Customer group amount restriction'),

                array('key' => 'PAYZEN_SEPA_TITLE',
                    'default' => array(
                        'en' => 'Payment with SEPA',
                        'fr' => 'Paiement avec SEPA',
                        'de' => 'Zahlung via SEPA'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_SEPA_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_SEPA_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_SEPA_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_SEPA_AMOUNTS', 'default' => array(), 'label' => 'SEPA payment - Customer group amount restriction'),

                array('key' => 'PAYZEN_SOFORT_TITLE',
                    'default' => array(
                        'en' => 'Payment with SOFORT Banking',
                        'fr' => 'Paiement avec SOFORT Banking',
                        'de' => 'Zahlung via SOFORT Banking'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_SOFORT_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_SOFORT_AMOUNTS', 'default' => array(), 'label' => 'SOFORT Banking payment - Customer group amount restriction'),

                array('key' => 'PAYZEN_PAYPAL_TITLE',
                    'default' => array(
                        'en' => 'Payment with PayPal',
                        'fr' => 'Paiement avec PayPal',
                        'de' => 'Zahlung via  PayPal'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_PAYPAL_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_PAYPAL_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_PAYPAL_VALIDATION', 'default' => '-1', 'label' => 'Payment validation'),
                array('key' => 'PAYZEN_PAYPAL_AMOUNTS', 'default' => array(), 'label' => 'PayPal payment - Customer group amount restriction'),

                array('key' => 'PAYZEN_CHOOZEO_TITLE',
                    'default' => array(
                        'en' => 'Payment with Choozeo',
                        'fr' => 'Paiement Choozeo',
                        'de' => 'Zahlung via Choozeo'
                    ),
                    'label' => 'Method title'),
                array('key' => 'PAYZEN_CHOOZEO_ENABLED', 'default' => 'False', 'label' => 'Activation'),
                array('key' => 'PAYZEN_CHOOZEO_DELAY', 'default' => '', 'label' => 'Capture delay'),
                array('key' => 'PAYZEN_CHOOZEO_AMOUNTS',
                    'default' => array(
                        array('min_amount' => '135', 'max_amount' => '2000')
                    ),
                    'label' => 'Choozeo payment - Customer group amount restriction'),
                array('key' => 'PAYZEN_CHOOZEO_OPTIONS', 'default' => array(), 'label' => 'Choozeo payment - Payment options')
        );

        return $params;
    }

    public static function convertIsoArrayToIdArray($array)
    {
        if (!is_array($array) || empty($array)) {
            return array();
        }

        $converted = array();

        foreach (Language::getLanguages(false) as $language) {
            $key = key_exists($language['iso_code'], $array) ? $language['iso_code'] : 'en';

            $converted[$language['id_lang']] = $array[$key];
        }

        return $converted;
    }

    public static function checkOneyRequirements($cart)
    {
        // check order_id param
        if (!preg_match(self::ORDER_ID_REGEX, $cart->id)) {
            $msg = 'Order ID «%s» does not match FacilyPay Oney specifications.';
            $msg .= ' The regular expression for this field is «%s». Module is not displayed.';
            self::getLogger()->logWarning(sprintf($msg, $cart->id, self::ORDER_ID_REGEX));
            return false;
        }

        // check customer ID param
        if (!preg_match(self::CUST_ID_REGEX, $cart->id_customer)) {
            $msg = 'Customer ID «%s» does not match FacilyPay Oney specifications.';
            $msg .= ' The regular expression for this field is «%s». Module is not displayed.';
            self::getLogger()->logWarning(sprintf($msg, $cart->id_customer, self::CUST_ID_REGEX));
            return false;
        }

        // check products
        foreach ($cart->getProducts(true) as $product) {
            if (!preg_match(self::PRODUCT_REF_REGEX, $product['id_product'])) {
                // product id doesn't match FacilyPay Oney rules

                $msg = 'Product reference «%s» does not match FacilyPay Oney specifications.';
                $msg .= ' The regular expression for this field is «%s». Module is not displayed.';
                self::getLogger()->logWarning(sprintf($msg, $product['id_product'], self::PRODUCT_REF_REGEX));
                return false;
            }
        }

        return true;
    }

    public static function getSupportedCardTypes()
    {
        $cards = PayzenApi::getSupportedCardTypes();

        if (isset($cards['ONEY'])) {
            unset($cards['ONEY']);
        }

        if (isset($cards['ONEY_SANDBOX'])) {
            unset($cards['ONEY_SANDBOX']);
        }

        return $cards;
    }

    public static function getSupportedMultiCardTypes()
    {
        $multi_cards = array(
            'AMEX', 'CB', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'JCB', 'MASTERCARD',
            'PRV_BDP', 'PRV_BDT', 'PRV_OPT', 'PRV_SOC', 'VISA', 'VISA_ELECTRON'
        );

        $cards = array();
        foreach (PayzenApi::getSupportedCardTypes() as $code => $label) {
            if (in_array($code, $multi_cards)) {
                $cards[$code] = $label;
            }
        }

        return $cards;
    }

    /**
     * SoColissimo does not set delivery address ID into cart object.
     * So we get address data from SoColissimo database table.
     *
     * @param Cart $cart
     * @return Address|null
     */
    public static function getColissimoDeliveryAddress($cart)
    {
        // SoColissimo not available
        if (!Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return null;
        }

        // SoColissimo is not selected as shipping method
        if ($cart->id_carrier != Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return null;
        }

        // get address saved by SoColissimo
        $row = Db::getInstance()->getRow(
            'SELECT * FROM '._DB_PREFIX_.'socolissimo_delivery_info WHERE id_cart = \''.
            (int)$cart->id.'\' AND id_customer = \''.(int)$cart->id_customer.'\''
        );

        if (!$row) {
            return null;
        }

        $not_allowed_chars = array(' ', '.', '-', ',', ';', '+', '/', '\\', '+', '(', ')');
        $so_address = new Address();

        $ps_address = new Address((int)$cart->id_address_delivery);
        $id_country = Country::getByIso(pSQL($row['cecountry']));

        if (Tools::strtoupper($ps_address->lastname) != Tools::strtoupper($row['prname'])
            || $ps_address->id_country != $id_country
            || Tools::strtoupper($ps_address->firstname) != Tools::strtoupper($row['prfirstname'])
            || Tools::strtoupper($ps_address->address1) != Tools::strtoupper($row['pradress3'])
            || Tools::strtoupper($ps_address->address2) != Tools::strtoupper($row['pradress2'])
            || Tools::strtoupper($ps_address->postcode) != Tools::strtoupper($row['przipcode'])
            || Tools::strtoupper($ps_address->city) != Tools::strtoupper($row['prtown'])
            || str_replace($not_allowed_chars, '', $ps_address->phone_mobile) != $row['cephonenumber']) {
            $so_address->lastname = Tools::substr($row['cename'], 0, 32);
            $so_address->firstname = Tools::substr($row['cefirstname'], 0, 32);
            $so_address->postcode = $row['przipcode'];
            $so_address->city = $row['prtown'];
            $so_address->id_country = $id_country;
            $so_address->phone_mobile = $row['cephonenumber'];

            if (!in_array($row['delivery_mode'], array('DOM', 'RDV'))) {
                $so_address->company = Tools::substr($row['prfirstname'], 0, 31).' '.Tools::substr($row['prname'], 0, 32);
                $so_address->address1 = $row['pradress1'];
                $so_address->address2 = $row['pradress2'];
            } else {
                $so_address->address1 = $row['pradress3'];
                $so_address->address2 = isset($row['pradress2']) ? $row['pradress2'] : '';
                $so_address->other = '';
                $so_address->other .= isset($row['pradress1']) ? $row['pradress1'] : '';
                $so_address->other .= isset($row['pradress4']) ? ' '.$row['pradress4'] : '';
            }

            // return the SoColissimo updated address
            return $so_address;
        }

        // use initial customer address
        return null;
    }

    private static $logger;

    public static function getLogger()
    {
        if (!self::$logger) {
            self::$logger = new PayzenFileLogger(Configuration::get('PAYZEN_ENABLE_LOGS') != 'False');

            $logs_dir = _PS_ROOT_DIR_.'/app/logs/';
            if (!file_exists($logs_dir)) {
                $logs_dir = _PS_ROOT_DIR_.'/log/';
            }

            self::$logger->setFilename($logs_dir.date('Y_m').'_payzen.log');
        }

        return self::$logger;
    }

    public static function getTemplatePath($tpl)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return 'module:payzen/views/templates/front/'.$tpl;
        }

        return $tpl;
    }

    public static function getPageLink($relative_url)
    {
        $url = $relative_url;

        if (strpos($url, 'index.php?controller=') !== false && strpos($url, 'index.php/') == 0) {
            $url = Tools::substr($url, Tools::strlen('index.php?controller='));
            if (Configuration::get('PS_REWRITING_SETTINGS')) {
                $url = Tools::strReplaceFirst('&', '?', $url);
            }
        }

        $explode = explode('?', $url);

        // don't use ssl if url is home page
        // used when logout for example
        $use_ssl = !empty($url);
        $url = Context::getContext()->link->getPageLink($explode[0], $use_ssl);
        if (isset($explode[1])) {
            $url .= '?'.$explode[1];
        }

        return $url;
    }
}
