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

class PayzenGroupedOtherPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_OTHER_';
    protected $tpl_name = 'payment_grouped_other.tpl';
    protected $logo = 'other.png';
    protected $name = 'grouped_other';

    protected $other_payments;

    public function setPaymentMeans($other_payments)
    {
        $this->other_payments = $other_payments;
    }

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        // check available payment options
        if (empty($this->other_payments)) {
            return false;
        }

        return true;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $options = array();
        foreach ($this->other_payments as $payment) {
            $title = is_array($payment['title']) ? $payment['title'][(int)$cart->id_lang] : $payment['title'];

            $options[$payment['code']] = $title;
        }

        $vars['payzen_other_options'] = $options;

        return $vars;
    }

    public function getPaymentOption($cart)
    {
        $option = parent::getPaymentOption($cart);

        $inputs = $option->getInputs();
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
        $request = parent::prepareRequest($cart, $data);

        $request->set('payment_cards', $data['card_type']);

        return $request;
    }

    public function hasForm()
    {
        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Other payment means');
    }
}
