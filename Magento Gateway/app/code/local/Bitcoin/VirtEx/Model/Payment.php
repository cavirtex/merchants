<?php


class Bitcoin_VirtEx_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'virtex';
	protected $_isInitializeNeeded = TRUE;
	protected $_canUseCheckout = TRUE;
	protected $_canUseInternal = FALSE;
	protected $_canUseForMultishipping = FALSE;

	protected $_order;
	protected $_merchantKey;
	protected $_merchantSecret;
	
    protected $_formBlockType = 'virtex/form';

	public function canUseForCurrency($currencyCode)
	{
		$currencyCode = strtoupper($currencyCode);
		if ( $currencyCode == "CAD") {
			return true;
		}
			
		//$baseCode = Mage::app()->getBaseCurrencyCode(); = $currencyCode
		$currencies = Mage::getStoreConfig('payment/virtex/currencies');
		$currencies = array_map('strtoupper', array_map('trim', explode(',', $currencies)) );
		$is_base_currency_allowed = array_search($currencyCode, $currencies);
		if ( $is_base_currency_allowed !== false ) {
			$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
			$allowedCurrencies = array_map('strtoupper', $allowedCurrencies);
			$rates = Mage::getModel('directory/currency')->getCurrencyRates($currencyCode, array_values($allowedCurrencies));
			if( array_search('CAD', $allowedCurrencies)!==false && !empty( $rates['CAD'] ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function isAvailable($quote = null)
    {
		// @todo: can be done using observer for event in parent::isAvailable()
		$maxcavirtex = Mage::getStoreConfig('payment/virtex/maxpayment');
		if ( (float)$quote->getGrandTotal() > intval($maxcavirtex) ) {
			return false;
		}
        return parent::isAvailable();
    }
	
	public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('virtex/form', $name);

        return $block;
    }
	
	public function canUseCheckout() {
		$secret = $this->getMerchantSecret();
		$apikey = $this->getMerchantKey();
		if (!$secret || !strlen($secret) || !$apikey || !strlen($apikey)) {
		  Mage::log('VirtEx: API key / Secret key not entered', null, 'virtex.log');
		  return false;
		}
		return $this->_canUseCheckout;
	}
	
	public function getOrderPlaceRedirectUrl()
	{
			return Mage::getUrl('virtex/payment/pay');
	}

	public function initialize($paymentAction, $stateObject)
	{
		$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$stateObject->setState($state);
		$stateObject->setStatus('pending_payment');
		$stateObject->setIsNotified(TRUE);
	}

	public function getOrder()
	{
		if (!$this->_order) {
			$this->_order = $this->getInfoInstance()->getOrder();
		}
		return $this->_order;
	}

	public function getMerchantKey()
	{
		if(!$this->_merchantKey) {
			$this->_merchantKey = Mage::getStoreConfig('payment/virtex/merchantkey');
		}
		return $this->_merchantKey;
	}

	public function getMerchantSecret()
	{
		if(!$this->_merchantSecret) {
			$this->_merchantSecret = Mage::getStoreConfig('payment/virtex/merchantsecret');
		}
		return $this->_merchantSecret;
	}

	public function createVirtexInvoice()
	{
		$httpClient = Mage_HTTP_Client::getInstance();
		$checkout = Mage::getSingleton('checkout/session');
		$orderIncrementId = $checkout->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		
		if (empty($orderIncrementId)) {
			Mage::log('VirtEx: Empty Order. Invoice could not be created.', null, 'virtex.log');
			return array();
		}
		
		$BaseTotalDue = $order->getBaseTotalDue();
		$addspread = (float)Mage::getStoreConfig('payment/virtex/addspread');
		if( empty( $addspread ) || $addspread == (float)100 )
			$getBaseTotalDue = $order->getBaseTotalDue();
		else
			$getBaseTotalDue = $order->getBaseTotalDue() * ( $addspread/100 );
			
		$currencyCode = Mage::app()->getBaseCurrencyCode();
		if( strtoupper($currencyCode) == 'CAD' ) {
			$cadconversionrate = 1;
		} else {
			$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
			$rates = Mage::getModel('directory/currency')->getCurrencyRates($currencyCode, array_values( array_map('strtoupper', $allowedCurrencies) ));
			if( !empty( $rates['CAD'] ) ) {
				$cadconversionrate = $rates['CAD'];
			} else {
				$cadconversionrate = 1; // @todo throw exception
			}
		}
		$custom_1_data = array( 'real_order_id' => $order->getRealOrderId() );
		
		$params = array( //@todo can add 'cancel_url'
			"shipping_required" => 0,
			"format" => "json",
			"name" => "magento_cart_total",
			"price" => $getBaseTotalDue * $cadconversionrate,
			"custom_1" => json_encode( array( 'data' => $custom_1_data, 'hash' => crypt( serialize($custom_1_data), $this->getMerchantKey() ) ) ),
			"code" => $order->getRealOrderId()
		);
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$params['customer_name'] = $customer->getName();
			//$params['email'] = $customer->getEmail(); //@possible dont send email as we dont want cavirtex to email invoice directly to customer // can enable this if we enable vsurcharge
		}
		
		//@possible disable this for vsurcharge
		$invoiceparams = $params;
		$invoiceparams['display_price'] = $order->getBaseTotalDue();
		$invoiceparams['addspread'] = $addspread;
		$invoiceparams['cadconversionrate'] = $cadconversionrate;
		Mage::getSingleton('core/session')->setBitcoinOrderData($invoiceparams);
		
		$httpClient->post("https://www.cavirtex.com/merchant_purchase/".$this->getMerchantKey(), $params);
		$body = $httpClient->getBody();
		Mage::log('VirtEx: Invoice created: '.$body, null, 'virtex.log');
		$j = json_decode($body, true);
		return $j;
	}
	

	public function confirmIpn($data)
	{	
		$custom_1 = json_decode($data['custom_1'], true);
		if( $custom_1['hash'] != crypt( serialize($custom_1['data']), $this->getMerchantKey() ) ) {
			Mage::log('VirtEx: IPN data not verified (incorrect data hash)'.' '.json_encode($data), null, 'virtex.log');
			return;	
		}
		
		$httpClient = Mage_HTTP_Client::getInstance();
		$params = array(
			"secret_key" => $this->getMerchantSecret(),
			"order_key" => $data["order_key"],
			"btc_received" => $data["btc_received"],
			"invoice_number" => $data["invoice_number"],
			"exchange_rate" => $data["exchange_rate"],
			"tax_total" => $data["tax_total"],
			"shipping_total" => $data["shipping_total"],
			"cad_total" => $data["cad_total"],
			"name" => $data["name"],
			"email" => $data["email"],
			"custom_1" => $data["custom_1"]
		);
		//Magento post doesnt deal with null properly. However, for ipn we need to send ALL even if they are empty, so lets use curl directly.
		//$httpClient->post("https://www.cavirtex.com/merchant_confirm_ipn", $params);
		//$body = $httpClient->getBody();
		//$ipn_confirm = json_decode($body, true);
		
		$ch = curl_init('https://cavirtex.com/merchant_confirm_ipn');
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_HEADER => false,
			CURLOPT_HTTP_VERSION => 1.0
		));
		$response = curl_exec($ch);
		if($response === FALSE){
			die(curl_error($ch));
		}
		$ipn_confirm = json_decode($response, true);
		
		$real_order_id = $custom_1['data']['real_order_id'];
		$order = Mage::getModel('sales/order')->loadByIncrementId($real_order_id);
		if (empty($real_order_id)) {
			Mage::log('VirtEx: IPN confirmed. Empty Order ID. Payment not processed'.' '.json_encode($data), null, 'virtex.log');
			return;
		}
		$vex_invoice_order = $this->VexInvoiceOrder($order);
			
		if( !empty($ipn_confirm['status']) && $ipn_confirm['status'] != 'error' ) {// status came back 'ok'
			Mage::log($logmessage.'VirtEx: Payment complete. IPN data confirmed. '.$vex_invoice_order.' '.$body, null, 'virtex.log');
			return;	
		} else {
			Mage::log('VirtEx: IPN unconfirmed: '.$ipn_confirm['message'].' :: '.$body.' '.json_encode($data).' '.$vex_invoice_order, null, 'virtex.log');
			return;	
		}
	}
	
	// payment confirmed, so invoice
	public function VexInvoiceOrder($order) {
		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		if (!count($order->getInvoiceCollection())) {
			$invoice = $order->prepareInvoice()
						->setTransactionId(1)
						->addComment('Invoiced automatically by Bitcoin/VirtEx/controllers/PaymentController.php') //@todo bug : $comment is empty when it reaches addComment($comment, $notify=false, $visibleOnFront=false) in mage/sales/mmodel/order/invoice.php
						->register()
						->pay();
		
			$transactionSave = Mage::getModel('core/resource_transaction')
								->addObject($invoice)
								->addObject($invoice->getOrder());
			$transactionSave->save();
			//$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
			try {
				$order->sendNewOrderEmail();
			} catch (Exception $e) {
				Mage::logException($e);
			}
			return 'Payment processed, order invoiced.';
		} else {
			return 'Count of InvoiceCollection was zero. Order not invoiced.';
		}
	}
	
	public function checkIpn($order_key) 
	{
        $options = array(
            CURLOPT_CUSTOMREQUEST  =>"GET", 
            CURLOPT_POST           =>false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false, 
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",   
            CURLOPT_AUTOREFERER    => true, 
            CURLOPT_CONNECTTIMEOUT => 120,  
            CURLOPT_TIMEOUT        => 120, 
            CURLOPT_MAXREDIRS      => 10,  
			CURLOPT_SSL_VERIFYPEER=>false,
			CURLOPT_SSL_VERIFYHOST=>false
        );
		$ch = curl_init('https://www.cavirtex.com/merchant_invoice_balance_check?merchant_key='.$this->getMerchantKey().'&order_key='.$order_key);
		curl_setopt_array( $ch, $options );
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
}
