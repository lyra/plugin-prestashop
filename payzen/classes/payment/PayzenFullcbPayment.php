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

class PayzenFullcbPayment extends AbstractPayzenPayment
{
    const FULLCB_THREE_TIMES_MAX_FEES = 9;
    const FULLCB_FOUR_TIMES_MAX_FEES = 12;

    protected $prefix = 'PAYZEN_FULLCB_';
    protected $tpl_name = 'payment_fullcb.tpl';
    protected $logo = 'fullcb.png';
    protected $name = 'fullcb';

    protected $currencies = array('EUR');
    protected $countries = array('FR');

    public function isAvailable($cart)
    {
        if (! parent::isAvailable($cart)) {
            return false;
        }

        if (Configuration::get($this->prefix . 'ENABLE_OPTS') === 'True') {
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
        if (! empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int) $cart->id_address_invoice);

        // Check address validity according to FullCB payment specifications.
        $errors = PayzenTools::checkAddress($billing_address, 'billing', $this->name);

        if (empty($errors)) {
            // Billing address is valid, check delivery address.
            $delivery_address = new Address((int) $cart->id_address_delivery);

            $errors = PayzenTools::checkAddress($delivery_address, 'delivery', $this->name);
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

        // Override with FullCB payment cards.
        if (isset($data['card_type']) && $data['card_type']) {
            $request->set('payment_cards', $data['card_type']);
        } else {
            $request->set('payment_cards', 'FULLCB3X;FULLCB4X');
        }

        // By default PrestaShop does not manage customer type.
        $request->set('cust_status', 'PRIVATE');

        // Override FullCb specific params.
        $request->set('validation_mode', '0');
        $request->set('capture_delay', '0');

        return $request;
    }

    public static function getAvailableOptions($cart)
    {
        // Fullcb payment options.
        $options = @unserialize(Configuration::get('PAYZEN_FULLCB_OPTIONS'));

        if (! is_array($options) || empty($options)) {
            return array();
        }

        $amount = $cart->getOrderTotal();

        $enabled_options = array();
        foreach ($options as $key => $option) {
            if (isset($option['enabled']) && ($option['enabled'] !== 'True')) {
                continue;
            }

            $min = $option['min_amount'];
            $max = $option['max_amount'];

            if ((empty($min) || $amount >= $min) && (empty($max) || $amount <= $max)) {
                $default = is_string($option['label']) ? $option['label'] : $option['count'] . ' x';
                $option_label = is_array($option['label']) && isset($option['label'][$cart->id_lang]) ?
                $option['label'][$cart->id_lang] : $default;

                $option['localized_label'] = $option_label;

                // Compute some fields.
                $count = (int) $option['count'];
                $rate = (float) $option['rate'];

                $max_fees = $option['cap'];
                if (! $max_fees) {
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

                $context = Context::getContext();

                $option['order_amount'] = PayzenTools::formatPrice($amount, $cart->id_currency, $context);
                $option['first_payment'] = PayzenTools::formatPrice($first, $cart->id_currency, $context);
                $option['monthly_payment'] = PayzenTools::formatPrice($payment, $cart->id_currency, $context);
                $option['total_amount'] = PayzenTools::formatPrice($amount + $fees, $cart->id_currency, $context);
                $option['fees'] = PayzenTools::formatPrice($fees, $cart->id_currency, $context);

                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $options = array();
        if (Configuration::get($this->prefix . 'ENABLE_OPTS') === 'True') {
            $options = self::getAvailableOptions($cart);
        }

        $vars['payzen_fullcb_options'] = $options;

        return $vars;
    }

    public function hasForm()
    {
        return Configuration::get($this->prefix . 'ENABLE_OPTS') === 'True';
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with FullCB');
    }
}
