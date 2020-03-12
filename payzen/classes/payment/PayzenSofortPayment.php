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

class PayzenSofortPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_SOFORT_';
    protected $tpl_name = 'payment_sofort.tpl';
    protected $logo = 'sofort_banking.png';
    protected $name = 'sofort';

    protected $currencies = array('EUR', 'CHF', 'GBP', 'PLN');
    protected $countries = array('DE', 'AT', 'BE', 'ES', 'FR', 'HU', 'IT', 'NL', 'PL', 'CZ', 'GB', 'SK', 'CH');

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
            $errors[] = $this->l('Country not supported by SOFORT Banking payment.');
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

        // Override with SOFORT payment card.
        $request->set('payment_cards', 'SOFORT_BANKING');

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with SOFORT Banking');
    }
}
