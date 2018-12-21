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

class PayzenStandardPayment extends AbstractPayzenPayment
{
    protected $prefix = 'PAYZEN_STD_';
    protected $tpl_name = 'payment_std.tpl';
    protected $logo = 'BannerLogo1.png';
    protected $name = 'standard';

    public function isAvailable($cart)
    {
        if (!parent::isAvailable($cart)) {
            return false;
        }

        if ($this->proposeOney()) {
            return PayzenTools::checkOneyRequirements($cart);
        }

        return true;
    }

    protected function proposeOney($data = array())
    {
        if (isset($data['card_type']) && !in_array($data['card_type'], array('ONEY_SANDBOX', 'ONEY'))) {
            return false;
        }

        if (Configuration::get($this->prefix.'PROPOSE_ONEY') != 'True' || $this->getEntryMode() == '3') {
            return false;
        }

        return true;
    }

    public function validate($cart, $data = array())
    {
        $errors = parent::validate($cart, $data);

        if (empty($errors) && $this->proposeOney($data)) {
            $billing_address = new Address((int)$cart->id_address_invoice);

            // check address validity according to FacilyPay Oney payment specifications
            $errors = PayzenTools::checkAddress($billing_address, 'billing', $this->name);

            if (empty($errors)) {
                // billing address is valid, check delivery address
                $delivery_address = new Address((int)$cart->id_address_delivery);

                $errors = PayzenTools::checkAddress($delivery_address, 'delivery', $this->name);
            }
        }

        return $errors;
    }

    public function getTplVars($cart)
    {
        $vars = parent::getTplVars($cart);

        $entry_mode = $this->getEntryMode();
        $vars['payzen_std_card_data_mode'] = $entry_mode;

        if ($entry_mode == '2' /* card type on website */ || $entry_mode == '3' /* data entry on website */) {
            $vars['payzen_avail_cards'] = $this->getPaymentCards();
        } elseif ($this->getEntryMode() == '4' /* iframe mode */) {
            $this->tpl_name = 'payment_std_iframe.tpl';
        }

        return $vars;
    }

    private function getPaymentCards()
    {
        // get selected card types
        $cards = Configuration::get($this->prefix.'PAYMENT_CARDS');
        if (!empty($cards)) {
            $cards = explode(';', $cards);
        } else {
            // no card type selected, display all supported cards
            $cards = array_keys(PayzenTools::getSupportedCardTypes());
        }

        if ($this->proposeOney()) {
            $cards[] = (Configuration::get('PAYZEN_MODE') == 'TEST') ? 'ONEY_SANDBOX' : 'ONEY';
        }

        // retrieve card labels
        $avail_cards = array();
        foreach (PayzenApi::getSupportedCardTypes() as $code => $label) {
            if (in_array($code, $cards)) {
                $avail_cards[$code] = $label;
            }
        }

        return $avail_cards;
    }

    public function getEntryMode()
    {
        // get data entry mode
        $entry_mode = Configuration::get($this->prefix.'CARD_DATA_MODE');
        if ($entry_mode == '3' && !$this->checkSsl()) {
            $entry_mode = '1'; // no data entry on merchant site without SSL
        }

        return $entry_mode;
    }

    private function checkSsl()
    {
        return Configuration::get('PS_SSL_ENABLED') && Tools::usingSecureMode();
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

        if (isset($data['iframe_mode']) && $data['iframe_mode']) {
            $request->set('action_mode', 'IFRAME');

            // hide logos below payment fields
            $request->set('theme_config', Configuration::get('theme_config') . '3DS_LOGOS=false;');

            // enable automatic redirection
            $request->set('redirect_enabled', '1');
            $request->set('redirect_success_timeout', '0');
            $request->set('redirect_error_timeout', '0');

            $return_url = $request->get('url_return');
            $sep = strpos($return_url, '?') === false ? '?' : '&';
            $request->set('url_return', $return_url.$sep.'content_only=1');
        }

        if (isset($data['card_type']) && $data['card_type']) {
            // override payemnt_cards parameter
            $request->set('payment_cards', $data['card_type']);

            if ($data['card_type'] === 'BANCONTACT') {
                // may not disable 3DS for Bancontact Mistercash
                $request->set('threeds_mpi', null);
            }

            if (isset($data['card_number']) && $data['card_number']) {
                $request->set('card_number', $data['card_number']);
                $request->set('cvv', $data['cvv']);
                $request->set('expiry_year', $data['expiry_year']);
                $request->set('expiry_month', $data['expiry_month']);

                // override action_mode to do a silent payment
                $request->set('action_mode', 'SILENT');
            }
        } else {
            $cards = Configuration::get($this->prefix.'PAYMENT_CARDS');
            if (!empty($cards) && $this->proposeOney()) {
                $cards .= ';'.(Configuration::get('PAYZEN_MODE') == 'TEST' ? 'ONEY_SANDBOX' : 'ONEY');
            }

            $request->set('payment_cards', $cards);
        }

        return $request;
    }

    public function hasForm()
    {
        if ($this->getEntryMode() == '1') {
            return false;
        }

        return true;
    }

    protected function getDefaultTitle()
    {
        return $this->l('Payment by credit card');
    }
}
