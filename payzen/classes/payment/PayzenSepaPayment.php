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

class PayzenSepaPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_SEPA_';
    protected $tpl_name = 'payment_sepa.tpl';
    protected $logo = 'sdd.png';
    protected $name = 'sepa';

    protected $currencies = array('EUR');
    protected $countries = array(
        'FI', 'AT', 'PT', 'BE', 'BG', 'ES', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FR', 'GF', 'DE', 'GI', 'GR',
        'GP', 'HU', 'IS', 'IE', 'LV', 'LI', 'LT', 'LU', 'PT', 'MT', 'MQ', 'YT', 'MC', 'NL', 'NO', 'PL',
        'RE', 'RO', 'BL', 'MF', 'PM', 'SM', 'SK', 'SE', 'CH', 'GB'
    );

    public function getCountries()
    {
        return $this->countries;
    }

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (! empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int) $cart->id_address_invoice);
        $billing_country = new Country((int) $billing_address->id_country);

        if (! in_array($billing_country->iso_code, $this->countries)) {
            $errors[] = $this->l('Country not supported by SEPA payment.');
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // Override with SEPA payment card.
        $request->set('payment_cards', 'SDD');

        $customer = new Customer((int) $cart->id_customer);

        // Sepa one click is active and customer is logged-in.
        if ($this->isOneClickActive() && $customer->id) {
            $customers_config = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));
            $saved_identifier = isset($customers_config[$cart->id_customer][$this->name]['n']) ? $customers_config[$cart->id_customer][$this->name]['n'] : '';
            $use_identifier = isset($data['sepa_payment_by_identifier']) ? $data['sepa_payment_by_identifier'] === '1' : false;

            if ($this->isValidSavedAlias()) {
                // Customer has an identifier.
                $request->set('identifier', $saved_identifier);

                if (! $use_identifier) {
                    // Customer choose to not use alias.
                    $request->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Bank data acquisition on payment page, let's ask customer for data registration.
                PayzenTools::getLogger()->logInfo('Customer ' . $request->get('cust_email') . ' will be asked for card data registration on payment page.');
                $request->set('page_action', 'ASK_REGISTER_PAY');
            }
        } else {
            $request->set('page_action', Configuration::get($this->prefix . 'MANDATE_MODE'));
        }

        return $request;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        // Payment by identifier.
        $vars['payzen_is_valid_sepa_identifier'] = false;
        $vars['payzen_sepa_saved_payment_mean'] = '';

        if ($this->isValidSavedAlias()) {
            $vars['payzen_is_valid_sepa_identifier'] = true;
            $customers_config = @unserialize(Configuration::get('PAYZEN_CUSTOMERS_CONFIG'));
            $vars['payzen_sepa_saved_payment_mean'] = isset($customers_config[$cart->id_customer][$this->name]['m']) ?
            $customers_config[$cart->id_customer][$this->name]['m'] : 'sk';
        }

        return $vars;
    }

    public function isOneClickActive()
    {
        // 1-Click enabled and SEPA direct debit mode is REGISTER_PAY.
        if ((Configuration::get($this->prefix . 'MANDATE_MODE') === 'REGISTER_PAY') && (Configuration::get($this->prefix . '1_CLICK_PAYMNT') === 'True')) {
            return true;
        }

        return false;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with SEPA');
    }
}
