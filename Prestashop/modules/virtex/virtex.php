<?php

function vxlog($contents)
{
	$file = 'vxlog.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contents, true)."\n", FILE_APPEND);		
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}

	class virtex extends PaymentModule
	{
		private $_html = '';
		private $_postErrors = array();
		private $key;

		function __construct()
		{
			$this->name = 'virtex';
			$this->tab = 'payments_gateways';
			$this->version = '1.0';

			$this->currencies = true;
			$this->currencies_mode = 'checkbox';
	
			parent::__construct();

			$this->page = basename(__FILE__, '.php');
			$this->displayName = $this->l('Virtex payment gateway');
			$this->description = $this->l('Accepts payments by Bitcoin via cavirtex.com');
			$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
		}

		public function install()
		{
			if (!parent::install() || !$this->registerHook('invoice') || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
			{
				return false;
			}

			$db = Db::getInstance();
			$query = "CREATE TABLE `"._DB_PREFIX_."order_virtex` (
			`id_payment` int(11) NOT NULL AUTO_INCREMENT,
			`id_order` int(11) NOT NULL,
			`cart_id` int(11) NOT NULL,
			`invoice_id` varchar(255) NOT NULL,
			`status` varchar(255) NOT NULL,
			PRIMARY KEY (`id_payment`),
			UNIQUE KEY `invoice_id` (`invoice_id`)
			) ENGINE="._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

			$db->Execute($query);

			return true;
		}

		public function uninstall()
		{
			Configuration::deleteByName('virtex_APIKEY');
			
			return parent::uninstall();
		}

		public function getContent()
		{
			$this->_html .= '<h2>'.$this->l('virtex').'</h2>';	
	
			$this->_postProcess();	
            $this->_setShowDetails();		
			$this->_setConfigurationForm();
			
			return $this->_html;
		}
            
		function hookPayment($params)
		{
			global $smarty;

			$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));

			return $this->display(__FILE__, 'payment.tpl');
		}
        private function _setShowDetails()
        {
            $this->_html.='<img src="../modules/virtex/virtex-logo.png" style="float:left; margin-right:15px;" />
            <b>'.$this->l('This module allows you to accept payments by cavirtex.com.').'</b><br /><br />
            '.$this->l('If the client chooses this payment mode, your Virtex account will be automatically credited.').'<br />
            '.$this->l('You need to configure your Virtex account before using this module.').'
            <div style="clear:both;">&nbsp;</div>';
        }
		private function _setConfigurationForm()
		{
			$this->_html .= '
			<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">	
				<script type="text/javascript">
					var pos_select = '.(($tab = (int)Tools::getValue('tabs')) ? $tab : '0').';
				</script>
				<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'tabpane.js"></script>
				<link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_CSS_DIR_.'tabpane.css" />
				<input type="hidden" name="tabs" id="tabs" value="0" />
				<div class="tab-pane" id="tab-pane-1" style="width:100%;">
					<div class="tab-page" id="step1">
						<h4 class="tab">'.$this->l('Settings').'</h2>
						'.$this->_getSettingsTabHtml().'
					</div>
				</div>
				<div class="clear"></div>
				<script type="text/javascript">
					function loadTab(id){}
					setupAllTabs();
				</script>
			</form>';
		}

		private function _getSettingsTabHtml()
		{
			global $cookie;
            
			$html = '
			<h2>'.$this->l('Settings').'</h2>
			<h3 style="clear:both;">'.$this->l('API Key').'</h3>
			<div class="margin-form">
				<input type="text" name="apikey_virtex" value="'.htmlentities(Tools::getValue('apikey', Configuration::get('virtex_APIKEY')), ENT_COMPAT, 'UTF-8').'" />
			</div>			
			<p class="center"><input class="button" type="submit" name="submitvxpay" value="'.$this->l('Save settings').'" /></p>';
			return $html;
		}

		private function _postProcess()
		{
			global $currentIndex, $cookie;

			if (Tools::isSubmit('submitvxpay'))
			{
				$template_available = array('A', 'B', 'C');

				$this->_errors = array();

				if (Tools::getValue('apikey_virtex') == NULL)
				{
					$this->_errors[] = $this->l('Missing API Key');
				}
				
				if (count($this->_errors) > 0)
				{
					$error_msg = '';
					foreach ($this->_errors AS $error)
						$error_msg .= $error.'<br />';
					$this->_html = $this->displayError($error_msg);
				}
				else
				{
					Configuration::updateValue('virtex_APIKEY', trim(Tools::getValue('apikey_virtex')));
					$this->_html = $this->displayConfirmation($this->l('Settings updated'));
				}
			}
		}

		public function execPayment($cart)
		{
			$currency = Currency::getCurrencyInstance((int)$cart->id_currency);

			// create invoice
			$options = $_POST;			
			$options['currency'] = $currency->iso_code;

			$total = $cart->getOrderTotal(true);

			$options['notificationURL'] = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/ipn.php';
			$options['redirectURL'] = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$this->id.'&id_order='.$this->currentOrder;
			$options['posData'] = '{"cart_id": "' . $cart->id . '"';
			$options['posData'].= ', "hash": "' . crypt($cart->id, Configuration::get('virtex_APIKEY')) . '"';

			$this->key = $this->context->customer->secure_key;
			$options['posData'].= ', "key": "' . $this->key . '"}';
			$options['orderID'] = $cart->id;
			$options['price'] = $total;
            $options['code'] = $cart->id;
    
			
			//$postOptions = array('orderID', 'itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 'posData', 'price', 'currency', 'physical', 'fullNotifications', 'buyerName', 'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone');
            $postOptions = array('name', 'price', 'shipping_required', 'quantity','code', 'return_url', 'customer_name','address', 'address2','city','quantity', 'province', 'postal', 'country', 'email'); 
             $cartProducts = $cart->getProducts();             
             $name="";
             
             foreach ($cartProducts as $p)            
             {
                 $name.=$p["name"]." ";
             }
            $post["customer_name"]=$this->context->customer->firstname." ".$this->context->customer->lastname;
            $post["email"]=$this->context->customer->email;
            $post["name"]=$name;    
            if (!empty($cart->id_address_delivery))
            $post["shipping_required"]=1;
            else
            $post["shipping_required"]=0;
            $post["format"]="json";    
            $address = new Address(intval($cart->id_address_delivery));
            if (is_object($address))
            {
                $post["address"]=$address->address1;
                $post["address2"]=$address->address2;
                $post["city"]=$address->city;
                $post["country"]=$address->country;
                $c= new Country(intval($address->id_country));                 
                if (is_object($c))
                $post["country"]=$c->iso_code;
                $post["postal"]=$address->postcode;
            }          
           
			foreach($postOptions as $o)
			{
				if (array_key_exists($o, $options))
				{
					$post[$o] = $options[$o];
				} else
                if (!isset($post[$o]))
                $post[$o]="";
			}
		
			// Call Virtex
            $uname = Configuration::get('virtex_APIKEY');
			$curl = curl_init('https://www.cavirtex.com/merchant_purchase/'.$uname);
							
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);				
			

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				
			$responseString = curl_exec($curl);            
			if(!$responseString) {
				$response = curl_error($curl);
				die(Tools::displayError("Error: no data returned from API server!"));
			} else {
				$response = json_decode($responseString, true);
			}
			curl_close($curl);            
			if($response['error']) {
				bplog($response['error']);
				die(Tools::displayError("Error occurred! (" . $response['error']['type'] . " - " . $response['error']['message'] . ")"));
				return false;
			} else if(!$response['return_url']) {
                if (isset($response["order_key"]))
                {
                    $response['url']="https://www.cavirtex.com/merchant_invoice?merchant_key=".$uname."&order_key=".$response["order_key"];
                    header('Location:  ' . $response['url']);
                } else                
				die(Tools::displayError("Error: There was an error proccesing your request. Please make sure you filled in all information correctly."));
			} else {
				header('Location:  ' . $response['return_url']);
			}			
		}

		function writeDetails($id_order, $cart_id, $invoice_id, $status)
		{
			$invoice_id = stripslashes(str_replace("'", '', $invoice_id));
			$status = stripslashes(str_replace("'", '', $status));
			$db = Db::getInstance();
			$result = $db->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_virtex` (`id_order`, `cart_id`, `invoice_id`, `status`) VALUES(' . intval($id_order) . ', ' . intval($cart_id) . ', "' . $invoice_id . '", "' . $status . '")');
		}

		function readVirtexpaymentdetails($id_order)
		{
			$db = Db::getInstance();
			$result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_virtex` WHERE `id_order` = ' . intval($id_order) . ';');
			return $result[0];
		}

		function hookInvoice($params)
		{
			global $smarty;

			$id_order = $params['id_order'];
			
			$bitcoinpaymentdetails = $this->readVirtexpaymentdetails($id_order);

			$smarty->assign(array(
				'invoice_id' => $bitcoinpaymentdetails['invoice_id'],
				'status' => $bitcoinpaymentdetails['status'],
				'id_order' => $id_order,
				'this_page' => $_SERVER['REQUEST_URI'],
				'this_path' => $this->_path,
				'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"
			));
		
			return $this->display(__FILE__, 'invoice_block.tpl');
		}

		function hookpaymentReturn($params)
		{
			global $smarty;

			$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));

			return $this->display(__FILE__, 'complete.tpl');
		}
	}
?>
