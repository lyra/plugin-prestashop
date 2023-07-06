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

class PayzenOney34Payment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_ONEY34_';
    protected $tpl_name = 'payment_oney34.tpl';
    protected $logo = 'oney34.png';
    protected $name = 'oney34';

    protected $label = 'Oney payment';

    protected $currencies = array('EUR');
    protected $countries = array('FR', 'GP', 'MQ', 'GF', 'RE', 'YT');

    protected $needs_cart_data = true;
    protected $needs_shipping_method_data = true;

    public function getCountries()
    {
        return $this->countries;
    }

    public function isAvailable($cart)
    {
        if (! parent::isAvailable($cart)) {
            return false;
        }

        $options = self::getAvailableOptions($cart);
        if (empty($options)) {
            return false;
        }

        if (! PayzenTools::checkOneyRequirements($cart, $this->label)) {
            return false;
        }

        return true;
    }

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (! empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int) $cart->id_address_invoice);

        // Check address validity according to Oney payment specifications.
        $errors = PayzenTools::checkAddress($billing_address, 'billing', 'oney34');

        if (empty($errors)) {
            // Billing address is valid, check delivery address.
            $delivery_address = new Address((int) $cart->id_address_delivery);

            $errors = PayzenTools::checkAddress($delivery_address, 'delivery', 'oney34');
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

        if (isset($data['opt']) && $data['opt']) {
            // Override option code and payment card parameters.
            $oney_options = self::getAvailableOptions($cart);
            $option = $oney_options[$data['opt']];

            $request->set('payment_option_code', $option['code']);
            $request->set('payment_cards', $option['card_type']);
        }

        return $request;
    }

    public static function getAvailableOptions($cart)
    {
        // Oney payment options.
        $options = @unserialize(Configuration::get('PAYZEN_ONEY34_OPTIONS'));
        if (! is_array($options) || empty($options)) {
            return array();
        }

        $amount = $cart->getOrderTotal();

        $enabled_options = array();
        foreach ($options as $key => $option) {
            $min = $option['min_amount'];
            $max = $option['max_amount'];

            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)) {
                $c = is_numeric($option['count']) ? $option['count'] : 1;
                $r = is_numeric($option['rate']) ? $option['rate'] : 0;

                // Get final option description.
                $search = array('%c', '%r');
                $replace = array($c, $r . ' %');
                $option_label = str_replace($search, $replace, $option['label'][$cart->id_lang]); // Label to display on payment page.

                $option['localized_label'] = $option_label;

                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $vars['payzen_oney_options'] = self::getAvailableOptions($cart);
        $vars['title'] = sprintf($this->l('Click here to pay with %s', 'payment_other'), 'Oney');
        $vars['suffix'] = '34';

        return $vars;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return sprintf($this->l('Payment with %s'), 'Oney');
    }
}
