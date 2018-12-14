<?php
/**
 * PayZen V2-Payment Module version 1.4.7 for PrestaShop 1.4. Support contact : support@payzen.eu.
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

require_once dirname(dirname(dirname(__FILE__))).'/config/config.inc.php';
require_once dirname(dirname(dirname(__FILE__))).'/init.php';
require_once dirname(__FILE__).'/payzen.php';

/* @var $smarty Smarty */
global $smarty;

$payzen = new Payzen();

/* assigning form params and url */
$payzen->prepareForm();

if (Configuration::get('PS_ORDER_PROCESS_TYPE')) {
    $smarty->assign('payzen_opc_enabled', true);
}

/* disable some cache */
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()-10).' GMT');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT' ); /* date in the past */
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
header('Pragma: no-cache');

/* display all and exit */
include(_PS_ROOT_DIR_.'/header.php');

/* dispaly form in redirect page */
echo $payzen->display('payzen.php', 'redirect.tpl');

include(_PS_ROOT_DIR_.'/footer.php');

die();
