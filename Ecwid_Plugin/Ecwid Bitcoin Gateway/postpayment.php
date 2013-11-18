<?php require_once 'config.php' ?>
<?php
// Some code belongs to Jazon Fazey of http://www.ecwid.com/forums/showthread.php?t=13635
function debuglogVirtEx($contents)
{
	$file = dirname(__FILE__).'/virtex/log.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contentsvirtexCreateInvoice, true)."\n", FILE_APPEND);
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}


if(!isset($_GET['posData'])){
	exit();
}
session_name("Ecwid-Orders_".$_GET["posData"]);
session_start();

require('virtex/virtex_lib.php');

$x_trans_id = $_SESSION["Ecwid_Orders"][$_GET["posData"]]["order_key"];
$x_invoice_num = $_SESSION["Ecwid_Orders"][$_GET["posData"]]["invoice_num"];

$response = virtexVerifyNotification($CaVirtEx_MerchantKey, $x_trans_id);

if($response['status'] == "paid" || $response['status'] == "credited")
{
	//got one of the payment complete messages
} else {
	exit();
}

// x_response_code must = 1 for the cart to update with an approved sale, your script should determine this before hand
$x_response_code = '1';
// x_response_reason_code must = 1  for the cart to update with an approved sale, your script should determine this before hand
$x_response_reason_code = '1';
// change total paid to the total paid. Please note it must match the original total that ecwid sent at the beginning
$x_amount = $_SESSION["Ecwid_Orders"][$_GET["posData"]]["invoice_total"];
 
// Do not change anything below this line, other than your_store_id_#

$string = $hash_value.$x_login.$x_trans_id.$x_amount;
$x_MD5_Hash = md5($string);
$datatopost = array (
"x_response_code" => $x_response_code,
"x_response_reason_code" => $x_response_reason_code,
"x_trans_id" => $x_trans_id,
"x_invoice_num" => $x_invoice_num,
"x_amount" => $x_amount,
"x_MD5_Hash" => $x_MD5_Hash,
);

$url = $_SESSION["Ecwid_Orders"][$_GET["posData"]]["store_url"];
$ch = curl_init($url);
 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
$response = curl_exec($ch);
curl_close($ch);

session_destroy();
?>
<?php echo $response; ?><br />