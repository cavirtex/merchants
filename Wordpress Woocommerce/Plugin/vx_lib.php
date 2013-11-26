<?php

require_once 'vx_options.php';

function vxCurl($url, $apiKey, $post = false) {
	global $vxOptions;	   
    $curl = curl_init($url.$apiKey);    
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
    if (is_array($post))
    {     
        /* re-map fields */
      /*$vx = array('name', 'price', 'shipping_required', 'quantity','code',  'return_url', 
        'customer_name','address', 'address2', 'city','quantity', 'province', 'postal', 'country', 'email');  */   
     $vx = array();
     $vx["name"]   ="Order #".$post["orderID"];
     $vx["price"]   =$post["price"];
     $vx["shipping_required"]=isset($post["shipping_address_1"])?1:0;
     $vx["code"]   =$post["orderID"];
     $vx["return_url"]   =$post["redirectURL"];
     $vx["customer_name"]   =$post["buyerName"];
     $vx["address"]   =$post["buyerAddress1"];
     $vx["address2"]   =$post["buyerAddress2"];
     $vx["city"]   =$post["buyerCity"];
     $vx["province"]   =$post["buyerState"];
     $vx["postal"]   =$post["buyerZip"];
     $vx["email"]   =$post["buyerEmail"];
     $vx["format"]="json";
     
    /*$postOptions = array('orderID', 'itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 
        'posData', 'price', 'currency', 'physical', 'fullNotifications', 'transactionSpeed', 'buyerName', 
        'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone');*/
     
        $post["format"]="json";
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $vx);        
    }           
    $responseString = curl_exec($curl);            
    if($responseString == false) {
        $response = curl_error($curl);
    } else {
        $response =json_decode($responseString, true);
    }
    curl_close($curl);  
    if (isset($response["order_key"]))
    {
        $response["url"]="https://www.cavirtex.com/merchant_invoice?merchant_key=".$apiKey."&order_key=".$response["order_key"];         
    }
    return $response;
}

function vxCreateInvoice($orderId, $price, $posData, $options = array()) {	
	global $vxOptions;	
	
	$options = array_merge($vxOptions, $options);	// $options override any options found in vx_options.php
	
	$pos = array('posData' => $posData);
	if ($vxOptions['verifyPos'])
		$pos['hash'] = crypt(serialize($posData), $options['apiKey']);
	$options['posData'] = json_encode($pos);
	
	$options['orderID'] = $orderId;
	$options['price'] = $price;
	
	$postOptions = array('orderID', 'itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 
		'posData', 'price', 'currency', 'physical', 'fullNotifications', 'transactionSpeed', 'buyerName', 
		'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone');
    $data=array();
	foreach($postOptions as $o)
		if (array_key_exists($o, $options))
			$data[$o] = $options[$o];		 
	$response = vxCurl('https://www.cavirtex.com/merchant_purchase/', $options['apiKey'], $data);	
	if (is_string($response))
		return array('error' => $response);	

	return $response;
}

// Call from your notification handler to convert $_POST data to an object containing invoice data
function vxVerifyNotification($apiKey = false) {
	global $vxOptions;
	if (!$apiKey)
		$apiKey = $vxOptions['apiKey'];		
	
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
	if($vxOptions['verifyPos'] and $posData['hash'] != crypt(serialize($posData['posData']), $apiKey)) 
		return array('error' => 'authentication failed (bad hash)');
	$json['posData'] = $posData['posData'];
		
	return $json;
}

// $options can include ('apiKey')
function vxGetInvoice($invoiceId, $apiKey=false) {
	global $vxOptions;
	  $_url="https://www.cavirtex.com/merchant_invoice/";
    if (!$apiKey)
        $apiKey = $virtexOptions['apiKey'];        
	$response = vxCurl( $_url, $apiKey,$post);
	if (is_string($response))
		return array('error' => $response); 
	//decode posData
	$response['posData'] = json_decode($response['posData'], true);
	$response['posData'] = $response['posData']['posData'];

	return $response;	
}


?>