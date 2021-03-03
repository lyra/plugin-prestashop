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

class PayzenFranfinancePayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_FFIN_';
    protected $tpl_name = 'payment_franfinance.tpl';
    protected $logo = 'franfinance.png';
    protected $name = 'franfinance';

    protected $currencies = array('EUR');
    protected $countries = array('FR', 'GP', 'MQ', 'GF', 'RE', 'YT');
    protected $needs_cart_data = true;

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

        return true;
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // Override with FranFinance specific params.
        $request->set('validation_mode', '0');
        $request->set('capture_delay', '0');

        $franfinance_options = self::getAvailableOptions($cart);
        $option = $franfinance_options[$data['opt']];

        // Override with FranFinance payment card.
        $request->set('payment_cards', 'FRANFINANCE_' . $option['count'] . 'X');

        if ($option['fees'] !== '-1') {
            $fees = $option['fees'] ? 'Y' : 'N';
            $request->set('acquirer_transient_data', '{"FRANFINANCE":{"FEES_' . $option['count'] . 'X":"' . $fees . '"}}');
        }

        return $request;
    }

    public static function getAvailableOptions($cart)
    {
        // FranFinance payment options.
        $options = @unserialize(Configuration::get('PAYZEN_FFIN_OPTIONS'));
        if (! is_array($options) || empty($options)) {
            return array();
        }

        if (! $cart) {
            return $options; // All options.
        }

        $amount = $cart->getOrderTotal();

        $enabled_options = array();
        foreach ($options as $key => $option) {
            $min = $option['min_amount'];
            $max = $option['max_amount'];

            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)) {
                $default = is_string($option['label']) ? $option['label'] : $option['count'] . ' x';
                $option_label = is_array($option['label']) && isset($option['label'][$cart->id_lang]) ?
                    $option['label'][$cart->id_lang] : $default;

                $c = is_numeric($option['count']) ? $option['count'] : 1;

                // Get final option description.
                $search = array('%c');
                $replace = array($c);
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

        $vars['payzen_ffin_options'] = self::getAvailableOptions($cart);

        return $vars;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with FranFinance');
    }
}
