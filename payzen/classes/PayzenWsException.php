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

class PayzenWsException extends Exception
{
    protected $code;

    /**
     * @param message[optional]
     * @param code[optional]
     */
    public function __construct ($message, $code = null)
    {
        parent::__construct($message, null);
        $this->code = $code;
    }
}
