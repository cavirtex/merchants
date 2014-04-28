<?php

class VirtEx_Lib {
	
	private $merchant_key;
	private $merchant_secret;
	
	public function __construct($merchant_key, $merchant_secret){
		$this->merchant_key    = $merchant_key;
		$this->merchant_secret = $merchant_secret;
	}
	
	public function merchant_purchase($params){
		$url = 'https://www.cavirtex.com/merchant_purchase/' . $this->merchant_key;
		$response = $this->vxCurl($url, $params);
		return $response;
	}
	
	public function merchant_invoice_balance_check($order_key){
		$url = 'https://www.cavirtex.com/merchant_invoice?merchant_key='.$this->merchant_key.'&order_key='.$order_key;
		$response = $this->vxCurl($url);
		return $response;
	}
	
	public function merchant_confirm_ipn($params){
		$params['secret_key'] = $this->merchant_secret;
		$url = 'https://www.cavirtex.com/merchant_confirm_ipn';
		$response = $this->vxCurl($url, $params);
		return $response;
	}
	
	private static function vxCurl($url, $post = false) {
		$curl = curl_init($url);    
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, 1.0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
		if (is_array($post)) {     
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);        
		}           
		$responseString = curl_exec($curl);            
		if(!$responseString) {
			$response = curl_error($curl);
		} else {
			$response =json_decode($responseString, true);
		}
		curl_close($curl);
		return $response;
	}
	
}

?>