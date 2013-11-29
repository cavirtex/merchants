<?php

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "cavirtex"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

$key = $GATEWAY['merchant_key'];
$sk = $GATEWAY['merchant_sk'];

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
$ipn = json_decode(file_get_contents('php://input'));
$btc_received = $ipn["btc_received"];
$order_key = $ipn["order_key"];
$invoice_number = $ipn["invoice_number"];


$url = 'https://www.cavirtex.com/merchant_confirm_ipn';
$postfields = array( 'secret_key' => $sk, 'order_key' => $order_key,'btc_received' => $btc_received,'invoice_number' => $invoice_number );
$response = json_decode(curlCall($url,$postfields,$options));

$invoice_number = checkCbInvoiceID($invoice_number,$GATEWAY['name']); # Checks invoice ID is a valid invoice number or ends processing

checkCbTransID($invoice_number); # Checks transaction number isn't already in the database and ends processing if it does

if ($response["status"] != "error") {
    # Successful
    addInvoicePayment($invoice_number,$order_key,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
	logTransaction($GATEWAY["name"],$_POST,"Successful"); # Save to Gateway Log: name, data array, status
} else {
	# Unsuccessful
    logTransaction($GATEWAY["name"],$_POST,"Unsuccessful"); # Save to Gateway Log: name, data array, status
}

?>