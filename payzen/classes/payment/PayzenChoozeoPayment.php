<?php
/**
 * PayZen V2-Payment Module version 1.10.2 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        // check available payment options
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
     * Generate form fields to post to the payment gateway.
     *
     * @param Cart $cart
     * @param array[string][string] $data
     * @return PayzenRequest
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // override with Choozeo payment card
        $request->set('payment_cards', $data['card_type']);

        // by default PrestaShop does not manage customer type
        $request->set('cust_status', 'PRIVATE');

        // Choozeo supports only automatic validation
        $request->set('validation_mode', '0');

        // send FR even address is in DOM-TOM unless form is rejected
        $request->set('cust_country', 'FR');

        return $request;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with Choozeo');
    }
}
