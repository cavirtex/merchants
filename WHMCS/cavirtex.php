<?php

// CAVIRTEX Bitcoin Payment Gateway Module for WHMCS

function cavirtex_config() {
	// Makes the assumption you're in the whmcs/admin directory
    $ipn = (!empty($_SERVER["HTTPS"]) ? "https://" : "http://") . $_SERVER["SERVER_NAME"];
    if($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) {
    	$ipn .= ":" . $_SERVER["SERVER_PORT"];
    }
    $exploded = explode(basename($_SERVER["PHP_SELF"]), $_SERVER["PHP_SELF"]);
    $ipn .= dirname($exploded[0]) . "/modules/gateways/callback/cavirtex.php";


    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"VirtEx"),
     "merchantkey" => array("FriendlyName" => "Merchant Key", "Type" => "text", "Size" => "40", "Description" =>
     	"Can be found on your <a target=\"_blank\" href=\"https://www.cavirtex.com/merchant_information\">Merchant Information page</a>." ),
     "secretkey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "40", "Description" =>
     	"Can be found on your <a target=\"_blank\" href=\"https://www.cavirtex.com/merchant_information\">Merchant Information page</a> as well.
     	<br>Your IPN URL is <code>$ipn</code>" )
    );
	return $configarray;
}


// Runs every time on the viewinvoice.php page
function cavirtex_link($params) {

	$postReturn = cavirtex_handle_post($params);

	$code = '<form method="post" action="">
<input type="hidden" name="docavirtex" value="doit" />
<input type="submit" name="paybtn" value="Pay with Bitcoin" onclick="this.disabled=true;this.value=\'Please wait...\';this.form.submit();" />';
	if(is_string($postReturn)) {
		// A string is treated as an error message
		$code .= '<div style="font-weight: bold;">Payment failed: '.htmlentities($postReturn).'</div>';
	}
	$code .= '</form>';

	return $code;
}


// Executes when the user clicks the "Pay with Bitcoin" button.
function cavirtex_handle_post($params) {
	if(!array_key_exists("docavirtex", $_POST)) {
		return false;
	}

	// Gateway Specific Variables
	$merchantkey = $params['merchantkey'];

	// Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

	// Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = str_replace(" ", "", $params['clientdetails']['postcode']);
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	// System Variables
	$returnurl = $params['returnurl'];

	if(strtoupper(trim($currency)) != "CAD") {
		return "Currency must be in CAD.";
	}

	$purchase = cavirtex_merchant_purchase($merchantkey, $description, $amount, $email, "$firstname $lastname",
		$address1, $address2, $city, $state, $postcode, $country, $returnurl);

	// Checks if the cURL call failed
	if($purchase === false) {
		return "API error.";
	}
	
	// Links the VirtEx order_key with our internal WHMCS invoice ID
	insert_query("tblcavirtex", array("whmcsid" => $invoiceid, "orderkey" => $purchase->order_key,
		"btc" => $purchase->btc_total, "expires" => $purchase->time_left + time()));

	// Redirects the user to the payment page
	header("Location: https://www.cavirtex.com/merchant_invoice?merchant_key=".urlencode($merchantkey).
		"&order_key=".urlencode($purchase->order_key));
	exit();
}

// PHP JSON endpoint for the merchant_purchase API
function cavirtex_merchant_purchase($merchantKey, $name, $price, $email, $customerName, $addr1, $addr2,
	$city, $prov, $post, $country, $returnUrl) {
	$post = array(
		"name" => $name,
		"price" => $price,
		"shipping_required" => "0",
		"cancel_url" => "",
		"return_url" => $returnUrl,
		"email" => $email,
		"customer_name" => $customerName,
		"address" => $addr1,
		"city" => $city,
		"province" => cavirtex_abbreviate_province($prov),
		"postal" => $post,
		"country" => $country,
		"format" => "json"
	);
	if(!empty($addr2)) {
		$post["address2"] = $addr2;
	}

	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, "https://www.cavirtex.com/merchant_purchase/" . trim($merchantKey));
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_POSTFIELDS, $post);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($c, CURLOPT_CAINFO, dirname(__FILE__) . "/cavirtex/GoDaddyClass2CA.crt");
	$data = curl_exec($c);
	curl_close($c);
	/* We could do content type checking but it always returns text/html even when it's JSON...
	$ct = explode(";", curl_getinfo($c, CURLINFO_CONTENT_TYPE));
	curl_close($c);
	if(strtolower(trim($ct[0])) != "application/json") {
		//return false;
	}
	*/
	$json = json_decode($data);
	return $data === false || $json === null ? false : $json;
}

// Of debatable usefulness
function cavirtex_abbreviate_province($province) {
	switch(strtolower(trim($province))) {
		case "alberta":
			return "AB";
		case "british columbia":
			return "BC";
		case "manitoba":
			return "MB";
		case "new brunswick":
			return "NB";
		case "newfoundland and labrador":
			return "NL";
		case "nova scotia":
			return "NS";
		case "northwest territories":
			return "NT";
		case "nunavut":
			return "NU";
		case "ontario":
			return "ON";
		case "prince edward island":
			return "PE";
		case "quebec":
			return "QC";
		case "saskatchewan":
			return "SK";
		case "yukon":
			return "YK";
	}
	return $province;
}
