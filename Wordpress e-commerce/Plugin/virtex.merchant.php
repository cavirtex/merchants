<?php

$nzshpcrt_gateways[$num]['name'] = 'Virtex E-Commerce Plugin';
$nzshpcrt_gateways[$num]['internalname'] = 'wpsc_merchant_virtex';
$nzshpcrt_gateways[$num]['function'] = 'gateway_virtex';
$nzshpcrt_gateways[$num]['form'] = 'form_virtex';
$nzshpcrt_gateways[$num]['submit_function'] = "submit_virtex";
$nzshpcrt_gateways[$num]['image'] =WPSC_URL. '/wpsc-merchants/virtex/virtex-logo.png';
$nzshpcrt_gateways[$num]['display_name']="Virtex E-Commerce Plugin";

function debuglogVirtEx($contents)
{
	$file = 'wp-content/plugins/wp-e-commerce/wpsc-merchants/virtex/log.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contents, true)."\n", FILE_APPEND);		
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}


function form_virtex()
{	
	$rows = array();
	
	// API key
	$rows[] = array('MERCHANT key', '<input name="virtex_apikey" type="text" value="'.get_option('virtex_apikey').'" />', 'Create this at cavirtex.com');

	
	//Allows the merchant to specify a URL to redirect to upon the customer completing payment on the cavirtex.com
	//invoice page. This is typcially the "Transaction Results" page.
	$rows[] = array('Return URL', '<input name="virtex_redirect" type="text" value="'.get_option('virtex_redirect').'" />', 'Put the URL that you want the buyer to be redirected to after payment.');
    
    $select='<select name="virtex_autoredirect" id="virtex_autoredirect">';
    $sel='';    
    if (get_option('virtex_autoredirect')==0)    
    $sel=' selected="selected"';
    $select.='<option value="0" '.$sel.'>Don\'t redirect</option>';
    $sel='';
    if (get_option('virtex_autoredirect')==1)    
    $sel=' selected="selected"';
    $select.='<option value="1" '.$sel.'>Allow redirect</option>';
    $select.='</select>';
    $rows[] = array('Open invoice page after transaction', $select, 'Redirect to Virtex invoice page after successfull transaction');
    
	foreach($rows as $r)
	{
		$output.= '<tr> <td>'.$r[0].'</td> <td>'.$r[1];
		if (isset($r[2]))
			$output .= '<BR/><small>'.$r[2].'</small></td> ';
		$output.= '</tr>';
	}
	
	return $output;
}

function submit_virtex()
{
	$params = array('virtex_apikey', 'virtex_redirect','virtex_autoredirect');
	foreach($params as $p)
		if ($_POST[$p] != null)
			update_option($p, $_POST[$p]);
	return true;
}

function gateway_virtex($seperator, $sessionid)
{
	require('wp-content/plugins/wp-e-commerce/wpsc-merchants/virtex/virtex_lib.php');
	
	//$wpdb is the database handle,
	//$wpsc_cart is the shopping cart object
	global $wpdb, $wpsc_cart;
	
	//This grabs the purchase log id from the database
	//that refers to the $sessionid
	$purchase_log = $wpdb->get_row(
		"SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
		"` WHERE `sessionid`= ".$sessionid." LIMIT 1"
		,ARRAY_A) ;

	//This grabs the users info using the $purchase_log
	// from the previous SQL query
	$usersql = "SELECT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value,
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`name`,
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM
		`".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN
		`".WPSC_TABLE_SUBMITED_FORM_DATA."` ON
		`".WPSC_TABLE_CHECKOUT_FORMS."`.id =
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id'];
	$userinfo = $wpdb->get_results($usersql, ARRAY_A);
	// convert from awkward format 
	foreach((array)$userinfo as $value) 
		if (strlen($value['value']))
			$ui[$value['unique_name']] = $value['value'];
	$userinfo = $ui;
		
	
	// name
	if (isset($userinfo['billingfirstname']))
	{
		$options['customer_name'] = $userinfo['billingfirstname'];
		if (isset($userinfo['billinglastname']))
			$options['customer_name'] .= ' '.$userinfo['billinglastname'];
	}
	
	//address -- remove newlines
	if (isset($userinfo['billingaddress']))
	{
		$newline = strpos($userinfo['billingaddress'],"\n");
		if ($newline !== FALSE)
		{
			$options['address'] = substr($userinfo['billingaddress'], 0, $newline);
			$options['address2'] = substr($userinfo['billingaddress'], $newline+1);
			$options['address2'] = preg_replace('/\r\n/', ' ', $options['address2'], -1, $count);
		}
		else
			$options['address'] = $userinfo['billingaddress'];
	}
	// state
	if (isset($userinfo['billingstate']))
		$options['province'] = wpsc_get_state_by_id($userinfo['billingstate'], 'code');

	// more user info
	foreach(array('billingemail' => 'email', 'billingcity' => 'city',  'billingcountry' => 'country', 'billingpostcode' => 'postal') as $f => $t)
		if ($userinfo[$f])
			$options[$t] = $userinfo[$f];

	// itemDesc
	if (count($wpsc_cart->cart_items) == 1)
	{
		$item = $wpsc_cart->cart_items[0];
		$options['name'] = $item->product_name;
		if ( $item->quantity > 1 )
			$options['name'] = $item->quantity.'x '.$options['name'];
	}
	else
	{
		foreach($wpsc_cart->cart_items as $item) 
			$quantity += $item->quantity;
		$options['name'] = $quantity.' items';
	}	

	if( get_option( 'permalink_structure' ) != '' ) {
		$separator = "?";
	} else {
		$separator = "&";
	}
	
	//currency
	//$currencyId = get_option( 'currency_type' );
	//$options['currency'] = $wpdb->get_var( $wpdb->prepare( "SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id` = %d LIMIT 1", $currencyId ) );
	//$options['notificationURL'] = get_option('siteurl')."/?virtex_callback=true";
	//pass sessionid along so that it can be used to populate the transaction results page
	$options['return_url'] = get_option('virtex_redirect').$separator."sessionid=".$sessionid;  
	//$options['transactionSpeed'] = get_option('virtex_transaction_speed');	
	$options['apiKey'] = get_option('virtex_apikey');
	$options['posData'] = $sessionid;
	
	
	// truncate if longer than 100 chars
	foreach(array("customer_name", "address", "address2", "city", "province", "postal", "country", "email") as $k)
		$options[$k] = substr($options[$k], 0, 100);
		
	$price = number_format($wpsc_cart->total_price,2);	
	$invoice = virtexCreateInvoice($sessionid, $price, $sessionid, $options);

	if (isset($invoice['error'])) {
		debuglog($invoice);
		// close order
		$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
		$wpdb->query($sql);
		//redirect back to checkout page with errors		
		$_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully, please try again.');
		header("Location: ".get_option('checkout_url'));
	}else{
		$wpsc_cart->empty_cart();
		unset($_SESSION['WpscGatewayErrorMessage']);
        $autoredirect=get_option("autoredirect");
        if (isset($invoice["order_key"]))
        {
            $url="https://www.cavirtex.com/merchant_invoice?merchant_key=".$options['apiKey']."&order_key=".$invoice["order_key"];                        
		    header("Location: ".$url);
            exit();
        } else
        {
            if ($options['return_url']!='')
            header("Location: ".$options['return_url']);
            else
            header("Location: ".get_option('checkout_url'));
            exit();
        }
		
	}

}

