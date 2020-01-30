<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayzenOtherPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_OTHER_';
    protected $tpl_name = 'payment_other.tpl';
    protected $logo;
    protected $name = 'other';
    protected $needs_cart_data = false;
    protected $force_local_cart_data = true;

    protected $payment_code;
    protected $payment_title;
    protected $min_amount;
    protected $max_amount;

    public function init($payment_code, $payment_title, $min_amount = null, $max_amount = null)
    {
        $this->payment_code = $payment_code;
        $this->payment_title = $payment_title;
        $this->logo = Tools::strtolower($payment_code).'.png';
        $this->min_amount = $min_amount;
        $this->max_amount = $max_amount;
    }

    protected function checkAmountRestriction($cart)
    {
        if (!parent::checkAmountRestriction($cart)) {
            return false;
        }

        $amount = $cart->getOrderTotal();
        if (($this->min_amount && $amount < $this->min_amount) || ($this->max_amount && $amount > $this->max_amount)) {
            return false;
        }

        return true;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $cards = PayzenApi::getSupportedCardTypes();

        $vars['payzen_other_payment_code'] = $this->payment_code;
        $vars['payzen_other_payment_label'] = $cards[$this->payment_code];

        return $vars;
    }

    public function getPaymentOption($cart)
    {
        $option = parent::getPaymentOption($cart);

        $inputs = $option->getInputs();
        $inputs[] = array('type' => 'hidden', 'name' => 'payzen_payment_code', 'value' => $this->payment_code);
        $inputs[] = array('type' => 'hidden', 'name' => 'payzen_payment_title', 'value' => $this->getTitle((int)$cart->id_lang));

        $option->setInputs($inputs);

        return $option;
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        // Recover payment parameters
        $available_payments = $this->getAvailablePaymentMeans($cart);
        $validation_mode = '-1';
        $capture_delay = '';

        foreach ($available_payments as $option) {
            if ($option['code'] === $data['card_type']) {
                $validation_mode = $option['validation'];
                $capture_delay = $option['capture'];

                // Send cart data to payment gateway?
                $this->needs_cart_data = isset($option['cart']) && ($option['cart'] === 'True');

                break;
            }
        }

        $request = parent::prepareRequest($cart, $data);

        // Set payment card
        $request->set('payment_cards', $data['card_type']);

        // Set validation mode
        if ($validation_mode !== '-1') {
            $request->set('validation_mode', $validation_mode);
        }

        // Set Capture delay
        if (is_numeric($capture_delay)) {
            $request->set('capture_delay', $capture_delay);
        }

        return $request;
    }

    public function getTitle($lang)
    {
        if (is_string($this->payment_title)) {
            return $this->payment_title;
        } elseif (is_array($this->payment_title) && isset($this->payment_title[$lang])) {
            return $this->payment_title[$lang];
        } else {
            return $this->getDefaultTitle();
        }
    }

    protected function getDefaultTitle()
    {
        $cards = PayzenApi::getSupportedCardTypes();

        return sprintf($this->l('Payment with %s'), $cards[$this->payment_code]);
    }

    public static function getAvailablePaymentMeans($cart = null)
    {
        // Other payment means
        $other_payment_means = @unserialize(Configuration::get('PAYZEN_OTHER_PAYMENT_MEANS'));
        if (!is_array($other_payment_means) || empty($other_payment_means)) {
            return array();
        }

        if (!$cart) {
            return $other_payment_means; // All options
        }

        $amount = $cart->getOrderTotal();
        $enabled_options = array();
        $billing_address = new Address((int)$cart->id_address_invoice);
        $billing_country = new Country((int)$billing_address->id_country);

        foreach ($other_payment_means as $key => $option) {
            $min = $option['min_amount'];
            $max = $option['max_amount'];
            $countries = isset($option['countries']) ? $option['countries'] : array(); // Authorized countries for this option.

            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)
                && (empty($countries) || in_array($billing_country->iso_code, $countries))) {
                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }
}
