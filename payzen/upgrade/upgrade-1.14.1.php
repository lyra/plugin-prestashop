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

function upgrade_module_1_14_1($module)
{
    define('PAYZEN_MODULE_UPGRADE', true);

    $test_mode = Configuration::get('PAYZEN_MODE') === 'TEST';
    $private_key = $test_mode ? Configuration::get('PAYZEN_PRIVKEY_TEST') : Configuration::get('PAYZEN_PRIVKEY_PROD');
    $default['PAYZEN_ENABLE_WS'] = empty($private_key) ? 'disabled' : 'enabled';

    define('PAYZEN_TRANSIENT_DEFAULT', serialize($default));

    return $module->install();
}
