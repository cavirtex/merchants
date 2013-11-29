<?php

function cavirtex_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"CaVirtex"),
     "merchant_key" => array("FriendlyName" => "Merchant Key", "Type" => "text", "Size" => "40", ),
     "merchant_sk" => array("FriendlyName" => "Merchant Secret Key", "Type" => "text", "Size" => "40", ),
     "shipping_required" => array("FriendlyName" => "Shipping Required", "Type" => "yesno", ),
    );
	return $configarray;
}

function cavirtex_link($params) {

	# Gateway Specific Variables
	$key = $params['merchant_key'];
	$sk = $params['merchant_sk'];
	$ship = $params['shipping_required'];

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];

	# Enter your code submit to the gateway...

	$code = '<form method="https://www.cavirtex.com/merchant_purchase/'.$key.'">
<input type="hidden" name="name" value="Invoice '.$invoiceid.' ('.$description.')" />
<input type="hidden" name="code" value="'.$invoiceid.'" />
<input type="hidden" name="price" value="'.$amount.'" />
<input type="hidden" name="shipping_required" value="'.$ship.'" />
<input type="hidden" name="amount" value="'.$amount.'" />
<input type="hidden" name="email" value="'.$email.'" />
<input type="hidden" name="customer_name" value="'.$firstname.' '.$lastname.'" />
<input type="hidden" name="address" value="'.$address1.'" />
<input type="hidden" name="address2" value="'.$address2.'" />
<input type="hidden" name="city" value="'.$city.'" />
<input type="hidden" name="province" value="'.$state.'" />
<input type="hidden" name="postal" value="'.$postcode.'" />
<input type="hidden" name="country" value="'.$country.'" />
<input type="submit" value="Pay Now" />
</form>';

	return $code;
}



?>