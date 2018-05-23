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

class PayzenOneyPayment extends AbstractPayzenPayment
{
    const ONEY_THREE_TIMES_MAX_FEES = 10;
    const ONEY_FOUR_TIMES_MAX_FEES = 20;

    protected $prefix = 'PAYZEN_ONEY_';
    protected $tpl_name = 'payment_oney.tpl';
    protected $logo = 'oney.png';
    protected $name = 'oney';

    protected $currencies = array('EUR');

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        if (Configuration::get($this->prefix.'ENABLE_OPTIONS') == 'True') {
            $options = self::getAvailableOptions($cart);

            if (empty($options)) {
                return false;
            }
        }

        if (!PayzenTools::checkOneyRequirements($cart)) {
            return false;
        }

        return true;
    }

    protected function proposeOney($data = array())
    {
        return true;
    }

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (!empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int)$cart->id_address_invoice);

        // check address validity according to FacilyPay Oney payment specifications
        $errors = PayzenTools::checkAddress($billing_address, 'billing', $this->name);

        if (empty($errors)) {
            // billing address is valid, check delivery address
            $delivery_address = new Address((int)$cart->id_address_delivery);

            $errors = PayzenTools::checkAddress($delivery_address, 'delivery', $this->name);
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

        // override with FacilyPay Oney payment cards
        $test_mode = $request->get('ctx_mode') == 'TEST';
        $request->set('payment_cards', $test_mode ? 'ONEY_SANDBOX' : 'ONEY');

        if (isset($data['opt']) && $data['opt']) {
            // override option code parameter
            $oney_options = self::getAvailableOptions($cart);
            $option = $oney_options[$data['opt']];

            $request->set('payment_option_code', $option['code']);
        }

        return $request;
    }

    public static function getAvailableOptions($cart)
    {
        // oney payment options
        $options = @unserialize(Configuration::get('PAYZEN_ONEY_OPTIONS'));
        if (!is_array($options) || empty($options)) {
            return array();
        }

        $amount = $cart->getOrderTotal();

        $enabled_options = array();
        foreach ($options as $key => $option) {
            $min = $option['min_amount'];
            $max = $option['max_amount'];

            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)) {
                $default = is_string($option['label']) ? $option['label'] : $option['count'].' x';
                $option_label = is_array($option['label']) && isset($option['label'][$cart->id_lang]) ?
                    $option['label'][$cart->id_lang] : $default;

                $option['localized_label'] = $option_label;

                // compute some fields
                $count = (int)$option['count'];
                $rate = (float)$option['rate'];

                $max_fees = null;
                switch ($count) {
                    case 3:
                        $max_fees = self::ONEY_THREE_TIMES_MAX_FEES;
                        break;
                    case 4:
                        $max_fees = self::ONEY_FOUR_TIMES_MAX_FEES;
                        break;
                    default:
                        $max_fees = null;
                        break;
                }

                $payment = round($amount / $count, 2);

                $fees = round($amount * $rate / 100, 2);
                if ($max_fees) {
                    $fees = min($fees, $max_fees);
                }

                $first = $amount - ($payment * ($count - 1)) + $fees;

                $option['order_total'] = Tools::displayPrice($amount);
                $option['first_payment'] = Tools::displayPrice($first);
                $option['funding_count'] = $count - 1; // real number of payments concerned by funding
                $option['monthly_payment'] = Tools::displayPrice($payment);
                $option['funding_total'] = Tools::displayPrice(($count - 1) * $payment - $fees);
                $option['funding_fees'] = Tools::displayPrice($fees);
                $option['taeg'] = ''; // TODO calculate TAEG

                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $options = array();
        if (Configuration::get($this->prefix.'ENABLE_OPTIONS') == 'True') {
            $options = self::getAvailableOptions($cart);
        }

        $vars['payzen_oney_options'] = $options;

        return $vars;
    }

    public function hasForm()
    {
        return Configuration::get($this->prefix.'ENABLE_OPTIONS') == 'True';
    }


    protected function getDefaultTitle()
    {
        return $this->l('Payment with FacilyPay Oney');
    }
}
