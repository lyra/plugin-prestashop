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

class PayzenFullcbPayment extends AbstractPayzenPayment
{
    const FULLCB_THREE_TIMES_MAX_FEES = 9;
    const FULLCB_FOUR_TIMES_MAX_FEES = 12;

    protected $prefix = 'PAYZEN_FULLCB_';
    protected $tpl_name = 'payment_fullcb.tpl';
    protected $logo = 'fullcb.png';
    protected $name = 'fullcb';

    protected $currencies = array('EUR');

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        if (Configuration::get($this->prefix.'ENABLE_OPTS') == 'True') {
            $options = self::getAvailableOptions($cart);

            if (empty($options)) {
                return false;
            }
        }

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
        if (isset($data['card_type']) && $data['card_type']) {
            $request->set('payment_cards', $data['card_type']);
        } else {
            $request->set('payment_cards', 'FULLCB3X;FULLCB4X');
        }

        // by default PrestaShop does not manage customer type
        $request->set('cust_status', 'PRIVATE');

        // override FullCb specific params
        $request->set('validation_mode', '0');
        $request->set('capture_delay', '0');

        return $request;
    }

    public static function getAvailableOptions($cart)
    {
        // fullcb payment options
        $options = @unserialize(Configuration::get('PAYZEN_FULLCB_OPTIONS'));

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

                $max_fees = $option['cap'];
                if (!$max_fees) {
                    switch ($count) {
                        case 3:
                            $max_fees = self::FULLCB_THREE_TIMES_MAX_FEES;
                            break;
                        case 4:
                            $max_fees = self::FULLCB_FOUR_TIMES_MAX_FEES;
                            break;
                        default:
                            $max_fees = null;
                            break;
                    }
                }

                $payment = round($amount / $count, 2);

                $fees = round($amount * $rate / 100, 2);
                if ($max_fees) {
                    $fees = min($fees, $max_fees);
                }

                $first = $amount - ($payment * ($count - 1)) + $fees;

                $option['order_amount'] = Tools::displayPrice($amount);
                $option['first_payment'] = Tools::displayPrice($first);
                $option['monthly_payment'] = Tools::displayPrice($payment);
                $option['total_amount'] = Tools::displayPrice($amount + $fees);
                $option['fees'] = Tools::displayPrice($fees);

                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $options = array();
        if (Configuration::get($this->prefix.'ENABLE_OPTS') == 'True') {
            $options = self::getAvailableOptions($cart);
        }

        $vars['payzen_fullcb_options'] = $options;

        return $vars;
    }

    public function hasForm()
    {
        return Configuration::get($this->prefix.'ENABLE_OPTS') == 'True';
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with FullCB');
    }
}
