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

/**
 * Extend logger class to implement logging disable and avoid to check before every log operation.
 */
class PayzenFileLogger extends FileLogger
{
    private $logs_enabled = false;

    public function __construct($logs_enabled, $level = self::INFO)
    {
        $this->logs_enabled = $logs_enabled;

        parent::__construct($level);
    }

    /**
     * Log message only if logs are enabled.
     *
     * @param string message
     * @param level
     */
    public function log($message, $level = self::DEBUG)
    {
        if (! $this->logs_enabled) {
            return;
        }

        parent::log($message, $level);
    }
}
