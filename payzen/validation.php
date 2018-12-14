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

/*
* The payment platform can use only one check url,
* but prestashop may have both standard payment module or the multi-payment module.
* They need to have their validation code in the same place (i.e. here),
* so we detect the case and load the appropriate module.
*/
if ((key_exists('vads_payment_config', $_REQUEST) && stripos($_REQUEST['vads_payment_config'], 'MULTI') !== false)
    || (key_exists('vads_contrib', $_REQUEST) && stripos($_REQUEST['vads_contrib'], 'multi') !== false)) {

    /* multi payment : let multi module do the work */
    require_once(dirname(dirname(__FILE__)).'/payzenmulti/validation.php');
    die();
}

require_once dirname(dirname(dirname(__FILE__))).'/config/config.inc.php';
require_once dirname(dirname(dirname(__FILE__))).'/init.php';

/* damn global variables */
global $cookie;

/* restore language from order info */
if ($info = Tools::getValue('vads_order_info')) {
    $parts = explode('=', $info, 2);
    $cookie->id_lang = $parts[1];
}

/**
 * @var Payzen $payzen
 * @var PayzenResponse $payzen_resp
 */

require dirname(__FILE__).'/payzen.php';
$payzen = new Payzen();

$payzen_resp = new PayzenResponse(
    $_REQUEST,
    Configuration::get('PAYZEN_CTX_MODE'),
    Configuration::get('PAYZEN_KEY_TEST'),
    Configuration::get('PAYZEN_KEY_PROD'),
    Configuration::get('PAYZEN_SIGN_ALGO')
);
$from_server = $payzen_resp->get('hash') != null;

/* check the authenticity of the request */
if (! $payzen_resp->isAuthentified()) {
    if ($from_server) {
        die($payzen_resp->getOutputForPlatform('auth_fail'));
    } else {
        /* goto index */
        Tools::redirectLink(__PS_BASE_URI__);
    }
}

/*
 * response is authentified
 */

/* retrieve cart */
$id_cart = $payzen_resp->get('order_id');
$cart = new Cart($id_cart);
if (! Validate::isLoadedObject($cart)) {
    /* unable to retrieve cart from db */
    if ($from_server) {
        die($payzen_resp->getOutputForPlatform('order_not_found'));
    } else {
        Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$id_cart.'&id_module='.$payzen->id.'&error=yes');
    }

    if (empty($cart->id_customer) || empty($cart->id_address_invoice)) {
        die();
    }
}

/* retrieve order */
$id_order = intval(Order::getOrderByCartId($cart->id));
$order = new Order($id_order);

$extra_param = '';

if ($payzen_resp->get('ctx_mode') == 'TEST') {
    $extra_param .= '&prod_info=yes';
}

/* act according to case */
if (empty($order->id_cart)) {
    /* order has not been accepted yet */
    if ($payzen_resp->isAcceptedPayment()) {
        /* payment OK */

        $order = $payzen->validate($id_cart, _PS_OS_PAYMENT_, $payzen_resp);

        /* display success message */
        if ($from_server) {
            /* display server code */
            die ($payzen_resp->getOutputForPlatform('payment_ok'));
        } else {
            if ($payzen_resp->get('ctx_mode') == 'TEST') {
                /* TEST mode (user is the webmaster) : IPN did not work, so we display a warning */
                $extra_param .= '&check_url_warn=yes';
            }

            /* amount paid not equals initial amount. Error ! */
            if (number_format($order->total_paid, 2) != number_format($payzen_resp->getFloatAmount(), 2)) {
                $extra_param .= '&error=yes';
            }

            Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$id_cart.'&id_module='.$payzen->id
                .'&id_order='.$order->id.'&key='.$order->secure_key.$extra_param);
        }
    } else {
        /* payment KO */
        $payzen->managePaymentFailure($payzen_resp, $id_cart, $from_server, $order);
    }
} else {
    /* order already registered */
    if ($order->hasBeenPaid() && $payzen_resp->isAcceptedPayment()) {
        /* just display a confirmation message */
        if ($from_server) {
            die($payzen_resp->getOutputForPlatform('payment_ok_already_done'));
        } else {
            Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$id_cart.'&id_module='.$payzen->id.
                '&id_order='.$order->id.'&key='.$order->secure_key.$extra_param);
        }
    } if (! $order->hasBeenPaid() && ! $payzen_resp->isAcceptedPayment()) {
        /* order has been registred with payment error status. Payment failure reconfirmed. */
        if ($from_server) {
            die($payzen_resp->getOutputForPlatform('payment_ko_already_done'));
        } else {
            Tools::redirectLink(__PS_BASE_URI__.'history.php');
        }
    } else {
        /* invalid payment code received, but order has already been registered ! */
        if ($from_server) {
            die($payzen_resp->getOutputForPlatform('payment_ko_on_order_ok'));
        } else {
            $extra_param .= '&error=yes';

            Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$id_cart.'&id_module='.$payzen->id
                .'&id_order='.$order->id.'&key='.$order->secure_key.$extra_param);
        }
    }
}
