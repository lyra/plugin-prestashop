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

require_once(_PS_MODULE_DIR_ . 'payzen/autoload.php');

/**
 * Payment module main class.
 */
class Payzen extends PaymentModule
{
    // Regular expressions.
    const DELIVERY_COMPANY_ADDRESS_REGEX = '#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /\'-]{1,72}$#ui';
    const DELIVERY_COMPANY_LABEL_REGEX = '#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /\'-]{1,55}$#ui';

    private $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'payzen';
        $this->tab = 'payments_gateways';
        $this->version = '1.15.4';
        $this->author = 'Lyra Network';
        $this->controllers = array('redirect', 'submit', 'rest', 'iframe');
        $this->module_key = 'f3e5d07f72a9d27a5a09196d54b9648e';
        $this->is_eu_compatible = 1;
        $this->need_instance = 1;

        $this->logger = PayzenTools::getLogger();

        // Check version compatibility.
        $minor = Tools::substr(_PS_VERSION_, strrpos(_PS_VERSION_, '.') + 1);
        $replace = (int) $minor + 1;
        $start = Tools::strlen(_PS_VERSION_) - Tools::strlen($minor);
        $version = substr_replace(_PS_VERSION_, (string) $replace, $start);
        $this->ps_versions_compliancy = array('min' => '1.5.0.0', 'max' => $version);

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $order_id = (int) Tools::getValue('id_order', 0);
        $order = new Order($order_id);
        if (($order->module == $this->name) && ($this->context->controller instanceof OrderConfirmationController)) {
            // Patch to use different display name according to the used payment submodule.
            $this->displayName = $order->payment;
        } else {
            $this->displayName = 'PayZen';
        }