function virtex_callback()
{
	
	if(isset($_GET['virtex_callback']))
	{
	
		global $wpdb;
		require('wp-content/plugins/wp-e-commerce/wpsc-merchants/virtex/virtex_lib.php');

		$response = virtexVerifyNotification(get_option('virtex_apikey'));
		
		if (isset($response['error']))
			debuglogVirtEx($response);
		else
		{
			$sessionid = $response['posData'];

			//get buyer email
			$sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`=".$sessionid;
			$purchase_log = $wpdb->get_results( $sql, ARRAY_A );
			
			$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );
			$email = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_log[0]['id'], $email_form_field ) );

			//get cart contents
			$sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=".$purchase_log[0]['id'];
			$cart_contents = $wpdb->get_results($sql, ARRAY_A);
			
			//get currency symbol
			$currency_id = get_option('currency_type');
			$sql = "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`=".$currency_id;
			$currency_data = $wpdb->get_results($sql, ARRAY_A);
			$currency_symbol = $currency_data[0]['symbol'];

			//list products and individual prices in the email
			$message_product = "\r\n\r\nTransaction Details: \r\n\r\n";
			$pnp = 0.0;
			$subtotal = 0.0;
			foreach($cart_contents as $product) {
				$pnp += $product['pnp']; //shipping for each item
				$message_product .= "x" . $product['quantity'] . " " . $product['name'] . " - " . $currency_symbol . $product['price']*$product['quantity'] . "\r\n";
				$subtotal += $product['price']*$product['quantity'];
			}

			//list subtotal
			$subtotal = number_format($subtotal , 2 , '.', ',');
			$message_product .= "\r\n" . "Subtotal: " . $currency_symbol . $subtotal . "\r\n";

			//list total taxes and total shipping costs in the email
			$message_product .= "Taxes: " . $currency_symbol . $purchase_log[0]['wpec_taxes_total'] . "\r\n";
			$message_product .= "Shipping: " . $currency_symbol . ($purchase_log[0]['base_shipping'] + $pnp) . "\r\n\r\n";

		
			//display total price in the email
			$message_product .= "Total Price: " . $currency_symbol . $purchase_log[0]['totalprice'];

			switch($response['status'])
			{
				//For low and medium transaction speeds, the order status is set to "Order Received". The customer receives
				//an initial email stating that the transaction has been paid.
				case 'paid':

					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
					if (is_numeric($sessionid)) {
						$wpdb->query($sql);	
					}

					$message = "Thank you! Your payment has been received, but the transaction has not been confirmed on the bitcoin network. " .
							   "You will receive another email when the transaction has been confirmed.";
					$message .= $message_product;
					wp_mail($email, "Payment Received", $message);

					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
					transaction_results($sessionid, false);	//false because this is just for email notification	
					break;

				//For low and medium transaction speeds, the order status will not change. For high transaction speed, the order
				//status is set to "Order Received" here. For all speeds, an email will be sent stating that the transaction has
				//been confirmed.
				case 'received':

					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
					if (is_numeric($sessionid)) {
						$wpdb->query($sql);	
					}                    				
						
					$message = "Thank you! Your payment has been received, and the transaction has been confirmed on the bitcoin network. " .
							   "You will receive another email when the transaction is complete.";
					$message .= $message_product; 
					wp_mail($email, "Payment Received", $message);

					
					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
					transaction_results($sessionid, false); 
					break;

				//The purchase receipt email is sent upon the invoice status changing to "complete", and the order
				//status is changed to Accepted Payment
				case 'complete':

					$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '3' WHERE `sessionid`=".$sessionid;
					if (is_numeric($sessionid)) {
						$wpdb->query($sql);
					}

					$message = "Your transaction is now complete! Thank you for using Virtex!" ;
				    wp_mail($email, "Transaction Complete", $message);

				    $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `email_sent`= '1' WHERE `sessionid`=".$sessionid;
				    transaction_results($sessionid, false); //false because this is just for email notification	
					break;
			}
		}
	}

}

add_action('init', 'virtex_callback');

?>
