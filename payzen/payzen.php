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

if (!defined('_CAN_LOAD_FILES_')) {
	exit;
}

if (!defined('_PS_ROOT_DIR_')) {
	include_once dirname(__FILE__) . '/payzen_api.php';
} else {
	include_once _PS_ROOT_DIR_ . '/modules/payzen/payzen_api.php';
}



class Payzen extends PaymentModule {
	const ON_FAILURE_RETRY = 'retry';
	const ON_FAILURE_GOTOHISTORY = 'gotohistory';
	
	/**
	 * Admin form
	 * @var string
	 */
	private $_html = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = 'payzen';
		$this->tab = 'payments_gateways';
		$this->version = '1.4f';
		$this->currencies = false;

		parent::__construct();

		$this->displayName = $this->l('PayZen');
		$this->description = $this->l('Pay by credit card with PayZen');
	}

	/**
	 * Return the list of configuration names and their default value
	 * @return array[string]string 
	 */
	private function _getAdminParameters() {
		//NB : names are 32 chars max
		$base_url = 'http://'
				. htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8')
				. __PS_BASE_URI__;
		return array(
				'PAYZEN_SITE_ID' => '12345678',
				'PAYZEN_PLATFORM_URL' => 'https://secure.payzen.eu/vads-payment/',
				'PAYZEN_MODE' => 'TEST',
				'PAYZEN_DELAY' => '',
				'PAYZEN_AVAILABLE_LANGUAGES' => '',
				'PAYZEN_LANGAGE_DEFAUT' => 'fr',
				'PAYZEN_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
				'PAYZEN_SHOP_URL' => $base_url,
				'PAYZEN_PAYMENT_CARDS' => '',
				'PAYZEN_AMOUNT_MIN' => '',
				'PAYZEN_AMOUNT_MAX' => '',
				'PAYZEN_VALIDATION_MODE' => '',
				'PAYZEN_URL_RETURN' => $base_url . 'modules/' . $this->name . '/validation.php',
				'PAYZEN_KEY_TEST' => '1111111111111111',
				'PAYZEN_KEY_PROD' => '2222222222222222',
				'PAYZEN_RETURN_MODE' => 'GET',
				'PAYZEN_REDIRECT_ENABLED' => 'False',
				'PAYZEN_REDIRECT_SUCCESS_T' => 5,
				'PAYZEN_REDIRECT_SUCCESS_M' => 'Redirection vers la boutique dans quelques instants',
				'PAYZEN_REDIRECT_ERROR_T' => 5,
				'PAYZEN_REDIRECT_ERROR_M' => 'Redirection vers la boutique dans quelques instants',
				'PAYZEN_FAILURE_MANAGEMENT' => self::ON_FAILURE_RETRY,
				'PAYZEN_RETURN_GET_PARAMS' => '',
				//TODO compilable smarty ? => return_get_params = customer={$customer->id}
				'PAYZEN_RETURN_POST_PARAMS' => '',
				'PAYZEN_3DS_MIN_AMOUNT' => ''
		);
	}

	private function _prestashopToPayzenNames() {
		return array(
				'PAYZEN_PLATFORM_URL' => 'platform_url',
				'PAYZEN_KEY_TEST' => 'key_test',
				'PAYZEN_KEY_PROD' => 'key_prod',
				'PAYZEN_MODE' => 'ctx_mode',
				'PAYZEN_DELAY' => 'capture_delay',
				'PAYZEN_AVAILABLE_LANGUAGES' => 'available_languages',
				'PAYZEN_SHOP_NAME' => 'shop_name',
				'PAYZEN_SHOP_URL' => 'shop_url',
				'PAYZEN_PAYMENT_CARDS' => 'payment_cards',
				'PAYZEN_SITE_ID' => 'site_id',
				'PAYZEN_VALIDATION_MODE' => 'validation_mode',
				'PAYZEN_URL_RETURN' => 'url_return',
				'PAYZEN_RETURN_MODE' => 'return_mode',
				'PAYZEN_REDIRECT_ENABLED' => 'redirect_enabled',
				'PAYZEN_REDIRECT_SUCCESS_T' => 'redirect_success_timeout',
				'PAYZEN_REDIRECT_SUCCESS_M' => 'redirect_success_message',
				'PAYZEN_REDIRECT_ERROR_T' => 'redirect_error_timeout',
				'PAYZEN_REDIRECT_ERROR_M' => 'redirect_error_message',
				'PAYZEN_RETURN_GET_PARAMS' => 'return_get_params',
				'PAYZEN_RETURN_POST_PARAMS' => 'return_post_params');
	}

	/**
	 * Returns a new PayzenApi object loaded with the module configuration
	 * @return PayzenApi
	 */
	public function getLoadedApi() {
		$api = new PayzenApi();
		$api->set('version', 'V2');
		$api->set('payment_config', 'SINGLE');
		$api->set('contrib', 'Prestashop1.4.0.x_1.4f/' . _PS_VERSION_);

		foreach ($this->_prestashopToPayzenNames() as $psName => $payzenName) {
			$api->set($payzenName, Configuration::get($psName));
		}

		return $api;
	}

	/**
	 * (non-PHPdoc)
	 * @see PaymentModuleCore::install()
	 */
	public function install() {
		if (!parent::install() || !$this->registerHook('payment')
				|| !$this->registerHook('orderConfirmation')) {
			return false;
		}

		foreach ($this->_getAdminParameters() as $name => $default) {
			if (!Configuration::updateValue($name, $default)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see PaymentModuleCore::uninstall()
	 */
	public function uninstall() {
		foreach ($this->_getAdminParameters() as $name => $default) {
			if (!Configuration::deleteByName($name)) {
				return false;
			}
		}

		return parent::uninstall();
	}

	/**
	 * Admin form management
	 * @return string
	 */
	public function getContent() {
		if (Tools::isSubmit('submitPayzen')) {
			$this->postProcess();
		}

		$this->_adminForm();
		return $this->_html;
	}

	/**
	 * Validate and save admin parameters from admin form
	 */
	public function postProcess() {
		$vars = $this->_getAdminParameters();
		$payzenNames = $this->_prestashopToPayzenNames();
		$api = new PayzenApi();
		$to_save = array();
		
		// Manage PAYZEN_PAYMENT_CARDS special value "ALL"
		if(is_array($_REQUEST['PAYZEN_PAYMENT_CARDS']) && in_array('ALL', $_REQUEST['PAYZEN_PAYMENT_CARDS'])) {
			$_REQUEST['PAYZEN_PAYMENT_CARDS'] = '';
		}
		
		// Load and validate from request
		foreach ($vars as $field_name => $default) {
			$value = array_key_exists($field_name, $_REQUEST)
					? $_REQUEST[$field_name]
					: null;
			$value = is_array($value) ? implode(';', $value) : $value;
			// Validate with PayzenApi
			if (array_key_exists($field_name, $payzenNames)) {
				if (!$api->set($payzenNames[$field_name], $value)) {
					$this->_errors[] = $this->l('Invalid value') . ' "' . $value . '" '
							. $this->l(' for field : ') . $field_name;
					continue;
				}
			}
			// Valid field : try to save into DB
			if (!Configuration::updateValue($field_name, $value)) {
				$this->_errors[] = $this->l('Problem occured while saving field : ')
						. $field_name;
			}
		}

		// If no error, display OK
		if (!is_array($this->_errors) || count($this->_errors) < 1) {
			$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" />'
					. $this->l('Settings updated') . '</div>';
		} else {
			// Else display errors
			$this->_html .= $this->displayError(implode('<br/>', $this->_errors));
		}
	}

	/**
	 * Builds the html code for the admin form
	 */
	public function _adminForm() {
		$submit_button = '<div class="clear"><input type="submit" class="button" name="submitPayzen" value="'
				. $this->l('Save') . '" /></div>';

		// Form beginning
		$this->_html .= '<form action="' . $_SERVER['REQUEST_URI']
				. '" method="post">';
		$this->_html .= $submit_button . '<br/>';
		$this->_html .= '
			<fieldset>
				<legend><img src="../modules/' . $this->name
				. '/logo.gif" alt="logo"/>' . $this->displayName . '</legend>
				' . $this->l('Developped by')
				. ' : <b><a href="http://www.lyra-network.com/" target="_blank">Lyra-Network</a></b><br/>
				' . $this->l('Contact email')
				. ' : <b>support@payzen.eu</b><br/>
				' . $this->l('Module version')
				. ' : <b>1.4f</b><br/>
				' . $this->l('Compatible with payment gateway')
				. ' : <b>V2</b><br/>
				' . $this->l('Tested with prestashop version')
				. ' : <b>1.4.0.x</b>
			</fieldset>
			<div class="clear">&nbsp;</div>';

		/*
		 * General configuration
		 */
		$this->_html .= '<fieldset><legend>' . $this->l('Payment gateway access')
				. '</legend>';
		$this->_adminFormTextinput('PAYZEN_SITE_ID', $this->l('Site id'),
						$this->l('Site id provided by the payment gateway'));
		$this->_adminFormTextinput('PAYZEN_KEY_TEST', $this->l('Test certificate'),
						$this->l('Certificate provided by the gateway'));
		$this->_adminFormTextinput('PAYZEN_KEY_PROD',
						$this->l('Production certificate'),
						$this->l('Certificate provided by the gateway'));
		// Mode select
		$options = array(
				'TEST' => $this->l('Test'),
				'PRODUCTION' => $this->l('Production'));
		$selected = Configuration::get('PAYZEN_MODE');
		$this->_adminFormSelect($options, $selected, 'PAYZEN_MODE', $this->l('Mode'),
						$this->l('Test or production mode'));
		$this->_adminFormTextinput('PAYZEN_PLATFORM_URL', $this->l('Gateway url'),
						$this->l('Url the client will be redirected to'), 'size="65"');
		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

		/*
		 * Payment settings
		 */
		$this->_html .= '<fieldset><legend>' . $this->l('Payment page')
				. '</legend>';
		// Langs
		//TODO use PayzenApi::getSupportedLanguages ? => prestashop translations ?
		$options = array(
				'de' => $this->l('German'),
				'en' => $this->l('English'),
				'es' => $this->l('Spanish'),
				'fr' => $this->l('French'),
				'it' => $this->l('Italian'),
				'ja' => $this->l('Japanese'),
				'zh' => $this->l('Chinese'),
				'pt' => $this->l('Portuguese'),
				'nl' => $this->l('Dutch'));
		asort($options);
		// default language
		$selected = array_key_exists(Configuration::get('PAYZEN_LANGAGE_DEFAUT'),
				$options) ? Configuration::get('PAYZEN_LANGAGE_DEFAUT') : 'fr';
		$this->_adminFormSelect($options, $selected, 'PAYZEN_LANGAGE_DEFAUT',
						$this->l('Default language'),
						$this->l('Default language on the payment page'));
		// available languages
		$selected = explode(';', Configuration::get('PAYZEN_AVAILABLE_LANGUAGES'));
		$this->_adminFormSelect($options, $selected, 'PAYZEN_AVAILABLE_LANGUAGES[]',
						$this->l('Available languages'),
						$this->l("Select none to use gateway config."),
						'multiple="multiple" size="8"');

		// Shop name
		$this->_adminFormTextinput('PAYZEN_SHOP_NAME', $this->l('Shop name'),
						$this->l('Shop name to display on the payment page. Leave blank to use gateway config.'));

		// Shop url
		$this->_adminFormTextinput('PAYZEN_SHOP_URL', $this->l('Shop url'),
						$this->l('Shop url to display on the payment page. Leave blank to use gateway config.'));

		// delay
		$this->_adminFormTextinput('PAYZEN_DELAY', $this->l('Delay'),
						$this->l('Delay before banking (in days)'));

		// validation mode
		$options = array(
				'' => $this->l('Default'),
				'0' => $this->l('Automatic'),
				'1' => $this->l('Manual'));
		$selected = array_key_exists(Configuration::get('PAYZEN_VALIDATION_MODE'),
				$options) ? Configuration::get('PAYZEN_VALIDATION_MODE') : '';
		$this->_adminFormSelect($options, $selected, 'PAYZEN_VALIDATION_MODE',
						$this->l('Payment validation'),
						$this->l('If manual is selected, you will have to confirm payments manually in your bank backoffice'));

		// cards
		//TODO plus de cartes + intégrer à PayzenApi ?
		$options = array(
				'ALL' => $this->l('All'),
				'AMEX' => $this->l('American express'),
				'CB' => $this->l('CB'),
				'MASTERCARD' => $this->l('Mastercard'),
				'VISA' => $this->l('Visa'));
		$payment_cards = Configuration::get('PAYZEN_PAYMENT_CARDS');
		$selected =($payment_cards == '') ? 'ALL' : explode(';', $payment_cards);
		$this->_adminFormSelect($options, $selected, 'PAYZEN_PAYMENT_CARDS[]',
						$this->l('Available payment cards'),
						$this->l('Select none to use gateway config.'),
						'multiple="multiple" size="4"');
		
		// min amount to activate three ds
		$this->_adminFormTextinput('PAYZEN_3DS_MIN_AMOUNT', $this->l('Minimum amount for which activate 3DS'),
						$this->l('Only if you have subscribed to Selective 3-D Secure option'));
		
		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

		/*
		 * Deactivate for specific amounts
		 */
		$this->_html .= '<fieldset><legend>' . $this->l('Amount restrictions')
				. '</legend>';
		$this->_adminFormTextinput('PAYZEN_AMOUNT_MIN', $this->l('Minimum amount'));
		$this->_adminFormTextinput('PAYZEN_AMOUNT_MAX', $this->l('Maximum amount'));
		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>';

		/*
		 * Return
		 */
		$this->_html .= '<fieldset><legend>' . $this->l('Return to shop')
				. '</legend>';
		// Automatic redirection
		$options = array(
				'False' => $this->l('Disabled'),
				'True' => $this->l('Enabled'));
		$selected = array_key_exists(Configuration::get('PAYZEN_REDIRECT_ENABLED'),
				$options) ? Configuration::get('PAYZEN_REDIRECT_ENABLED') : 'False';
		$this->_adminFormSelect($options, $selected, 'PAYZEN_REDIRECT_ENABLED',
						$this->l('Automatic redirection'),
						$this->l('Redirect the client to the shop at the end of the payment process'));
		$this->_adminFormTextinput('PAYZEN_REDIRECT_SUCCESS_T',
						$this->l('Success timeout'),
						$this->l('Time before the client is redirected after a successful payment'));
		$this->_adminFormTextinput('PAYZEN_REDIRECT_SUCCESS_M',
						$this->l('Success message'),
						$this->l('Message displayed before redirection after a successful payment'),
						'size="65"');
		$this->_adminFormTextinput('PAYZEN_REDIRECT_ERROR_T',
						$this->l('Failure timeout'),
						$this->l('Time before the client is redirected after a failed payment'));
		$this->_adminFormTextinput('PAYZEN_REDIRECT_ERROR_M',
						$this->l('Failure message'),
						$this->l('Message displayed before redirection after a failed payment'),
						'size="65"');

		// Return mode
		$options = array(
				'GET' => $this->l('GET (parameters in url)'),
				'POST' => $this->l('POST (parameters in a form)'));
		$selected = array_key_exists(Configuration::get('PAYZEN_RETURN_MODE'),
				$options) ? Configuration::get('PAYZEN_RETURN_MODE') : 'GET';
		$this->_adminFormSelect($options, $selected, 'PAYZEN_RETURN_MODE',
						$this->l('Return mode'),
						$this->l('How the client will transmit the payment result'));

		// Payment failed management
		$options = array(
				self::ON_FAILURE_RETRY => $this->l('Go back to checkout'),
				self::ON_FAILURE_GOTOHISTORY => $this->l('Save order and go back to order history'));
		$selected = array_key_exists(Configuration::get('PAYZEN_FAILURE_MANAGEMENT'),
				$options) ? Configuration::get('PAYZEN_FAILURE_MANAGEMENT') : self::ON_FAILURE_RETRY;
		$this->_adminFormSelect($options, $selected, 'PAYZEN_FAILURE_MANAGEMENT',
						$this->l('Payment failed management'),
						$this->l('How to deal the client when the payment process failed'));
		
		// Additional return parameters
		$this->_adminFormTextinput('PAYZEN_RETURN_GET_PARAMS',
						$this->l('Additional GET parameters'),
						$this->l('Extra parameters sent in the return url'), 'size="65"');

		$this->_adminFormTextinput('PAYZEN_RETURN_POST_PARAMS',
						$this->l('Additional POST parameters'),
						$this->l('Extra parameters sent in the return form'), 'size="65"');

		// Urls
		$this->_adminFormTextinput('PAYZEN_URL_RETURN', $this->l('Default url'),
						$this->l('Default return url'), 'size="65"');
		$this->_html .= '<label>'
				. $this->l('Check url to copy in your bank backoffice')
				. '</label>
					<div class="margin-form">
						<p>http://'
				. htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8')
				. __PS_BASE_URI__ . 'modules/' . $this->name
				. '/validation.php</p>
					</div>';
		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>';
		$this->_html .= $submit_button;
		$this->_html .= '</form>';
	}

	/**
	 * Shortcut function for creating a html text input
	 * @param string $name
	 * @param string $label
	 * @param string $description
	 * @param string $extra_attributes
	 */
	function _adminFormTextinput($name, $label, $description = null,
			$extra_attributes = '') {
		$value = Configuration::get($name);
		$this->_html .= "\n";
		$this->_html .= '<label for="' . $name . '">' . $label . '</label>';
		$this->_html .= '<div class="margin-form">';
		$this->_html .= '<input type="text" id="' . $name . '" name="' . $name
				. '" value="' . $value . '" ' . $extra_attributes . '/>';
		$this->_html .= '<p>' . $description . '</p>';
		$this->_html .= '</div>';
	}

	/**
	 * Shortcut function for creating a html select
	 * @param array[string]string $options
	 * @param mixed $selected a single string value or an array
	 * @param string $name
	 * @param string $label
	 * @param string $description
	 * @param string $extra_attributes
	 */
	function _adminFormSelect($options, $selected, $name, $label, $description,
			$extra_attributes = null) {
		$this->_html .= "\n";
		$this->_html .= '<label for="' . $name . '">' . $label . '</label>';
		$this->_html .= '<div class="margin-form">';
		$this->_html .= '<select name="' . $name . '" ' . $extra_attributes . '>';
		foreach ($options as $value => $label) {
			$this->_html .= '<option value="' . $value . '"';
			$is_selected = is_array($selected)
					? in_array($value, $selected)
					: ((string) $value == (string) $selected);
			$this->_html .= $is_selected ? ' selected="selected"' : '';
			$this->_html .= '>' . $label . '</option>';
		}
		$this->_html .= '</select><p>' . $description . '</p></div>';
	}

	/**
	 * Payment function, redirects the client to payment page
	 * @param array $params
	 * @return void|Ambigous <string, void, boolean, mixed, unknown>
	 */
	public function hookPayment($params) {
		/* @var $smarty Smarty */ 
		/* @var $cookie Cookie */
		global $smarty, $cookie;
		
		/* @var $cart Cart */
		$cart = new Cart((int)($cookie->id_cart));
		
		// amount restrictions
		$min = Configuration::get('PAYZEN_AMOUNT_MIN');
		$max = Configuration::get('PAYZEN_AMOUNT_MAX');
		if (($min != '' && $cart->getOrderTotal() < $min)
			|| ($max != '' && $cart->getOrderTotal() > $max)) {
			return;
		}
		
		// currency support
		$currency_cart = new Currency(intval($cart->id_currency));
		$currency_code = $this->getLoadedApi()->getCurrencyNumCode($currency_cart->iso_code);
		if (!$currency_code) {
			$smarty->assign('payzen_unknown_currency', $currency_cart);
			return $this->display(__FILE__, 'unknown_currency.tpl');
		}
		
		$smarty->assign('payzen_link_action', 'modules/payzen/redirect.php');
		return $this->display(__FILE__, 'order_payzen.tpl');
	}
	
	/**
	 * Manage payement gateway response
	 * @param array $params
	 */
	public function hookOrderConfirmation($params) {
		global $smarty;
		if (!$this->active || $params['objOrder']->module != $this->name) {
			return;
		}
		
		$error_msg = key_exists('error', $_GET) && ($_GET['error'] == 'yes');
		
		$array = array(
				'check_url_warn' => (key_exists('check_url_warn', $_GET) && $_GET['check_url_warn'] == 'yes'),
				'prod_info' => (key_exists('prod_info', $_GET) && $_GET['prod_info'] == 'yes'),
				'error_msg' => $error_msg
		);
		
		if ($error_msg === false) {
			$array['total_to_pay'] = Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false);
			$array['id_order'] = $params['objOrder']->id;
		}
		
		$smarty->assign($array);
		
		return $this->display(__FILE__, 'payment_return.tpl');
	}
	
	/**
	 * Manage a failed payment notification (see validation.php). Behaviour depends on PAYZEN_FAILURE_MANAGEMENT config.
	 * 
	 * @param PayzenResponse $payzen_resp
	 * @param int $id_cart
	 * @param Customer $customer
	 * @param boolean $from_server
	 * @param Order $order
	 */
	public function managePaymentFailure(PayzenResponse $payzen_resp, $id_cart, $from_server, $order) {
		// Behaviour 1 : save order so that it can be seen from admin panel
		if(Configuration::get('PAYZEN_FAILURE_MANAGEMENT') == self::ON_FAILURE_GOTOHISTORY) {
			$order_state = $payzen_resp->isCancelledPayment() ? _PS_OS_CANCELED_ : _PS_OS_ERROR_;
			if($order->getCurrentState() != _PS_OS_CANCELED_ && $order->getCurrentState() != _PS_OS_ERROR_) {
				// Order has not been save yet, let's do it
				
				$this->validate($id_cart, $order_state,	$payzen_resp);
			}
			
			// Confirm for gateway / redirect client to history
			if ($from_server) {
				die($payzen_resp->getOutputForGateway('payment_ko'));
			} else {
				Tools::redirectLink(__PS_BASE_URI__ . 'history.php');
			}
		// Behaviour 2 : just get back to checkout process 
		} else {
			if ($from_server) {
				die($payzen_resp->getOutputForGateway('payment_ko'));
			} else {
				Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=3');
			}
		}
	}
	
	// TODO to remove when So Colissimo fix cart delivery address id
	function getColissimoShippingAddress($cart, $psAddress, $idCustomer) {
		// So Colissimo not installed
		if(!Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
			return false;
		}
	
		// So Colissimo is not selected as shipping method
		if ($cart->id_carrier != Configuration::get('SOCOLISSIMO_CARRIER_ID')) {
			return false;
		}
	
		// Get address saved by So Colissimo
		$return = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'socolissimo_delivery_info WHERE id_cart =\''.(int)($cart->id).'\' AND id_customer =\''.(int)($idCustomer).'\'');
		$newAddress = new Address();
	
		if (strtoupper($psAddress->lastname) != strtoupper($return['prname'])
			|| strtoupper($psAddress->firstname) != strtoupper($return['prfirstname'])
			|| strtoupper($psAddress->address1) != strtoupper($return['pradress3'])
			|| strtoupper($psAddress->address2) != strtoupper($return['pradress2'])
			|| strtoupper($psAddress->postcode) != strtoupper($return['przipcode'])
			|| strtoupper($psAddress->city) != strtoupper($return['prtown'])
			|| str_replace(array(' ', '.', '-', ',', ';', '+', '/', '\\', '+', '(', ')'),'',$psAddress->phone_mobile) != $return['cephonenumber']) {
				
			// Address is modified in So Colissimo page : use it as shipping address
			$newAddress->lastname = substr($return['prname'], 0, 32);
			$newAddress->firstname = substr($return['prfirstname'], 0, 32);
			$newAddress->postcode = $return['przipcode'];
			$newAddress->city = $return['prtown'];
			$newAddress->id_country = Country::getIdByName(null, 'france');
	
			if (!in_array($return['delivery_mode'], array('DOM', 'RDV'))) {
				$newAddress->address1 = $return['pradress1'];
				$newAddress->address1 .= isset($return['pradress2']) ?  ' ' . $return['pradress2'] : '';
				$newAddress->address1 .= isset($return['pradress3']) ?  ' ' . $return['pradress3'] : '';
				$newAddress->address1 .= isset($return['pradress4']) ?  ' ' . $return['pradress4'] : '';
			} else {
				$newAddress->address1 = $return['pradress3'];
				$newAddress->address2 = isset($return['pradress4']) ? $return['pradress4'] : '';
				$newAddress->other = isset($return['pradress1']) ?  $return['pradress1'] : '';
				$newAddress->other .= isset($return['pradress2']) ?  ' ' . $return['pradress2'] : '';
			}
				
			// Return the So Colissimo updated
			return $newAddress;
		} else {
			// Use initial address
			return false;
		}
	}
	
	/**
	* Generate form to post to the payment gateway.
	*/
	public function prepareForm() {
		/* @var $smarty Smarty */
		/* @var $cookie Cookie */
		global $smarty, $cookie;
		
		/* @var $cust Customer */
		/* @var $cart Cart */
		$cust = new Customer(intval($cookie->id_customer));
		$cart = new Cart((int)($cookie->id_cart));
		
		if($cart->nbProducts() <= 0) {
			$smarty->assign('payzen_empty_cart', true);
			return;
		}
		
		/* @var $billingCountry Address */
		$billingAddress = new Address($cart->id_address_invoice);
		$billingCountry = new Country($billingAddress->id_country);
			
		/* @var $deliveryAddress Address */
		$deliveryAddress = new Address($cart->id_address_delivery);
			
		// TODO to remove when So Colissimo fix cart delivery address id
		$colissimoAddress = $this->getColissimoShippingAddress($cart, $deliveryAddress, $cust->id);
		if (is_a($colissimoAddress, 'Address')) {
			$deliveryAddress = $colissimoAddress;
		}
		$deliveryCountry = new Country($deliveryAddress->id_country);
		
		/* @var $api PayzenApi */
		$api = $this->getLoadedApi();
		
		/* detect default language */
		$arrayLang = $api->getSupportedLanguages();
			
		$language = strtolower(Language::getIsoById(intval($cookie->id_lang)));
		if (!in_array($language, $arrayLang)) {
			$language = Configuration::get('PAYZEN_LANGAGE_DEFAUT');
		}
		
		/* detect store currency */ 
		$currency_cart = new Currency(intval($cart->id_currency));
		$currency = $api->findCurrencyByAlphaCode($currency_cart->iso_code);
		
		/* Amount */
		$amount = $cart->getOrderTotal();
			
		$api->set('amount', $currency->convertAmountToInteger($amount));
		$api->set('currency', $currency->num);
			
		$api->set('cust_email', $cust->email);
		$api->set('cust_id', $cust->id);
			
		$api->set('cust_name', $cust->lastname . ' ' . $cust->firstname);
		$api->set('cust_address', $billingAddress->address1 . ' ' . $billingAddress->address2);
		$api->set('cust_zip', $billingAddress->postcode);
		$api->set('cust_city', $billingAddress->city);
		$api->set('cust_phone', $billingAddress->phone);
		$api->set('cust_country', $billingCountry->iso_code);
		if ($billingAddress->id_state) {
			$state = new State((int) ($billingAddress->id_state));
			$api->set('cust_state', $state->iso_code);
		}
			
		$api->set('ship_to_name', $cust->lastname . ' ' . $cust->firstname);
		$api->set('ship_to_street', $deliveryAddress->address1);
		$api->set('ship_to_street2', $deliveryAddress->address2);
		$api->set('ship_to_zip', $deliveryAddress->postcode);
		$api->set('ship_to_city', $deliveryAddress->city);
		$api->set('ship_to_phone_num', $deliveryAddress->phone);
		$api->set('ship_to_country', $deliveryCountry->iso_code);
		if ($deliveryAddress->id_state) {
			$state = new State((int) ($deliveryAddress->id_state));
			$api->set('ship_to_state', $state->iso_code);
		}
		
		// activate 3ds ?
		$threeds_mpi = null;
		$threeds_min_amount = Configuration::get('PAYZEN_3DS_MIN_AMOUNT');
		if($threeds_min_amount != '' && $amount < $threeds_min_amount) {
			$threeds_mpi = '2';
		}
		$api->set('threeds_mpi', $threeds_mpi);
		
		$api->set('language', $language);
		$api->set('order_id', $cart->id);
		$api->set('order_info', 'language_id=' . $cookie->id_lang);
		
		// prepare data for PayZen payment form
		$params = array();
			
		$fields = $api->getRequestFields();
		foreach ($fields as $field) {
			if ($field->isFilled()) {
				$params[$field->getName()] = htmlspecialchars($field->getValue(), ENT_QUOTES, 'UTF-8');
			}
		}
		
		$smarty->assign('payzen_params', $params);
		$smarty->assign('payzen_url', $api->platformUrl);
		$smarty->assign('payzen_empty_cart', false);
	}
	
	/**
	* Save order and transaction info.
	*/
	public function validate($id_cart, $order_status, $payzen_resp) {
		// Retrieve customer from cust_id
		$customer = new Customer($payzen_resp->get('cust_id'));
		
		// ps id_currency from currency iso num code
		$currencyId = Db::getInstance()->getValue('SELECT id_currency FROM '._DB_PREFIX_.'currency WHERE iso_code_num = '.(int)$payzen_resp->get('currency'));
		
		// TODO translate 3ds extra message ?
		$msg_3ds = "\n  Authentification 3DS : ";
		if ($payzen_resp->get('threeds_status') == "Y") {
			$msg_3ds .= "OUI";
			$msg_3ds .= "\n  Certificat 3DS  : ".$payzen_resp->get('threeds_cavv');
		} else {
			$msg_3ds .= "NON";
		}
		
		// call payment module validateOrder
		$this->validateOrder(
			$id_cart, 
			$order_status,
			$payzen_resp->getFloatAmount(),
			$this->displayName,
			$payzen_resp->getLogString().$msg_3ds,
			array(),		//$extraVars
			$currencyId,	//$currency_special
			false,			//$dont_touch_amount
			$customer->secure_key
		);
		
		// Reload order
		$id_order = intval(Order::getOrderByCartId($id_cart));
		$order = new Order($id_order);
		
		// check if table payment_cc exists (1.4.x prestashp versions)
		Db::getInstance()->Execute("SHOW TABLES LIKE '"._DB_PREFIX_."payment_cc'");
		$pcc_exists = Db::getInstance()->NumRows() > 0;
		
		// save transaction info
		if (class_exists('PaymentCC') && $pcc_exists) {
			$pcc = new PaymentCC();
				
			$pcc->id_order = (int)$order->id;
			$pcc->id_currency = $currencyId;
			$pcc->amount = $payzen_resp->getFloatAmount();
			$pcc->transaction_id = $payzen_resp->get('trans_id');
			$pcc->card_number = $payzen_resp->get('card_number');
			$pcc->card_brand = $payzen_resp->get('card_brand');
			$pcc->card_expiration = str_pad($payzen_resp->get('expiry_month'), 2, '0', STR_PAD_LEFT)
									. '/' . $payzen_resp->get('expiry_year');
			$pcc->card_holder = NULL;
			$pcc->add();
		} elseif (class_exists('OrderPayment')) {
			$payments = $order->getOrderPaymentCollection();
			
			if($payments->count() > 0) {
				$pcc = $payments[0];
	
				$pcc->transaction_id = $payzen_resp->get('trans_id');
				$pcc->card_number = $payzen_resp->get('card_number');
				$pcc->card_brand = $payzen_resp->get('card_brand');
				$pcc->card_expiration = str_pad($payzen_resp->get('expiry_month'), 2, '0', STR_PAD_LEFT)
										. '/' . $payzen_resp->get('expiry_year');
				$pcc->card_holder = NULL;
				$pcc->update();
			}
		}
		
		return $order;
	}
	
	
}
?>