        $this->description = $this->l('Accept payments by credit cards');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your module details?');
    }

    /**
     * @see PaymentModuleCore::install()
     */
    public function install()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            // Incompatible version of PrestaShop.
            return false;
        }

        $installError = false;
        if (! defined('PAYZEN_MODULE_UPGRADE')) {
            $parentInstallResult = parent::install();

            // Error in PrestaShop install function, do not interrupt installation.
            if (is_string($parentInstallResult)) {
                $this->logger->logWarning('PaymentModule::install returned an error: ' . $parentInstallResult);
                $this->_errors[] = $parentInstallResult;

                $installError = true;
            } elseif (! $parentInstallResult) {
                $this->logger->logWarning('PaymentModule::install returned an unknown error. Installation of the module will continue.');
                $installError = true;
            }
        }

        // Install hooks.
        if (! $this->registerHook('header') || ! $this->registerHook('paymentReturn')
            || ! $this->registerHook('adminOrder') || ! $this->registerHook('actionObjectOrderSlipAddBefore')
            || ! $this->registerHook('actionProductCancel')
            || ! $this->registerHook('actionOrderStatusUpdate')
            || ! $this->registerHook('actionOrderStatusPostUpdate')
            || ! $this->registerHook('actionAdminCarrierWizardControllerSaveBefore')
            || ! $this->registerHook('actionAdminCarriersOptionsModifier')) {
            $this->logger->logWarning('One or more hooks necessary for the module could not be saved.');
            $this->_errors[] = $this->l('One or more hooks necessary for the module could not be saved.');

            $installError = true;
        }

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            if (! $this->registerHook('payment') || ! $this->registerHook('displayPaymentEU')) {
                $this->logger->logWarning('Hook « displayPaymentEU » could not be saved.');
                $this->_errors[] = $this->l('One or more hooks necessary for the module could not be saved.');

                $installError = true;
            }
        } elseif (! $this->registerHook('paymentOptions')) {
            $this->logger->logWarning('Hook « paymentOptions » could not be saved.');
            $this->_errors[] = $this->l('One or more hooks necessary for the module could not be saved.');

            $installError = true;
        }

        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            if (! $this->registerHook('displayAdminOrderTop')) {
                $this->logger->logWarning('Hook « displayAdminOrderTop » could not be saved.');
                $this->_errors[] = $this->l('One or more hooks necessary for the module could not be saved.');

                $installError = true;
            }
        } elseif (! $this->registerHook('displayAdminOrder')) {
            $this->logger->logWarning('Hook « displayAdminOrder » could not be saved.');
            $this->_errors[] = $this->l('One or more hooks necessary for the module could not be saved.');

            $installError = true;
        }

        $admin_config_params = PayzenTools::getAdminParameters();

        if (defined('PAYZEN_MODULE_UPGRADE')) {
            $results = Db::getInstance()->executeS(
                'SELECT `name` FROM `' . _DB_PREFIX_ . "configuration` WHERE `name` LIKE 'PAYZEN_%' AND `name` NOT LIKE 'PAYZEN_OS_%'"
            );

            $already_installed_params = array_map(function($ar) { return $ar['name']; }, $results);
        }

        // Set default values.
        foreach ($admin_config_params as $param) {
            $key = $param['key'];

            if (! defined('PAYZEN_MODULE_UPGRADE') || ! in_array($key, $already_installed_params)) {
                if (in_array($key, PayzenTools::$multi_lang_fields)) {
                    $default = PayzenTools::convertIsoArrayToIdArray($param['default']);
                } elseif (is_array($param['default'])) {
                    $default = serialize($param['default']);
                } elseif (defined('PAYZEN_TRANSIENT_DEFAULT')) {
                    $defaults = unserialize(PAYZEN_TRANSIENT_DEFAULT);
                    $default = isset($defaults[$key]) ? $defaults[$key] : $param['default'];
                } else {
                    $default = $param['default'];
                }

                if (! Configuration::updateValue($key, $default, false, false, false)) {
                    $this->logger->logWarning("Error while saving default value for field {$key}.");
                    $this->_errors[] = sprintf($this->l('Error while saving default value for field « %s ».'), $key);

                    $installError = true;
                }
            }
        }

        // Delete already saved and not used params.
        if (defined('PAYZEN_MODULE_UPGRADE') && ! empty($already_installed_params)) {
            $params_to_be_installed = array_map(function($ar) { return $ar['key']; }, $admin_config_params);
            foreach ($already_installed_params as $param) {
                if (! in_array($param, $params_to_be_installed) && ! Configuration::deleteByName($param)) {
                    $this->logger->logWarning("Error while deleting already saved and not used configuration parameter $param.");
                    $installError = true;
                }
            }
        }

        // Create custom order states.
        if (PayzenTools::$plugin_features['oney'] && ! Configuration::get('PAYZEN_OS_ONEY_PENDING')) {
            // Create Oney pending confirmation order state.
            $name = array(
                'en' => 'Funding request in progress',
                'fr' => 'Demande de financement en cours',
                'de' => 'Finanzierungsanfrage im Gange',
                'es' => 'Solicitud de financiación en curso'
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

            if (! $oney_state->save() || ! Configuration::updateValue('PAYZEN_OS_ONEY_PENDING', $oney_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «Funding request in progress».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), $this->l('Funding request in progress'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_oney.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_ONEY_PENDING') . '.gif'
            );
        }

        if (! Configuration::get('PAYZEN_OS_TO_VALIDATE')) {
            // Create to validate payment order state.
            $name = array(
                'en' => 'To validate payment',
                'fr' => 'Paiement à valider',
                'de' => 'Um zu überprüfen Zahlung',
                'es' => 'Para validar el pago'
            );

            $tvp_state = new OrderState();
            $tvp_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $tvp_state->invoice = false;
            $tvp_state->send_email = false;
            $tvp_state->module_name = $this->name;
            $tvp_state->color = '#FF8C00';
            $tvp_state->unremovable = true;
            $tvp_state->hidden = false;
            $tvp_state->logable = false;
            $tvp_state->delivery = false;
            $tvp_state->shipped = false;
            $tvp_state->paid = false;

            if (! $tvp_state->save() || ! Configuration::updateValue('PAYZEN_OS_TO_VALIDATE', $tvp_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «To validate payment».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), $this->l('To validate payment'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_tvp.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_TO_VALIDATE') . '.gif'
            );
        }

        if (! Configuration::get('PS_OS_OUTOFSTOCK_PAID') && ! Configuration::get('PAYZEN_OS_PAYMENT_OUTOFSTOCK')) {
            // Create a payment OK but order out of stock state.
            $name = array(
                'en' => 'On backorder (payment accepted)',
                'fr' => 'En attente de réapprovisionnement (paiement accepté)',
                'de' => 'Artikel nicht auf Lager (Zahlung eingegangen)',
                'es' => 'Pedido pendiente por falta de stock (pagado)'
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

            if (! $oos_state->save() || ! Configuration::updateValue('PAYZEN_OS_PAYMENT_OUTOFSTOCK', $oos_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «On backorder (payment accepted)».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), $this->l('On backorder (payment accepted)'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_oos.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_PAYMENT_OUTOFSTOCK') . '.gif'
            );
        }

        if (! Configuration::get('PAYZEN_OS_AUTH_PENDING')) {
            // Create payment pending authorization order state.
            $name = array(
                'en' => 'Pending authorization',
                'fr' => 'En attente d\'autorisation',
                'de' => 'Autorisierung angefragt',
                'es' => 'En espera de autorización'
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

            if (! $auth_state->save() || ! Configuration::updateValue('PAYZEN_OS_AUTH_PENDING', $auth_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «Pending authorization».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), $this->l('Pending authorization'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_auth.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_AUTH_PENDING') . '.gif'
            );
        }

        if ((PayzenTools::$plugin_features['sofort'] || PayzenTools::$plugin_features['sepa'])
            && ! Configuration::get('PAYZEN_OS_TRANS_PENDING')) {
            // Create SOFORT and SEPA pending funds order state.
            $name = array(
                'en' => 'Pending funds transfer',
                'fr' => 'En attente du transfert de fonds',
                'de' => 'Warten auf Geldtransfer',
                'es' => 'En espera de la transferencia de fondos'
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

            if (! $sofort_state->save() || ! Configuration::updateValue('PAYZEN_OS_TRANS_PENDING', $sofort_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «Pending funds transfer».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), $this->l('Pending funds transfer'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_trans.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_TRANS_PENDING') . '.gif'
            );
        }

        if (! Configuration::get('PAYZEN_OS_REFUNDED')) {
            // Create to validate payment order state.
            $name = array(
                'en' => 'Refunded with PayZen',
                'fr' => 'Remboursé avec PayZen',
                'de' => 'Rückerstattet mit PayZen',
                'es' => 'Reembolsado con PayZen'
            );

            $refund_state = new OrderState();
            $refund_state->name = PayzenTools::convertIsoArrayToIdArray($name);
            $refund_state->invoice = false;
            $refund_state->send_email = false;
            $refund_state->module_name = $this->name;
            $refund_state->color = '#ec2e15';
            $refund_state->unremovable = true;
            $refund_state->hidden = false;
            $refund_state->logable = false;
            $refund_state->delivery = false;
            $refund_state->shipped = false;
            $refund_state->paid = false;

            if (! $refund_state->save() || ! Configuration::updateValue('PAYZEN_OS_REFUNDED', $refund_state->id)) {
                $this->logger->logWarning('Error while creating customized order status «Refunded with PayZen».');

                $this->_errors[] = sprintf($this->l('Error while creating customized order status « %s ».'), sprintf($this->l('Refunded with %s'), 'PayZen'));
                $installError = true;
            }

            // Add small icon to state.
            @copy(
                _PS_MODULE_DIR_ . 'payzen/views/img/os_refund.gif',
                _PS_IMG_DIR_ . 'os/' . Configuration::get('PAYZEN_OS_REFUNDED') . '.gif'
            );
        }

        // Clear module compiled templates.
        $tpls = array(
            'redirect', 'redirect_bc', 'redirect_js',
            'iframe/redirect', 'iframe/redirect_bc', 'iframe/response', 'iframe/loader',

            'bc/payment_ancv', 'bc/payment_choozeo', 'bc/payment_fullcb', 'bc/payment_multi', 'bc/payment_oney',
            'bc/payment_oney34','bc/payment_paypal', 'bc/payment_sepa', 'bc/payment_sofort', 'bc/payment_std_eu',
            'bc/payment_std_iframe', 'bc/payment_std', 'bc/payment_std_rest', 'bc/payment_franfinance',

            'payment_choozeo', 'payment_fullcb', 'payment_multi', 'payment_oney', 'payment_oney34',
            'payment_return', 'payment_std_iframe', 'payment_std', 'payment_std_rest', 'payment_franfinance'
        );
        foreach ($tpls as $tpl) {
            $this->context->smarty->clearCompiledTemplate($this->getTemplatePath($tpl . '.tpl'));
        }

        return ! $installError;
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

        // Delete all obsolete gateway params but not custom order states.
        $result &= Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . "configuration` WHERE `name` LIKE 'PAYZEN_%' AND `name` NOT LIKE 'PAYZEN_OS_%'"
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

        if (isset($this->context->cookie->payzenEmailSendMsg)) {
            $msg = $this->context->cookie->payzenEmailSendMsg;
            unset($this->context->cookie->payzenEmailSendMsg);
        }

        if (Tools::isSubmit('payzen_submit_admin_form')) {
            $this->postProcess();

            if (empty($this->_errors)) {
                // No error, display update ok message.
                $msg .= $this->displayConfirmation($this->l('Settings updated.'));
            } else {
                // Display errors.
                $msg .= $this->displayError(implode('<br />', $this->_errors));
            }

            $msg .= '<br />';
        } else if (Tools::getValue('submitter') === 'payzen_send_support') {
            if (Tools::getValue('sender') && Tools::getValue('subject') && Tools::getValue('message')) {
                $email = array(
                    'sender' => Tools::getValue('sender'),
                    'subject' => Tools::getValue('subject'),
                    'message' => Tools::getValue('message')
                );

                if ($this->sendEmail($email)) {
                   if (Tools::getValue('payzen_mail_origine') === 'order') {
                       // Display success message in Order details page.
                       $this->context->cookie->payzenMessageSuccessSent = $this->l('Thank you for contacting us. Your email has been successfully sent.');
                       die();
                   } else {
                       // Display success message in module backend.
                       $msg .= $this->displayConfirmation($this->l('Thank you for contacting us. Your email has been successfully sent.'));
                   }
                } else {
                    if (Tools::getValue('payzen_mail_origine') === 'order') {
                        // Display error message in Order details page.
                        $this->context->cookie->payzenMessageErrorSent = $this->l('An error has occurred. Your email was not sent.');
                        die();
                    } else {
                        $msg .= $this->displayError($this->l('An error has occurred. Your email was not sent.'));
                    }
                }

                $this->context->cookie->payzenEmailSendMsg = $msg;
            } else {
                if (Tools::getValue('payzen_mail_origine') === 'order') {
                    // Display error message in Order details page.
                    $this->context->cookie->payzenMessageErrorSent = $this->l('Please make sure to configure all required fields.');
                    die();
                } else {
                    $this->context->cookie->payzenEmailSendMsg = $this->displayError($this->l('Please make sure to configure all required fields.'));
                }
            }
        }

        return $msg . $this->renderForm();
    }

    /**
     * Send support email.
     */
    private function sendEmail($email)
    {
        return Mail::Send(
            (int) $this->context->language->id,
            'payzen', // Email template file to be use.
            $email['subject'], // Email subject.
            array(
                '{email}' => $email['sender'], // Sender email address.
                '{message}' => $email['message'] // Email content.
            ),
            PayzenTools::getDefault('SUPPORT_EMAIL'), // Receiver email address.
            NULL, // Receiver name.
            $email['sender'], // From email address.
            NULL,  // From name.
            NULL, // File attachment.
            NULL, // Mode smtp.
            _PS_MODULE_DIR_ . $this->name . '/mails/' // Custom template path.
        );
    }

    /**
     * Validate and save module admin parameters.
     */
    private function postProcess()
    {
        $request = new PayzenRequest(); // New instance of PayzenRequest for parameters validation.

        // Load and validate from request.
        foreach (PayzenTools::getAdminParameters() as $param) {
            $key = $param['key']; // PrestaShop parameter key.

            if (! Tools::getIsset($key)) {
                // If field is disabled, don't save it.
                continue;
            }

            $label = $this->l($param['label'], 'back_office'); // Translated human-readable label.
            $name = isset($param['name']) ? $param['name'] : null; // Gateway API parameter name.

            $value = Tools::getValue($key, null);

            // Load countries restriction list.
            $isCountriesList = (Tools::substr($key, -12) === '_COUNTRY_LST');

            if (in_array($key, PayzenTools::$multi_lang_fields)) {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                }
            } elseif (in_array($key, PayzenTools::$group_amount_fields)) {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (($key === 'PAYZEN_CHOOZEO_OPTIONS') && ! isset($option['enabled'])) {
                            $value[$id]['enabled'] = 'False';
                        }

                        if (isset($option['min_amount']) && $option['min_amount'] && (! is_numeric($option['min_amount']) || $option['min_amount'] < 0)) {
                            $value[$id]['min_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }

                        if (isset($option['max_amount']) && $option['max_amount'] && (! is_numeric($option['max_amount']) || $option['max_amount'] < 0)) {
                            $value[$id]['max_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_MULTI_OPTIONS') {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (! is_numeric($option['count'])
                                || ! is_numeric($option['period'])
                                || ($option['first'] && (! is_numeric($option['first']) || $option['first'] < 0 || $option['first'] > 100))) {
                            unset($value[$id]); // Error, do not save this option.
                            $error = true;
                        } else {
                            $default = is_string($option['label']) && $option['label'] ?
                                $option['label'] : $option['count'] . ' x';
                            $option_label = is_array($option['label']) ? $option['label'] : array();

                            foreach (Language::getLanguages(false) as $language) {
                                $lang = $language['id_lang'];
                                if (! isset($option_label[$lang]) || empty($option_label[$lang])) {
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
                $value = (is_array($value) && ! empty($value)) ? implode(';', $value) : '';
            } elseif ($key === 'PAYZEN_STD_PAYMENT_CARDS' || $key === 'PAYZEN_MULTI_PAYMENT_CARDS') {
                if (! is_array($value) || in_array('', $value)) {
                    $value = array();
                }

                $value = implode(';', $value);
                if (Tools::strlen($value) > 127) {
                    $this->_errors[] = $this->l('Too many card types are selected.');
                    continue;
                }

                $name = 'payment_cards';
            } elseif ($isCountriesList) {
                if (! is_array($value) || in_array('', $value)) {
                    $value = array();
                }

                $value = implode(';', $value);
            } elseif ($key === 'PAYZEN_ONEY_SHIP_OPTIONS') {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    foreach ($value as $id => $option) {
                        if ($option['type'] === 'RECLAIM_IN_SHOP') {
                            $address = ($option['address'] ? ' ' . $option['address'] : '') . ($option['zip'] ? ' ' . $option['zip'] : '')
                                . ($option['city'] ? ' ' . $option['city'] : '');
                            if (! preg_match(self::DELIVERY_COMPANY_ADDRESS_REGEX, $address)) {
                                unset($value[$id]); // Error, not save this option.

                                $this->_errors[] = sprintf($this->l('The field « %1$s » is invalid: please check column « %2$s » of the option « %3$s » in section « %4$s ».'), $label, $this->l('Address'), $id, $this->l('ADDITIONAL OPTIONS'))
                                    . ' ' . sprintf($this->l('Use %1$d alphanumeric characters, accentuated characters and these special characters: space, slash, hyphen, apostrophe.'), 65);
                            }
                        }
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_CATEGORY_MAPPING') {
                if (Tools::getValue('PAYZEN_COMMON_CATEGORY', null) !== 'CUSTOM_MAPPING') {
                    continue;
                }

                if (! is_array($value) || empty($value)) {
                    $value = array();
                }

                $value = serialize($value);
            } elseif (($key === 'PAYZEN_ONEY34_ENABLED') && ($value === 'True')) {
                $error = $this->validateOney();

                if (is_string($error) && ! empty($error)) {
                    $this->_errors[] = $error;
                    $value = 'False'; // There is errors, not allow 3 or 4 times Oney activation.
                }
            } elseif (in_array($key, PayzenTools::$amount_fields)) {
                if (! empty($value) && (! is_numeric($value) || $value < 0)) {
                    $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $value, $label);
                    continue;
                }
            } elseif ($key === 'PAYZEN_ONEY34_OPTIONS') {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (! is_numeric($option['count']) || ! is_numeric($option['rate']) || empty($option['code'])) {
                            unset($value[$id]); // Error, do not save this option.
                            $error = true;
                        } else {
                            $default = is_string($option['label']) && $option['label'] ?
                            $option['label'] : $option['count'] . ' x';
                            $option_label = is_array($option['label']) ? $option['label'] : array();

                            foreach (Language::getLanguages(false) as $language) {
                                $lang = $language['id_lang'];
                                if (! isset($option_label[$lang]) || empty($option_label[$lang])) {
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
            } elseif ($key === 'PAYZEN_FFIN_OPTIONS') {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if ($option['min_amount'] && ! is_numeric($option['min_amount']) || $option['min_amount'] < 0) {
                            $value[$id]['min_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }

                        if ($option['max_amount'] && ! is_numeric($option['max_amount']) || $option['max_amount'] < 0) {
                            $value[$id]['max_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }

                        $default = is_string($option['label']) && $option['label'] ?
                        $option['label'] : $option['count'] . ' x';
                        $option_label = is_array($option['label']) ? $option['label'] : array();

                        foreach (Language::getLanguages(false) as $language) {
                            $lang = $language['id_lang'];
                            if (! isset($option_label[$lang]) || empty($option_label[$lang])) {
                                $option_label[$lang] = $default;
                            }
                        }

                        $value[$id]['label'] = $option_label;
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif (($key === 'PAYZEN_FULLCB_OPTIONS') && (Tools::getValue('PAYZEN_FULLCB_ENABLE_OPTS', 'False') === 'True')) {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        if (! isset($option['enabled'])) {
                            $value[$id]['enabled'] = 'False';
                        }

                        if ($option['min_amount'] && ! is_numeric($option['min_amount']) || $option['min_amount'] < 0) {
                            $value[$id]['min_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }

                        if ($option['max_amount'] && ! is_numeric($option['max_amount']) || $option['max_amount'] < 0) {
                            $value[$id]['max_amount'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }

                        if (! is_numeric($option['rate']) || $option['rate'] < 0 || $option['rate'] > 100) {
                            $value[$id]['rate'] = '0'; // Error, reset incorrect value.
                            $error = true;
                        }

                        if (! is_numeric($option['cap']) || $option['cap'] < 0) {
                            $value[$id]['cap'] = ''; // Error, reset incorrect value.
                            $error = true;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_OTHER_PAYMENT_MEANS') {
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    $used_cards = array();
                    $titles = array(
                        'fr' => 'Paiement avec %s',
                        'en' => 'Payment with %s',
                        'de' => 'Zahlung mit %s',
                        'es' => 'Pago con %s'
                    );

                    $cards = PayzenApi::getSupportedCardTypes();

                    // Add extra means of payment to supported payment means.
                    $extra_cards = @unserialize(Configuration::get('PAYZEN_EXTRA_PAYMENT_MEANS'));
                    foreach ($extra_cards as $option_card) {
                        if (! isset($cards[$option_card['code']])) {
                            $cards[$option_card['code']] = $option_card['title'];
                        }
                    }

                    foreach ($value as $id => $option) {
                        if (in_array($option['code'], $used_cards)) {
                            unset($value[$id]);
                            continue;
                        } else {
                            $used_cards[] = $option['code'];
                        }

                        if (($option['min_amount'] && ! is_numeric($option['min_amount']))
                            || $option['min_amount'] < 0
                            || ($option['max_amount'] && ! is_numeric($option['max_amount']))
                            || $option['max_amount'] < 0
                            || ($option['min_amount'] && ($option['max_amount'] && $option['min_amount'] > $option['max_amount']))
                            || ($option['capture'] && ! is_numeric($option['capture']))) {
                            unset($value[$id]); // Error, do not save this option.
                            $error = true;
                        } else {
                            $selected_card = isset($cards[$option['code']]) ? $cards[$option['code']] : $option['code'];
                            $option_title = is_array($option['title']) ? $option['title'] : array();

                            foreach (Language::getLanguages(false) as $language) {
                                $lang = $language['id_lang'];
                                $iso = $language['iso_code'];
                                $default = isset($titles[$iso]) ? $titles[$iso] : $titles['en'];

                                if (! isset($option_title[$lang]) || empty($option_title[$lang])) {
                                    $option_title[$lang] = is_string($option['title']) && $option['title'] ?
                                        $option['title'] : sprintf($default, $selected_card);
                                }
                            }

                            $value[$id]['title'] = $option_title;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_EXTRA_PAYMENT_MEANS') {
                $used_cards = array_keys(PayzenApi::getSupportedCardTypes());
                if (! is_array($value) || empty($value)) {
                    $value = array();
                } else {
                    $error = false;
                    foreach ($value as $id => $option) {
                        $code = trim($option['code']);
                        $title = $option['title'];
                        if (empty($code)
                            || ! preg_match('#^[A-Za-z0-9\-_]+$#', $code)
                            || empty($title)
                            || ! preg_match('#^[^<>]*$#', $title)
                            || in_array($code, $used_cards)) {
                            // Invalid format of code or title, or code already exists: delete this means of payment and display error.
                            unset($value[$id]);
                            $error = true;
                        } else {
                            $used_cards[] = $code;
                            // Update payment means code (to apply trim).
                            $value[$id]['code'] = $code;
                        }
                    }

                    if ($error) {
                        $this->_errors[] = sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                    }
                }

                // Update PAYZEN_OTHER_PAYMENT_MEANS (options containing deleted payment means should not appear).
                $other_payment_means = @unserialize(Configuration::get('PAYZEN_OTHER_PAYMENT_MEANS'));
                foreach ($other_payment_means as $key_payment_mean => $option_payment_mean) {
                    if (! in_array($option_payment_mean['code'], $used_cards)) {
                        unset($other_payment_means[$key_payment_mean]);
                    }
                }

                Configuration::updateValue('PAYZEN_OTHER_PAYMENT_MEANS', serialize($other_payment_means));

                $value = serialize($value);
            } elseif ($key === 'PAYZEN_STD_REST_PLACEHLDR') {
                $value = serialize($value);
            } elseif ($key === 'PAYZEN_STD_REST_ATTEMPTS') {
                if ($value && (! is_numeric($value) || $value < 0 || $value > 10)) {
                    $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $value, $label);
                    continue;
                }
            } elseif ($key === 'PAYZEN_REST_SERVER_URL' || $key === 'PAYZEN_REST_JS_CLIENT_URL') {
                if (! preg_match('#^https?://([^/]+/)+$#u', $value)) {
                    if (empty($value)) {
                        $this->_errors[] = sprintf($this->l('The field « %s » is mandatory.'), $label);
                    } else {
                        $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $value, $label);
                    }

                    continue;
                }
            }

            // Validate with PayzenRequest.
            if ($name && ($name !== 'theme_config')) {
                $values = is_array($value) ? $value : array($value); // To check multilingual fields.
                $error = false;

                foreach ($values as $v) {
                    if (! $request->set($name, $v)) {
                        $error = true;
                        if (empty($v)) {
                            if ($name !== 'shop_url') {
                               $this->_errors[] = sprintf($this->l('The field « %s » is mandatory.'), $label);
                            } else {
                                $error = false;
                            }
                        } else {
                            $this->_errors[] = sprintf($this->l('Invalid value « %1$s » for field « %2$s ».'), $v, $label);
                        }
                    }
                }

                if ($error) {
                    continue; // Do not save fields with errors.
                }
            }

            // Valid field: try to save into DB.
            if (! Configuration::updateValue($key, $value)) {
                $this->_errors[] = sprintf($this->l('Problem occurred while saving field « %s ».'), $label);
            } else {
                // Temporary variable set to update PrestaShop cache.
                Configuration::set($key, $value);
            }
        }
    }

    private function validateOney($inside = false)
    {
        $label = $this->l('Payment in 3 or 4 times Oney', 'payzenoney34payment');

        if (Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            return sprintf($this->l('Multishipping is activated. %s cannot be used.'), $label);
        }

        if (! $inside) {
            $key = 'PAYZEN_ONEY34_AMOUNTS';
            $group_amounts = Tools::getValue($key);

            $default_min = $group_amounts[0]['min_amount'];
            $default_max = $group_amounts[0]['max_amount'];

            if (empty($default_min) || empty($default_max)) {
                return sprintf($this->l('Please, enter minimum and maximum amounts in %s tab as agreed with Banque Accord.'), $label);
            }

            $label = sprintf($this->l('%s - Customer group amount restriction'), $label);

            foreach ($group_amounts as $id => $group) {
                if (empty($group) || $id === 0) { // All groups.
                    continue;
                }

                $min_amount = $group['min_amount'];
                $max_amount = $group['max_amount'];
                if (($min_amount && $min_amount < $default_min) || ($max_amount && $max_amount > $default_max)) {
                    return sprintf($this->l('One or more values are invalid for field « %s ». Only valid entries are saved.'), $label);
                }
            }

            if (! Tools::getValue('PAYZEN_ONEY34_OPTIONS')) {
                return sprintf($this->l('The field « %s » is mandatory.'), $this->l('Payment in 3 or 4 times Oney - Payment options', 'back_office'));
            }
        }

        return true;
    }

    private function renderForm()
    {
        $this->addJS('payzen.js');

        if (PayzenTools::$plugin_features['support']) {
            $this->addJs('support.js');
        }

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

        $this->context->smarty->assign(PayzenHelperForm::getAdminFormContext());
        $form = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'payzen/views/templates/admin/back_office.tpl');

        $prefered_post_vars = 0;
        $prefered_post_vars += substr_count($form, 'name="PAYZEN_');
        $prefered_post_vars += 100; // To take account of dynamically created inputs.

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
                // Process errors from other pages.
                $controller->errors = array_merge(
                    $controller->errors,
                    explode("\n", $this->context->cookie->payzenPayErrors)
                );
                unset($this->context->cookie->payzenPayErrors);

                // Unset HTTP_REFERER from global server variable to avoid back link display in error message.
                $_SERVER['HTTP_REFERER'] = null;
                $this->context->smarty->assign('server', $_SERVER);
            }

            // Add main module CSS.
            $this->addCss('payzen.css');

            $html = '';

            $standard = new PayzenStandardPayment();
            if ($standard->isAvailable($this->context->cart)) {
                if ($standard->isEmbedded()) {
                    $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
                    $pub_key = $test_mode ? Configuration::get('PAYZEN_PUBKEY_TEST') :
                        Configuration::get('PAYZEN_PUBKEY_PROD');

                    // URL where to redirect after payment.
                    $return_url = $this->context->link->getModuleLink('payzen', 'rest', array(), true);

                    // Current language or default if not supported.
                    $language = Language::getLanguage((int) $this->context->cart->id_lang);
                    $language_iso_code = Tools::strtolower($language['iso_code']);
                    if (! PayzenApi::isSupportedLanguage($language_iso_code)) {
                        $language_iso_code = Configuration::get('PAYZEN_DEFAULT_LANGUAGE');
                    }

                    $html .= '<script>
                                var PAYZEN_LANGUAGE = "' . $language_iso_code . '";
                              </script>';

                    $html .= '<script src="' . Configuration::get('PAYZEN_REST_JS_CLIENT_URL') . 'js/krypton-client/V4.0/stable/kr-payment-form.min.js"
                                      kr-public-key="' . $pub_key . '"
                                      kr-post-url-success="' . $return_url . '"
                                      kr-post-url-refused="' . $return_url . '"
                                      kr-language="' . $language_iso_code . '"
                                      kr-label-do-register="' . Configuration::get('PAYZEN_STD_REST_LBL_REGIST', $language['id_lang']) . '"';

                    $rest_placeholders = @unserialize(Configuration::get('PAYZEN_STD_REST_PLACEHLDR'));
                    if ($pan_label = $rest_placeholders['pan'][$language['id_lang']]) {
                        $html .= ' kr-placeholder-pan="' . $pan_label . '"';
                    }

                    if ($expiry_label = $rest_placeholders['expiry'][$language['id_lang']]) {
                        $html .= ' kr-placeholder-expiry="' . $expiry_label . '"';
                    }

                    if ($cvv_label = $rest_placeholders['cvv'][$language['id_lang']]) {
                        $html .= ' kr-placeholder-security-code="' . $cvv_label . '"';
                    }

                    $html .= '></script>' . "\n";

                    // Theme and plugins, should be loaded after the javascript library.
                    $rest_theme = Configuration::get('PAYZEN_STD_REST_THEME') ? Configuration::get('PAYZEN_STD_REST_THEME') : 'material';
                    $html .= '<link rel="stylesheet" href="' . Configuration::get('PAYZEN_REST_JS_CLIENT_URL') . 'js/krypton-client/V4.0/ext/' . $rest_theme . '-reset.css">
                              <script src="' . Configuration::get('PAYZEN_REST_JS_CLIENT_URL') . 'js/krypton-client/V4.0/ext/' . $rest_theme . '.js"></script>';

                    $this->context->smarty->assign('payzen_rest_theme', $rest_theme);

                    $page = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';

                    Media::addJsDef(array('payzen' => array('restUrl' => $return_url, 'pageType' => $page)));
                    $this->addJS('rest.js');
                }
            }

            // Add backward compatibility module CSS.
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $this->addCss('payzen_bc.css');

                // Load payment module style to apply it to our tag.
                if ($this->useMobileTheme()) {
                    $css_file = _PS_THEME_MOBILE_DIR_ . 'css/global.css';
                } else {
                    $css_file = _PS_THEME_DIR_ . 'css/global.css';
                }

                $css = Tools::file_get_contents(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $css_file));

                $matches = array();
                $res = preg_match_all('#(p\.payment_module(?:| a| a\:hover) ?\{[^\}]+\})#i', $css, $matches);
                if ($res && ! empty($matches) && isset($matches[1]) && is_array($matches[1]) && ! empty($matches[1])) {
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
            return ($this->context->getMobileDevice() && file_exists(_PS_THEME_MOBILE_DIR_ . 'layout.tpl'));
        }

        return false;
    }

    private function addJs($js_file)
    {
        $controller = $this->context->controller;

        if (method_exists($controller, 'registerJavascript')) { // PrestaShop 1.7.
            $controller->registerJavascript(
                'module-payzen',
                'modules/' . $this->name . '/views/js/' . $js_file,
                array('position' => 'bottom', 'priority' => 150)
            );
        } else {
            $controller->addJs($this->_path . 'views/js/' . $js_file);
        }
    }

    private function addCss($css_file)
    {
        $controller = $this->context->controller;

        if (method_exists($controller, 'registerStylesheet')) { // PrestaShop 1.7.
            $controller->registerStylesheet(
                'module-payzen-' . basename($css_file, '.png'),
                'modules/' . $this->name . '/views/css/' . $css_file,
                array('media' => 'all', 'priority' => 90)
            );
        } else {
            $controller->addCss($this->_path . 'views/css/' . $css_file, 'all');
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
        if (! $this->active) {
            return;
        }

        if (! $this->checkCurrency()) {
            return;
        }

        $cart = $this->context->cart;

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            $payment_options = array(
                'cta_text' => $standard->getTitle((int) $cart->id_lang),
                'logo' => $this->_path . 'views/img/' . $standard->getLogo(),
                'form' => $this->display(__FILE__, 'bc/payment_std_eu.tpl')
            );

            return $payment_options;
        }
    }

    /**
     * Payment function, display payment buttons/forms for all submodules.
     *
     * @param array $params
     * @return void|string
     */
    public function hookPayment($params)
    {
        if (! $this->active) {
            return;
        }

        // Currency support.
        if (! $this->checkCurrency()) {
            return;
        }

        $cart = $this->context->cart;
        $customer = new Customer((int) $cart->id_customer);
        $customersConfig = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));

        // Version tag for specific styles.
        $tag = version_compare(_PS_VERSION_, '1.6', '<') ? 'payzen15' : 'payzen16';
        $this->context->smarty->assign('payzen_tag', $tag);

        $html = '';

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            if ($standard->isOneClickActive()) {
                $savedStdIdentifier = isset($customersConfig[$cart->id_customer]['standard']['n']) ? $customersConfig[$cart->id_customer]['standard']['n'] : '';
                // Check std saved alias.
                $standard->setCookieValidPaymentByAlias($savedStdIdentifier, $customer);
            }

            $this->context->smarty->assign($standard->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $standard->getTplName());
        }

        $multi = new PayzenMultiPayment();
        if ($multi->isAvailable($cart)) {
            $this->context->smarty->assign($multi->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $multi->getTplName());
        }

        $choozeo = new PayzenChoozeoPayment();
        if ($choozeo->isAvailable($cart)) {
            $this->context->smarty->assign($choozeo->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $choozeo->getTplName());
        }

        $oney34 = new PayzenOney34Payment();
        if ($oney34->isAvailable($cart)) {
            $this->context->smarty->assign($oney34->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $oney34->getTplName());
        }

        $franfinance = new PayzenFranfinancePayment();
        if ($franfinance->isAvailable($cart)) {
            $this->context->smarty->assign($franfinance->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $franfinance->getTplName());
        }

        $fullcb = new PayzenFullcbPayment();
        if ($fullcb->isAvailable($cart)) {
            $this->context->smarty->assign($fullcb->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $fullcb->getTplName());
        }

        $ancv = new PayzenAncvPayment();
        if ($ancv->isAvailable($cart)) {
            $this->context->smarty->assign($ancv->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $ancv->getTplName());
        }

        $sepa = new PayzenSepaPayment();
        if ($sepa->isAvailable($cart)) {
            if ($sepa->isOneClickActive()) {
                $savedSepaIdentifier = isset($customersConfig[$cart->id_customer]['sepa']['n']) ? $customersConfig[$cart->id_customer]['sepa']['n'] : '';
                // Check sepa saved alias.
                $sepa->setCookieValidPaymentByAlias($savedSepaIdentifier, $customer);
            }

            $this->context->smarty->assign($sepa->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $sepa->getTplName());
        }

        $paypal = new PayzenPaypalPayment();
        if ($paypal->isAvailable($cart)) {
            $this->context->smarty->assign($paypal->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $paypal->getTplName());
        }

        $sofort = new PayzenSofortPayment();
        if ($sofort->isAvailable($cart)) {
            $this->context->smarty->assign($sofort->getTplVars($cart));
            $html .= $this->display(__FILE__, 'bc/' . $sofort->getTplName());
        }

        $other_payments = PayzenOtherPayment::getAvailablePaymentMeans($cart);
        if (Configuration::get('PAYZEN_OTHER_GROUPED_VIEW') === 'True' && count($other_payments) > 1) {
            $grouped = new PayzenGroupedOtherPayment();
            $grouped->setPaymentMeans($other_payments);

            if ($grouped->isAvailable($cart)) {
                $this->context->smarty->assign($grouped->getTplVars($cart));
                $html .= $this->display(__FILE__, 'bc/' . $grouped->getTplName());
            }
        } else {
            foreach ($other_payments as $option) {
                $other = new PayzenOtherPayment();
                $other->init($option['code'], $option['title'], $option['min_amount'], $option['max_amount']);

                if ($other->isAvailable($cart)) {
                    $this->context->smarty->assign($other->getTplVars($cart));
                    $html .= $this->display(__FILE__, 'bc/' . $other->getTplName());
                }
            }
        }

        return $html;
    }

    /**
     * Payment function, display payment buttons/forms for all submodules in PrestaShop 1.7+.
     *
     * @param array $params
     * @return void|array[\PrestaShop\PrestaShop\Core\Payment\PaymentOption]
     */
    public function hookPaymentOptions($params)
    {
        if (! $this->active) {
            return array();
        }

        if (! $this->checkCurrency()) {
            return array();
        }

        $cart = $this->context->cart;
        $customer = new Customer((int) $cart->id_customer);
        $customersConfig = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));

        /**
         * @var array[\PrestaShop\PrestaShop\Core\Payment\PaymentOption]
         */
        $options = array();

        // Version tag for specific styles.
        $this->context->smarty->assign('payzen_tag', 'payzen17');

        /**
         * AbstractPayzenPayment::getPaymentOption() returns a payment option of type
         * \PrestaShop\PrestaShop\Core\Payment\PaymentOption
         */

        $standard = new PayzenStandardPayment();
        if ($standard->isAvailable($cart)) {
            $option = $standard->getPaymentOption($cart);

            // Payment by identifier.
            $additionalForm = '';
            $oneClickPayment = false;
            if ($standard->isOneClickActive()) {
                $savedStdIdentifier = isset($customersConfig[$cart->id_customer]['standard']['n']) ? $customersConfig[$cart->id_customer]['standard']['n'] : '';
                $isStandardValidAlias = $standard->setCookieValidPaymentByAlias($savedStdIdentifier, $customer);
                if ($isStandardValidAlias) {
                    $oneClickPayment = true;
                    $this->context->smarty->assign($standard->getTplVars($cart));
                    $additionalForm = $this->fetch('module:payzen/views/templates/hook/payment_std_oneclick.tpl');
                    $option->setAdditionalInformation($additionalForm);
                }
            }

            if ($standard->hasForm() || $oneClickPayment) {
                if (! $oneClickPayment) {
                    $this->context->smarty->assign($standard->getTplVars($cart));
                }

                $form = $this->fetch('module:payzen/views/templates/hook/' . $standard->getTplName());
                $isRestPayment = strpos($standard->getTplName(), 'rest'); // Check if it's really a payment by embedded fields.
                $isIframePayment = strpos($standard->getTplName(), 'iframe');

                if ($isIframePayment || ($standard->isEmbedded() && $isRestPayment)) {
                    // Iframe or REST mode.
                    $option->setAdditionalInformation($form . $additionalForm);
                    if ($oneClickPayment) {
                        $option->setForm('<form id="payzen_standard" onsubmit="javascript: payzenSubmit(event);"><input id="payzen_payment_by_identifier" type="hidden" name="payzen_payment_by_identifier" value="1" /></form>');
                    } else {
                        $option->setForm('<form id="payzen_standard" onsubmit="javascript: payzenSubmit(event);"></form>');
                    }
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
                $form = $this->fetch('module:payzen/views/templates/hook/' . $multi->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $choozeo = new PayzenChoozeoPayment();
        if ($choozeo->isAvailable($cart)) {
            $option = $choozeo->getPaymentOption($cart);

            if ($choozeo->hasForm()) {
                $this->context->smarty->assign($choozeo->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/' . $choozeo->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $oney34 = new PayzenOney34Payment();
        if ($oney34->isAvailable($cart)) {
            $option = $oney34->getPaymentOption($cart);

            if ($oney34->hasForm()) {
                $this->context->smarty->assign($oney34->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/' . $oney34->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $franfinance = new PayzenFranfinancePayment();
        if ($franfinance->isAvailable($cart)) {
            $option = $franfinance->getPaymentOption($cart);

            if ($franfinance->hasForm()) {
                $this->context->smarty->assign($franfinance->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/' . $franfinance->getTplName());
                $option->setForm($form);
            }

            $options[] = $option;
        }

        $fullcb = new PayzenFullcbPayment();
        if ($fullcb->isAvailable($cart)) {
            $option = $fullcb->getPaymentOption($cart);

            if ($fullcb->hasForm()) {
                $this->context->smarty->assign($fullcb->getTplVars($cart));
                $form = $this->fetch('module:payzen/views/templates/hook/' . $fullcb->getTplName());
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
            $option = $sepa->getPaymentOption($cart);

            if ($sepa->isOneClickActive()) {
                $savedSepaIdentifier = isset($customersConfig[$cart->id_customer]['sepa']['n']) ? $customersConfig[$cart->id_customer]['sepa']['n'] : '';
                $isSepaValidAlias = $sepa->setCookieValidPaymentByAlias($savedSepaIdentifier, $customer);
                if ($isSepaValidAlias) {
                    $this->context->smarty->assign($sepa->getTplVars($cart));
                    $additionalForm = $this->fetch('module:payzen/views/templates/hook/payment_sepa_oneclick.tpl');
                    $option->setAdditionalInformation($additionalForm);
                    $option->setForm(
                        '<form action="' . $this->context->link->getModuleLink('payzen', 'redirect', array(), true) . '" method="post">
                          <input type="hidden" name="payzen_payment_type" value="sepa" />
                          <input id="payzen_sepa_payment_by_identifier" type="hidden" name="payzen_sepa_payment_by_identifier" value="1" />
                         </form>'
                    );
                }
            }

            $options[] = $option;
        }

        $paypal = new PayzenPaypalPayment();
        if ($paypal->isAvailable($cart)) {
            $options[] = $paypal->getPaymentOption($cart);
        }

        $sofort = new PayzenSofortPayment();
        if ($sofort->isAvailable($cart)) {
            $options[] = $sofort->getPaymentOption($cart);
        }

        $other_payments = PayzenOtherPayment::getAvailablePaymentMeans($cart);
        if (Configuration::get('PAYZEN_OTHER_GROUPED_VIEW') === 'True' && count($other_payments) > 1) {
            $grouped = new PayzenGroupedOtherPayment();
            $grouped->setPaymentMeans($other_payments);

            if ($grouped->isAvailable($cart)) {
                $option = $grouped->getPaymentOption($cart);

                if ($grouped->hasForm()) {
                    $this->context->smarty->assign($grouped->getTplVars($cart));
                    $form = $this->fetch('module:payzen/views/templates/hook/' . $grouped->getTplName());
                    $option->setForm($form);
                }

                $options[] = $option;
            }
        } else {
            foreach ($other_payments as $option) {
                $other = new PayzenOtherPayment();
                $other->init($option['code'], $option['title'], $option['min_amount'], $option['max_amount']);

                if ($other->isAvailable($cart)) {
                    $options[] = $other->getPaymentOption($cart);
                }
            }
        }

        return $options;
    }

    private function checkCurrency()
    {
        $cart = $this->context->cart;

        $cart_currency = new Currency((int) $cart->id_currency);
        $currencies = $this->getCurrency((int) $cart->id_currency);

        if (! is_array($currencies) || empty($currencies)) {
            return false;
        }

        foreach ($currencies as $currency) {
            if ($cart_currency->id == $currency['id_currency']) {
                // Cart currency is allowed for this module.
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

        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        $error = (Tools::getValue('error') === 'yes');
        $amount_error = (Tools::getValue('amount_error') === 'yes');

        $array = array(
            'check_url_warn' => (Tools::getValue('check_url_warn') === 'yes'),
            'maintenance_mode' => ! Configuration::get('PS_SHOP_ENABLE'),
            'prod_info' => (Tools::getValue('prod_info') === 'yes'),
            'error_msg' => $error,
            'amount_error_msg' => $amount_error
        );

        if (! $error) {
            $array['total_to_pay'] = PayzenTools::formatPrice(
                $order->total_paid_real,
                $order->id_currency,
                $this->context
            );

            $array['id_order'] = $order->id;
            $array['status'] = 'ok';
            $array['shop_name'] = Configuration::get('PS_SHOP_NAME');

            if (isset($order->reference) && ! empty($order->reference)) {
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
        if (isset($this->context->cookie->payzenRefundWarn)) {
            $this->context->controller->warnings[] = $this->context->cookie->payzenRefundWarn;
            unset($this->context->cookie->payzenRefundWarn);
        }
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $order = new Order((int) $params['id_order']);
        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        $url_admin_orders = $this->context->link->getAdminLink('AdminOrders');
        $url_admin_order = str_replace('/?_token=', '/' . $order->id . '/view?_token=', $url_admin_orders);

        if (isset($this->context->cookie->payzenMessageErrorSent)) {
            $this->get('session')->getFlashBag()->set('error', $this->context->cookie->payzenMessageErrorSent);
            unset($this->context->cookie->payzenMessageErrorSent);
            $this->context->cookie->write();

            Tools::redirectAdmin($url_admin_order);
        }

        if (isset($this->context->cookie->payzenMessageSuccessSent)) {
            $this->get('session')->getFlashBag()->set('success', $this->context->cookie->payzenMessageSuccessSent);
            unset($this->context->cookie->payzenMessageSuccessSent);
            $this->context->cookie->write();

            Tools::redirectAdmin($url_admin_order);
        }

        return $this->displayRefundOnlineCheckbox() . $this->displaySupportContactFromOrderDetails($order);
    }

    public function hookDisplayAdminOrder($params)
    {
        $order = new Order((int) $params['id_order']);
        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        if (isset($this->context->cookie->payzenMessageErrorSent)) {
            $this->context->controller->errors[] = $this->context->cookie->payzenMessageErrorSent;
            unset($this->context->cookie->payzenMessageErrorSent);
        }

        if (isset($this->context->cookie->payzenMessageSuccessSent)) {
            $this->context->controller->confirmations[] = $this->context->cookie->payzenMessageSuccessSent;
            unset($this->context->cookie->payzenMessageSuccessSent);
        }

        return $this->displayRefundOnlineCheckbox(true) . $this->displaySupportContactFromOrderDetails($order);
    }

    private function displayRefundOnlineCheckbox($isBackwardCompatibility = false)
    {
        $template = _PS_MODULE_DIR_ . 'payzen/views/templates/admin/';

        if ($isBackwardCompatibility) {
            $template .= 'refund_bc.tpl';
        } else {
            $template .= 'refund.tpl';
        }

        return $this->context->smarty->fetch($template);
    }

    private function displaySupportContactFromOrderDetails($order)
    {
        if (! PayzenTools::$plugin_features['support']) {
            return '';
        }

        $this->addJs('support.js');

        $tpl_vars = PayzenHelperForm::getAdminFormContext();
        $tpl_vars['trans_id_title'] = $this->l('Transaction UUID : ');
        $tpl_vars['payzen_site_id'] = Configuration::get('PAYZEN_SITE_ID', null, $order->id_shop_group, $order->id_shop);
        $tpl_vars['payzen_mode'] = Configuration::get('PAYZEN_MODE', null, $order->id_shop_group, $order->id_shop);
        $tpl_vars['payzen_sign_algo'] = Configuration::get('PAYZEN_SIGN_ALGO', null, $order->id_shop_group, $order->id_shop);

        $card_data_mode = Configuration::get('PAYZEN_STD_CARD_DATA_MODE', null, $order->id_shop_group, $order->id_shop);
        $tpl_vars['payzen_std_card_data_mode'] = $card_data_mode ? $card_data_mode : '1';
        $tpl_vars['id_cart'] = $order->id_cart;
        $tpl_vars['order_reference'] = $order->reference;
        $order_status = $order->getCurrentStateFull($this->context->language->id);
        $tpl_vars['order_status'] = $order_status['name'];
        $tpl_vars['date_add'] = $order->date_add;
        $tpl_vars['total_paid'] = PayzenTools::formatPrice($order->total_paid, $order->id_currency, $this->context);
        $tpl_vars['total_products_wt'] = PayzenTools::formatPrice($order->total_products_wt, $order->id_currency, $this->context);
        $tpl_vars['total_shipping'] = PayzenTools::formatPrice($order->total_shipping, $order->id_currency, $this->context);
        $tpl_vars['total_discounts'] = PayzenTools::formatPrice($order->total_discounts, $order->id_currency, $this->context);

        // Recover carrier name.
        $tpl_vars['order_carrier'] = '';
        foreach ($tpl_vars['prestashop_carriers'] as $carrier) {
            if ($carrier['id_carrier'] === $order->id_carrier) {
                $tpl_vars['order_carrier'] = $carrier['name'];
                break;
            }
        }

        $tpl_vars['id_carrier'] = $order->id_carrier;

        // Recover POST uri.
        $payzen_request_uri = $this->context->link->getAdminLink('AdminModules');
        $payzen_request_uri = str_replace('&token=', '&configure=payzen&token=', $payzen_request_uri);
        $tpl_vars['payzen_request_uri'] = $payzen_request_uri;
        $this->context->smarty->assign($tpl_vars);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'payzen/views/templates/admin/support_contact_from_order_details.tpl');
    }

    /**
     *  Before updating order status.
     *
     * @param array $params
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order((int) $params['id_order']);
        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        // It is an IPN, no online refund.
        if (PayzenTools::checkFormIpnValidity() || PayzenTools::checkRestIpnValidity()) {
            return;
        }

        $managedRefundStatuses = array((int) Configuration::get('PAYZEN_OS_REFUNDED'), (int) Configuration::get('PS_OS_CANCELED'));
        if (! in_array($params['newOrderStatus']->id, $managedRefundStatuses)) {
            return;
        }

        if ($order->total_paid_real <= 0) {
            // Order already cancelled or refunded.
            $this->logger->logInfo("Order #{$order->id} was already cancelled or refunded.");
            return;
        }

        // Update order status is manually changed to "Refunded with PayZen or Canceled" (not by refund function).
        $this->context->cookie->payzenManualUpdateToManagedRefundStatuses = 'True';

        // If any error during WS refund/cancel redirect to order details to avoid display success message.
        if (! $this->refund($order, $order->total_paid_real)) {
            if (Tools::isSubmit('token')) {
                // PrestaShop versions < 1.7.7.
                Tools::redirectAdmin(AdminController::$currentIndex . '&id_order=' . $order->id . '&vieworder&token=' . Tools::getValue('token'));
            } else {
                // Display warning to customer if any for PrestaShop versions >= 1.7.7.
                if (isset($this->context->cookie->payzenRefundWarn)) {
                    $this->get('session')->getFlashBag()->set('warning', $this->context->cookie->payzenRefundWarn);
                    unset($this->context->cookie->payzenRefundWarn);
                }

                // PrestaShop versions >= 1.7.7.
                $url_admin_orders = $this->context->link->getAdminLink('AdminOrders');
                $url_admin_order = str_replace('/?_token=', '/' . $order->id . '/view?_token=', $url_admin_orders);

                Tools::redirectAdmin($url_admin_order);
            }
        } elseif (! Tools::isSubmit('token') && isset($this->context->cookie->payzenRefundWarn)) {
            // Display warning to customer if any for Prestashop versions >= 1.7.7.
            $this->get('session')->getFlashBag()->set('warning', $this->context->cookie->payzenRefundWarn);
            unset($this->context->cookie->payzenRefundWarn);
        }

        return true;
    }

    /**
     *  After updating order status.
     *
     * @param array $params
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $order = new Order((int) $params['id_order']);
        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        // If it is manual update of order state to refunded, update order total paid real to 0.
        if (isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses)
            && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True')) {
            unset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses);
            $order->total_paid_real = 0;
            $order->update();
        }

        return true;
    }

    /**
     * Before order slip add in backend.
     *
     * @param OrderSlip $orderSlip
     */
    public function hookActionObjectOrderSlipAddBefore($orderSlip)
    {
        if (! Tools::isSubmit('doPartialRefundPayzen')
            && ! Tools::isSubmit('doStandardRefundPayzen')) {
            return;
        }

        $orderSlipObject = $orderSlip['object'];
        $order_id = (int) $orderSlipObject->id_order;
        $order = new Order($order_id);

        if (! $this->active || ($order->module != $this->name)) {
            return;
        }

        // Stop the refund if the merchant want to generate a discount.
        // If in POST we receive generateDiscount OR generateDiscountRefund for versions < 1.7.7.
        // If in POST we receive cancel_product['voucher'] for versions >= 1.7.7.
        if (Tools::isSubmit('generateDiscount') // PrestaShop >= 1.7.7
            || Tools::isSubmit('generateDiscountRefund') // PrestaShop < 1.7.7
            || (($cancel_product = Tools::getValue('cancel_product')) && isset($cancel_product['voucher']))) {
            return;
        }

        // Get amount from OrderSlip.
        $amount = $orderSlipObject->amount;
        if (Tools::isSubmit('TaxMethod')) {
            // Prestashop versions < 1.7.7.
            // For now it's a workaround instead of use OrderSlip->amount for a bug in prestashop calculation.
            $amount = ! Tools::getValue('TaxMethod') ? $orderSlipObject->total_products_tax_excl : $orderSlipObject->total_products_tax_incl;
        }

        // Add shipping cost amount.
        $amount += $orderSlipObject->shipping_cost_amount;

        // Case of order with discount.
        // Check if the merchant choose refund option: "Product(s) price, excluding amount of initial voucher".
        if ($orderSlipObject->order_slip_type && $orderSlipObject->order_slip_type == 1) {
            $amount-= $order->total_discounts;
        }

        // If any error during WS refund, redirect to order details to avoid creation of a credit and displaying success message.
        if (! $this->refund($order, $amount)) {
            // No refund, so get back refunded products quantities, and available products stock quantities.
            $id_order_details = Tools::isSubmit('generateCreditSlip') ? Tools::getValue('cancelQuantity')
                : Tools::getValue('partialRefundProductQuantity');
            if (is_array($id_order_details) && ! empty($id_order_details)) {
                // Prestashop versions < 1.7.7.
                foreach ($id_order_details as $id_order_detail => $quantity) {
                    // Update order detail.
                    $order_detail = new OrderDetail($id_order_detail);
                    $order_detail->product_quantity_refunded -= $quantity;
                    $order_detail->update();

                    // Update product available quantity.
                    StockAvailable::updateQuantity($order_detail->product_id, $order_detail->product_attribute_id, -$quantity, $order->id_shop);
                }
            }

            if (Tools::isSubmit('token')) {
                // Prestashop versions < 1.7.7.
                Tools::redirectAdmin(AdminController::$currentIndex . '&id_order=' . $order->id . '&vieworder&token=' . Tools::getValue('token'));
            } else {
                // Display warning to customer if any for Prestashop versions >= 1.7.7.
                if (isset($this->context->cookie->payzenRefundWarn)) {
                    $this->get('session')->getFlashBag()->set('warning', $this->context->cookie->payzenRefundWarn);
                    unset($this->context->cookie->payzenRefundWarn);
                }

                // Prestashop versions >= 1.7.7.
                $url_admin_orders = $this->context->link->getAdminLink('AdminOrders');
                $url_admin_order = str_replace('/?_token=', '/' . $order->id . '/view?_token=', $url_admin_orders);

                Tools::redirectAdmin($url_admin_order);
            }
        } elseif (! Tools::isSubmit('token') && isset($this->context->cookie->payzenRefundWarn)) {
            // Display warning to customer if any for Prestashop versions >= 1.7.7.
            $this->get('session')->getFlashBag()->set('warning', $this->context->cookie->payzenRefundWarn);
            unset($this->context->cookie->payzenRefundWarn);
        }

        return true;
    }

    /**
     * Refund money.
     *
     * @param Order $order
     * @param float $amount
     */
    private function refund($order, $amount)
    {
        // Client has not configured private key in module backend, let PrestaShop do offline refund.
        if (! $this->getPrivateKey()) {
            $this->logger->logWarning("Impossible to make online refund for order #{$order->id}: private key is not configured." .
                ' Let PrestaShop do offline refund.');
            // Allow offline refund and display warning message.
            $this->context->cookie->payzenRefundWarn = $this->l('Payment is refunded/canceled only in PrestaShop. Please, consider making necessary changes in PayZen Back Office.');
            return true;
        }

        // Get currency.
        $orderCurrency = new Currency((int) $order->id_currency);
        $currency = PayzenApi::findCurrencyByAlphaCode($orderCurrency->iso_code);
        $amount = Tools::ps_round($amount, $currency->getDecimals());
        $amountInCents = $currency->convertAmountToInteger($amount);

        $this->logger->logInfo("Start refund of {$amount} {$orderCurrency->sign} for order " .
            "#{$order->id} with PayZen payment method.");

        try {
            // Get payment details.
            $getPaymentDetails = $this->getPaymentDetails($order);
            if (count($getPaymentDetails) > 1) {
                // Payment in installements, refund the desired amount from last installement to first one.
                // Check if we can refund $amount.
                $refundableAmount = 0;
                foreach ($getPaymentDetails as $key => $transaction) {
                    // Get the refundable amount of each transaction.
                    $transactionRefundableAmount = $this->getTransactionRefundableAmount($transaction, $orderCurrency, $order->reference);
                    $getPaymentDetails[$key]['transactionRefundableAmount'] = $transactionRefundableAmount;
                    $refundableAmount += $transactionRefundableAmount;
                }

                if ($amountInCents > $refundableAmount) {
                    // Unable to refund more than the sum of the refundable amount of each installement.
                    $msg = sprintf(
                        $this->l('Remaining amount (%1$s %2$s) is less than requested refund amount (%3$s %2$s).'),
                        $currency->convertAmountToFloat($refundableAmount),
                        $orderCurrency->sign,
                        $amount
                    );
                    throw new Exception($msg);
                } else {
                    $AmountStillToRefund = $amountInCents;
                    foreach ($getPaymentDetails as $transaction) {
                        if ($transaction['transactionRefundableAmount'] > 0) {
                            $transactionAmounRefund = min($transaction['transactionRefundableAmount'], $AmountStillToRefund);
                            $AmountStillToRefund -= $transactionAmounRefund;

                            // Do not update order status till we refund all the amount.
                            $forceUpdateOrderStatus = (($amountInCents == $refundableAmount) && ($AmountStillToRefund == 0));
                            $this->refundFromOneTransaction($order, $transactionAmounRefund, $transaction, $currency, $orderCurrency, $forceUpdateOrderStatus);

                            if ($AmountStillToRefund == 0) {
                                break;
                            }
                        }
                    }
                }
            } else {
                // Standard payment, refund on the only transaction.
                $this->refundFromOneTransaction($order, $amountInCents, reset($getPaymentDetails), $currency, $orderCurrency);
            }

            return true;
        } catch (Exception $e) {
            $this->logger->logError("{$e->getMessage()}" . ($e->getCode() > 0 ? ' (' . $e->getCode() . ').' : ''));

            $errorCode = $e->getCode() <= -1 ? -1 : $e->getCode();
            switch ((string)$errorCode) {
                case 'PSP_100':
                    // Merchant don't have offer allowing REST WS.
                    // Allow offline refund and display warning message.
                    $this->context->cookie->payzenRefundWarn = $this->l('Payment is refunded/canceled only in PrestaShop. Please, consider making necessary changes in PayZen Back Office.');
                    return true;

                case 'PSP_083':
                    $message = $this->l('Chargebacks cannot be refunded.');
                    break;

                case '-1': // Manage cUrl errors.
                    $message = sprintf($this->l('Error occurred when refunding payment for order #%1$s. Please consult the payment module log for more details.'), $order->reference);
                    break;

                case '0':
                    $message = sprintf($this->l('Cannot refund payment for order #%1$s.'), $order->reference) . ' ' . $e->getMessage();
                    break;

                default:
                    $message = $this->l('Refund error') . ': ' . $e->getMessage();
                    break;
            }

            $this->context->cookie->payzenRefundWarn = $message;
            if (isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses)
                && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True')) {
                unset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses);
            }

            return false;
        }
    }

    private function refundFromOneTransaction($order, $amountInCents, $transaction, $currency, $orderCurrency, $forceUpdateOrderStatus = null)
    {
        $amount = $currency->convertAmountToFloat($amountInCents, $currency->getDecimals());
        $successStatuses = array_merge(
            PayzenApi::getSuccessStatuses(),
            PayzenApi::getPendingStatuses()
        );

        $transStatus = $transaction['detailedStatus'];
        $uuid = $transaction['uuid'];
        $commentText = $this->getUserInfo();

        /** @var PayzenRest $client */
        $client = new PayzenRest(
            Configuration::get('PAYZEN_REST_SERVER_URL'),
            Configuration::get('PAYZEN_SITE_ID'),
            $this->getPrivateKey()
        );

        if ($transStatus === 'CAPTURED') { // Transaction captured, we can do refund.
            $real_refund_amount = $amountInCents;
            // Get transaction amount and already transaction refunded amount.
            if ($orderCurrency->iso_code != $transaction['currency']) {
                $currency_conversion = true;
                $transAmount = $transaction['transactionDetails']['effectiveAmount'];
                $refundedAmount = $transaction['transactionDetails']['cardDetails']['captureResponse']['effectiveRefundAmount'];
            } else {
                $currency_conversion = false;
                $transAmount = $transaction['amount'];
                $refundedAmount = $transaction['transactionDetails']['cardDetails']['captureResponse']['refundAmount'];
            }

            if (empty($refundedAmount)) {
                $refundedAmount = 0;
            }

            $remainingAmount = $transAmount - $refundedAmount; // Calculate remaing amount.
            $currency_alpha3 = $currency->getAlpha3();

            if ($remainingAmount < $amountInCents) {
                if (! $currency_conversion) {
                    $remainingAmountFloat = $currency->convertAmountToFloat($remainingAmount);
                    $msg = sprintf(
                        $this->l('Remaining amount (%1$s %2$s) is less than requested refund amount (%3$s %2$s).'),
                        $remainingAmountFloat,
                        $orderCurrency->sign,
                        $amount
                        );
                    throw new Exception($msg);
                } else {
                    // It may be caused by currency conversion.
                    // We ferund all the transaction refundable remaining amount in the gateway currency to avoid also conversions rounding.
                    $amountInCents = $transaction['amount'] - $transaction['transactionDetails']['cardDetails']['captureResponse']['refundAmount'];
                    $currency_alpha3 = $transaction['currency'];

                    $forceUpdateOrderStatus = true;
                    $real_refund_amount = $remainingAmount; // Real refunded amount in order currency;
                }
            }

            $requestData = array(
                'uuid' => $uuid,
                'amount' => $amountInCents,
                'currency' => $currency_alpha3,
                'resolutionMode' => 'REFUND_ONLY',
                'comment' => $commentText
            );

            $refundPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

            PayzenTools::checkRestResult(
                $refundPaymentResponse,
                $successStatuses
            );

            // Check operation type.
            $transType = $refundPaymentResponse['answer']['operationType'];

            if ($transType !== 'CREDIT') {
                throw new Exception(sprintf($this->l('Unexpected transaction type received (%1$s).'), $transType));
            }

            $responseData = PayzenTools::convertRestResult($refundPaymentResponse['answer']);
            $response = new PayzenResponse($responseData, null, null, null);

            // Save refund transaction in PrestaShop.
            $refundedAmount += $real_refund_amount;

            // Create payment.
            $this->createMessage($order, $response);
            $this->savePayment($order, $response);

            // Update order status if it is not a call from hookActionOrderStatusUpdate to avoid double status update.
            $isManualUpdateRefundStatus = isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses) && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True');
            if (! $isManualUpdateRefundStatus &&
                ((($forceUpdateOrderStatus !== null) && $forceUpdateOrderStatus) || ($forceUpdateOrderStatus === null) && ($refundedAmount == $transAmount))) {

                $order->setCurrentState((int) Configuration::get('PAYZEN_OS_REFUNDED'));
            }

            $this->logger->logInfo("Online refund $amount {$orderCurrency->sign} for transaction with uuid #$uuid for order #{$order->id} is successful.");
        } else {
            $transAmount = $transaction['amount'];

            // If order currency different than transaction currency we use transaction effective amount.
            if ($orderCurrency->iso_code != $transaction['currency']) {
                $transAmount = $transaction['transactionDetails']['effectiveAmount'];
            }

            if ($amountInCents > $transAmount) {
                $transAmountFloat = $currency->convertAmountToFloat($transAmount);
                $msg = sprintf($this->l('Transaction amount (%1$s %2$s) is less than requested refund amount (%3$s %2$s).'), $transAmountFloat, $orderCurrency->sign, $amount);
                throw new Exception($msg);
            }

            if ($amountInCents == $transAmount) { // Transaction cancel in gateway.
                $requestData = array(
                    'uuid' => $uuid,
                    'resolutionMode' => 'CANCELLATION_ONLY',
                    'comment' => $commentText
                );

                $cancelPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));
                PayzenTools::checkRestResult($cancelPaymentResponse, array('CANCELLED'));

                // Total refund, update order status as well.
                $responseData = PayzenTools::convertRestResult($cancelPaymentResponse['answer']);
                $response = new PayzenResponse($responseData, null, null, null);

                // Save refund transaction in PrestaShop.
                $this->savePayment($order, $response, true);

                $isManualUpdateRefundStatus = isset($this->context->cookie->payzenManualUpdateToManagedRefundStatuses) && ($this->context->cookie->payzenManualUpdateToManagedRefundStatuses === 'True');
                if (! $isManualUpdateRefundStatus
                    && ((($forceUpdateOrderStatus !== null) && $forceUpdateOrderStatus)|| ($forceUpdateOrderStatus === null))) {
                    $order->setCurrentState((int) Configuration::get('PAYZEN_OS_REFUNDED'));
                }

                $this->logger->logInfo("Online transaction with uuid #$uuid cancel for order #{$order->id} is successful.");
            } else {
                // Partial transaction cancel, call update WS.
                $new_transaction_amount = $transAmount - $amountInCents;
                $requestData = array(
                    'uuid' => $uuid,
                    'cardUpdate' => array(
                        'amount' => $new_transaction_amount,
                        'currency' => $currency->getAlpha3()
                    ),
                    'comment' => $commentText
                );

                $updatePaymentResponse = $client->post('V4/Transaction/Update', json_encode($requestData));

                PayzenTools::checkRestResult(
                    $updatePaymentResponse,
                    array(
                        'AUTHORISED',
                        'AUTHORISED_TO_VALIDATE',
                        'WAITING_AUTHORISATION',
                        'WAITING_AUTHORISATION_TO_VALIDATE'
                    )
                );

                $responseData = PayzenTools::convertRestResult($updatePaymentResponse['answer']);
                $response = new PayzenResponse($responseData, null, null, null);

                // Save refund transaction in PrestaShop.
                $this->createMessage($order, $response);
                $this->savePayment($order, $response);

                $this->logger->logInfo("Online transaction with uuid #$uuid update for order #{$order->id} is successful.");
            }
        }
    }

    private function getTransactionRefundableAmount($transaction, $orderCurrency, $orderReference)
    {
        if ($transaction['detailedStatus'] === 'CAPTURED') {
            // Get transaction amount and already refunded amount.
            if ($orderCurrency->iso_code !== $transaction['currency']) {
                $transAmount = $transaction['transactionDetails']['effectiveAmount'];
                $refundedAmount = $transaction['transactionDetails']['cardDetails']['captureResponse']['effectiveRefundAmount'];
            } else {
                $transAmount = $transaction['amount'];
                $refundedAmount = $transaction['transactionDetails']['cardDetails']['captureResponse']['refundAmount'];
            }

            if (empty($refundedAmount)) {
                $refundedAmount = 0;
            }

            $refundedableAmount = $transAmount - $refundedAmount;
        } else {
            $refundedableAmount = ($orderCurrency->iso_code !== $transaction['currency']) ?
                $transaction['transactionDetails']['effectiveAmount'] : $transaction['amount'];
        }

        return $refundedableAmount;
    }

    /**
     * Get payment details for Order $order.
     *
     * @param Order $order
     * @return array
     */
    private function getPaymentDetails($order)
    {
        /** @var PayzenRest $client */
        $client = new PayzenRest(
            Configuration::get('PAYZEN_REST_SERVER_URL'),
            Configuration::get('PAYZEN_SITE_ID'),
            $this->getPrivateKey()
        );

        $requestData = array(
            'orderId' => $order->id_cart,
            'operationType' => 'DEBIT'
        );

        $getOrderResponse = $client->post('V4/Order/Get', json_encode($requestData));
        PayzenTools::checkRestResult($getOrderResponse);

        // Order transactions organized by sequence numbers.
        $transBySequence = array();
        foreach ($getOrderResponse['answer']['transactions'] as $transaction) {
            $sequenceNumber = $transaction['transactionDetails']['sequenceNumber'];
            // Unpaid transactions are not considered.
            if ($transaction['status'] !== 'UNPAID') {
                $transBySequence[$sequenceNumber] = $transaction;
            }
        }

        ksort($transBySequence);
        return array_reverse($transBySequence);
    }

    private function getPrivateKey()
    {
        $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
        $private_key = $test_mode ? Configuration::get('PAYZEN_PRIVKEY_TEST') : Configuration::get('PAYZEN_PRIVKEY_PROD');

        return $private_key;
    }

    private function getUserInfo()
    {
        $commentText = 'PrestaShop user: ' . $this->context->employee->email;
        $commentText .= ' ; IP address: ' . Tools::getRemoteAddr();

        return $commentText;
    }

    /**
     * Before (modifying or new) carrier save in backend.
     *
     * @param array $params
     */
    public function hookActionAdminCarrierWizardControllerSaveBefore($params)
    {
        if ((Configuration::get('PAYZEN_SEND_SHIP_DATA') === 'True') || (Configuration::get('PAYZEN_FFIN_ENABLED') === 'True') || (Configuration::get('PAYZEN_ONEY34_ENABLED') === 'True')) {
            $msg = $this->l('Warning! Do not forget to configure the shipping options mapping in the payment module: GENERAL CONFIGURATION > ADDITIONAL OPTIONS.');
            $this->context->cookie->payzenShippingOptionsWarn = $msg;
        }
    }

    /**
     * After (modifying or new) carrier save in backend.
     *
     * @param array $params
     */
    public function hookActionAdminCarriersOptionsModifier($params)
    {
        if (isset($this->context->cookie->payzenShippingOptionsWarn)) {
            $this->context->controller->warnings[] = $this->context->cookie->payzenShippingOptionsWarn;
            unset($this->context->cookie->payzenShippingOptionsWarn);
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

        // Retrieve customer from cart.
        $customer = new Customer((int) $cart->id_customer);

        $currency = PayzenApi::findCurrency($response->get('currency'));
        $decimals = $currency->getDecimals();

        // PrestaShop id_currency from currency iso num code.
        $currency_id = Currency::getIdByIsoCode($currency->getAlpha3());

        // Real paid total on gateway.
        $paid_total = $currency->convertAmountToFloat($response->get('amount'));
        if (number_format($cart->getOrderTotal(), $decimals) == number_format($paid_total, $decimals)) {
            // To avoid rounding issues and bypass PaymentModule::validateOrder() check.
            $paid_total = $cart->getOrderTotal();
        }

        // Get extra parameters.
        $module_id = $response->getExtInfo('module_id');

        // Recover used payment method.
        $class_name = 'Payzen' . PayzenTools::ucClassName($module_id) . 'Payment';
        if (! $module_id || ! class_exists($class_name)) {
            $this->logger->logWarning("Invalid submodule identifier ($module_id) received from gateway for cart #{$cart->id}.");

            // Use standard submodule as default.
            $class_name = 'PayzenStandardPayment';
        }

        $payment = new $class_name();

        // Specific case of "Other payment means" submodule.
        if (is_a($payment, 'PayzenOtherPayment')) {
            $method = PayzenOtherPayment::getMethodByCode($response->get('card_brand'));
            $payment->init($method['code'], $method['title']);
        }

        $title = $payment->getTitle((int) $cart->id_lang);

        if ($option_id = $response->getExtInfo('option_id')) {
            // This is multiple payment submodule.
            $multi_options = $payment::getAvailableOptions();
            $option = $multi_options[$option_id];
            $title .= $option ? ' (' . $option['count'] . ' x)' : '';
        }

        $this->logger->logInfo("Call PaymentModule::validateOrder() PrestaShop function to create order for cart #{$cart->id}.");

        // Call payment module validateOrder.
        $this->validateOrder(
            $cart->id,
            $state,
            $paid_total,
            $title, // Title defined in admin panel.
            null, // $message.
            array(), // $extraVars.
            $currency_id, // $currency_special.
            true, // $dont_touch_amount.
            $customer->secure_key
        );

        $this->logger->logInfo("PaymentModule::validateOrder() PrestaShop function called successfully for cart #{$cart->id}.");

        // Reload order.
        $order = new Order((int) Order::getOrderByCartId($cart->id));
        $this->logger->logInfo("Order #{$order->id} created successfully for cart #{$cart->id}.");

        $this->createMessage($order, $response);
        $this->savePayment($order, $response);
        $this->saveIdentifier($customer, $response);

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

        // 3DS extra message.
        $msg_3ds = "\n" . $this->l('3DS authentication : ');
        if ($status = $response->get('threeds_status')) {
            $msg_3ds .= $this->getThreedsStatus($status);
            $msg_3ds .= ($threeds_cavv = $response->get('threeds_cavv')) ? "\n" . $this->l('3DS certificate : ') . $threeds_cavv : '';
            $msg_3ds .= ($threeds_auth_type = $response->get('threeds_auth_type')) ? "\n" . $this->l('Authentication type : ') . $threeds_auth_type : '';
        } else {
            $msg_3ds .= 'UNAVAILABLE';
        }

        // IPN call source.
        $msg_src = ($url_check_src = $response->get('url_check_src')) ? "\n" . $this->l('IPN source : ') . $url_check_src : "";

        // Transaction UUID.
        $msg_trans_uuid = "\n" . $this->l('Transaction UUID : ') . $response->get('trans_uuid');

        // Authorized amount.
        $msg_authorized_amount = '';
        if ($authorized_amount = $response->get('authorized_amount')) {
            $currency = PayzenApi::findCurrencyByNumCode($response->get('currency'));
            $msg_authorized_amount = "\n" . $this->l('Authorized amount: ') . $currency->convertAmountToFloat($authorized_amount) . ' ' . $currency->getAlpha3();
        }

        // Store installments number/config.
        $msg_installments_number = '';
        if (($installments_number = $response->get('payment_option_code')) && is_numeric($installments_number)) {
            $msg_installments_number = "\n" . $this->l('Installments number: ') . $installments_number;
        }

        $message = $response->getCompleteMessage() . $msg_brand_choice . $msg_3ds . $msg_src . $msg_trans_uuid . $msg_authorized_amount . $msg_installments_number;

        if ((Configuration::get('PAYZEN_ENABLE_CUST_MSG') === 'True') && version_compare(_PS_VERSION_, '1.7.1.2', '>=')) {
            $msg = new CustomerMessage();
            $msg->message = $message;
            $msg->id_customer_thread = $this->createCustomerThread((int) $order->id);
            $msg->id_order = (int) $order->id;
            $msg->private = 1;
            $msg->read = 1;
            $msg->save();
        }

        // Create order message anyway to prevent changes on PrestaShop coming versions.
        $msg = new Message();
        $msg->message = $message;
        $msg->id_order = (int) $order->id;
        $msg->private = 1;
        $msg->add();

        // Mark message as read to archive it.
        Message::markAsReaded($msg->id, 0);
    }

    private function getThreedsStatus($status)
    {
        switch ($status) {
            case 'Y':
                return 'SUCCESS';

            case 'N':
                return 'FAILED';

            case 'U':
                return 'UNAVAILABLE';

            case 'A':
                return 'ATTEMPT';

            default :
                return $status;
        }
    }

    private function createCustomerThread($id_order)
    {
        $customerThread = new CustomerThread();
        $customerThread->id_shop = $this->context->shop->id;
        $customerThread->id_lang = $this->context->language->id;
        $customerThread->id_contact = 0;
        $customerThread->id_order = $id_order;
        $customerThread->id_customer = $this->context->customer->id;
        $customerThread->status = 'closed';
        $customerThread->email = $this->context->customer->email;
        $customerThread->token = Tools::passwdGen(12);
        $customerThread->add();

        return (int) $customerThread->id;
    }

    /**
     * Save payment information.
     *
     * @param Order $order
     * @param PayzenResponse $response
     */
    public function savePayment($order, $response, $force_stop_payment_creation = false)
    {
        $payments = $order->getOrderPayments();

        $currency = PayzenApi::findCurrency($response->get('currency'));
        $decimals = $currency->getDecimals();

        // Delete payments created by default and cancelled payments.
        if (is_array($payments) && ! empty($payments)) {
            $number = $response->get('sequence_number') ? $response->get('sequence_number') : '1';
            $trans_id = $number . '-' . $response->get('trans_id');
            $cancelled = $response->getTransStatus() === 'CANCELLED';

            $update = false;

            foreach ($payments as $payment) {
                if (! $payment->transaction_id || (($payment->transaction_id == $trans_id) && $cancelled)) {
                    // Round to avoid floats like 2.4868995751604E-14.
                    $order->total_paid_real = Tools::ps_round($order->total_paid_real - $payment->amount, $decimals);

                    // Delete payment and invoice reference.
                    $this->deleteOrderPayment($payment);

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

        if ((! $this->isSuccessState($order) && ! $response->isAcceptedPayment()) || $force_stop_payment_creation) {
            // No payment creation.
            return;
        }

        // Save transaction info.
        $this->logger->logInfo("Save payment information for cart #{$order->id_cart}.");

        $invoices = $order->getInvoicesCollection();
        $invoice = ($invoices && $invoices->getFirst()) ? $invoices->getFirst() : null;

        $payment_ids = array();

        // Recover option_id if any.
        $option_id = ($response->getExtInfo('option_id') ? $response->getExtInfo('option_id') : '');

        if ($response->get('card_brand') === 'MULTI') {
            $sequences = Tools::jsonDecode($response->get('payment_seq'));
            $transactions = array_filter($sequences->transactions, 'Payzen::filterTransactions');

            $last_trs = end($transactions); // Last transaction.
            foreach ($transactions as $trs) {
                // Real paid total on gateway.
                $amount = $currency->convertAmountToFloat($trs->{'amount'});

                if ($trs === $last_trs) {
                    $remaining = $order->total_paid - $order->total_paid_real;
                    if (number_format($remaining, $decimals) == number_format($amount, $decimals)) {
                        // To avoid rounding problems and pass PaymentModule::validateOrder() check.
                        $amount = $remaining;
                    }
                }

                $trans_id = $trs->{'sequence_number'} . '-' . $trs->{'trans_id'};
                $timestamp = isset($trs->{'presentation_date'}) ? strtotime($trs->{'presentation_date'} . ' UTC') : time();

                $data = array(
                    'card_number' => $trs->{'card_number'},
                    'card_brand' => $trs->{'card_brand'},
                    'expiry_month' => isset($trs->{'expiry_month'}) ? $trs->{'expiry_month'} : null,
                    'expiry_year' => isset($trs->{'expiry_year'}) ? $trs->{'expiry_year'} : null
                );

                if (isset($trs->{'change_rate'})) {
                    $data['change_rate'] = $trs->{'change_rate'};
                }

                if (! ($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                    return;
                }

                $payment_ids[] = $pccId;
            }
        } elseif ($option_id && (strpos($response->get('payment_config'), 'MULTI') !== false)) {
            $multi_options = PayzenMultiPayment::getAvailableOptions();
            $option = $multi_options[$option_id];

            $count = (int) $option['count'];

            $total_amount = $response->get('amount');

            if (isset($option['first']) && $option['first']) {
                $first_amount = round($total_amount * $option['first'] / 100);
            } else {
                $first_amount = round($total_amount / $count);
            }

            $installment_amount = (int)(string)(($total_amount - $first_amount) / ($count - 1));

            $first_timestamp = strtotime($response->get('presentation_date').' UTC');

            $data = array(
                'card_number' => $response->get('card_number'),
                'card_brand' => $response->get('card_brand'),
                'expiry_month' => $response->get('expiry_month'),
                'expiry_year' => $response->get('expiry_year')
            );

            if ($response->get('change_rate') !== null) {
                $data['change_rate'] = $response->get('change_rate');
            }

            $total_paid_real = 0;
            $first_seq_num = $response->get('sequence_number') ? (int) $response->get('sequence_number') : 1;
            for ($i = 1; $i <= $option['count']; $i++) {
                $trans_id = ($first_seq_num + $i - 1) . '-' . $response->get('trans_id');

                $delay = (int) $option['period'] * ($i - 1);
                $timestamp = strtotime("+$delay days", $first_timestamp);

                switch (true) {
                    case ($i == 1): // First transaction.
                        $amount = $currency->convertAmountToFloat($first_amount);
                        break;
                    case ($i == $option['count']): // Last transaction.
                        $amount = $currency->convertAmountToFloat($total_amount) - $total_paid_real;

                        $remaining = $order->total_paid - $order->total_paid_real;
                        if (number_format($remaining, $decimals) == number_format($amount, $decimals)) {
                            // To avoid rounding problems and pass PaymentModule::validateOrder() check.
                            $amount = $remaining;
                        }

                        break;
                    default: // Others.
                        $amount = $currency->convertAmountToFloat($installment_amount);
                        break;
                }

                $total_paid_real += $amount;

                if (! ($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                    return;
                }

                $payment_ids[] = $pccId;
            }
        } else {
            // Real paid total on gateway.
            $amount_in_cents = $response->get('amount');
            if ($response->get('effective_currency') && ($response->get('effective_currency') == $response->get('currency'))) {
                $amount_in_cents = $response->get('effective_amount'); // Use effective amount to get modified amount.
            }

            $amount = $currency->convertAmountToFloat($amount_in_cents);

            if (number_format($order->total_paid, $decimals) == number_format($amount, $decimals)) {
                // To avoid rounding problems and pass PaymentModule::validateOrder() check.
                $amount = $order->total_paid;
            }

            if ($response->get('operation_type') === 'CREDIT') {
                if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
                    // Workarround for PrestaShop 1.7.7.x, payments with negative amounts not accepted.
                    // Create a credit slip if it is not already created from BO PrestaShop.
                    if (! Tools::isSubmit('doPartialRefundPayzen') && ! Tools::isSubmit('doStandardRefundPayzen')) {
                        $orderCurrency = new Currency((int) $order->id_currency);
                        $formatted_price_amount = $amount . ' ' . $orderCurrency->sign;
                        $this->logger->logInfo("Creating order slip with $formatted_price_amount amount for order #{$order->id}");

                        try {
                            if($this->payzenCreateOrderSlip($order, $amount)) {
                                $this->logger->logInfo("Order slip with $formatted_price_amount amount was successfully created for order #{$order->id}");
                            } else {
                                $this->logger->logInfo("Order slip with $formatted_price_amount amount could not be created for order #{$order->id}");
                            }
                        } catch (Exception $e) {
                            $this->logger->logError("Error while creating order slip with $formatted_price_amount amount for order #{$order->id} : {$e->getMessage()}" . ($e->getCode() > 0 ? ' (' . $e->getCode() . ').' : ''));
                        }
                    }

                    // Update order total paid real.
                    $order->total_paid_real -= $amount;

                    if ($order->total_paid_real < 0) {
                        $order->total_paid_real = 0;
                    }

                    $order->update();
                    return;
                } else {
                    // This is a refund, set transaction amount to negative.
                    $amount = $amount * -1;
                }
            }

            $timestamp = strtotime($response->get('presentation_date').' UTC');

            $number = $response->get('sequence_number') ? $response->get('sequence_number') : '1';
            $trans_id = $number . '-' . $response->get('trans_id');

            $data = array(
                'card_number' => $response->get('card_number'),
                'card_brand' => $response->get('card_brand'),
                'expiry_month' => $response->get('expiry_month'),
                'expiry_year' => $response->get('expiry_year')
            );

            if ($response->get('change_rate') !== null) {
                $data['change_rate'] = $response->get('change_rate');
            }

            if (! ($pccId = $this->addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data))) {
                return;
            }

            $payment_ids[] = $pccId;
        }

        $payment_ids = implode(', ', $payment_ids);
        $this->logger->logInfo(
            "Payment information with ID(s) {$payment_ids} saved successfully for cart #{$order->id_cart}."
        );
    }

    /**
     * Delete payment and invoice reference.
     *
     * @param OrderPayment $payment
     */
    private function deleteOrderPayment($payment)
    {
        // Delete payment.
        $payment->delete();

        // Delete invoice reference.
        $result = Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'order_invoice_payment` WHERE `id_order_payment` = ' . (int) $payment->id
        );

        if (! $result) {
            $this->logger->logWarning(
                "An error occurred when deleting invoice reference for payment #{$payment->id}:."
            );
        }
    }

    public function saveIdentifier($customer, $response)
    {
        if (! $customer->id) {
            return;
        }

        if ($response->get('identifier') && in_array($response->get('identifier_status'), array('CREATED', 'UPDATED'))) {
            $this->logger->logInfo(
                "Identifier for customer #{$customer->id} successfully "
                ."created or updated on payment gateway. Let's save it and save masked card and expiry date."
            );

            // Mask all card digits unless the last 4 ones.
            $number = $response->get('card_number');
            $masked = '';

            $matches = array();
            if (preg_match('#^([A-Z]{2}[0-9]{2}[A-Z0-9]{10,30})(_[A-Z0-9]{8,11})?$#i', $number, $matches)) {
                // IBAN(_BIC).
                $masked .= isset($matches[2]) ? str_replace('_', '', $matches[2]) . ' / ' : ''; // BIC.

                $iban = $matches[1];
                $masked .= Tools::substr($iban, 0, 4) . str_repeat('X', Tools::strlen($iban) - 8) . Tools::substr($iban, -4);
            } elseif (Tools::strlen($number) > 4) {
                $masked = str_repeat('X', Tools::strlen($number) - 4) . Tools::substr($number, -4);

                if ($response->get('expiry_month') && $response->get('expiry_year')) {
                    // Format card expiration data.
                    $masked .= ' ';
                    $masked .= str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT);
                    $masked .= ' / ';
                    $masked .= $response->get('expiry_year');
                }
            }

            // Save customers configuration as array: n = identifier, m = masked PAN.
            $customers_config = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));
            if (! is_array($customers_config)) {
                $customers_config = array();
            }

            // Recover module_id.
            $module_id = $response->getExtInfo('module_id');
            if (! $module_id) {
                $module_id = 'standard';
            }

            $customers_config[$customer->id][$module_id] = array(
                'n' => $response->get('identifier'),
                'm' => $masked
            );
            Configuration::updateValue('PAYZEN_CUSTOMERS_CONFIG', serialize($customers_config));

            $this->logger->logInfo(
                "Identifier for customer #{$customer->id} and masked PAN #{$masked} successfully saved."
            );
        }
    }

    public function checkIdentifier($identifier, $customerEmail)
    {
        try {
            $requestData = array(
                'paymentMethodToken' => $identifier
            );

            // Perform REST request to check identifier.
            $client = new PayzenRest(
                Configuration::get('PAYZEN_REST_SERVER_URL'),
                Configuration::get('PAYZEN_SITE_ID'),
                $this->getPrivateKey()
            );

            $checkIdentifierResponse = $client->post('V4/Token/Get', json_encode($requestData));
            PayzenTools::checkRestResult($checkIdentifierResponse);

            $cancellationDate = PayzenTools::getProperty($checkIdentifierResponse['answer'], 'cancellationDate');
            if ($cancellationDate && (strtotime($cancellationDate) <= time())) {
                $this->logger->logWarning(
                    "Saved identifier for customer {$customerEmail} is expired on payment gateway in date of: {$cancellationDate}."
                );
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $invalidIdentCodes = array('PSP_030', 'PSP_031', 'PSP_561', 'PSP_607');

            if (in_array($e->getCode(), $invalidIdentCodes, true)) {
                // The identifier is invalid or doesn't exist.
                $this->logger->logWarning(
                    "Identifier for customer {$customerEmail} is invalid or doesn't exist: {$e->getMessage()}."
                );
                return false;
            } else {
                throw $e;
            }
        }
    }

    private function findOrderPayment($order_ref, $trans_id)
    {
        $payment_id = Db::getInstance()->getValue(
            'SELECT `id_order_payment` FROM `' . _DB_PREFIX_ . 'order_payment`
            WHERE `order_reference` = \'' . pSQL($order_ref) . '\' AND transaction_id = \'' . pSQL($trans_id) . '\''
        );

        if (! $payment_id) {
            return false;
        }

        return new OrderPayment((int) $payment_id);
    }

    private function addOrderPayment($order, $invoice, $trans_id, $amount, $timestamp, $data)
    {
        $date = date('Y-m-d H:i:s', $timestamp);

        if (! ($pcc = $this->findOrderPayment($order->reference, $trans_id))) {
            // Order payment not created yet, let's create it.
            $method = sprintf($this->l('%s payment'), $data['card_brand']);
            if (! $order->addOrderPayment($amount, $method, $trans_id, null, $date, $invoice)
                || ! ($pcc = $this->findOrderPayment($order->reference, $trans_id))) {
                $this->logger->logWarning(
                    "Error: payment information for cart #{$order->id_cart} cannot be saved.
                     Error may be caused by another module hooked on order update event."
                );
                return false;
            }
        } elseif (Validate::isLoadedObject($invoice)) {
            $result = Db::getInstance()->execute(
                'REPLACE INTO `' . _DB_PREFIX_ . 'order_invoice_payment`
                 VALUES(' . (int) $invoice->id . ', ' . (int) $pcc->id . ', ' . (int) $order->id . ')'
            );

            if (! $result) {
                $this->logger->logWarning(
                    "An error has occurred during updating invoice reference for payment #{$pcc->id}."
                );
            }
        }

        // Set card info.
        $pcc->card_number = $data['card_number'];
        $pcc->card_brand = $data['card_brand'];
        if ($data['expiry_month'] && $data['expiry_year']) {
            $pcc->card_expiration = str_pad($data['expiry_month'], 2, '0', STR_PAD_LEFT) . '/' . $data['expiry_year'];
        }

        $pcc->card_holder = null;

        // Update transaction info if payment is modified in gateway Back Office.
        $diff = 0;
        if ($pcc->amount != $amount) {
            $diff = $pcc->amount - $amount;
            $pcc->amount = $amount;

            $this->logger->logInfo("Transaction amount is modified for cart #{$order->id_cart}. New amount is $amount.");
        }

        if ($pcc->date_add != $date) {
            $pcc->date_add = $date;

            $this->logger->logInfo("Transaction presentation date is modified for cart #{$order->id_cart}. New date is $date.");
        }

        // Set conversion_rate.
        if (isset($data['change_rate'])) {
            $pcc->conversion_rate = $data['change_rate'];
        }

        if ($pcc->update()) {
            if ($diff > 0) {
                $order->total_paid_real -= $diff;
                $order->update();
            }

            return $pcc->id;
        } else {
            $this->logger->logWarning("Problem: payment mean information for cart #{$order->id_cart} cannot be saved.");
            return false;
        }
    }

    public static function filterTransactions($trs)
    {
        $successful_states = array_merge(
            PayzenApi::getSuccessStatuses(),
            PayzenApi::getPendingStatuses()
        );

        return $trs->{'operation_type'} === 'DEBIT' && in_array($trs->{'trans_status'}, $successful_states);
    }

    public static function nextOrderState($response, $outofstock = false, $old_state = null, $is_partial_payment = false)
    {
        // PAYZEN_OS_REFUNDED is a final state no state change.
        if (Configuration::get('PAYZEN_OS_REFUNDED') == $old_state) {
            return $old_state;
        }

        if ($response->isAcceptedPayment()) {
            $valid = false;

            switch (true) {
                case $response->isToValidatePayment():
                    // To validate payment order state.
                    $new_state = 'PAYZEN_OS_TO_VALIDATE';

                    break;
                case $response->isPendingPayment():
                    if (self::isOney($response)) {
                        // Pending Oney confirmation order state.
                        $new_state = 'PAYZEN_OS_ONEY_PENDING';
                    } else {
                        // Pending authorization order state.
                        $new_state = 'PAYZEN_OS_AUTH_PENDING';
                    }

                    break;
                default:
                    // Payment successful.
                    if (($response->get('operation_type') === 'CREDIT') && ! $is_partial_payment) {
                        $new_state = 'PAYZEN_OS_REFUNDED';
                    } elseif (self::isSofort($response) || self::isSepa($response)) {
                        // Pending funds transfer order state.
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
            // Do not cancel PrestaShop order, no state changing return PrestaShop order old state.
            if ($response->get('operation_type') === 'CREDIT' || $is_partial_payment) {
                return $old_state;
            } else {
                $new_state = 'PS_OS_CANCELED';
            }
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
        $os = new OrderState((int) $order->getCurrentState());
        if (! $os->id) {
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

        // If state is one of supported states or custom state with paid flag.
        return self::isStateInArray($os->id, $s_states) || (bool) $os->paid;
    }

    public static function isOutOfStock($order)
    {
        $state = $order->getCurrentState();
        $oos_states = array(
            'PS_OS_OUTOFSTOCK_UNPAID', // Override pending states since PrestaShop 1.6.1.
            'PS_OS_OUTOFSTOCK_PAID', // Override paid state since PrestaShop 1.6.1.
            'PS_OS_OUTOFSTOCK', // Considered as pending by module for PrestaShop < 1.6.1.
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK' // Paid state for PrestaShop < 1.6.1.
        );

        return self::isStateInArray($state, $oos_states);
    }

    public static function isPaidOrder($order)
    {
        $os = new OrderState((int) $order->getCurrentState());
        if (! $os->id) {
            return false;
        }

        // Final states.
        $paid_states = array(
            'PS_OS_OUTOFSTOCK_PAID', // Override paid state since PrestaShop 1.6.1.
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK', // Paid state for PrestaShop < 1.6.1.
            'PS_OS_PAYMENT',
            'PAYZEN_OS_TRANS_PENDING'
        );

        return self::isStateInArray($os->id, $paid_states) || (bool) $os->paid;
    }

    public static function getManagedStates()
    {
        $managed_states = array(
            'PS_OS_OUTOFSTOCK_UNPAID', // Override pending state since PrestaShop 1.6.1.
            'PS_OS_OUTOFSTOCK_PAID', // Override paid state since PrestaShop 1.6.1.
            'PS_OS_OUTOFSTOCK', // Considered as pending by module for PrestaShop < 1.6.1.
            'PAYZEN_OS_PAYMENT_OUTOFSTOCK', // Paid state for PrestaShop < 1.6.1.

            'PS_OS_PAYMENT',
            'PAYZEN_OS_ONEY_PENDING',
            'PAYZEN_OS_TRANS_PENDING',
            'PAYZEN_OS_AUTH_PENDING',
            'PAYZEN_OS_TO_VALIDATE',
            'PS_OS_ERROR',
            'PS_OS_CANCELED',
            'PAYZEN_OS_REFUNDED'
        );

        return $managed_states;
    }

    public static function hasAmountError($order)
    {
        $orders = Order:: getByReference($order->reference);
        $total_paid = 0;

        // Browse sister orders (orders with the same reference).
        foreach ($orders as $sister_order) {
            $total_paid += $sister_order->total_paid;
        }

        return number_format($total_paid, 2) != number_format($order->total_paid_real, 2);
    }

    public static function isStateInArray($state_id, $state_names)
    {
        if (is_string($state_names)) {
            $state_names = array($state_names);
        }

        foreach ($state_names as $state_name) {
            if (! is_string($state_name) || ! Configuration::get($state_name)) {
                continue;
            }

            if ((int) $state_id === (int) Configuration::get($state_name)) {
                return true;
            }
        }

        return false;
    }

    public static function isOney($response)
    {
        return $response->get('card_brand') ==='ONEY_3X_4X';
    }

    public static function isSofort($response)
    {
        return $response->get('card_brand') === 'SOFORT_BANKING';
    }

    public static function isSepa($response)
    {
        return $response->get('card_brand') === 'SDD';
    }

    public static function isFirstSequenceInOrderPayments($order, $transaction_id, $sequence_number)
    {
        $payments = $order->getOrderPayments();

        // No payments created yet.
        if (! is_array($payments) || empty($payments)) {
            return true;
        }

        $sequence_numbers = array();
        foreach ($payments as $payment) {
            if (! $payment->transaction_id || ! strpos($payment->transaction_id, '-')) {
                continue;
            }

            $pos = strpos($payment->transaction_id, '-');
            if (substr($payment->transaction_id, $pos + 1) !== $transaction_id) {
                continue;
            }

            $sequence_numbers[] = (int) substr($payment->transaction_id, 0, $pos);
        }

        if (empty($sequence_numbers)) {
            // No gateway payment created, there are only payments created by PrestaShop.
            return true;
        }

        return min($sequence_numbers) === (int) $sequence_number;
    }

    private static function payzenCreateOrderSlip($order, $amount)
    {
        $currency = new Currency($order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->amount = 0;
        $orderSlip->shipping_cost = false;
        $orderSlip->shipping_cost_amount = 0;
        $orderSlip->conversion_rate = $currency->conversion_rate;
        $orderSlip->partial = 0;
        $orderSlip->total_products_tax_excl = 0;
        $orderSlip->total_products_tax_incl = 0;
        $orderSlip->total_shipping_tax_excl = $amount;
        $orderSlip->total_shipping_tax_incl = $amount;

        return $orderSlip->add();
    }
}
