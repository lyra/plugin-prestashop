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

class PayzenAncvPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_ANCV_';
    protected $tpl_name = 'payment_ancv.tpl';
    protected $logo = 'cvco.png';
    protected $name = 'ancv';

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);
        if (! empty($errors)) {
            return $errors;
        }

        $billing_address = new Address((int) $cart->id_address_invoice);
        $billing_country = new Country((int) $billing_address->id_country);

        if ($billing_country->iso_code !== 'FR') {
            $errors[] = $this->l('Country not supported by ANCV payment.');
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

        // Override with ANCV card.
        $request->set('payment_cards', 'E_CV;CVCO');

        return $request;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment with ANCV');
    }
}
