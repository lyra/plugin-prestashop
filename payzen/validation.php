<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement PayZen
#						Version : 1.4f (révision 46408)
#									########################
#					Développé pour Prestashop
#						Version : 1.4.0.x
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						22/04/2013
#						Contact : support@payzen.eu
#
#####################################################################################################

/*
* The payment platform can use only one check url,
* but prestashop may have both standard payment module or the multi-payment module.
* They need to have their validation code in the same place (i.e. here),
* so we detect the case and load the appropriate module.
*/
if ((array_key_exists('vads_payment_config', $_REQUEST) && stripos($_REQUEST['vads_payment_config'], 'MULTI') !== false)
		|| (array_key_exists('vads_contrib', $_REQUEST) && stripos($_REQUEST['vads_contrib'], 'multi') !== false)) {

	// Multi payment : let multi module do the work
	require_once(dirname(dirname(__FILE__)) . '/payzenmulti/validation.php');
	die();
}

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.inc.php';
require_once dirname(dirname(dirname(__FILE__))) . '/init.php';

// Damn global variables
global $cookie;

// restore language from order info
if(key_exists('vads_order_info', $_REQUEST) && !empty($_REQUEST['vads_order_info'])) {
	$parts = explode('=', $_REQUEST['vads_order_info'], 2);
	$cookie->id_lang = $parts[1];
}

/**
 * @var Payzen $payzen
 * @var PayzenResponse $payzen_resp
 */

require dirname(__FILE__) . '/payzen.php';
$payzen = new Payzen();

$payzen_resp = new PayzenResponse($_REQUEST, Configuration::get('PAYZEN_MODE'), Configuration::get('PAYZEN_KEY_TEST'), Configuration::get('PAYZEN_KEY_PROD'));
$from_server = $payzen_resp->get('hash') != null;

// Check the authenticity of the request
if (!$payzen_resp->isAuthentified()) {
	if ($from_server) {
		die($payzen_resp->getOutputForGateway('auth_fail'));
	} else {
		// Goto index
		Tools::redirectLink(__PS_BASE_URI__);
	}
}

/*
 * response is authentified
 */

// Retrieve cart
$id_cart = $payzen_resp->get('order_id');
$cart = new Cart($id_cart);
if (!$cart) {
	// unable to retrieve cart from db
	if ($from_server) {
		die($payzen_resp->getOutputForGateway('order_not_found'));
	} else {
		Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . $id_cart . '&id_module=' . $payzen->id . '&error=yes');
	}
}

// Retrieve order
$id_order = intval(Order::getOrderByCartId($cart->id));
$order = new Order($id_order);

$extra_param = "";

if ($payzen_resp->get('ctx_mode') == 'TEST') {
	$extra_param .= "&prod_info=yes";
}

// Act according to case
if (empty($order->id_cart)) {
	// Order has not been accepted yet
	if ($payzen_resp->isAcceptedPayment()) {
		// Payment OK
		
		$order = $payzen->validate($id_cart, _PS_OS_PAYMENT_, $payzen_resp);

		// Display success message
		if ($from_server) {
			// Display server code
			die ($payzen_resp->getOutputForGateway('payment_ok'));
		} else {
			if ($payzen_resp->get('ctx_mode') == 'TEST') {
				// !$from_server => this is a client return
				// ctx_mode=TEST => the user is the webmaster
				// order has not been paid, but we receive a successful payment code => automatic response didn't work
				// So we display a warning about the not working check_url
				$extra_param .= "&check_url_warn=yes";
			}
			
			// Amount paid not equals initial amount. Error ! 
			if (number_format($order->total_paid, 2) != number_format($payzen_resp->getFloatAmount(), 2)) {
				$extra_param .= "&error=yes";
			}
			
			Tools::redirectLink(
					__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . $id_cart
							. '&id_module=' . $payzen->id . '&id_order=' . $order->id . '&key='
							. $order->secure_key . $extra_param);
		}
	} else {
		// Payment KO
		$payzen->managePaymentFailure($payzen_resp, $id_cart, $from_server, $order);
	}
} else {
	// Order already registered
	if ($order->hasBeenPaid() && $payzen_resp->isAcceptedPayment()) {
		// Just display a confirmation message
		if ($from_server) {
			die($payzen_resp->getOutputForGateway('payment_ok_already_done'));
		} else {
			Tools::redirectLink(
					__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . $id_cart
							. '&id_module=' . $payzen->id . '&id_order=' . $order->id . '&key='
							. $order->secure_key . $extra_param);
		}
	} if(!$order->hasBeenPaid() && !$payzen_resp->isAcceptedPayment()) {
		// Order has been registred with payment error status. Payment failure reconfirmed. 
		if ($from_server) {
			die($payzen_resp->getOutputForGateway('payment_ko_already_done'));
		} else {
			Tools::redirectLink(__PS_BASE_URI__ . 'history.php');
		}
	} else {
		// Invalid payment code received, but order has already been registered !
		if ($from_server) {
			die($payzen_resp->getOutputForGateway('payment_ko_on_order_ok'));
		} else {
			$extra_param .= "&error=yes";
						
			Tools::redirectLink(
					__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . $id_cart
							. '&id_module=' . $payzen->id . '&id_order=' . $order->id . '&key='
							. $order->secure_key . $extra_param);
		}
	}
}
?>