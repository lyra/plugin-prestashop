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

class PayzenMultiPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_MULTI_';
    protected $tpl_name = 'payment_multi.tpl';
    protected $logo = 'multi.png';
    protected $name = 'multi';

    public function isAvailable($cart)
    {
        if (! parent::isAvailable($cart)) {
            return false;
        }

        // Check available payment options.
        $options = self::getAvailableOptions($cart);
        if (empty($options)) {
            return false;
        }

        return true;
    }

    public static function getAvailableOptions($cart = null)
    {
        // Multi payment options.
        $options = @unserialize(Configuration::get('PAYZEN_MULTI_OPTIONS'));
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

                $option['localized_label'] = $option_label;

                $enabled_options[$key] = $option;
            }
        }

        return $enabled_options;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);
        $vars['payzen_multi_options'] = self::getAvailableOptions($cart);

        $entry_mode = Configuration::get($this->prefix . 'CARD_MODE');
        $vars['payzen_multi_card_mode'] = $entry_mode;

        if ($entry_mode === '2') {
            $vars['payzen_avail_cards'] = $this->getPaymentCards();
        }

        return $vars;
    }

    private function getPaymentCards()
    {
        $all_cards = PayzenTools::getSupportedMultiCardTypes();

        // Get selected card types.
        $config_cards = Configuration::get($this->prefix . 'PAYMENT_CARDS');
        if (! empty($config_cards)) {
            $cards = explode(';', $config_cards); // Card codes only.

            // Retrieve card labels.
            $avail_cards = array();
            foreach ($all_cards as $code => $label) {
                if (in_array($code, $cards)) {
                    $avail_cards[$code] = $label;
                }
            }
        } else {
            // No card type selected, display all supported cards.
            $avail_cards = $all_cards;
        }

        return $avail_cards;
    }

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        $multi_options = self::getAvailableOptions($cart);
        $option = $multi_options[$data['opt']];

        $amount = $request->get('amount');

        $config_first = $option['first'];
        $first = $config_first ? round(($config_first / 100) * $amount) : null;
        $request->setMultiPayment(null /* To use already set amount. */, $first, $option['count'], $option['period']);

        // Override cb contract.
        $request->set('contracts', ($option['contract']) ? 'CB=' . $option['contract'] : null);

        if (isset($data['card_type']) && $data['card_type']) {
            // Override payment_cards parameter.
            $request->set('payment_cards', $data['card_type']);
        } else {
            $cards = Configuration::get($this->prefix . 'PAYMENT_CARDS');
            $request->set('payment_cards', $cards);
        }

        // Override title to append selected option.
        $request->set('order_info', 'module_id=' . $this->name . '&option_id=' . $data['opt']);

        return $request;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment by credit card in installments');
    }
}
