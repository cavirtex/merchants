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
$ipnData = file_get_contents("php://input");
if(empty($ipnData)) {
	exit();
}
$ipnData = json_decode($ipnData);
if($ipnData === null) {
	exit();
}
// Check for orderkey in database
$query = select_query("tblcavirtex", "whmcsid,btc", array("orderkey" => $ipnData->order_key));
if(!mysql_num_rows($query)) {
	errorExit(array("orderkey" => $ipnData->order_key), "Order Key Not Found");
}

checkCbTransID($ipnData->order_key);

$dbinfo = mysql_fetch_assoc($query);
if($dbinfo["btc"] != $ipnData->btc_received) {
	errorExit(array("ipn" => json_encode($ipnData)), "Amount Not Paid In Full");
}

$invoiceid = checkCbInvoiceID($dbinfo["whmcsid"], $GATEWAY["name"]); // Checks invoice ID is a valid invoice number or ends processing

$localInvoice = localAPI("getinvoice", array("invoiceid" => $dbinfo["whmcsid"]), 1); // Uses admin ID of 1
if($localInvoice["result"] == "error") {
	errorExit(array("local" => json_encode($localInvoice), "ipn" => json_encode($ipnData)), "localAPI GetInvoice Failed");
}

if(strtolower($localInvoice["status"]) != "unpaid") {
	errorExit(array("local" => json_encode($localInvoice), "ipn" => json_encode($ipnData)), "Invoice Is Not Unpaid");
}

// Verify IPN data
$post = array(
	"secret_key" => trim($GATEWAY["secretkey"]),
	"order_key" => $ipnData->order_key,
	"btc_received" => (string)$ipnData->btc_received,
	"invoice_number" => (string)$ipnData->invoice_number
);
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
		addInvoicePayment($dbinfo["whmcsid"], $ipnData->order_key, $localInvoice["total"], "0", $gatewaymodule);
		logTransaction($GATEWAY["name"], array("ipn" => json_encode($ipnData), "confirm" => json_encode($data)), "Successful");
		break;
	case "error":
		errorExit(array("ipn" => json_encode($ipnData), "confirm" => json_encode($data), "post" => json_encode($post)), "IPN Confirmation Failed");
}

function errorExit($data, $status = "Unsuccessful") {
	logTransaction($GLOBALS["GATEWAY"]["name"], $data, $status);
	exit();
}
