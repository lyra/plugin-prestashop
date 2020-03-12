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

class PayzenPaypalPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_PAYPAL_';
    protected $tpl_name = 'payment_paypal.tpl';
    protected $logo = 'paypal.png';
    protected $name = 'paypal';
    protected $needs_cart_data = true;

    /**
     * {@inheritDoc}
     * @see AbstractPayzenPayment::prepareRequest()
     */
    public function prepareRequest($cart, $data = array())
    {
        $request = parent::prepareRequest($cart, $data);

        // Override with PayPal cards.
        $test_mode = $request->get('ctx_mode') === 'TEST';
        $request->set('payment_cards', $test_mode ? 'PAYPAL_SB' : 'PAYPAL');

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with Paypal');
    }
}
