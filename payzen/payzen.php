<?php
/**
 * PayZen V2-Payment Module version 1.10.2 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'payzen/classes/PayzenApi.php';
require_once _PS_MODULE_DIR_.'payzen/classes/PayzenFileLogger.php';
require_once _PS_MODULE_DIR_.'payzen/classes/PayzenTools.php';

require_once _PS_MODULE_DIR_.'payzen/classes/payment/AbstractPayzenPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenAncvPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenChoozeoPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenFullcbPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenMultiPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenOneyPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenPaypalPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenSepaPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenSofortPayment.php';
require_once _PS_MODULE_DIR_.'payzen/classes/payment/PayzenStandardPayment.php';

/**
 * PayZen payment module main class.
 */
class Payzen extends PaymentModule
{
    // regular expressions
    const DELIVERY_COMPANY_REGEX = '#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /\'-]{1,127}$#ui';

    private $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'payzen';
        $this->tab = 'payments_gateways';
        $this->version = '1.10.2';
        $this->author = 'Lyra Network';
        $this->controllers = array('redirect', 'submit', 'iframe');
        $this->module_key = 'f3e5d07f72a9d27a5a09196d54b9648e';
        $this->is_eu_compatible = 1;

        $this->logger = PayzenTools::getLogger();

        // check version compatibility
        $minor = Tools::substr(_PS_VERSION_, strrpos(_PS_VERSION_, '.') + 1);
        $replace = (int)$minor + 1;
        $start = Tools::strlen(_PS_VERSION_) - Tools::strlen($minor);
        $version = substr_replace(_PS_VERSION_, (string)$replace, $start);
        $this->ps_versions_compliancy = array('min' => '1.5.0.0', 'max' => $version);

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $order_id = (int)Tools::getValue('id_order', 0);
        $order = new Order($order_id);
        if (($order->module == $this->name) && ($this->context->controller instanceof OrderConfirmationController)) {
            // patch to use different display name according to the used payment sub-module
            $this->displayName = $order->payment;
        } else {
            $this->displayName = 'PayZen';
        }

