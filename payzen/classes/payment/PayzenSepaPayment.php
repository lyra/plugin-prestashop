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

class PayzenSepaPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_SEPA_';
    protected $tpl_name = 'payment_sepa.tpl';
    protected $logo = 'sdd.png';
    protected $name = 'sepa';

    protected $currencies = array('EUR');
    private $sepa_countries = array('FI', 'AT', 'PT', 'BE', 'BG', 'ES', 'HR', 'CY', 'CZ', 'DK', 'EE',
            'FR', 'GF', 'DE', 'GI', 'GR', 'GP', 'HU', 'IS', 'IE', 'LV', 'LI', 'LT', 'LU', 'PT', 'MT', 'MQ',
            'YT', 'MC', 'NL', 'NO', 'PL', 'RE', 'RO', 'BL', 'MF', 'PM', 'SM', 'SK', 'SE', 'CH', 'GB');

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (!empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int)$cart->id_address_invoice);
        $billing_country = new Country((int)$billing_address->id_country);

        if (!in_array($billing_country->iso_code, $this->sepa_countries)) {
            $errors[] = $this->l('Country not supported by SEPA payment.');
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

        // override with SEPA card
        $request->set('payment_cards', 'SDD');

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with SEPA');
    }
}
