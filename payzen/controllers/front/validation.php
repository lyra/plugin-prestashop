<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

/**
 * Instant payment notification file. Wait for payment gateway confirmation, then validate order.
 */
require_once(_PS_MODULE_DIR_ . 'payzen/classes/PayzenIpnProcessor.php');

class PayzenValidationModuleFrontController extends ModuleFrontController
{
    private $processor;

    public function __construct()
    {
        parent::__construct();

        $this->processor = new PayzenIpnProcessor();
    }

    public function postProcess()
    {
       $this->processor->process();
    }

}
