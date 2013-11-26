<?php
require_once 'virtex_options.php';

function virtexCurl($url, $apiKey, $post = false) {
	global $virtexOptions;			
	$curl = curl_init($url.$apiKey);	
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
    if (is_array($post))
    {      
        $post["format"]="json";
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);        
    }   		
	$responseString = curl_exec($curl);    	
	if($responseString == false) {
		$response = curl_error($curl);
	} else {
		$response =json_decode($responseString, true);
	}
	curl_close($curl);      
    
	return $response;
}
// $orderId: Used to display an orderID to the buyer. In the account summary view, this value is used to 
// identify a ledger entry if present.
//
// $price: by default, $price is expressed in the currency you set in virtex_options.php.  The currency can be 
// changed in $options.
//
// $posData: this field is included in status updates or requests to get an invoice.  It is intended to be used by
// the merchant to uniquely identify an order associated with an invoice in their system.  Aside from that, Virtex does
// not use the data in this field.  The data in this field can be anything that is meaningful to the merchant.
//
// $options keys can include any of: 
// ('name', 'price', 'shipping_required','shipping', 'quantity','code',  'return_url','cancel_url', 
//		'customer_name','address', 'address2', 'city', 'province', 'postal', 'country', 'email')
// If a given option is not provided here, the value of that option will default to what is found in virtex_options.php
// (see api documentation for information on these options).
function virtexCreateInvoice($orderId, $price, $posData, $options = array()) {	
	global $virtexOptions;	
	
	$options = array_merge($virtexOptions, $options);
	$_url="https://www.cavirtex.com/merchant_purchase/";
	$pos = array('posData' => $posData);
	if ($virtexOptions['verifyPos'])
		$pos['hash'] = crypt(serialize($posData), $options['apiKey']);
	$options['posData'] = json_encode($pos);
	
	$options['code'] = $orderId;
	$options['price'] = $price;
	
	$postOptions = array('name', 'price', 'shipping_required', 'quantity','code',  'return_url', 
		'customer_name','address', 'address2', 'city','quantity', 'province', 'postal', 'country', 'email');
	foreach($postOptions as $o)
		if (array_key_exists($o, $options))
			$post[$o] = $options[$o];	
	 
	$response = virtexCurl($_url, $options['apiKey'], $post);	        
	if (is_string($response))
		return array('error' => $response);	            
	return $response;
}

// Call from your notification handler to convert $_POST data to an object containing invoice data
function virtexVerifyNotification($apiKey = false) {
	global $virtexOptions;
	if (!$apiKey)
		$apiKey = $virtexOptions['apiKey'];		
	
	$post = file_get_contents("php://input");
	if (!$post)
		return array('error' => 'No post data');
		
	$json = json_decode($post, true);	
	if (is_string($json))
		return array('error' => $json); // error

	if (!array_key_exists('posData', $json)) 
		return array('error' => 'no posData');
		
	// decode posData
	$posData = json_decode($json['posData'], true);
	if($virtexOptions['verifyPos'] and $posData['hash'] != crypt(serialize($posData['posData']), $apiKey)) 
		return array('error' => 'authentication failed (bad hash)');
	$json['posData'] = $posData['posData'];
		
	return $json;
}

// $options can include ('apiKey')
function virtexGetInvoice($invoiceId, $apiKey=false) {
	global $virtexOptions;
    $_url="https://www.cavirtex.com/merchant_invoice/";
	if (!$apiKey)
		$apiKey = $virtexOptions['apiKey'];		
    $post=array();
	$response = virtexCurl($_url, $apiKey,$post);
	if (is_string($response))
		return array('error' => $response); 
	//decode posData
	$response['posData'] = json_decode($response['posData'], true);
	$response['posData'] = $response['posData']['posData'];

	return $response;	
}


?>