        $this->description = $this->l('Accept payments by credit cards');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your module details ?');
    }

    /**
     * @see PaymentModuleCore::install()
     */
    public function install()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            // incompatible version of PrestaShop
            return false;
        }

        // install hooks
        if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('paymentReturn')
            || !$this->registerHook('adminOrder') || !$this->registerHook('actionOrderSlipAdd')) {
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            if (!$this->registerHook('payment') || !$this->registerHook('displayPaymentEU')) {
                return false;
            }
        } else {
            if (!$this->registerHook('paymentOptions')) {
                return false;
            }
        }

        // set default values
        foreach (PayzenTools::getAdminParameters() as $param) {
            if (in_array($param['key'], PayzenTools::$multi_lang_fields)) {
                $default = PayzenTools::convertIsoArrayToIdArray($param['default']);
            } elseif (is_array($param['default'])) {
                $default = serialize($param['default']);
            } else {
                $default = $param['default'];
            }

            if (!Configuration::updateValue($param['key'], $default, false, false, false)) {
                return false;
            }
        }

        // create custom order states
        if (PayzenTools::$plugin_features['oney'] && !Configuration::get('PAYZEN_OS_ONEY_PENDING')) {
            // create FacilyPay Oney pending confirmation order state
            $name = array (
                'en' => 'Funding request in progress',
                'fr' => 'Demande de financement en cours',
                'de' => 'Finanzierungsanfrage im Gange'
            );

            $oney_state = new OrderState();
            $oney_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $oney_state->invoice = false;
            $oney_state->send_email = false;
            $oney_state->module_name = $this->name;
            $oney_state->color = '#FF8C00';
            $oney_state->unremovable = true;
            $oney_state->hidden = false;
            $oney_state->logable = false;
            $oney_state->delivery = false;
            $oney_state->shipped = false;
            $oney_state->paid = false;

            if (!$oney_state->save() || !Configuration::updateValue('PAYZEN_OS_ONEY_PENDING', $oney_state->id)) {
                return false;
            }

            // add small icon to state
            @copy(
                _PS_MODULE_DIR_.'payzen/views/img/os_oney.gif',
                _PS_IMG_DIR_.'os/'.Configuration::get('PAYZEN_OS_ONEY_PENDING').'.gif'
            );
        }

        if (!Configuration::get('PAYZEN_OS_TO_VALIDATE')) {
            // create to validate payment order state
            $name = array (
                'en' => 'To validate payment',
                'fr' => 'Paiement à valider',
                'de' => 'Um zu überprüfen Zahlung'
            );

            $tvp_state = new OrderState();
            $tvp_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $tvp_state->invoice = false;
            $tvp_state->send_email = false;
            $tvp_state->module_name = $this->name;
            $tvp_state->color = '#41664D';
            $tvp_state->unremovable = true;
            $tvp_state->hidden = false;
            $tvp_state->logable = false;
            $tvp_state->delivery = false;
            $tvp_state->shipped = false;
            $tvp_state->paid = false;

            if (!$tvp_state->save() || !Configuration::updateValue('PAYZEN_OS_TO_VALIDATE', $tvp_state->id)) {
                return false;
            }

            // add small icon to state
            @copy(
                _PS_MODULE_DIR_.'payzen/views/img/os_tvp.gif',
                _PS_IMG_DIR_.'os/'.Configuration::get('PAYZEN_OS_TO_VALIDATE').'.gif'
            );
        }

        if (!Configuration::get('PS_OS_OUTOFSTOCK_PAID') && !Configuration::get('PAYZEN_OS_PAYMENT_OUTOFSTOCK')) {
            // create a payment OK but order out of stock state
            $name = array (
                'en' => 'On backorder (payment accepted)',
                'fr' => 'En attente de réapprovisionnement (paiement accepté)',
                'de' => 'Artikel nicht auf Lager (Zahlung eingegangen)'
            );

            $oos_state = new OrderState();
            $oos_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $oos_state->invoice = true;
            $oos_state->send_email = true;
            $oos_state->module_name = $this->name;
            $oos_state->color = '#FF69B4';
            $oos_state->unremovable = true;
            $oos_state->hidden = false;
            $oos_state->logable = false;
            $oos_state->delivery = false;
            $oos_state->shipped = false;
            $oos_state->paid = true;
            $oos_state->template = 'outofstock';

            if (!$oos_state->save() || !Configuration::updateValue('PAYZEN_OS_PAYMENT_OUTOFSTOCK', $oos_state->id)) {
                return false;
            }

            // add small icon to state
            @copy(
                _PS_MODULE_DIR_.'payzen/views/img/os_oos.gif',
                _PS_IMG_DIR_.'os/'.Configuration::get('PAYZEN_OS_PAYMENT_OUTOFSTOCK').'.gif'
            );
        }

        if (!Configuration::get('PAYZEN_OS_AUTH_PENDING')) {
            // create payment pending authorization order state
            $name = array (
                'en' => 'Pending authorization',
                'fr' => 'En attente d\'autorisation',
                'de' => 'Autorisierung angefragt'
            );

            $auth_state = new OrderState();
            $auth_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $auth_state->invoice = false;
            $auth_state->send_email = false;
            $auth_state->module_name = $this->name;
            $auth_state->color = '#FF8C00';
            $auth_state->unremovable = true;
            $auth_state->hidden = false;
            $auth_state->logable = false;
            $auth_state->delivery = false;
            $auth_state->shipped = false;
            $auth_state->paid = false;

            if (!$auth_state->save() || !Configuration::updateValue('PAYZEN_OS_AUTH_PENDING', $auth_state->id)) {
                return false;
            }

            // add small icon to state
            @copy(
                _PS_MODULE_DIR_.'payzen/views/img/os_auth.gif',
                _PS_IMG_DIR_.'os/'.Configuration::get('PAYZEN_OS_AUTH_PENDING').'.gif'
            );
        }

        if ((PayzenTools::$plugin_features['sofort'] || PayzenTools::$plugin_features['sepa'])
            && !Configuration::get('PAYZEN_OS_TRANS_PENDING')) {
            // create SOFORT and SEPA pending funds order state
            $name = array (
                'en' => 'Pending funds transfer',
                'fr' => 'En attente du transfert de fonds',
                'de' => 'Warten auf Geldtransfer'
            );

            $sofort_state = new OrderState();
            $sofort_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $sofort_state->invoice = false;
            $sofort_state->send_email = false;
            $sofort_state->module_name = $this->name;
            $sofort_state->color = '#FF8C00';
            $sofort_state->unremovable = true;
            $sofort_state->hidden = false;
            $sofort_state->logable = false;
            $sofort_state->delivery = false;
            $sofort_state->shipped = false;
            $sofort_state->paid = false;

            if (!$sofort_state->save() || !Configuration::updateValue('PAYZEN_OS_TRANS_PENDING', $sofort_state->id)) {
                return false;
            }

            // add small icon to state
            @copy(
                _PS_MODULE_DIR_.'payzen/views/img/os_trans.gif',
                _PS_IMG_DIR_.'os/'.Configuration::get('PAYZEN_OS_TRANS_PENDING').'.gif'
            );
        }

        // clear module compiled templates
        $tpls = array(
            'redirect', 'redirect_bc', 'redirect_js',
            'iframe/redirect', 'iframe/redirect_bc', 'iframe/response', 'iframe/loader',

            'bc/payment_ancv', 'bc/payment_choozeo', 'bc/payment_fullcb', 'bc/payment_multi', 'bc/payment_oney',
            'bc/payment_paypal', 'bc/payment_sepa', 'bc/payment_sofort', 'bc/payment_std_eu', 'bc/payment_std_iframe',
            'bc/payment_std',

            'payment_choozeo', 'payment_fullcb', 'payment_multi', 'payment_oney', 'payment_return',
            'payment_std_iframe', 'payment_std'
        );
        foreach ($tpls as $tpl) {
            $this->context->smarty->clearCompiledTemplate($this->getTemplatePath($tpl.'.tpl'));
        }

        return true;
    }

    /**
     * @see PaymentModuleCore::uninstall()
     */
    public function uninstall()
    {
        $result = true;
        foreach (PayzenTools::getAdminParameters() as $param) {
            $result &= Configuration::deleteByName($param['key']);
        }

        // delete all obsolete PayZen params but not custom order states
        $result &= Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_."configuration` WHERE `name` LIKE 'PAYZEN_%' AND `name` NOT LIKE 'PAYZEN_OS_%'"
        );

        return $result && parent::uninstall();
    }

    /**
     * Admin form management.
     * @return string
     */
    public function getContent()
    {
        $msg = '';

        if (Tools::isSubmit('payzen_submit_admin_form')) {
            $this->postProcess();

            if (empty($this->_errors)) {
                // no error, display update ok message
                $msg .= $this->displayConfirmation($this->l('Settings updated.'));
            } else {
                // display errors
                $msg .= $this->displayError(implode('<br />', $this->_errors));
            }

            $msg .= '<br />';
        }

        return $msg.$this->renderForm();
    }

    /**
     * Validate and save module admin parameters.
     */
    private function postProcess()
    {
        require_once _PS_MODULE_DIR_.'payzen/classes/PayzenRequest.php';
        $request = new PayzenRequest(); // new instance of PayzenRequest for parameters validation

        // load and validate from request
        foreach (PayzenTools::getAdminParameters() as $param) {
            $key = $param['key']; // PrestaShop parameter key

            if (!Tools::getIsset($key)) {
                // if field is disabled, don't save it
                continue;
            }

            $label = $this->l($param['label'], 'back_office'); // translated human-readable label
            $name = isset($param['name']) ? $param['name'] : null; // PayZen API parameter name

            $value = Tools::getValue($key, null);
            if ($value === '') { // consider empty strings as null
                $value = null;
            }

            if (in_array($key, PayzenTools::$multi_lang_fields)) {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                }
            } elseif (in_array($key, PayzenTools::$group_amount_fields)) {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (isset($option['min_amount']) && $option['min_amount'] && (!is_numeric($option['min_amount']) || $option['min_amount'] < 0)) {
                            $value[$id]['min_amount'] = ''; // error, reset incorrect value
                            $error = true;
                        }

                        if (isset($option['max_amount']) && $option['max_amount'] && (!is_numeric($option['max_amount']) || $option['max_amount'] < 0)) {
                            $value[$id]['max_amount'] = ''; // error, reset incorrect value
                            $error = true;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_MULTI_OPTIONS') {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (!is_numeric($option['count'])
                                || !is_numeric($option['period'])
                                || ($option['first'] && (!is_numeric($option['first']) || $option['first'] < 0 || $option['first'] > 100))) {
                            unset($value[$id]); // error, do not save this option
                            $error = true;
                        } else {
                            $default = is_string($option['label']) && $option['label'] ?
                                $option['label'] : $option['count'].' x';
                            $option_label = is_array($option['label']) ? $option['label'] : array();

                            foreach (Language::getLanguages(false) as $language) {
                                $lang = $language['id_lang'];
                                if (!isset($option_label[$lang]) || empty($option_label[$lang])) {
                                    $option_label[$lang] = $default;
                                }
                            }

                            $value[$id]['label'] = $option_label;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_AVAILABLE_LANGUAGES') {
                $value = (is_array($value) && !empty($value)) ? implode(';', $value) : '';
            } elseif ($key === 'PAYZEN_STD_PAYMENT_CARDS' || $key === 'PAYZEN_MULTI_PAYMENT_CARDS') {
                if (!is_array($value) || in_array('', $value)) {
                    $value = array();
                }

                $value = implode(';', $value);
                if (Tools::strlen($value) > 127) {
                    $this->_errors[] = $this->l('Too many card types are selected.');
                    continue;
                }

                $name = 'payment_cards';
            } elseif ($key === 'PAYZEN_ONEY_SHIP_OPTIONS') {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    foreach ($value as $id => $option) {
                        $carrier = $option['label'].($option['address'] ? ' '.$option['address'] : '').
                            ($option['zip'] ? ' '.$option['zip'] : '').($option['city'] ? ' '.$option['city'] : '');

                        if (!preg_match(self::DELIVERY_COMPANY_REGEX, $carrier)) {
                            unset($value[$id]); // error, not save this option
                            $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $carrier, $label);
                        }
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_CATEGORY_MAPPING') {
                if (Tools::getValue('PAYZEN_COMMON_CATEGORY', null) != 'CUSTOM_MAPPING') {
                    continue;
                }

                if (!is_array($value) || empty($value)) {
                    $value = array();
                }

                $value = serialize($value);
            } elseif (($key === 'PAYZEN_ONEY_ENABLED') && ($value == 'True')) {
                $error = $this->validateOney();

                if (is_string($error) && !empty($error)) {
                    $this->_errors[] = $error;
                    $value = 'False'; // there is errors, not allow Oney activation
                }
            } elseif (in_array($key, PayzenTools::$amount_fields)) {
                if (!empty($value) && (!is_numeric($value) || $value < 0)) {
                    $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $value, $label);
                    continue;
                }
            } elseif ($key === 'PAYZEN_STD_CARD_DATA_MODE' && $value == '3' && !Configuration::get('PS_SSL_ENABLED')) {
                $value = '1'; // force default mode
                $this->_errors[] = $this->l('The bank data acquisition on merchant site cannot be used without enabling SSL.');
                continue;
            } elseif (($key === 'PAYZEN_STD_PROPOSE_ONEY') && ($value == 'True')) {
                $oney_enabled = Tools::getValue('PAYZEN_ONEY_ENABLED', 'False') == 'True' ? true : false;

                if ($oney_enabled) {
                    $value = 'False';
                    $this->_errors[] = $this->l('FacilyPay Oney payment mean cannot be enabled in one-time payment and in FacilyPay Oney sub-module.');
                    $this->_errors[] = $this->l('You must disable the FacilyPay Oney sub-module to enable it in one-time payment.');
                } else {
                    $error = $this->validateOney(true);

                    if (is_string($error) && !empty($error)) {
                        $this->_errors[] = $error;
                        $value = 'False'; // there is errors, not allow Oney activation in standard payment
                    }
                }
            } elseif (($key === 'PAYZEN_ONEY_OPTIONS') && (Tools::getValue('PAYZEN_ONEY_ENABLE_OPTIONS', 'False') == 'True')) {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (!is_numeric($option['count']) || !is_numeric($option['rate']) || empty($option['code'])) {
                            unset($value[$id]); // error, do not save this option
                            $error = true;
                        } else {
                            $default = is_string($option['label']) && $option['label'] ?
                                $option['label'] : $option['count'].' x';
                            $option_label = is_array($option['label']) ? $option['label'] : array();

                            foreach (Language::getLanguages(false) as $language) {
                                $lang = $language['id_lang'];
                                if (!isset($option_label[$lang]) || empty($option_label[$lang])) {
                                    $option_label[$lang] = $default;
                                }
                            }

                            $value[$id]['label'] = $option_label;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif (($key === 'PAYZEN_FULLCB_OPTIONS') && (Tools::getValue('PAYZEN_FULLCB_ENABLE_OPTS', 'False') == 'True')) {
                if (!is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if ($option['min_amount'] && !is_numeric($option['min_amount']) || $option['min_amount'] < 0) {
                            $value[$id]['min_amount'] = ''; // error, reset incorrect value
                            $error = true;
                        }

                        if ($option['max_amount'] && !is_numeric($option['max_amount']) || $option['max_amount'] < 0) {
                            $value[$id]['max_amount'] = ''; // error, reset incorrect value
                            $error = true;
                        }

                        if (!is_numeric($option['rate']) || $option['rate'] < 0 || $option['rate'] > 100) {
                            $value[$id]['rate'] = '0'; // error, reset incorrect value
                            $error = true;
                        }

                        if (!is_numeric($option['cap']) || $option['cap'] < 0) {
                            $value[$id]['cap'] = ''; // error, reset incorrect value
                            $error = true;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            }

            // validate with PayzenRequest
            if ($name) {
                $values = is_array($value) ? $value : array($value); // to check multilingual fields
                $error = false;

                foreach ($values as $v) {
                    if (!$request->set($name, $v)) {
                        $error = true;
                        if (empty($v)) {
                            $this->_errors[] = sprintf($this->l('The field « %s » is mandatory.'), $label);
                        } else {
                            $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $v, $label);
                        }
                    }
                }

                if ($error) {
                    continue; // not save fields with errors
                }
            }

            // valid field : try to save into DB
            if (!Configuration::updateValue($key, $value)) {
                $this->_errors[] = sprintf($this->l('Problem occurred while saving field « %s ».'), $label);
            } else {
                // temporary variable set to update PrestaShop cache
                Configuration::set($key, $value);
            }
        }
    }

    private function validateOney($inside = false)
    {
        if (Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            return $this->l('Multishipping is activated. FacilyPay Oney payment cannot be used.');
        }

        if (!$inside) {
            $group_amounts = Tools::getValue('PAYZEN_ONEY_AMOUNTS');

            $default_min = $group_amounts[0]['min_amount'];
            $default_max = $group_amounts[0]['max_amount'];

            if (empty($default_min) || empty($default_max)) {
                return $this->l('Please, enter minimum and maximum amounts in FacilyPay Oney payment tab as agreed with Banque Accord.');
            }

            $msg = 'FacilyPay Oney payment - Customer group amount restriction';
            $label = $this->l($msg, 'back_office');

            foreach ($group_amounts as $id => $group) {
                if (empty($group) || $id === 0) { // all groups
                    continue;
                }

                $min_amount = $group['min_amount'];
                $max_amount = $group['max_amount'];
                if (($min_amount && $min_amount < $default_min) || ($max_amount && $max_amount > $default_max)) {
                    return sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                }
            }
        }

        return true;
    }

    private function renderForm()
    {
        $this->addJS('payzen.js');
        $this->context->controller->addJqueryUI('ui.accordion');

        $html = '';

        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $html .= '<style type="text/css">
                            #content {
                                min-width: inherit !important;
                            }
                     </style>';
            $html .= "\n";
        }

        require_once _PS_MODULE_DIR_.'payzen/classes/admin/PayzenHelperForm.php';

        $this->context->smarty->assign(PayzenHelperForm::getAdminFormContext());
        $form = $this->context->smarty->fetch(_PS_MODULE_DIR_.'payzen/views/templates/admin/back_office.tpl');

        $prefered_post_vars = 0;
        $prefered_post_vars += substr_count($form, 'name="PAYZEN_');
        $prefered_post_vars += 100; // to take account of dynamically created inputs

        if ((ini_get('suhosin.post.max_vars') && ini_get('suhosin.post.max_vars') < $prefered_post_vars)
                || (ini_get('suhosin.request.max_vars') && ini_get('suhosin.request.max_vars') < $prefered_post_vars)) {
            $html .= $this->displayError(sprintf($this->l('Warning, please increase the suhosin patch for PHP post and request limits to save module configurations correctly. Recommended value is %s.'), $prefered_post_vars));
        } elseif (ini_get('max_input_vars') && ini_get('max_input_vars') < $prefered_post_vars) {
            $html .= $this->displayError(sprintf($this->l('Warning, please increase the value of the max_input_vars directive in php.ini to to save module configurations correctly. Recommended value is %s.'), $prefered_post_vars));
        }

        $html .= $form;
        return $html;
    }

    /**
     * Payment method selection page header.
     *
     * @param array $params
     * @return string|void
     */
    public function hookHeader($params)
    {
        $controller = $this->context->controller;
        if ($controller instanceof OrderController || $controller instanceof OrderOpcController) {
            if (isset($this->context->cookie->payzenPayErrors)) {
                // process errors from other pages
                $controller->errors = array_merge(
                    $controller->errors,
                    explode("\n", $this->context->cookie->payzenPayErrors)
                );
                unset($this->context->cookie->payzenPayErrors);

                // unset HTTP_REFERER from global server variable to avoid back link display in error message
                $_SERVER['HTTP_REFERER'] = null;
                $this->context->smarty->assign('server', $_SERVER);
            }

            // add main module CSS
            $this->addCss('payzen.css');

            $standard = new PayzenStandardPayment();
            if ($standard->isAvailable($this->context->cart) && $standard->getEntryMode() == '3') {
                // data entry on merchant website, let's load appropriate script and styles

                $this->addJs('card.js');
                $this->addCss('card.css');
            }

            // add backward compatibility module CSS
            $html = '';
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $this->addCss('payzen_bc.css');

                // load payment module style to apply it to our tag
                if ($this->useMobileTheme()) {
                    $css_file = _PS_THEME_MOBILE_DIR_.'css/global.css';
                } else {
                    $css_file = _PS_THEME_DIR_.'css/global.css';
                }

                $css = Tools::file_get_contents(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $css_file));

                $matches = array();
                $res = preg_match_all('#(p\.payment_module(?:| a| a\:hover) ?\{[^\}]+\})#i', $css, $matches);
                if ($res && !empty($matches) && isset($matches[1]) && is_array($matches[1]) && !empty($matches[1])) {
                    $html .= '<style type="text/css">'."\n";
                    $html .= str_ireplace('p.payment_module', 'div.payment_module', implode("\n", $matches[1]))."\n";
                    $html .= '</style>'."\n";
                }
            }

            return $html;
        }
    }

    protected function useMobileTheme()
    {
        if (method_exists(get_parent_class($this), 'useMobileTheme')) {
            return parent::useMobileTheme();
        } elseif (method_exists($this->context, 'getMobileDevice')) {
            return ($this->context->getMobileDevice() && file_exists(_PS_THEME_MOBILE_DIR_.'layout.tpl'));
        } else {
            return false;
        }
    }

    private function addJs($js_file)
    {
        $controller = $this->context->controller;

        if (method_exists($controller, 'registerJavascript')) { // PrestaShop 1.7
            $controller->registerJavascript(
                'module-payzen',
                'modules/'.$this->name.'/views/js/'.$js_file,
                array('position' => 'bottom', 'priority' => 150)
            );
        } else {
            $controller->addJs($this->_path . 'views/js/'.$js_file);
        }
    }

    private function addCss($css_file)
    {
        $controller = $this->context->controller;

        if (method_exists($controller, 'registerStylesheet')) { // PrestaShop 1.7
            $controller->registerStylesheet(
                'module-payzen',
                'modules/'.$this->name.'/views/css/'.$css_file,
                array('media' => 'all', 'priority' => 90)
            );
        } else {
            $controller->addCss($this->_path . 'views/css/'.$css_file, 'all');
        }
    }

    /**
     * Payment function, payment button render if Advanced EU Compliance module is used.
     *
     * @param array $params
     * @return void|array
     */
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency()) {
            return;
        }

        $cart = $this->context->cart;

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            $payment_options = array(
                'cta_text' => $standard->getTitle((int)$cart->id_lang),
                'logo' => $this->_path.'views/img/'.$standard->getLogo(),
                'form' => $this->display(__FILE__, 'bc/payment_std_eu.tpl')
            );

            return $payment_options;
        }
    }

    /**
     * Payment function, display payment buttons/forms for all sub-modules.
     *
     * @param array $params
     * @return void|string
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        // currency support
        if (!$this->checkCurrency()) {
            return;
        }

        $cart = $this->context->cart;

        // version tag for specific styles
        $tag = version_compare(_PS_VERSION_, '1.6', '<') ? 'payzen15' : 'payzen16';
        $this->context->smarty->assign('payzen_tag', $tag);

        $html = '';

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            $this->context->smarty->assign($standard->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$standard->getTplName());
        }

        $multi = new PayzenMultiPayment();
        if ($multi->isAvailable($cart)) {
            $this->context->smarty->assign($multi->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$multi->getTplName());
        }

        $choozeo = new PayzenChoozeoPayment();
        if ($choozeo->isAvailable($cart)) {
            $this->context->smarty->assign($choozeo->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$choozeo->getTplName());
        }

        $oney = new PayzenOneyPayment();
        if ($oney->isAvailable($cart)) {
            $this->context->smarty->assign($oney->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$oney->getTplName());
        }

        $fullcb = new PayzenFullcbPayment();
        if ($fullcb->isAvailable($cart)) {
            $this->context->smarty->assign($fullcb->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$fullcb->getTplName());
        }

        $ancv = new PayzenAncvPayment();
        if ($ancv->isAvailable($cart)) {
            $this->context->smarty->assign($ancv->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$ancv->getTplName());
        }

        $sepa = new PayzenSepaPayment();
        if ($sepa->isAvailable($cart)) {
            $this->context->smarty->assign($sepa->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$sepa->getTplName());
        }

        $paypal = new PayzenPaypalPayment();
        if ($paypal->isAvailable($cart)) {
            $this->context->smarty->assign($paypal->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$paypal->getTplName());
        }

        $sofort = new PayzenSofortPayment();
        if ($sofort->isAvailable($cart)) {
            $this->context->smarty->assign($sofort->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/'.$sofort->getTplName());
        }

        return $html;
    }

    /**
     * Payment function, display payment buttons/forms for all sub-modules in PrestaShop 1.7+.
     *
     * @param array $params
     * @return void|array[PaymentOption]
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return array();
        }

        if (!$this->checkCurrency()) {
            return array();
        }

        $cart = $this->context->cart;

        /**
         * @var array[\PrestaShop\PrestaShop\Core\Payment\PaymentOption]
         */
        $options = array();

        // version tag for specific styles
        $this->context->smarty->assign('payzen_tag', 'payzen17');

        /**
         * AbstractPayzenPayment::getPaymentOption() returns a payment option of type
         * PrestaShop\PrestaShop\Core\Payment\PaymentOption
         */

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            $option = $standard->getPaymentOption($cart);

            if ($standard->hasForm()) {
                $this->context->smarty->assign($standard->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/'.$standard->getTplName());

                if ($standard->getEntryMode() == '4') {
                    // iframe mode
                    $option->setAdditionalInformation($form);
                    $option->setForm('<form id="payzen_standard" onsubmit="javascript: return false;"></form>');
                } else {
                    $option->setForm($form);
                }
            }

            $options[] = $option;
        }

        $multi = new PayzenMultiPayment();
        if ($multi->isAvailable($cart)) {
            $option = $multi->getPaymentOption($cart);

            if ($multi->hasForm()) {
                $this->context->smarty->assign($multi->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/'.$multi->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $choozeo = new PayzenChoozeoPayment();
        if ($choozeo->isAvailable($cart)) {
            $option = $choozeo->getPaymentOption($cart);

            if ($choozeo->hasForm()) {
                $this->context->smarty->assign($choozeo->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/'.$choozeo->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $oney = new PayzenOneyPayment();
        if ($oney->isAvailable($cart)) {
            $option = $oney->getPaymentOption($cart);

            if ($oney->hasForm()) {
                $this->context->smarty->assign($oney->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/'.$oney->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $fullcb = new PayzenFullcbPayment();
        if ($fullcb->isAvailable($cart)) {
            $option = $fullcb->getPaymentOption($cart);

            if ($fullcb->hasForm()) {
                $this->context->smarty->assign($fullcb->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/'.$fullcb->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $ancv = new PayzenAncvPayment();
        if ($ancv->isAvailable($cart)) {
            $options[] = $ancv->getPaymentOption($cart);
        }

        $sepa = new PayzenSepaPayment();
        if ($sepa->isAvailable($cart)) {
            $options[] = $sepa->getPaymentOption($cart);
        }

        $paypal = new PayzenPaypalPayment();
        if ($paypal->isAvailable($cart)) {
            $options[] = $paypal->getPaymentOption($cart);
        }

        $sofort = new PayzenSofortPayment();
        if ($sofort->isAvailable($cart)) {
            $options[] = $sofort->getPaymentOption($cart);
        }

        return $options;
    }

    private function checkCurrency()
    {
        $cart = $this->context->cart;

        $cart_currency = new Currency((int)$cart->id_currency);
        $currencies = $this->getCurrency((int)$cart->id_currency);

        if (!is_array($currencies) || empty($currencies)) {
            return false;
        }

        foreach ($currencies as $currency) {
            if ($cart_currency->id == $currency['id_currency']) {
                // cart currency is allowed for this module
                return PayzenApi::findCurrencyByAlphaCode($cart_currency->iso_code) != null;
            }
        }

        return false;
    }

    /**
     * Manage payement gateway response.
     *
     * @param array $params
     */
    public function hookPaymentReturn($params)
    {
        $order = isset($params['order']) ? $params['order'] : $params['objOrder'];

        if (!$this->active || ($order->module != $this->name)) {
            return;
        }

        $error = (Tools::getValue('error') == 'yes');

        $array = array(
            'check_url_warn' => (Tools::getValue('check_url_warn') == 'yes'),
            'maintenance_mode' => !Configuration::get('PS_SHOP_ENABLE'),
            'prod_info' => (Tools::getValue('prod_info') == 'yes'),
            'error_msg' => $error
        );

        if (!$error) {
            $array['total_to_pay'] = Tools::displayPrice(
                $order->getOrdersTotalPaid(),
                new Currency($order->id_currency),
                false
            );

            $array['id_order'] = $order->id;
            $array['status'] = 'ok';
            $array['shop_name'] = Configuration::get('PS_SHOP_NAME');

            if (isset($order->reference) && !empty($order->reference)) {
                $array['reference'] = $order->reference;
            }
        }

        $this->context->smarty->assign($array);

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     * Before order details display in backend.
     *
     * @param array $params
     */
    public function hookAdminOrder($params)
    {
        if (isset($this->context->cookie->payzenPartialRefundWarn)) {
            $this->context->controller->warnings[] = $this->context->cookie->payzenPartialRefundWarn;
            unset($this->context->cookie->payzenPartialRefundWarn);
        }
    }

    /**
     * After order slip add in backend.
     *
     * @param array $params
     */
    public function hookActionOrderSlipAdd($params)
    {
        if (Tools::isSubmit('partialRefund') && ($params['order']->module == $this->name)) {
            $msg = $this->l('Refunding is not possible for this payment module. Please modify payment from your store Back Office.');
            $this->context->cookie->payzenPartialRefundWarn = $msg;
        }
    }

    /**
     * Save order and transaction info.
     *
     * @param Cart $cart
     * @param int $state
     * @param PayzenResponse $response
     * @return Order
     */
    public function saveOrder($cart, $state, $response)
    {
        $this->logger->logInfo("Create order for cart #{$cart->id}.");

        // retrieve customer from cart
        $customer = new Customer((int)$cart->id_customer);

        $currency = PayzenApi::findCurrency($response->get('currency'));
        $decimals = $currency->getDecimals();

        // PrestaShop id_currency from currency iso num code
        $currency_id = Currency::getIdByIsoCode($currency->getAlpha3());

        // real paid total on gateway
        $paid_total = $currency->convertAmountToFloat($response->get('amount'));
        if (number_format($cart->getOrderTotal(), $decimals) == number_format($paid_total, $decimals)) {
            // to avoid rounding issues and bypass PaymentModule::validateOrder() check
            $paid_total = $cart->getOrderTotal();
        }

        // call payment module validateOrder
        $this->validateOrder(
            $cart->id,
            $state,
            $paid_total,
            $response->get('order_info'), // title defined in admin panel and sent to gateway as order_info
            null, // $message
            array(), // $extraVars
            $currency_id, // $currency_special
            true, // $dont_touch_amount
            $customer->secure_key
        );

        // reload order
        $order = new Order((int)Order::getOrderByCartId($cart->id));
        $this->logger->logInfo("Order #{$order->id} created successfully for cart #{$cart->id}.");

        $this->createMessage($order, $response);
        $this->savePayment($order, $response);

        return $order;
    }


    /**
     * Update current order state.
     *
     * @param Order $order
     * @param int $order_state
     * @param PayzenResponse $response
     */
    public function setOrderState($order, $order_state, $response)
    {
        $this->logger->logInfo(
            "Payment status for cart #{$order->id_cart} has changed. New order state is $order_state."
        );
        $order->setCurrentState($order_state);
        $this->logger->logInfo("Order state successfully changed, cart #{$order->id_cart}.");

        $this->createMessage($order, $response);
        $this->savePayment($order, $response);
    }

    /**
     * Create private message to information about order payment.
     *
     * @param Order $order
     * @param PayzenResponse $response
     */
    public function createMessage($order, $response)
    {
        $msg_brand_choice = '';
        if ($response->get('brand_management')) {
            $brand_info = Tools::jsonDecode($response->get('brand_management'));
            $msg_brand_choice .= "\n";

            if (isset($brand_info->userChoice) && $brand_info->userChoice) {
                $msg_brand_choice .= $this->l('Card brand chosen by buyer.');
            } else {
                $msg_brand_choice .= $this->l('Default card brand used.');
            }
        }

        // 3DS extra message
        $msg_3ds = "\n".$this->l('3DS authentication : ');
        if ($response->get('threeds_status') == 'Y') {
            $msg_3ds .= $this->l('YES');
            $msg_3ds .= "\n".$this->l('3DS certificate : ').$response->get('threeds_cavv');
        } else {
            $msg_3ds .= $this->l('NO');
        }

        // IPN call source
        $msg_src = "\n".$this->l('IPN source : ').$response->get('url_check_src');

        $msg = new Message();
        $msg->message = $response->getCompleteMessage().$msg_brand_choice.$msg_3ds.$msg_src;
        $msg->id_order = (int)$order->id;
        $msg->private = 1;
        $msg->add();

        // mark message as read to archive it
        Message::markAsReaded($msg->id, 0);
    }


    /**
     * Save payment information.
     *
     * @param Order $order
     * @param PayzenResponse $response
     */
    public function savePayment($order, $response)
    {
        $payments = $order->getOrderPayments();

        // delete payments created by default and cancelled payments
        if (is_array($payments) && !empty($payments)) {
            $number = $response->get('sequence_number') ? $response->get('sequence_number') : '1';
            $trans_id = $number.'-'.$response->get('trans_id');
            $cancelled = $response->getTransStatus() === 'CANCELLED';

            $update = false;

            foreach ($payments as $payment) {
                if (!$payment->transaction_id || (($payment->transaction_id == $trans_id) && $cancelled)) {
                    $order->total_paid_real -= $payment->amount;
                    $payment->delete();

                    $update = true;
                }
            }

            if ($update) {
                if ($order->total_paid_real < 0) {
                    $order->total_paid_real = 0;
                }

                $order->update();
            }
        }

        if (!$this->isSuccessState($order) && !$response->isAcceptedPayment()) {
            // no payment creation
            return;
        }

        // save transaction info
        $this->logger->logInfo("Save payment information for cart #{$order->id_cart}.");

        $invoices = $order->getInvoicesCollection();
        $invoice = ($invoices && $invoices->getFirst()) ? $invoices->getFirst() : null;

        $currency = PayzenApi::findCurrency($response->get('currency'));
        $decimals = $currency->getDecimals();

        $payment_ids = array();
        if ($response->get('card_brand') == 'MULTI') {
            $sequences = Tools::jsonDecode($response->get('payment_seq'));
            $transactions = array_filter($sequences->transactions, 'Payzen::filterTransactions');

            $last_trs = end($transactions); // last transaction
            foreach ($transactions as $trs) {
                // real paid total on gateway
                $amount = $currency->convertAmountToFloat($trs->{'amount'});

                if ($trs === $last_trs) {
                    $remaining = $order->total_paid - $order->total_paid_real;
                    if (number_format($remaining, $decimals) == number_format($amount, $decimals)) {
                        // to avoid rounding problems and pass PaymentModule::validateOrder() check
                        $amount = $remaining;
                    }
                }

                $trans_id = $trs->{'sequence_number'}.'-'.$trs->{'trans_id'};
                $timestamp = isset($trs->{'presentation_date'}) ? strtotime($trs->{'presentation_date'}.' UTC') : time();

                $data = array(
                    'card_number' => $trs->{'card_number'},
                    'card_brand' => $trs->{'card_brand'},
                    'expiry_month' => isset($trs->{'expiry_month'}) ? $trs->{'expiry_month'} : null,
                    'expiry_year' => isset($trs->{'expiry_year'}) ? $trs->{'expiry_year'} : null
                );

                if (!($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                    return;
                }

                $payment_ids[] = $pccId;
            }
        } elseif (($info2 = $response->get('order_info2')) && (strpos($response->get('payment_config'), 'MULTI') !== false)) {
            // ID of selected payment option
            $option_id = Tools::substr($info2, Tools::strlen('option_id='));

            $multi_options = PayzenMultiPayment::getAvailableOptions();
            $option = $multi_options[$option_id];

            $count = (int) $option['count'];

            $total_amount = $response->get('amount');

            if (isset($option['first']) && $option['first']) {
                $first_amount = round($total_amount * $option['first'] / 100);
            } else {
                $first_amount = round($total_amount / $count);
            }

            $installment_amount = (int) (string) (($total_amount - $first_amount) / ($count - 1));

            $first_timestamp = strtotime($response->get('presentation_date').' UTC');

            $data = array(
                'card_number' => $response->get('card_number'),
                'card_brand' => $response->get('card_brand'),
                'expiry_month' => $response->get('expiry_month'),
                'expiry_year' => $response->get('expiry_year')
            );

            $total_paid_real = 0;
            for ($i = 1; $i <= $option['count']; $i++) {
                $trans_id = $i.'-'.$response->get('trans_id');

                $delay = (int) $option['period'] * ($i - 1);
                $timestamp = strtotime("+$delay days", $first_timestamp);

                switch (true) {
                    case ($i == 1): // first transaction
                        $amount = $currency->convertAmountToFloat($first_amount);
                        break;
                    case ($i == $option['count']): // last transaction
                        $amount = $currency->convertAmountToFloat($total_amount) - $total_paid_real;

                        $remaining = $order->total_paid - $order->total_paid_real;
                        if (number_format($remaining, $decimals) == number_format($amount, $decimals)) {
                            // to avoid rounding problems and pass PaymentModule::validateOrder() check
                            $amount = $remaining;
                        }
                        break;
                    default: // others
                        $amount = $currency->convertAmountToFloat($installment_amount);
                        break;
                }

                $total_paid_real += $amount;

                if (!($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                    return;
                }

                $payment_ids[] = $pccId;
            }
        } else {
            // real paid total on gateway
            $amount_in_cents = $response->get('amount');
            if ($response->get('effective_currency') && ($response->get('effective_currency') == $response->get('currency'))) {
                $amount_in_cents = $response->get('effective_amount'); // use effective amount to get modified amount
            }

            $amount = $currency->convertAmountToFloat($amount_in_cents);

            if (number_format($order->total_paid, $decimals) == number_format($amount, $decimals)) {
                // to avoid rounding problems and pass PaymentModule::validateOrder() check
                $amount = $order->total_paid;
            }

            if ($response->get('operation_type') === 'CREDIT') {
                // this is a refund, set transaction amount to negative
                $amount = $amount * -1;
            }

            $timestamp = strtotime($response->get('presentation_date').' UTC');

            $number = $response->get('sequence_number') ? $response->get('sequence_number') : '1';
            $trans_id = $number.'-'.$response->get('trans_id');

            $data = array(
                'card_number' => $response->get('card_number'),
                'card_brand' => $response->get('card_brand'),
                'expiry_month' => $response->get('expiry_month'),
                'expiry_year' => $response->get('expiry_year')
            );

            if (!($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                return;
            }

            $payment_ids[] = $pccId;
        }

        $payment_ids = implode(', ', $payment_ids);
        $this->logger->logInfo(
            "Payment information with ID(s) {$payment_ids} saved successfully for cart #{$order->id_cart}."
        );
    }

    private function findOrderPayment($order_ref, $trans_id)
    {
        $payment_id = Db::getInstance()->getValue(
            'SELECT `id_order_payment` FROM `'._DB_PREFIX_.'order_payment`
            WHERE `order_reference` = \''.pSQL($order_ref).'\' AND transaction_id = \''.pSQL($trans_id).'\''
        );

        if (!$payment_id) {
            return false;
        }

        return new OrderPayment((int)$payment_id);
    }

    private function addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data)
    {
        $date = date('Y-m-d H:i:s', $timestamp);

        if (!($pcc = $this->findOrderPayment($order->reference, $trans_id))) {
            // order payment not created yet, let's create it

            $method = sprintf($this->l('%s payment'), $data['card_brand']);
            if (!$order->addOrderPayment($amount, $method, $trans_id, null, $date, $invoice)
                || !($pcc = $this->findOrderPayment($order->reference, $trans_id))) {
                $this->logger->logWarning(
                    "Error : payment information for cart #{$order->id_cart} cannot be saved.
                     Error may be caused by another module hooked on order update event."
                );
                return false;
            }
        } elseif (Validate::isLoadedObject($invoice)) {
            Db::getInstance()->execute(
                'REPLACE INTO `'._DB_PREFIX_.'order_invoice_payment`
                 VALUES('.(int)$invoice->id.', '.(int)$pcc->id.', '.(int)$order->id.')'
            );
        }

        // set card info
        $pcc->card_number = $data['card_number'];
        $pcc->card_brand = $data['card_brand'];
        if ($data['expiry_month'] && $data['expiry_year']) {
            $pcc->card_expiration = str_pad($data['expiry_month'], 2, '0', STR_PAD_LEFT).'/'.$data['expiry_year'];
        }
        $pcc->card_holder = null;

        // update transaction info if payment is modified in PayZen Back Office
        if ($pcc->amount != $amount) {
            $pcc->amount = $amount;

            $this->logger->logInfo("Transaction amount is modified for cart #{$order->id_cart}. New amount is $amount.");
        }

        if ($pcc->date_add != $date) {
            $pcc->date_add = $date;

            $this->logger->logInfo("Transaction presentation date is modified for cart #{$order->id_cart}. New date is $date.");
        }

        if ($pcc->update()) {
            return $pcc->id;
        } else {
            $this->logger->logWarning("Problem : payment mean information for cart #{$order->id_cart} cannot be saved.");
            return false;
        }
    }

    public static function filterTransactions($trs)
    {
        $successful_states = array(
            'INITIAL', 'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE',
            'UNDER_VERIFICATION', 'AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'CAPTURED',
            'CAPTURE_FAILED' /* capture will be redone */
        );

        return $trs->{'operation_type'} == 'DEBIT' && in_array($trs->{'trans_status'}, $successful_states);
    }

    public static function nextOrderState($response, $total_refund = false, $outofstock = false)
    {
        if ($response->isAcceptedPayment()) {
            $valid = false;

            switch (true) {
                case $response->isToValidatePayment():
                    // to validate payment order state
                    $new_state = 'PAYZEN_OS_TO_VALIDATE';

                    break;
                case $response->isPendingPayment():
                    if (self::isOney($response)) {
                        // pending Oney confirmation order state
                        $new_state = 'PAYZEN_OS_ONEY_PENDING';
                    } else {
                        // pending authorization order state
                        $new_state = 'PAYZEN_OS_AUTH_PENDING';
                    }

                    break;
                default:
                    // payment successful

                    if (($response->get('operation_type') === 'CREDIT') && $total_refund) {
                        $new_state = 'PS_OS_REFUND';
                    } elseif (self::isSofort($response) || self::isSepa($response)) {
                        // pending funds transfer order state
                        $new_state = 'PAYZEN_OS_TRANS_PENDING';
                    } else {
                        $new_state = 'PS_OS_PAYMENT';
                        $valid = true;
                    }

                    break;
            }

            if ($outofstock) {
                if ($valid) {
                    $new_state = Configuration::get('PS_OS_OUTOFSTOCK_PAID') ? 'PS_OS_OUTOFSTOCK_PAID' : 'PAYZEN_OS_PAYMENT_OUTOFSTOCK';
                } else {
                    $new_state = Configuration::get('PS_OS_OUTOFSTOCK_UNPAID') ? 'PS_OS_OUTOFSTOCK_UNPAID' : 'PS_OS_OUTOFSTOCK';
                }
            }
        } elseif ($response->isCancelledPayment() || ($response->getTransStatus() === 'CANCELLED')) {
            $new_state = 'PS_OS_CANCELED';
        } else {
            $new_state = 'PS_OS_ERROR';
        }

        return Configuration::get($new_state);
    }

    /**
     * Return true if order is in a successful state (paid or pending confirmation).
     *
     * @param Order $order
     * @return boolean
     */
    public static function isSuccessState($order)
    {
        $os = new OrderState((int)$order->getCurrentState());
        if (!$os->id) {
            return false;
        }

        if (self::isOutOfStock($order)) {
            return true;
        }

        $s_states = array(
            'PS_OS_PAYMENT',
            'PAYZEN_OS_TRANS_PENDING',
            'PAYZEN_OS_TO_VALIDATE',
            'PAYZEN_OS_ONEY_PENDING',
            'PAYZEN_OS_AUTH_PENDING'
        );

        // if state is one of supported states or custom state with paid flag
        return self::isStateInArray($os->id, $s_states) || (bool)$os->paid;
    }

    public static function isOutOfStock($order)
    {
        $state = $order->getCurrentState();
        $oos_states = array(
            'PS_OS_OUTOFSTOCK_UNPAID', // override pending states since PrestaShop 1.6.1
            'PS_OS_OUTOFSTOCK_PAID', // override paid state since PrestaShop 1.6.1
            'PS_OS_OUTOFSTOCK', // considered as pending by PayZen module for PrestaShop < 1.6.1
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK' // paid state for PrestaShop < 1.6.1
        );

        return self::isStateInArray($state, $oos_states);
    }

    public static function isPaidOrder($order)
    {
        $os = new OrderState((int)$order->getCurrentState());
        if (!$os->id) {
            return false;
        }

        // final states
        $paid_states = array(
            'PS_OS_OUTOFSTOCK_PAID', // override paid state since PrestaShop 1.6.1
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK', // paid state for PrestaShop < 1.6.1
            'PS_OS_PAYMENT',
            'PAYZEN_OS_TRANS_PENDING'
        );

        return self::isStateInArray($os->id, $paid_states) || (bool)$os->paid;
    }

    public static function getManagedStates()
    {
        $managed_states = array(
            'PS_OS_OUTOFSTOCK_UNPAID', // override pending states since PrestaShop 1.6.1
            'PS_OS_OUTOFSTOCK_PAID', // override paid state since PrestaShop 1.6.1
            'PS_OS_OUTOFSTOCK', // considered as pending by PayZen module for PrestaShop < 1.6.1
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK', // paid state for PrestaShop < 1.6.1

            'PS_OS_PAYMENT',
            'PAYZEN_OS_ONEY_PENDING',
            'PAYZEN_OS_TRANS_PENDING',
            'PAYZEN_OS_AUTH_PENDING',
            'PAYZEN_OS_TO_VALIDATE',
            'PS_OS_ERROR',
            'PS_OS_CANCELED',
            'PS_OS_REFUND'
        );

        return $managed_states;
    }

    public static function hasAmountError($order)
    {
        return number_format($order->total_paid, 2) != number_format($order->total_paid_real, 2);
    }

    public static function isStateInArray($state_id, $state_names)
    {
        if (is_string($state_names)) {
            $state_names = array($state_names);
        }

        foreach ($state_names as $state_name) {
            if (!is_string($state_name) || !Configuration::get($state_name)) {
                continue;
            }

            if ((int)$state_id === (int)Configuration::get($state_name)) {
                return true;
            }
        }

        return false;
    }

    public static function isOney($response)
    {
        return in_array($response->get('card_brand'), array('ONEY', 'ONEY_SANDBOX'));
    }

    public static function isSofort($response)
    {
        return $response->get('card_brand') == 'SOFORT_BANKING';
    }

    public static function isSepa($response)
    {
        return $response->get('card_brand') == 'SDD';
    }
}
