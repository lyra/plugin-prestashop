<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class PayzenIframeModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Configuration::get('PAYZEN_CART_MANAGEMENT') !== PayzenTools::KEEP_CART) {
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
