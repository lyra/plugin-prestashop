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

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.inc.php';
require_once dirname(dirname(dirname(__FILE__))) . '/init.php';
require_once dirname(__FILE__) . '/payzen.php';

/* @var $smarty Smarty */ 
global $smarty;

$payzen = new Payzen();

// assigning form params and url
$payzen->prepareForm(); 

if(Configuration::get('PS_ORDER_PROCESS_TYPE')) {
	$smarty->assign('payzen_opc_enabled', true);
} 

// Disable some cache 
header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()-10) . " GMT");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT" ); // Date in the past
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0");
header("Pragma: no-cache");

// Display all and exit
include(_PS_ROOT_DIR_.'/header.php');

// dispaly form in redirect page
echo $payzen->display('payzen.php', 'redirect.tpl');

include(_PS_ROOT_DIR_.'/footer.php');

die ();
?>