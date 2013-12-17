<?php
require './auth.php';	
require $xcart_dir.'/modules/Virtex/bp_lib.php';

$module_params = func_get_pm_params('ps_virtex.php');
$api_key = $module_params['param01'];
$transaction_speed = $module_params['param02'];

if (!isset($_POST['paymentid'])) { // POST from virtex's server       
	bpLog(file_get_contents('php://input'));
	
	$invoice = bpVerifyNotification($api_key);
	if (is_string($invoice)) { 
		bpLog($invoice); // log the error
	}
	else	
	{
		// fetch session
		$skey = $orderids = $invoice['posData'];
		$bill_output['sessid'] = func_query_first_cell("SELECT sessid FROM $sql_tbl[cc_pp3_data] WHERE ref='".$orderids."'");

		// APC system responder
		foreach ($_POST as $k => $v) {
			$advinfo[] = "$k: $v";
		}
			
		// update order status
		if ($invoice['status'] == 'confirmed' or $invoice['status'] == 'complete')
		{
			$bill_output['sessid'] = func_query_first_cell("SELECT sessid FROM $sql_tbl[cc_pp3_data] WHERE ref='".$orderids."'");
				
			$bill_output['code'] = 1;			
			$bill_output['billmsg'] = 'Order paid for';
			bpLog($bill_output);
			require($xcart_dir.'/payment/payment_ccend.php');
    
		}
		#elseif (invoice['status'] == 'expired')
			#$bill_output['code'] = 2;
			#$bill_output['billmes'] = 'expired';
			#require($xcart_dir.'/payment/payment_ccend.php');

	}
	
} 
else { // POST from customer placing the order

    if (!defined('XCART_START')) { header("Location: ../"); die("Access denied"); }	
	
	// associate order id with session
	$_orderids = join("-",$secure_oid);
    if (!$duplicate)
        db_query("REPLACE INTO $sql_tbl[cc_pp3_data] (ref,sessid,trstat) VALUES ('".$_orderids."','".$XCARTSESSID."','GO|".implode('|',$secure_oid)."')");
	
	
	
	// itemDesc
	if (count($cart['products']) == 1)
	{
		$item = $cart['products'][0];
		$name = $item['product'];
		if ( $item['amount'] > 1 )
			$name = $item['amount'].'x '.$name;
	}
	else
	{
		foreach($cart['products'] as $item) 
			$quantity += $item['amount'];
		$name = $quantity.' items';
	}
	
	
	// create invoice
	$options = array(
		'name' => $name,
		'return_url' => $current_location.'/order.php?orderid='.$_orderids,
		'shipping_required' => 0,
		'currency' => $module_params['param03'],
		'customer_name' => $bill_firstname . ' ' . $bill_lastname,
		'email' => $userinfo['email'],
		'address' => $userinfo['s_address'],
		'city' => $userinfo['s_city'],
		'province' => $userinfo['s_state'],
		'postal' => $userinfo['s_zipcode'],
		'country' => $userinfo['s_country'],
		'apiKey' => $api_key,
	);
	bpLog($options);
	$invoice = bpCreateInvoice($_orderids, $cart['total_cost'], $_orderids, $options);
	bpLog($invoice);
	
	if (is_array($invoice) && isset($invoice['order_key']))
	{
		$url = $bpOptions['apiURL']."/merchant_invoice?merchant_key=".$invoice['merchant_key']."&order_key=".$invoice['order_key'];   
		print "<script> window.location = '$url'; </script>"; 
		die();
	}
	
	
	return false;
}
