<?php
/**
 * PayZen V2-Payment Module version 1.10.0 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
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

class PayzenSofortPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_SOFORT_';
    protected $tpl_name = 'payment_sofort.tpl';
    protected $logo = 'sofort_banking.png';
    protected $name = 'sofort';

    protected $currencies = array('EUR', 'CHF', 'GBP', 'PLN');
    private $sofort_countries = array('DE', 'AT', 'BE', 'ES', 'FR', 'HU', 'IT', 'NL', 'PL', 'CZ', 'GB', 'SK', 'CH');

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (!empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int)$cart->id_address_invoice);
        $billing_country = new Country((int)$billing_address->id_country);

        if (!in_array($billing_country->iso_code, $this->sofort_countries)) {
            $errors[] = $this->l('Country not supported by SOFORT Banking payment.');
        }

        return $errors;
    }

    /**
     * Generate form fields to post to the payment gateway.
     *
     * @param Cart $cart
     * @param array[string][string] $data
     * @return PayzenRequest
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // override with SOFORT payment card
        $request->set('payment_cards', 'SOFORT_BANKING');

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with SOFORT Banking');
    }
}
