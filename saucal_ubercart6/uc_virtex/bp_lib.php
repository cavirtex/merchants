<?php

require_once 'bp_options.php';

function bpLog($contents)
{
	$file = dirname(__FILE__) . '/bclog.txt';
	file_put_contents($file, date('m-d H:i:s') . ": ", FILE_APPEND);
	
	if (is_array($contents))
		$contents = var_export($contents, true);	
	else if (is_object($contents))
		$contents = json_encode($contents);
		
	file_put_contents($file, $contents . "\n", FILE_APPEND);			
}

function bpCurl($url, $apiKey = '', $post = false) {
	global $bpOptions;	
	
	if(!empty($apiKey)) $url .= '/'.$apiKey;
	$curl = curl_init($url);
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
	
	if ($post)
	{
		$post["format"] = "json";
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	}
	
	
	$responseString = curl_exec($curl);
	
	if($responseString == false) {
		$response = array('error' => curl_error($curl));
	} else {
		
		if ($post)
		{
			$response = json_decode($responseString, true);
			if (!$response) $response = array('error' => 'invalid json: ' . $responseString);
		} else $response = $responseString;
	}
	curl_close($curl);
	return $response;
}
// $orderId: Used to display an orderID to the buyer. In the account summary view, this value is used to 
// identify a ledger entry if present.
//
// $price: by default, $price is expressed in the currency you set in bp_options.php.  The currency can be 
// changed in $options.
//
// $posData: this field is included in status updates or requests to get an invoice.  It is intended to be used by
// the merchant to uniquely identify an order associated with an invoice in their system.  Aside from that, Bit-Pay does
// not use the data in this field.  The data in this field can be anything that is meaningful to the merchant.
//
// $options keys can include any of: 
// ('name', 'price', 'shipping_required','shipping', 'quantity','code',  'return_url','cancel_url', 
//                'customer_name','address', 'address2', 'city', 'province', 'postal', 'country', 'email')
// If a given option is not provided here, the value of that option will default to what is found in bp_options.php
// (see api documentation for information on these options).
function bpCreateInvoice($orderId, $price, $posData, $options = array()) {	
	global $bpOptions;	
	
	$options = array_merge($bpOptions, $options);	// $options override any options found in bp_options.php
	
	$pos = array('posData' => $posData);
	
	$options['code'] = $orderId;
	$options['price'] = $price;
	
	$postOptions = array('name', 'price', 'shipping_required', 'quantity', 'code',  'return_url', 'customer_name','address', 'address2', 'city','quantity', 'province', 'postal', 'country', 'email');
	foreach($postOptions as $o)
		if (array_key_exists($o, $options))
			$post[$o] = $options[$o];
	
	$post = $post;
	
	
	$response = bpCurl($bpOptions['apiURL'].'merchant_purchase', $options['apiKey'], $post);
	
	
	return $response;
}

// Call from your notification handler to convert $_POST data to an object containing invoice data
function bpVerifyNotification($apiKey = false) {
	global $bpOptions;
	if (!$apiKey)
		$apiKey = $bpOptions['apiKey'];		
	
	$post = file_get_contents("php://input");
	if (!$post)
		return 'No post data';
		
	$json = json_decode($post, true);
	
	if (is_string($json))
		return $json; // error

	if (!array_key_exists('posData', $json)) 
		return 'no posData';
		
	$posData = json_decode($json['posData'], true);
	if($bpOptions['verifyPos'] and $posData['hash'] != crypt(serialize($posData['posData']), $apiKey)) 
		return 'authentication failed (bad hash)';
	$json['posData'] = $posData['posData'];
	
	return $json;
}

// $options can include ('apiKey')
function bpGetInvoice($invoiceId, $apiKey=false) {
	global $bpOptions;
	if (!$apiKey) $apiKey = $bpOptions['apiKey'];		
	
	$request = $bpOptions['apiURL'].'merchant_invoice?merchant_key='.$apiKey.'&order_key='.$invoiceId;
	bpLog($request);
	$response = bpCurl($request);
	bpLog($response);
	
	if (is_string($response)) return $response; // error
	
	$response['posData'] = json_decode($response['posData'], true);
	$response['posData'] = $response['posData']['posData'];
	
	
	return $response;	
}
