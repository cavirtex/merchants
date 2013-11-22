<?php

defined ('_JEXEC') or die('Restricted access');

/**
 * payments using BTC and the CaVirtEx merchant API.
 * @author Max Milbers, Valérie Isaksen, Aaron Kuchma
 * @version $Id: cavirtex.php,v 1.0 2013/11/21
 * @package VirtueMart
 * @subpackage payment
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
if (!class_exists ('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentCavirtex extends vmPSPlugin {

	function __construct (& $subject, $config) {

		parent::__construct ($subject, $config);
		// 		vmdebug('Plugin stuff',$subject, $config);
		$this->_loggable = TRUE;
		$this->tableFields = array_keys ($this->getTableSQLFields ());
		$this->_tablepkey = 'id';
		$this->_tableId = 'id';
		$varsToPush = $this->getVarsToPush ();
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);

	}

	protected function getVmPluginCreateTableSQL ()
	{
		return $this->createTableSQL('Payment CaVirtEx Table');
	}

	function getTableSQLFields ()
	{

		$SQLfields = array(
			'id' => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'int(1) UNSIGNED',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'cavirtex_order_key' => 'char(128)',
			'cavirtex_btc_received' => 'char(128)',
			'cavirtex_invoice_number' => 'text'
		);
		return $SQLfields;
	}
	
	

	function _getPostUrl ($method)
	{
		return 'https://www.cavirtex.com/merchant_purchase/'.$method->merchant_key;
	}
	
	function _getFormattedDate ($month, $year)
	{
	
		return sprintf('%02d-%04d', $month, $year);
	}
	
	function _getfield ($string, $length)
	{
	
		return substr($string, 0, $length);
	}
	
	function _setBillingInformation ($usrBT)
	{
		isset($usrBT->zip) ? $postalNice = str_replace(" ", "", $this->_getField($usrBT->zip, 40)) : NULL;
	
		$shipParams = array();
	
		if(isset($usrBT->email)) $shipParams['email'] = $this->_getField($usrBT->email, 100);
		if(isset($usrBT->first_name) || isset($usrBT->last_name))
			$shipParams['customer_name'] = (isset($usrBT->first_name) ? $this->_getField($usrBT->first_name, 50).' '
					: '').(isset($usrBT->last_name) ? $this->_getField($usrBT->last_name, 50) : '');
		if(isset($usrBT->address_1) || isset($usrBT->address_2)) $shipParams['address'] =
		(isset($usrBT->address_1) ? $this->_getField($usrBT->address_1, 60)
		: '').' '.(isset($usrBT->address_2) ? $this->_getField($usrBT->address_2, 60) : '');
		if(isset($usrBT->city)) $shipParams['city'] = $this->_getField($usrBT->city, 40);
		if(isset($postalNice)) $shipParams['postal'] = $postalNice;
		$db = JFactory::getDBO();
		if(isset($usrBT->virtuemart_state_id)) { 
			$q = "SELECT `state_2_code` FROM `#__virtuemart_states` WHERE `virtuemart_state_id` = ".$usrBT->virtuemart_state_id.";";
			$db->setQuery($q);
			$statecode = $db->loadResult();
			if(strlen($statecode) == 2 )
			{
				$shipParams['province'] = $statecode;
			}
		}
		if(isset($usrBT->virtuemart_country_id)) {
			
			$q = "SELECT `country_2_code` FROM `#__virtuemart_countries` WHERE `virtuemart_country_id` = ".$usrBT->virtuemart_country_id.";";
			$db->setQuery($q);
			$countrycode = $db->loadResult();
			if(strlen($countrycode) == 2 )
			{
				$shipParams['country'] = $countrycode;
			} 
		}
		
		return $shipParams;
	}
	
	function _setTransactionData ($orderDetails, $method, $totalInPaymentCurrency)
	{
		return array(
				'price' => $totalInPaymentCurrency,
				'code' => "Order #".$orderDetails->order_number,
				'quantity' => 1,
				'name' => "VirtueMart Order.",
				'shipping_required' => 0,
				'format' => "json",
				//Shipping required set to false; shipping handled in virtual mart and shipping cost may have been converted to another currency.
		);
	}
	
	/**
	 * Posts the request to CaVirtEx & returns response using curl
	 */
	function _sendRequest ($post_url, $post_string)
	{
	
		//$this->writelog($post_string , "_sendRequest", 'debug');
	
		$curl_request = curl_init($post_url);
		//Added the next line to fix SSL verification issue (CURL error verifying the far end SSL Cert)
		curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_request, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl_request, CURLOPT_FRESH_CONNECT, 1);
	
		curl_setopt($curl_request, CURLOPT_POST, 1);
	
		$responseString = curl_exec($curl_request);
	 
		if($responseString == false) {
			$response = curl_error($curl_request);
			$this->writelog($response, '_sendRequest CURL error', 'error');
			vmError('CaVirtEx: '."----CURL ERROR---- " . $response);
		} else {
			$response =json_decode($responseString, true);
		} 
		
		curl_close($curl_request);
	
		return $response;
	}
	
	function _handlePaymentCancel ($virtuemart_order_id, $html)
	{
	
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		$modelOrder = VmModel::getModel('orders');
		$modelOrder->remove(array('virtuemart_order_id' => $virtuemart_order_id));
		// error while processing the payment
		$mainframe = JFactory::getApplication();
		$mainframe->enqueueMessage($html);
		$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&task=editpayment',FALSE), JText::_('COM_VIRTUEMART_CART_ORDERDONE_DATA_NOT_VALID'));
	}
	
	
	function plgVmConfirmedOrder (VirtueMartCart $cart, $order)
	{
	
		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL;
		}
	
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
	
		$BillTo = $order['details']['BT'];
		$ShipTo = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);
		$session = JFactory::getSession();
		$return_context = $session->getId();
	
		$transaction_key = $this->get_passkey();
		if ($transaction_key === FALSE) {
			return FALSE;
		}
	
		//Convert total to Canadian dollars
		$payment_currency_id = shopFunctions::getCurrencyIDByName("CAD");
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$payment_currency_id);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
	
		// Set up data
		$formdata = array();
		$formdata = array_merge($this->_setBillingInformation($ShipTo), $formdata);
		$formdata = array_merge($this->_setTransactionData($order['details']['BT'], $method, $totalInPaymentCurrency['value']), $formdata);
	
		// prepare the array to post
		$poststring = '';
		foreach ($formdata AS $key => $val) {
			$poststring .= urlencode($key) . "=" . urlencode($val) . "&";
		}
		$poststring = rtrim($poststring, "& ");
	
		// send a request
		$response = $this->_sendRequest($this->_getPostUrl($method), $poststring);
	
		//$this->writelog($response , "plgVmConfirmedOrder. Plugin ID: ".$this->_getID(), 'debug');
		
		if (is_string($response)) {
			$invoice = array('error' => $response);
		} else {
			$invoice = $response;
		}
	
		if(isset($invoice['error'])) {
			$this->writelog("Response was error", "plgVmConfirmedOrder", 'debug');
			$this->_handlePaymentCancel($order['details']['BT']->virtuemart_order_id, 
					"<span style='color:red'>There was an error: </span>".print_r($response, TRUE) );
			return false;
			//should gracefully handle the error.
		} else{
			if (isset($invoice["order_key"]))
			{
				$order_key = $invoice["order_key"];
				//$cavirtex_invoice_number = $invoice["invoice_number"];
	
				$url="https://www.cavirtex.com/merchant_invoice?merchant_key=".$method->merchant_key."&order_key=".$order_key;
	
			} else {
				$this->writelog("No order key. ".print_r($response, TRUE), "plgVmConfirmedOrder", 'debug');
				$this->_handlePaymentCancel($order['details']['BT']->virtuemart_order_id,
						"<span style='color:red'>There was an error: </span>".print_r($response, TRUE) );
				// should gracefully handle not getting an order key
				return false;
			}
		}
	
		// Prepare data that should be stored in the database
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['virtuemart_order_id'] = $order['details']['BT']->virtuemart_order_id;
		$dbValues['virtuemart_paymentmethod_id'] = $order['details']['BT']->virtuemart_paymentmethod_id;
		$dbValues['payment_order_total'] = $totalInPaymentCurrency['value'];
		$dbValues['payment_currency'] = $payment_currency_id;
		$dbValues['cavirtex_order_key'] = $order_key;
		//$dbValues['cavirtex_invoice_number'] = $cavirtex_invoice_number;
	
		//'cavirtex_btc_received' => 'char(128)',
		//'cavirtex_invoice_number' => 'text'
 
		
		$this->storePSPluginInternalData($dbValues);
 
		header("Location: ".$url);
		
		$html = "<meta http-equiv='refresh' content='0; url=".$url."' />";
		$cart->emptyCart();
		JRequest::setVar('html', $html);
		//echo($html);
		return TRUE;
	
	}
	
	
	
	
	
	public function _getID(){
		$db = JFactory::getDBO();

		$sql='SELECT `extension_id` FROM `#__extensions` WHERE `name` = "CaVirtEx Bitcoin Plugin";';
		$db->setQuery($sql);		
		if(!($plg=$db->loadResult())){
			JError::raiseError(100,"Fatal: Plugin isn't installed.");
		} else {
			return $plg;
		}
	}
	
	public function _getPaymentID(){
		$db = JFactory::getDBO();
		
		$sql='SELECT `virtuemart_paymentmethod_id` FROM `jos_virtuemart_paymentmethods` WHERE `payment_jplugin_id` ='.$this->_getID().';';
		$db->setQuery($sql);
		if(!($plg=$db->loadResult())){
			JError::raiseError(100,"Fatal: Plugin isn't set as a payment method.");
		} else {
			return $plg;
		}
	}
	
	public function _getInvoiceIDFromOrderKey($order_key){
		$db = JFactory::getDBO();
	
		$sql='SELECT `virtuemart_order_id` FROM `jos_virtuemart_payment_plg_cavirtex` WHERE `cavirtex_order_key` = "'.$order_key.'";';
		$db->setQuery($sql);
		if(!($plg=$db->loadResult())){
			JError::raiseError(100,"Fatal: Order key doesn't exist!");
		} else {
			return $plg;
		}
	}
	
	
	//pm must be numeric
	
	function plgVmOnPaymentNotification() {
		$lgfile = 'callback.php';
		$date = date('Y/m/d H:i:s');
		//file_put_contents($lgfile, $date." Got callback. ".$this->_getID()."\r\n", FILE_APPEND | LOCK_EX);
		
		//$this->_debug = true;
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		
		if (!isset($_GET['pm'])) {
			return FALSE;
		}
	
		if($_GET['pm'] != $this->_getID()) {
			return FALSE;
		} //this is the right payment method
		
		$data = json_decode(file_get_contents('php://input'), true);
		//$data = JRequest::get('post');
		
		//$this->writelog( $date." Got callback. " , "plgVmOnPaymentNotification. Plugin ID: ".$this->_getID(), 'debug');
		/*		
		if(isset($data["order_key"])){
			$lgfile = 'callback.php';
			$date = date('Y/m/d H:i:s');
			file_put_contents($lgfile, $date."\r\n", FILE_APPEND | LOCK_EX);
			file_put_contents($lgfile, "Callback Post: ".print_r($_POST, TRUE)."\r\n", FILE_APPEND | LOCK_EX);
			file_put_contents($lgfile, "Callback Get: ".print_r($_GET, TRUE)."\r\n", FILE_APPEND | LOCK_EX);
			file_put_contents($lgfile, "Full data dump: ".file_get_contents('php://input')."\r\n", FILE_APPEND | LOCK_EX);
		}
		*/
		
		$order_key = $data["order_key"];
		$invoice_number = $data["invoice_number"];
		$btc_received = $data["btc_received"];
		
		$url="https://www.cavirtex.com/merchant_confirm_ipn";
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		
		$method = $this->getVmPluginMethod($this->_getPaymentID());
		
		$post = array();
		$post["invoice_number"]=$invoice_number;
		$post["order_key"]=$order_key;
		$post["btc_received"]=$btc_received;
		$post["secret_key"]=$method->merchant_secret_key;
		 
		//file_put_contents($lgfile, "POST contents: ".print_r($post, TRUE)."\r\n", FILE_APPEND | LOCK_EX);
		
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		
		$responseString = curl_exec($curl);
		if($responseString == false) {
			$response = curl_error($curl);
		} else {
			$response =json_decode($responseString, true);
		}
		curl_close($curl);
		
		//file_put_contents($lgfile, "IPN Confirm Response: ".print_r($response, TRUE)."\r\n", FILE_APPEND | LOCK_EX);

		if($response["status"] != "ok"){
			return false;
		} // IPN confirmed at this point. Good to go.
		
		$modelOrder = VmModel::getModel('orders');
		$order = array();
	
		$order['customer_notified'] = 1;
		$order['order_status'] = 'C';
		$order['comments'] = "Payment Confirmed. CaVirtEx invoice #".$invoice_number;
			
		$virtuemart_order_id = $this->_getInvoiceIDFromOrderKey($order_key);
		$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, TRUE);
		//// remove vmcart
		
		//die();
		return true;
	}
	
	
	
	
	
	
	
	

	/*
		 * Keep backwards compatibility
		 * a new parameter has been added in the xml file
		 */
	function getNewStatus ($method) {

		if (isset($method->status_pending) and $method->status_pending!="") {
			return $method->status_pending;
		} else {
			return 'P';
		}
	}

	/**
	 * Display stored payment data for an order
	 *
	 */
	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $virtuemart_payment_id) {

		if (!$this->selectedThisByMethodId ($virtuemart_payment_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
			return NULL;
		}
		VmConfig::loadJLang('com_virtuemart');

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE ();
		$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_PAYMENT_NAME', $paymentTable->payment_name);
		$html .= $this->getHtmlRowBE ('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
		$html .= '</table>' . "\n";
		return $html;
	}

/*	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {

		if (preg_match ('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr ($method->cost_percent_total, 0, -1);
		} else {
			$cost_percent_total = $method->cost_percent_total;
		}
		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}
*/
	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 *
	 * @author: Valerie Isaksen
	 *
	 * @param $cart_prices: cart prices
	 * @param $payment
	 * @return true: if the conditions are fulfilled, false otherwise
	 *
	 */
	protected function checkConditions ($cart, $method, $cart_prices) {

		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) ) {
			return TRUE;
		}

		return FALSE;
	}


	/*
* We must reimplement this triggers for joomla 1.7
*/

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {

		return $this->onStoreInstallPluginTable ($jplugin_id);
	}

	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valérie isaksen
	 *
	 * @param VirtueMartCart $cart: the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {

		return $this->OnSelectCheck ($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object  $cart Cart object
	 * @param integer $selected ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		return $this->displayListFE ($cart, $selected, $htmlIn);
	}

	/*
* plgVmonSelectedCalculatePricePayment
* Calculate the price (value, tax_id) of the selected method
* It is called by the calculator
* This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
* @author Valerie Isaksen
* @cart: VirtueMartCart the current cart
* @cart_prices: array the new cart prices
* @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
*
*
*/

	public function plgVmonSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
	}

	function plgVmgetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

		if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}
		$this->getPaymentCurrency ($method);

		$paymentCurrencyId = $method->payment_currency;
		return;
	}

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {

		$this->onShowOrderFE ($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}
/**
	 * @param $orderDetails
	 * @param $data
	 * @return null
	 */

	function plgVmOnUserInvoice ($orderDetails, &$data) {

		if (!($method = $this->getVmPluginMethod ($orderDetails['virtuemart_paymentmethod_id']))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return NULL;
		}
		//vmdebug('plgVmOnUserInvoice',$orderDetails, $method);

		if (!isset($method->send_invoice_on_order_null) or $method->send_invoice_on_order_null==1 or $orderDetails['order_total'] > 0.00){
			return NULL;
		}

		if ($orderDetails['order_salesPrice']==0.00) {
			$data['invoice_number'] = 'reservedByPayment_' . $orderDetails['order_number']; // Nerver send the invoice via email
		}

	}
		/**
		 * @param $virtuemart_paymentmethod_id
		 * @param $paymentCurrencyId
		 * @return bool|null
		 */
		function plgVmgetEmailCurrency($virtuemart_paymentmethod_id, $virtuemart_order_id, &$emailCurrencyId) {

			if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
				return NULL; // Another method was selected, do nothing
			}
			if (!$this->selectedThisElement($method->payment_element)) {
				return FALSE;
			}
			if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
				// JError::raiseWarning(500, $db->getErrorMsg());
				return '';
			}
			if (empty($payments[0]->email_currency)) {
				$vendorId = 1; //VirtueMartModelVendor::getLoggedVendor();
				$db = JFactory::getDBO();
				$q = 'SELECT   `vendor_currency` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id`=' . $vendorId;
				$db->setQuery($q);
				$emailCurrencyId = $db->loadResult();
			} else {
				$emailCurrencyId = $payments[0]->email_currency;
			}

		}
	/**
	 * This event is fired during the checkout process. It can be used to validate the
	 * method data as entered by the user.
	 *
	 * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
	 * @author Max Milbers

	public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
	return null;
	}
	 */

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment ($order_number, $method_id) {

		return $this->onShowOrderPrint ($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPayment ($name, $id, &$data) {

		return $this->declarePluginParams ('payment', $name, $id, $data);
	}

	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {

		return $this->setOnTablePluginParams ($name, $id, $table);
	}
	
}

// No closing tag
