<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

spl_autoload_register('payzenLoadClass', true, true);

function payzenLoadClass($class)
{
    if (strpos($class, 'Payzen') === false) {
        return;
    }

    $generalPath = _PS_MODULE_DIR_ . 'payzen' . DIRECTORY_SEPARATOR . 'classes';
    $adminPath = $generalPath . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $class . '.php';
    $paymentPath = $generalPath . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . $class . '.php';

    if (is_file($adminPath)) {
        require_once $adminPath;
    } elseif (is_file($paymentPath)) {
        require_once $paymentPath;
    } elseif (is_file($generalPath . DIRECTORY_SEPARATOR . $class . '.php')) {
        require_once $generalPath . DIRECTORY_SEPARATOR . $class . '.php';
    }
}
