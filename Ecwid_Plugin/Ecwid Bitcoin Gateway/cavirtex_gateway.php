<?php require_once 'config.php' ?>
<?php
// -------------------------------------------------------------------------
// Create required variables for your merchant
// Some code belongs to Jazon Fazey of http://www.ecwid.com/forums/showthread.php?t=13635
// -------------------------------------------------------------------------
// request this order number
$payment_reference = $_POST['x_invoice_num'];
$orderNum = $_POST['x_description'];
// request the order total
$payment_amount = $_POST['x_amount'];

$post = array();
foreach (explode('&', file_get_contents('php://input')) as $keyValuePair) {
	list($key, $value) = explode('=', $keyValuePair);
	$post[$key][] = urldecode($value);
}

$whenDone = $_POST['x_relay_url'];

$customer_name = $_POST['x_last_name'];
if($_POST['x_first_name'] != '') {
	$customer_name = $_POST['x_first_name'].' '.$_POST['x_last_name'];
} 

$address = $_POST['x_address'];
$city = $_POST['x_city'];
$province = $_POST['x_state'];
$postal = $_POST['x_zip'];
$country = $_POST['x_country'];
$email = $_POST['x_email']; 
  
if(isset($_POST['x_ship_to_last_name'])){
	$ship_name = $_POST['x_ship_to_last_name'];
	if($_POST['x_ship_to_first_name'] != '') {
		$ship_name = $_POST['x_ship_to_first_name'].' '.$_POST['x_ship_to_last_name'];
	}	
	$ship_address = $_POST['x_ship_to_address'];
	$ship_city = $_POST['x_ship_to_city'];
	$ship_province = $_POST['x_ship_to_state'];
	$ship_postal = $_POST['x_ship_to_zip'];
	$ship_country = $_POST['x_ship_to_country'];		
}

$postal = str_replace(" ", "", $postal);
$ship_postal = str_replace(" ", "", $ship_postal);

if(isset($_POST['x_freight'])){
	$shipping = $_POST['x_freight'];
}

require('virtex/virtex_lib.php');
$options['email'] = $email;


if(isset($ship_name)){
	$options['customer_name'] = $ship_name;
	$options['address'] = $ship_address;
	$pc = getProvinceCode($ship_province, $PROVINCES);
	if($pc != "") {
		$options['province'] = $pc;
	} 
	$options['city'] = $ship_city;
	$cc = getCountryCode($ship_country, $COUNTRYCODES);
	if($cc != "") {
		$options['country'] = $cc;
	}
	$options['postal'] = $ship_postal;
	$options['shipping_required'] = 1;
	if(!isset($shipping)){
		$shipping = 0;
	}
	$options['shipping'] = $shipping;
} else {
	$options['customer_name'] = $customer_name;
	$options['address'] = $address;
	$pc = getProvinceCode($province, $PROVINCES);
	if($pc != "") {
		$options['province'] = $pc;
	}
	$options['city'] = $city;
	$cc = getCountryCode($country, $COUNTRYCODES);
	if($cc != "") {
		$options['country'] = $cc;
	}
	$options['postal'] = $postal;
	$options['shipping_required'] = 0;
}

if (count($post['x_line_item']) == 1)
{
	$lineItem = line_item_exploder($post['x_line_item'][0]);
	
	$options['name'] = $lineItem['name'];
	$options['price'] = $lineItem['unitPrice'];
	$options['quantity'] = $lineItem['quantity'];
	$options['code'] = $lineItem['ID'];
	//'shipping_required', 'return_url',
	if($options['quantity'] != 1) {
		$options['name'] = $options['quantity'].'x '.$options['name'];
	}
} else {
	//$quantity = 0;
	$onItem = 0;
	foreach($post['x_line_item'] as $li) {
		$lineItem = line_item_exploder($li);
		$onItem++;
		if(onItem==1){
			$options['name'] = $lineItem['name'];
			$options['price'] = $lineItem['unitPrice'];
			$options['quantity'] = $lineItem['quantity'];
			$options['code'] = $lineItem['ID'];
			//'shipping_required', 'return_url',
			if($options['quantity'] != 1) {
				$options['name'] = $options['quantity'].'x '.$options['name'];
			}		
		} else {
			$options['name'.$onItem] = $lineItem['name'];
			$options['price'.$onItem] = $lineItem['unitPrice'];
			$options['quantity'.$onItem] = $lineItem['quantity'];
			$options['code'.$onItem] = $lineItem['ID'];
			//'shipping_required', 'return_url',
			if($options['quantity'.$onItem] != 1) {
				$options['name'.$onItem] = $options['quantity'.$onItem].'x '.$options['name'.$onItem];
			}	
		}
				
		//$quantity += $lineItem['quantity'];
	}
    //$options['name'] = $quantity.' items';
}

// The wordpress plugin didn't use currency, so it's not used here.
// $options['currency'] = $_POST['x_currency_code'];

$options['return_url'] = $return_link_url."?posData=".$payment_reference;
$options['apiKey'] = $CaVirtEx_MerchantKey;

foreach(array("customer_name", "address", "address2", "city", "province", "postal", "country", "email") as $k)
{
	$options[$k] = substr($options[$k], 0, 100);
}

$price = number_format($payment_amount,2);

$options['posData'] = $payment_reference;

$invoice = virtexCreateInvoice($payment_reference, $price, $payment_reference, $options);

if (isset($invoice['error'])) {
	debuglog($invoice);
	exit();
	//redirect back to checkout page with errors
	//$_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully, please try again.');
	//header("Location: ".get_option('checkout_url'));
}else{
		
	if (isset($invoice["order_key"]))
	{
		session_name("Ecwid-Orders_".$payment_reference);
		session_start();
		$_SESSION["Ecwid_Orders"][$payment_reference]["order_key"] = $invoice["order_key"];
		$_SESSION["Ecwid_Orders"][$payment_reference]["invoice_num"] = $payment_reference;
		$_SESSION["Ecwid_Orders"][$payment_reference]["store_url"] = $_POST['x_relay_url'];
		$_SESSION["Ecwid_Orders"][$payment_reference]["invoice_total"] = $payment_amount;
		
		$url="https://www.cavirtex.com/merchant_invoice?merchant_key=".$options['apiKey']."&order_key=".$invoice["order_key"];
		header("Location: ".$url);
		exit();
	} else
	{
			header("Location: ".$options['return_url']);
		exit();
	}
}

function line_item_exploder($lineItem){
	$lineItemValues = Array();
	$lineItemParts = explode("<|>", $lineItem);
	$lineItemValues['name'] = $lineItemParts[0]; 
	$lineItemValues['ID'] = $lineItemParts[1];
	$lineItemValues['description'] = $lineItemParts[2];
	$lineItemValues['quantity'] = $lineItemParts[3];
	$lineItemValues['unitPrice'] = $lineItemParts[4];
	$lineItemValues['taxable'] = $lineItemParts[5];
	
	return $lineItemValues;
}

function getCountryCode($country, $COUNTRYCODES){
	foreach($COUNTRYCODES as $ck => $cv){
		if($cv == $country) {
			return $ck;
		}
	}
	return "";
}

function getProvinceCode($province, $PROVINCES){
	foreach($PROVINCES as $pk => $pv){
		if($pv == $province) {
			return $pk;
		}
	}
	return "";
}

function debuglogVirtEx($contents)
{
	$file = 'virtex/log.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contentsvirtexCreateInvoice, true)."\n", FILE_APPEND);
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}
?>