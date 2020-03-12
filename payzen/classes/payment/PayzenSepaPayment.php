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

        // Override with SEPA card.
        $request->set('payment_cards', 'SDD');
        $request->set('page_action', Configuration::get($this->prefix . 'MANDATE_MODE'));

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with SEPA');
    }
}
