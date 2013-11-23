<?php
$data = json_decode(file_get_contents('php://input'), true);

$lgfile = 'callback.php';
$date = date('Y/m/d H:i:s');
//file_put_contents($lgfile, $date." Got callback. ".print_r($data, TRUE)."\r\n", FILE_APPEND | LOCK_EX);

require('includes/application_top.php');

global $db;

$order_key = $data["order_key"];
$invoice_number = $data["invoice_number"];
$btc_received = $data["btc_received"];

$url="https://www.cavirtex.com/merchant_confirm_ipn";

$curl = curl_init($url);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

$post = array();
$post["invoice_number"]=$invoice_number;
$post["order_key"]=$order_key;
$post["btc_received"]=$btc_received;
$post["secret_key"]=MODULE_PAYMENT_CAVIRTEX_MERCHANT_SECRET_KEY;

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
	die(); 
} // IPN confirmed at this point. Good to go.

$db->Execute("update " . TABLE_ORDERS . " set orders_status = " . 
		(int)MODULE_PAYMENT_CAVIRTEX_CONFIRMED_STATUS_ID . " where cavirtex_order_key = '" .
		$order_key . "'");

//require('includes/application_bottom.php');
		
die();

?>