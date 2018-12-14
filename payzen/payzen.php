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

if (! defined('_CAN_LOAD_FILES_')) {
    exit;
}

if (! defined('_PS_ROOT_DIR_')) {
    include_once dirname(__FILE__).'/payzen_api.php';
} else {
    include_once _PS_ROOT_DIR_.'/modules/payzen/payzen_api.php';
}

class Payzen extends PaymentModule
{
    const ON_FAILURE_RETRY = 'retry';
    const ON_FAILURE_SAVE = 'save';

    public static $plugin_features = array(
        'qualif' => false,
        'prodfaq' => true,
        'shatwo' => true
    );

    /**
     * Admin form
     * @var string
     */
    private $_html = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = 'payzen';
        $this->tab = 'payments_gateways';
        $this->version = '1.4.7';
        $this->currencies = false;

        parent::__construct();

        $this->displayName = 'PayZen';
        $this->description = sprintf($this->l('Accept payments by credit cards with %s.'), ' PayZen');
    }

    /**
     * Return the list of configuration names and their default value
     * @return array[string]string
     */
    private function _getAdminParameters()
    {
        // names are 32 chars max

        return array(
            'PAYZEN_SITE_ID' => '12345678',
            'PAYZEN_KEY_TEST' => '1111111111111111',
            'PAYZEN_KEY_PROD' => '2222222222222222',
            'PAYZEN_CTX_MODE' => 'TEST',
            'PAYZEN_SIGN_ALGO' => 'SHA-256',
            'PAYZEN_PLATFORM_URL' => 'https://secure.payzen.eu/vads-payment/',
            'PAYZEN_LANGAGE' => 'fr',
            'PAYZEN_AVAILABLE_LANGUAGES' => '',
            'PAYZEN_DELAY' => '',
            'PAYZEN_VALIDATION_MODE' => '',
            'PAYZEN_PAYMENT_CARDS' => '',
            'PAYZEN_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
            'PAYZEN_SHOP_URL' => $this->_getShopBaseUrl(),
            'PAYZEN_3DS_MIN_AMOUNT' => '',
            'PAYZEN_AMOUNT_MIN' => '',
            'PAYZEN_AMOUNT_MAX' => '',
            'PAYZEN_REDIRECT_ENABLED' => 'False',
            'PAYZEN_REDIRECT_SUCCESS_T' => 5,
            'PAYZEN_REDIRECT_SUCCESS_M' => $this->l('Redirection to shop in a few seconds...'),
            'PAYZEN_REDIRECT_ERROR_T' => 5,
            'PAYZEN_REDIRECT_ERROR_M' => $this->l('Redirection to shop in a few seconds...'),
            'PAYZEN_RETURN_MODE' => 'GET',
            'PAYZEN_FAILURE_MANAGEMENT' => self::ON_FAILURE_RETRY
        );
    }

    private function _prestashopToPayzenNames()
    {
        return array(
            'PAYZEN_SITE_ID' => 'site_id',
            'PAYZEN_KEY_TEST' => 'key_test',
            'PAYZEN_KEY_PROD' => 'key_prod',
            'PAYZEN_CTX_MODE' => 'ctx_mode',
            'PAYZEN_SIGN_ALGO' => 'sign_algo',
            'PAYZEN_PLATFORM_URL' => 'platform_url',
            'PAYZEN_AVAILABLE_LANGUAGES' => 'available_languages',
            'PAYZEN_DELAY' => 'capture_delay',
            'PAYZEN_VALIDATION_MODE' => 'validation_mode',
            'PAYZEN_PAYMENT_CARDS' => 'payment_cards',
            'PAYZEN_SHOP_NAME' => 'shop_name',
            'PAYZEN_SHOP_URL' => 'shop_url',
            'PAYZEN_URL_RETURN' => 'url_return',
            'PAYZEN_REDIRECT_ENABLED' => 'redirect_enabled',
            'PAYZEN_REDIRECT_SUCCESS_T' => 'redirect_success_timeout',
            'PAYZEN_REDIRECT_SUCCESS_M' => 'redirect_success_message',
            'PAYZEN_REDIRECT_ERROR_T' => 'redirect_error_timeout',
            'PAYZEN_REDIRECT_ERROR_M' => 'redirect_error_message',
            'PAYZEN_RETURN_MODE' => 'return_mode',
        );
    }

    private function _getShopBaseUrl()
    {
        $protocol = _PS_SSL_ENABLED_ ? 'https://' : 'http://';
        return $protocol.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__;
    }

    /**
     * Returns a new PayzenRequest object loaded with the module configuration
     * @return PayzenRequest
     */
    public function getLoadedApi()
    {
        $request = new PayzenRequest();
        $request->set('version', 'V2');
        $request->set('payment_config', 'SINGLE');
        $request->set('contrib', 'PrestaShop1.4_1.4.7/'._PS_VERSION_.'/'.PHP_VERSION);

        foreach ($this->_prestashopToPayzenNames() as $ps_name => $payzen_name) {
            $request->set($payzen_name, Configuration::get($ps_name));
        }

        return $request;
    }

    /**
     * (non-PHPdoc)
     * @see PaymentModuleCore::install()
     */
    public function install()
    {
        if (! parent::install() || ! $this->registerHook('payment') || ! $this->registerHook('orderConfirmation')) {
            return false;
        }

        foreach ($this->_getAdminParameters() as $name => $default) {
            if (! Configuration::updateValue($name, $default)) {
                return false;
            }
        }

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see PaymentModuleCore::uninstall()
     */
    public function uninstall()
    {
        foreach ($this->_getAdminParameters() as $name => $default) {
            if (! Configuration::deleteByName($name)) {
                return false;
            }
        }

        return parent::uninstall();
    }

    /**
     * Admin form management
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('payzen_submit_btn')) {
            $this->postProcess();
        }

        $this->_adminForm();
        return $this->_html;
    }

    /**
     * Validate and save admin parameters from admin form
     */
    public function postProcess()
    {
        $vars = $this->_getAdminParameters();
        $payzen_names = $this->_prestashopToPayzenNames();
        $request = new PayzenRequest();

        // load and validate from request
        foreach ($vars as $field_name => $default) {
            $value = Tools::getValue($field_name, null);

            // for ctx mode
            if (! $value && ($field_name === 'PAYZEN_CTX_MODE')) {
                $value = $default;
            }

            $value = is_array($value) ? implode(';', $value) : $value;

            // validate with PayzenApi
            if (key_exists($field_name, $payzen_names)) {
                if (! $request->set($payzen_names[$field_name], $value)) {
                    $this->_errors[] = sprintf($this->l('Invalid value « %1s » for field « %2s ».'), $value, $field_name);
                    continue;
                }
            }

            // valid field : try to save into DB
            if (! Configuration::updateValue($field_name, $value)) {
                $this->_errors[] = sprintf($this->l('Problem occurred while saving field « %s ».'), $field_name);
            }
        }

        // if no error, display OK
        if (! is_array($this->_errors) || empty($this->_errors)) {
            $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" />'.$this->l('Settings updated.').'</div>';
        } else {
            // display errors
            $this->_html .= $this->displayError(implode('<br/>', $this->_errors));
        }
    }

    /**
     * Builds the html code for the admin form
     */
    public function _adminForm()
    {
        $submit_button = '<div class="clear"><input type="submit" class="button" name="payzen_submit_btn" value="'.$this->l('Save').'" /></div>';

        // form beginning
        $this->_html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        $this->_html .= $submit_button.'<br/>';

        // get documentation links
        $filenames = glob(_PS_MODULE_DIR_.'payzen/installation_doc/PayZen_PrestaShop_1.4_v1.4.7*.pdf');

        $doc_languages = array(
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español'
            // complete when other languages are managed
        );

        $doc_links = '';

        foreach ($filenames as $filename) {
            $base_filename = basename($filename, '.pdf');
            $lang = Tools::substr($base_filename, -2); // extract language code
            $doc_links .= '<a style="margin-left: 10px; font-weight: bold; text-transform: uppercase;" href="../modules/payzen/installation_doc/'.$base_filename.'.pdf" target="_blank">'.$doc_languages[$lang].'</a>';
        }

        if ($doc_links) {
            $doc_links = '<span style="color: red; font-weight: bold; text-transform: uppercase;">'.
                $this->l('Click to view the module configuration documentation :').'</span>'.$doc_links;
        }

        $this->_html .= '
            <fieldset>
                <legend><img src="../modules/'.$this->name.'/logo.gif" alt="logo"/>'.$this->displayName.'</legend>
                '.$this->l('Developped by').' : <b><a href="http://www.lyra-network.com/" target="_blank">Lyra Network</a></b><br/>
                '.$this->l('Contact email').' : <b><a href="mailto:support@payzen.eu">support@payzen.eu</a></b><br/>
                '.$this->l('Module version').' : <b>1.4.7</b><br/>
                '.$this->l('Gateway version').' : <b>V2</b><br/>
                '.$doc_links.'
            </fieldset>
            <div class="clear">&nbsp;</div>';

        /*
         * General configuration
         */
        $this->_html .= '<fieldset><legend>'.$this->l('PAYMENT GATEWAY ACCESS').'</legend>';

        $this->_adminFormTextinput('PAYZEN_SITE_ID', $this->l('Shop ID'),
            $this->l('The identifier provided by the gateway.'));

        if (! self::$plugin_features['qualif']) {
            $this->_adminFormTextinput('PAYZEN_KEY_TEST', $this->l('Certificate in test mode'),
                $this->l('Certificate provided by the gateway for test mode (available in your store Back Office).'));
        }

        $this->_adminFormTextinput('PAYZEN_KEY_PROD', $this->l('Certificate in production mode'),
            $this->l('Certificate provided by the gateway (available in your store Back Office after enabling production mode).'));

        // context mode selection
        $options = array(
            'TEST' => $this->l('TEST'),
            'PRODUCTION' => $this->l('PRODUCTION')
        );
        $selected = Configuration::get('PAYZEN_CTX_MODE');
        $this->_adminFormSelect($options, $selected, 'PAYZEN_CTX_MODE', $this->l('Mode'),
            $this->l('The context mode of this module.'), self::$plugin_features['qualif'] ? 'disabled="disabled"' : '');

        $options = array(
            'SHA-1' => 'SHA-1',
            'SHA-256' => 'HMAC-SHA-256'
        );
        $selected = Configuration::get('PAYZEN_SIGN_ALGO');
        $desc_detail = ! self::$plugin_features['shatwo'] ? '<br /><b>'.$this->l('The HMAC-SHA-256 algorithm should not be activated if it is not yet available in the gateway Back Office, the feature will be available soon.').'</b>' : '';
        $this->_adminFormSelect($options, $selected, 'PAYZEN_SIGN_ALGO', $this->l('Signature algorithm'),
            $this->l('Algorithm used to compute the payment form signature. Selected algorithm must be the same as one configured in the gateway Back Office.').$desc_detail);

        $this->_html .= '<label>'.$this->l('Instant Payment Notification URL').'</label>
            <div class="margin-form">
                <p>
                    ' . $this->_getShopBaseUrl() . 'modules/'.$this->name.'/validation.php
                </p>
                <p style="color: red;">
                    <img style="vertical-align: middle;" src="'._MODULE_DIR_.'payzenmulti/warn.png">'
                    		.$this->l('URL to copy into your gateway Back Office > Settings > Notification rules.').'
                </p>
            </div>';

        $this->_adminFormTextinput('PAYZEN_PLATFORM_URL', $this->l('Payment page URL'),
            $this->l('Link to the payment page.'), 'size="65"');

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

        /*
         * payment settings
         */
        $this->_html .= '<fieldset><legend>'.$this->l('PAYMENT PAGE').'</legend>';

        // supported languages
        $options = array();
        foreach (PayzenApi::getSupportedLanguages() as $key => $value) {
            $options[$key] = $this->l($value);
        }
        asort($options);

        // default language
        $selected = key_exists(Configuration::get('PAYZEN_LANGAGE'), $options) ?
            Configuration::get('PAYZEN_LANGAGE') : 'fr';
        $this->_adminFormSelect($options, $selected, 'PAYZEN_LANGAGE', $this->l('Default language'),
            $this->l('Default language on the payment page.'));

        // available languages
        $selected = explode(';', Configuration::get('PAYZEN_AVAILABLE_LANGUAGES'));
        $this->_adminFormSelect($options, $selected, 'PAYZEN_AVAILABLE_LANGUAGES[]', $this->l('Available languages'),
            $this->l('Languages available on the payment page. If you do not select any, all the supported languages will be available.'),
            'multiple="multiple" size="8"');

        // delay
        $this->_adminFormTextinput('PAYZEN_DELAY', $this->l('Capture delay'),
            $this->l('The number of days before the bank capture (adjustable in your gateway Back Office).'));

        // validation mode
        $options = array(
            '' => $this->l('Back Office configuration'),
            '0' => $this->l('Automatic'),
            '1' => $this->l('Manual')
        );
        $selected = key_exists(Configuration::get('PAYZEN_VALIDATION_MODE'), $options) ?
            Configuration::get('PAYZEN_VALIDATION_MODE') : '';
        $this->_adminFormSelect($options, $selected, 'PAYZEN_VALIDATION_MODE',  $this->l('Validation mode'),
            $this->l('If manual is selected, you will have to confirm payments manually in your gateway Back Office.'));

        // cards
        $payment_cards = Configuration::get('PAYZEN_PAYMENT_CARDS');
        $selected = ! $payment_cards ? '' : explode(';', $payment_cards);
        $this->_adminFormSelect(PayzenApi::getSupportedCardTypes(), $selected, 'PAYZEN_PAYMENT_CARDS[]',
            $this->l('Card Types'),
            $this->l('The card type(s) that can be used for the payment. Select none to use gateway configuration.'),
            'multiple="multiple" size="7"');

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

        /*
         * payment page customize settings
         */
        $this->_html .= '<fieldset><legend>'.$this->l('PAYMENT PAGE CUSTOMIZE').'</legend>';

        // shop name
        $this->_adminFormTextinput('PAYZEN_SHOP_NAME', $this->l('Shop name'),
            $this->l('Shop name to display on the payment page. Leave blank to use gateway configuration.'));

        // shop url
        $this->_adminFormTextinput('PAYZEN_SHOP_URL', $this->l('Shop URL'),
            $this->l('Shop URL to display on the payment page. Leave blank to use gateway configuration.'));

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

        /*
         * 3DS by amount settings
         */
        $this->_html .= '<fieldset><legend>'.$this->l('SELECTIVE 3DS').'</legend>';

        // min amount to activate three ds
        $this->_adminFormTextinput('PAYZEN_3DS_MIN_AMOUNT', $this->l('Disable 3DS'),
            $this->l('Amount below which 3DS will be disabled. Needs subscription to selective 3DS option. For more information, refer to the module documentation.'));

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

        /*
         * disable method for specific amounts
         */
        $this->_html .= '<fieldset><legend>'.$this->l('AMOUNT RESTRICTION').'</legend>';

        $this->_adminFormTextinput('PAYZEN_AMOUNT_MIN', $this->l('Minimum amount'),
            $this->l('Minimum amount for wich this payment method is available.'));

        $this->_adminFormTextinput('PAYZEN_AMOUNT_MAX', $this->l('Maximum amount'),
            $this->l('Maximum amount for wich this payment method is available.'));

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

        /*
         * return to shop settings
         */
        $this->_html .= '<fieldset><legend>'.$this->l('RETURN TO SHOP').'</legend>';

        // automatic redirection
        $options = array(
            'False' => $this->l('Disabled'),
            'True' => $this->l('Enabled')
        );
        $selected = key_exists(Configuration::get('PAYZEN_REDIRECT_ENABLED'), $options) ?
            Configuration::get('PAYZEN_REDIRECT_ENABLED') : 'False';
        $this->_adminFormSelect($options, $selected, 'PAYZEN_REDIRECT_ENABLED', $this->l('Automatic redirection'),
            $this->l('If enabled, the buyer is automatically redirected to your site at the end of the payment.'));

        $this->_adminFormTextinput('PAYZEN_REDIRECT_SUCCESS_T', $this->l('Redirection timeout on success'),
            $this->l('Time in seconds (0-300) before the buyer is automatically redirected to your website after a successful payment.'));

        $this->_adminFormTextinput('PAYZEN_REDIRECT_SUCCESS_M', $this->l('Redirection message on success'),
            $this->l('Message displayed on the payment page prior to redirection after a successful payment.'),
            'size="65"');

        $this->_adminFormTextinput('PAYZEN_REDIRECT_ERROR_T', $this->l('Redirection timeout on failure'),
            $this->l('Time in seconds (0-300) before the buyer is automatically redirected to your website after a declined payment.'));

        $this->_adminFormTextinput('PAYZEN_REDIRECT_ERROR_M', $this->l('Redirection message on failure'),
            $this->l('Message displayed on the payment page prior to redirection after a declined payment.'),
            'size="65"');

        // return mode
        $options = array(
            'GET' => 'GET',
            'POST' => 'POST'
        );
        $selected = key_exists(Configuration::get('PAYZEN_RETURN_MODE'), $options) ?
            Configuration::get('PAYZEN_RETURN_MODE') : 'GET';
        $this->_adminFormSelect($options, $selected, 'PAYZEN_RETURN_MODE',  $this->l('Return mode'),
            $this->l('Method that will be used for transmitting the payment result from the payment page to your shop.'));

        // payment failed management
        $options = array(
            self::ON_FAILURE_RETRY => $this->l('Go back to checkout'),
            self::ON_FAILURE_SAVE => $this->l('Save order and go back to order history')
        );
        $selected = key_exists(Configuration::get('PAYZEN_FAILURE_MANAGEMENT'), $options) ?
            Configuration::get('PAYZEN_FAILURE_MANAGEMENT') : self::ON_FAILURE_RETRY;
        $this->_adminFormSelect($options, $selected, 'PAYZEN_FAILURE_MANAGEMENT',
                        $this->l('Payment failed management'),
                        $this->l('How to manage the buyer return to shop when the payment is failed.'));

        $this->_html .= '</fieldset><div class="clear">&nbsp;</div>';
        $this->_html .= $submit_button;
        $this->_html .= '</form>';
    }

    /**
     * Shortcut function for creating a html text input
     * @param string $name
     * @param string $label
     * @param string $description
     * @param string $extra_attributes
     */
    function _adminFormTextinput($name, $label, $description = null, $extra_attributes = '')
    {
        $value = Configuration::get($name);
        $this->_html .= "\n";
        $this->_html .= '<label for="'.$name.'">'.$label.'</label>';
        $this->_html .= '<div class="margin-form">';
        $this->_html .= '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$value.'" '.$extra_attributes.'/>';
        $this->_html .= '<p>'.$description.'</p>';
        $this->_html .= '</div>';
    }

    /**
     * Shortcut function for creating a html select
     * @param array[string]string $options
     * @param mixed $selected a single string value or an array
     * @param string $name
     * @param string $label
     * @param string $description
     * @param string $extra_attributes
     */
    function _adminFormSelect($options, $selected, $name, $label, $description, $extra_attributes = null)
    {
        $this->_html .= "\n";
        $this->_html .= '<label for="'.$name.'">'.$label.'</label>';
        $this->_html .= '<div class="margin-form">';
        $this->_html .= '<select name="'.$name.'" '.$extra_attributes.'>';

        foreach ($options as $value => $label) {
            $this->_html .= '<option value="'.$value.'"';
            $is_selected = is_array($selected) ? in_array($value, $selected) : ((string) $value == (string) $selected);
            $this->_html .= $is_selected ? ' selected="selected"' : '';
            $this->_html .= '>'.$label.'</option>';
        }

        $this->_html .= '</select><p>'.$description.'</p></div>';
    }

    /**
     * Payment function, redirects the client to payment page
     * @param array $params
     * @return void|Ambigous <string, void, boolean, mixed, unknown>
     */
    public function hookPayment($params)
    {
        /* @var $smarty Smarty */
        /* @var $cookie Cookie */
        global $smarty, $cookie;

        /* @var $cart Cart */
        $cart = new Cart((int)$cookie->id_cart);

        // amount restrictions
        $min = Configuration::get('PAYZEN_AMOUNT_MIN');
        $max = Configuration::get('PAYZEN_AMOUNT_MAX');
        if (($min && ($cart->getOrderTotal() < $min)) || ($max && ($cart->getOrderTotal() > $max))) {
            return;
        }

        // currency support
        $currency_cart = new Currency(intval($cart->id_currency));
        $currency_code = PayzenApi::getCurrencyNumCode($currency_cart->iso_code);
        if (! $currency_code) {
            // do not propose payment module
            return;
        }

        $smarty->assign('payzen_link_action', 'modules/payzen/redirect.php');
        return $this->display(__FILE__, 'order_payzen.tpl');
    }

    /**
     * Manage payement gateway response
     * @param array $params
     */
    public function hookOrderConfirmation($params)
    {
        global $smarty;
        if (! $this->active || $params['objOrder']->module != $this->name) {
            return;
        }

        $error_msg = Tools::getValue('error', 'no') === 'yes';

        $array = array(
            'check_url_warn' => Tools::getValue('check_url_warn', 'no') === 'yes',
            'prod_info' => self::$plugin_features['prodfaq'] && (Tools::getValue('prod_info', 'no') === 'yes'),
            'maintenance_mode' => ! Configuration::get('PS_SHOP_ENABLE'),
            'error_msg' => $error_msg
        );

        if ($error_msg === false) {
            $array['total_to_pay'] = Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false);
            $array['id_order'] = $params['objOrder']->id;
        }

        $smarty->assign($array);

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     * Manage a failed payment notification (see validation.php). Behaviour depends on PAYZEN_FAILURE_MANAGEMENT config.
     *
     * @param PayzenResponse $payzen_resp
     * @param int $id_cart
     * @param Customer $customer
     * @param boolean $from_server
     * @param Order $order
     */
    public function managePaymentFailure(PayzenResponse $payzen_resp, $id_cart, $from_server, $order)
    {
        if (Configuration::get('PAYZEN_FAILURE_MANAGEMENT') == self::ON_FAILURE_SAVE) {
            // behaviour 1 : save order so that it can be seen from admin panel
            $order_state = $payzen_resp->isCancelledPayment() ? _PS_OS_CANCELED_ : _PS_OS_ERROR_;
            if ($order->getCurrentState() != _PS_OS_CANCELED_ && $order->getCurrentState() != _PS_OS_ERROR_) {
                // order has not been save yet, let's do it
                $this->validate($id_cart, $order_state, $payzen_resp);
            }

            // confirm for gateway / redirect client to history
            if ($from_server) {
                die($payzen_resp->getOutputForPlatform('payment_ko'));
            } else {
                Tools::redirectLink(__PS_BASE_URI__.'history.php');
            }
        } else {
            // behaviour 2 : just get back to checkout process
            if ($from_server) {
                die($payzen_resp->getOutputForPlatform('payment_ko'));
            } else {
                Tools::redirectLink(__PS_BASE_URI__.'order.php?step=3');
            }
        }
    }

    function getColissimoShippingAddress($cart, $psAddress, $idCustomer)
    {
        // So Colissimo not installed
        if (! Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return false;
        }

        // So Colissimo is not selected as shipping method
        if ($cart->id_carrier != Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
            return false;
        }

        // get address saved by So Colissimo
        $return = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'socolissimo_delivery_info WHERE id_cart =\''.(int)($cart->id).'\' AND id_customer =\''.(int)($idCustomer).'\'');
        if (! empty($return)) {
            return false;
        }

        $newAddress = new Address();

        if (strtoupper($psAddress->lastname) != strtoupper($return['prname'])
            || strtoupper($psAddress->firstname) != strtoupper($return['prfirstname'])
            || strtoupper($psAddress->address1) != strtoupper($return['pradress3'])
            || strtoupper($psAddress->address2) != strtoupper($return['pradress2'])
            || strtoupper($psAddress->postcode) != strtoupper($return['przipcode'])
            || strtoupper($psAddress->city) != strtoupper($return['prtown'])
            || str_replace(array(' ', '.', '-', ',', ';', '+', '/', '\\', '+', '(', ')'),'', $psAddress->phone_mobile) != $return['cephonenumber']) {

            // address is modified in So Colissimo page : use it as shipping address
            $newAddress->lastname = substr($return['prname'], 0, 32);
            $newAddress->firstname = substr($return['prfirstname'], 0, 32);
            $newAddress->postcode = $return['przipcode'];
            $newAddress->city = $return['prtown'];
            $newAddress->id_country = Country::getIdByName(null, 'france');

            if (! in_array($return['delivery_mode'], array('DOM', 'RDV'))) {
                $newAddress->address1 = $return['pradress1'];
                $newAddress->address1 .= isset($return['pradress2']) ? ' '.$return['pradress2'] : '';
                $newAddress->address1 .= isset($return['pradress3']) ? ' '.$return['pradress3'] : '';
                $newAddress->address1 .= isset($return['pradress4']) ? ' '.$return['pradress4'] : '';
            } else {
                $newAddress->address1 = $return['pradress3'];
                $newAddress->address2 = isset($return['pradress4']) ? $return['pradress4'] : '';
                $newAddress->other = isset($return['pradress1']) ? $return['pradress1'] : '';
                $newAddress->other .= isset($return['pradress2']) ? ' '.$return['pradress2'] : '';
            }

            // return the So Colissimo updated
            return $newAddress;
        } else {
            // use initial address
            return false;
        }
    }

    /**
    * Generate form to post to the payment gateway.
    */
    public function prepareForm()
    {
        /* @var $smarty Smarty */
        /* @var $cookie Cookie */
        global $smarty, $cookie;

        /* @var $cust Customer */
        /* @var $cart Cart */
        $cust = new Customer(intval($cookie->id_customer));
        $cart = new Cart((int)($cookie->id_cart));

        if (empty($cart->id_customer) || empty($cart->id_address_invoice) || $cart->id_customer != $cookie->id_customer) {
            sleep(300);
            die();
        }

        if ($cart->nbProducts() <= 0) {
            $smarty->assign('payzen_empty_cart', true);
            return;
        }

        /* @var $billingCountry Address */
        $billingAddress = new Address($cart->id_address_invoice);
        $billingCountry = new Country($billingAddress->id_country);

        /* @var $deliveryAddress Address */
        $deliveryAddress = new Address($cart->id_address_delivery);

        $colissimoAddress = $this->getColissimoShippingAddress($cart, $deliveryAddress, $cust->id);
        if (is_a($colissimoAddress, 'Address')) {
            $deliveryAddress = $colissimoAddress;
        }

        $deliveryCountry = new Country($deliveryAddress->id_country);

        /* @var $request PayzenRequest */
        $request = $this->getLoadedApi();

        /* detect default language */
        $language = strtolower(Language::getIsoById(intval($cookie->id_lang)));
        if (! PayzenApi::isSupportedLanguage($language)) {
            $language = Configuration::get('PAYZEN_LANGAGE');
        }

        $request->set('language', $language);

        /* detect store currency */
        $cart_currency = new Currency((int)$cart->id_currency);
        $currency = PayzenApi::findCurrencyByAlphaCode($cart_currency->iso_code);

        /* Amount */
        $amount = $cart->getOrderTotal();

        $request->set('amount', $currency->convertAmountToInteger($amount));
        $request->set('currency', $currency->getNum());

        $request->set('cust_email', $cust->email);
        $request->set('cust_id', $cust->id);

        $request->set('cust_first_name', $cust->firstname);
        $request->set('cust_last_name', $cust->lastname);
        $request->set('cust_legal_name', $billingAddress->company);

        $request->set('cust_address', $billingAddress->address1.' '.$billingAddress->address2);
        $request->set('cust_zip', $billingAddress->postcode);
        $request->set('cust_city', $billingAddress->city);
        $request->set('cust_phone', $billingAddress->phone);
        $request->set('cust_country', $billingCountry->iso_code);
        if ($billingAddress->id_state) {
            $state = new State((int) ($billingAddress->id_state));
            $request->set('cust_state', $state->iso_code);
        }

        $request->set('ship_to_name', $cust->lastname.' '.$cust->firstname);
        $request->set('ship_to_street', $deliveryAddress->address1);
        $request->set('ship_to_street2', $deliveryAddress->address2);
        $request->set('ship_to_zip', $deliveryAddress->postcode);
        $request->set('ship_to_city', $deliveryAddress->city);
        $request->set('ship_to_phone_num', $deliveryAddress->phone);
        $request->set('ship_to_country', $deliveryCountry->iso_code);
        if ($deliveryAddress->id_state) {
            $state = new State((int) ($deliveryAddress->id_state));
            $request->set('ship_to_state', $state->iso_code);
        }

        // disable 3DS ?
        $threeds_mpi = null;
        $threeds_min_amount = Configuration::get('PAYZEN_3DS_MIN_AMOUNT');
        if ($threeds_min_amount && ($amount < $threeds_min_amount)) {
            $threeds_mpi = '2';
        }

        $request->set('threeds_mpi', $threeds_mpi);

        $request->set('order_id', $cart->id);
        $request->set('order_info', 'language_id='.$cookie->id_lang);

        $request->set('url_return', $this->_getShopBaseUrl().'modules/'.$this->name.'/validation.php');

        // prepare data for PayZen payment form
        $params = array();

        $fields = $request->getRequestFields();
        foreach ($fields as $field) {
            if ($field->isFilled()) {
                $params[$field->getName()] = htmlspecialchars($field->getValue(), ENT_QUOTES, 'UTF-8');
            }
        }

        $smarty->assign('payzen_params', $params);
        $smarty->assign('payzen_url', $request->get('platform_url'));
        $smarty->assign('payzen_empty_cart', false);
    }

    /**
    * Save order and transaction info.
    */
    public function validate($id_cart, $order_status, $payzen_resp)
    {
        // retrieve customer from cust_id
        $customer = new Customer($payzen_resp->get('cust_id'));

        // ps id_currency from currency iso num code
        $currencyId = Db::getInstance()->getValue('SELECT id_currency FROM '._DB_PREFIX_.'currency WHERE iso_code_num = '.(int)$payzen_resp->get('currency'));

        $msg_3ds = "\n".$this->l('3DS authentication : ');
        if ($payzen_resp->get('threeds_status') == 'Y') {
            $msg_3ds .= $this->l('YES');
            $msg_3ds .= "\n".$this->l('3DS Certificate : ').$payzen_resp->get('threeds_cavv');
        } else {
            $msg_3ds .= $this->l('NO');
        }

        // call payment module validateOrder
        $this->validateOrder(
            $id_cart,
            $order_status,
            $payzen_resp->getFloatAmount(),
            $this->displayName,
            $payzen_resp->getLogMessage().$msg_3ds,
            array(), // $extraVars
            $currencyId, // $currency_special
            false, // $dont_touch_amount
            $customer->secure_key
        );

        // reload order
        $id_order = intval(Order::getOrderByCartId($id_cart));
        $order = new Order($id_order);

        // check if table payment_cc exists (1.4.x prestashp versions)
        Db::getInstance()->Execute("SHOW TABLES LIKE '"._DB_PREFIX_."payment_cc'");
        $pcc_exists = Db::getInstance()->NumRows() > 0;

        // save transaction info
        if (class_exists('PaymentCC') && $pcc_exists) {
            $pcc = new PaymentCC();

            $pcc->id_order = (int)$order->id;
            $pcc->id_currency = $currencyId;
            $pcc->amount = $payzen_resp->getFloatAmount();
            $pcc->transaction_id = $payzen_resp->get('trans_id');
            $pcc->card_number = $payzen_resp->get('card_number');
            $pcc->card_brand = $payzen_resp->get('card_brand');
            $pcc->card_expiration = str_pad($payzen_resp->get('expiry_month'), 2, '0', STR_PAD_LEFT)
                .'/'.$payzen_resp->get('expiry_year');
            $pcc->card_holder = NULL;
            $pcc->add();
        } elseif (class_exists('OrderPayment')) {
            $payments = $order->getOrderPaymentCollection();

            if ($payments->count() > 0) {
                $pcc = $payments[0];

                $pcc->transaction_id = $payzen_resp->get('trans_id');
                $pcc->card_number = $payzen_resp->get('card_number');
                $pcc->card_brand = $payzen_resp->get('card_brand');
                $pcc->card_expiration = str_pad($payzen_resp->get('expiry_month'), 2, '0', STR_PAD_LEFT)
                    .'/'.$payzen_resp->get('expiry_year');
                $pcc->card_holder = NULL;
                $pcc->update();
            }
        }

        return $order;
    }
}
