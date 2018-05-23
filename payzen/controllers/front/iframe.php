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

class PayzenIframeModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Configuration::get('PAYZEN_CART_MANAGEMENT') != PayzenTools::KEEP_CART) {
            if ($this->context->cart->id) {
                $this->context->cookie->payzenCartId = (int)$this->context->cart->id;
            }

            if (isset($this->context->cookie->payzenCartId)) {
                $this->context->cookie->id_cart = $this->context->cookie->payzenCartId;
            }
        }

        $this->setTemplate(PayzenTools::getTemplatePath('iframe/loader.tpl'));
    }
}
