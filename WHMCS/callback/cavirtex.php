<?php

// CAVIRTEX Bitcoin Payment Gateway Module for WHMCS

// Required File Includes
require("../../../dbconnect.php");
require("../../../includes/functions.php");
require("../../../includes/gatewayfunctions.php");
require("../../../includes/invoicefunctions.php");

$gatewaymodule = "cavirtex";

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); // Checks gateway module is active before accepting callback

// Get initial IPN data
$rawData = file_get_contents("php://input");
if(empty($rawData)) {
	exit();
}
$ipnData = json_decode($rawData);
if($ipnData === null) {
	exit();
}

// Verify custom data
if(!property_exists($ipnData, "custom_1") || empty($ipnData->custom_1)) {
	errorExit(array("orderkey" => $ipnData->order_key), "No Custom Data");
}
$customData = json_decode($ipnData->custom_1);
if($customData->hmac != hash_hmac("sha256", $customData->data, $GATEWAY["secretkey"])) {
	errorExit(array("ipn" => json_encode($ipnData)), "HMAC Verification Failed.");
}
// We can now trust $customData->data
$customData = json_decode($customData->data);

checkCbTransID($ipnData->order_key);

$invoiceid = checkCbInvoiceID($customData->whmcsid, $GATEWAY["name"]); // Checks invoice ID is a valid invoice number or ends processing

$localInvoice = localAPI("getinvoice", array("invoiceid" => $customData->whmcsid), 1); // Uses admin ID of 1
if($localInvoice["result"] == "error") {
	errorExit(array("local" => json_encode($localInvoice), "ipn" => json_encode($ipnData)), "localAPI GetInvoice Failed");
}

if(strtolower($localInvoice["status"]) != "unpaid") {
	errorExit(array("local" => json_encode($localInvoice), "ipn" => json_encode($ipnData)), "Invoice Is Not Unpaid");
}

// Check if CAD values match
if($ipnData->cad_total != $customData->amount) {
	errorExit(array("ipn" => json_encode($ipnData)), "CAD Value Mismatch.");
}

// Verify IPN data
$post = json_decode($rawData, true);
$post["secret_key"] = trim($GATEWAY["secretkey"]);

$c = curl_init();
curl_setopt($c, CURLOPT_URL, "https://www.cavirtex.com/merchant_confirm_ipn");
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POSTFIELDS, $post);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($c, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . "/cavirtex/GoDaddyClass2CA.crt");
$data = curl_exec($c);
$curlError = curl_error($c);
curl_close($c);

if($data === false) {
	errorExit(array("ipn" => json_encode($ipnData), "error" => $curlError), "cURL Query Failed");
}

$data = json_decode($data);

switch($data->status) {
	case "ok":
		addInvoicePayment($customData->whmcsid, $ipnData->order_key, $ipnData->cad_total, "0", $gatewaymodule);
		logTransaction($GATEWAY["name"], array("ipn" => json_encode($ipnData), "confirm" => json_encode($data)), "Successful");
		break;
	default:
		errorExit(array("ipn" => json_encode($ipnData), "confirm" => json_encode($data), "post" => json_encode($post)), "IPN Confirmation Failed");
}

function errorExit($data, $status = "Unsuccessful") {
	logTransaction($GLOBALS["GATEWAY"]["name"], $data, $status);
	exit();
}
