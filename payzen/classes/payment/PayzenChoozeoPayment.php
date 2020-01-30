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

class PayzenChoozeoPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_CHOOZEO_';
    protected $tpl_name = 'payment_choozeo.tpl';
    protected $logo = 'choozeo.png';
    protected $name = 'choozeo';

    protected $currencies = array('EUR');
    protected $countries = array('FR', 'GP', 'MQ', 'GF', 'RE', 'YT');

    public function getCountries()
    {
        return $this->countries;
    }

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        // Check available payment options
        $options = self::getAvailableOptions($cart);
        if (empty($options)) {
            return false;
        }

        return true;
    }

    public static function getAvailableOptions($cart = null)
    {
        // Choozeo payment options
        $options = @unserialize(Configuration::get('PAYZEN_CHOOZEO_OPTIONS'));
        $amount = $cart->getOrderTotal();

        $enabled_options = array();
        foreach ($options as $key => $option) {
            if (isset($option['enabled']) && ($option['enabled'] !== 'True')) {
                continue;
            }

            $min = $option['min_amount'];
            $max = $option['max_amount'];


            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)) {
                $enabled_options[$key] = Tools::strtolower(Tools::substr($key, -2)).' CB';
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);
        $vars['payzen_choozeo_options'] = self::getAvailableOptions($cart);

        return $vars;
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // Override with Choozeo payment card
        $request->set('payment_cards', $data['card_type']);

        // By default PrestaShop does not manage customer type
        $request->set('cust_status', 'PRIVATE');

        // Choozeo supports only automatic validation
        $request->set('validation_mode', '0');

        // Send FR even address is in DOM-TOM unless form is rejected
        $request->set('cust_country', 'FR');

        return $request;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with Choozeo without fees');
    }
}
