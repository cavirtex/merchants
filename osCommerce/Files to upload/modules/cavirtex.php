<?php
/*
  cavirtex.php, v1.0
  
  Portions Copyright (c) 2003 Zen Cart
*/

function _sendCaVirtExRequest ($post_url, $post_string)
{
	$curl_request = curl_init($post_url);
	
	curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post_string);
	curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
	curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_request, CURLOPT_FORBID_REUSE, 1);
	curl_setopt($curl_request, CURLOPT_FRESH_CONNECT, 1);

	curl_setopt($curl_request, CURLOPT_POST, 1);

	$responseString = curl_exec($curl_request);

	if($responseString == false) {
		$response = curl_error($curl_request);
		echo('CaVirtEx: '."----CURL ERROR---- " . $response);
	} else {
		$response =json_decode($responseString, true);
	}

	curl_close($curl_request);

	return $response;
}

function getProvinceCode($province){
	$PROVINCES =  array(   'AB' => 'Alberta' ,
			'BC' => 'British Columbia' ,
			'MB' => 'Manitoba' ,
			'NB' => 'New Brunswick' ,
			'NL' => 'Newfoundland/Labrador' ,
			'NS' => 'Nova Scotia' ,
			'NT' => 'Northwest Territories' ,
			'NU' => 'Nunavut' ,
			'ON' => 'Ontario' ,
			'PE' => 'Prince Edward Island' ,
			'QC' => 'Quebec' ,
			'SK' => 'Saskatchewan' ,
			'YT' => 'Yukon' );
	
	foreach($PROVINCES as $pk => $pv){
		if($pv == $province) {
			return $pk;
		}
	}
	return "";
}

  class cavirtex {
    var $code, $title, $description, $enabled;

	//TODO figure out what this code does
    function cavirtex() {
      global $order;

      $this->code = 'cavirtex';
      $this->title = MODULE_PAYMENT_CAVIRTEX_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_CAVIRTEX_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_CAVIRTEX_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_CAVIRTEX_STATUS == 'True') ? true : false);
       
      if (is_object($order)) $this->update_status();	
	}

    function update_status() {
      global $order;
      //$this->enabled = true;
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
		$selection=array('id' => $this->code,
		         'module' => $this->title);
		return $selection;
    }

    function pre_confirmation_check() {
			return false;
    }

    function confirmation() {		
		return false;
    }

	

    function process_button() {
    }

    function before_process() {
		
    }

    function after_process() { 
		global $_POST, $_GET, $insert_id, $currencies, $cart;

		$order = new order($insert_id);
		
		//echo(print_r($order,TRUE));
		
		
		$totalsum = $order->info['total'];
		$validChars = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ".");
		$totalsumfixed = "";
		foreach(str_split($totalsum) as $totalsumchar ){
			if(in_array($totalsumchar, $validChars)) $totalsumfixed = $totalsumfixed.$totalsumchar; 
		}
		
		$totalsum = $totalsumfixed;
		//$order->info['currency'] => CAD
		//$totalInPaymentCurrency=$order->info['total'];
		$totalInPaymentCurrency=tep_round($totalsum * $currencies->get_value("CAD"), 2);
		$URL = "https://www.cavirtex.com/merchant_purchase/".MODULE_PAYMENT_CAVIRTEX_MERCHANT_KEY;
			
		$cavpost = array();
	
		
		
		

		if(($order->delivery['country'] != "")) {
			
		
			if(($order->customer['email_address']  != "")) $cavpost['email'] = $order->customer['email_address'];
			if(($order->delivery['firstname']  != "") || ($order->delivery['lastname']  != ""))
				$cavpost['customer_name'] = (($order->delivery['firstname']  != "") ? $order->delivery['firstname'].' '
						: '').(($order->delivery['lastname']  != "") ? $order->delivery['lastname'] : '');
			//some versions just have "name".
			if(($order->delivery['name']  != "") ) $cavpost['customer_name'] = $order->delivery['name'];
			
			if(($order->delivery['street_address']  != "") ) $cavpost['address'] = $order->delivery['street_address'];
			if(($order->delivery['suburb']  != "")) $cavpost['address2'] = $order->delivery['suburb'];
			if(($order->delivery['city']  != "")) $cavpost['city'] = $order->delivery['city'];
			($order->delivery['postcode'] != "") ? $postalNice = str_replace(" ", "", $order->delivery['postcode']) : NULL;
			if(($postalNice != NULL)) $cavpost['postal'] = $postalNice;
		
			$prov = getProvinceCode($order->delivery['state']);
			if($prov != "") $cavpost['province'] = $prov;
			
			$coun = getCountryCode($order->delivery['country']);
			if($coun != "") {
				$cavpost['country'] = $coun;
			} else {
				$coun = getCountryCode($order->delivery['country']['title']);
				if($coun != "") {
					$cavpost['country'] = $coun;
				}				
			}
		} else if ($order->billing['country'] != ""){
				
			if(($order->customer['email_address']  != "")) $cavpost['email'] = $order->customer['email_address'];
			if(($order->billing['firstname']  != "") || ($order->billing['lastname']  != ""))
				$cavpost['customer_name'] = (($order->billing['firstname']  != "") ? $order->billing['firstname'].' '
						: '').(($order->billing['lastname']  != "") ? $order->billing['lastname'] : '');
			//some versions just have "name".
			if(($order->billing['name']  != "") ) $cavpost['customer_name'] = $order->billing['name'];
			
			if(($order->billing['street_address']  != "") ) $cavpost['address'] = $order->billing['street_address'];
			if(($order->billing['suburb']  != "")) $cavpost['address2'] = $order->billing['suburb'];
			if(($order->billing['city']  != "")) $cavpost['city'] = $order->billing['city'];
			($order->billing['postcode'] != "") ? $postalNice = str_replace(" ", "", $order->billing['postcode']) : NULL;
			if(($postalNice != NULL)) $cavpost['postal'] = $postalNice;
				
			$prov = getProvinceCode($order->billing['state']);
			if($prov != "") $cavpost['province'] = $prov;
			
			$coun = getCountryCode($order->billing['country']);
			if($coun != "") {
				$cavpost['country'] = $coun;
			} else {
				$coun = getCountryCode($order->billing['country']['title']);
				if($coun != "") {
					$cavpost['country'] = $coun;
				}
			}
		}	 
			
		//  $order->delivery['city']
		$cavpost['price'] = $totalInPaymentCurrency;
		$cavpost['code'] = "Order #".$insert_id;
		$cavpost['quantity'] = 1;
		$cavpost['name'] = "osCommerce Order.";
		$cavpost['shipping_required'] = 0;
		$cavpost['format'] = "json";
			
			
		$poststring = '';
		foreach ($cavpost AS $key => $val) {
			$poststring .= urlencode($key) . "=" . urlencode($val) . "&";
		}
		$poststring = rtrim($poststring, "& ");
			
		$lgfile = 'callback.php';
		$date = date('Y/m/d H:i:s');
		//file_put_contents($lgfile, $date." Posting.\r\n".$poststring, FILE_APPEND | LOCK_EX);
		//file_put_contents($lgfile, $date." \r\n".print_r($order,TRUE), FILE_APPEND | LOCK_EX);
		//file_put_contents($lgfile, print_r($currencies, TRUE)." \r\n", FILE_APPEND | LOCK_EX);
		//file_put_contents($lgfile, "\r\n".$order->info['total']." .... ".$currencies->get_value("CAD")." $->$ ... $" . $totalsum . " ... total: " . $totalInPaymentCurrency . "\r\n", FILE_APPEND | LOCK_EX);
		
		//echo(print_r($poststring,TRUE));
		$response = _sendCaVirtExRequest($URL, $poststring );
	
		//file_put_contents($lgfile, $date." Got Response.\r\n".print_r($response, TRUE), FILE_APPEND | LOCK_EX);
			
		if (is_string($response)) {
			$invoice = array('error' => $response);
		} else {
			$invoice = $response;
		}
			
		if(isset($invoice['error'])) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message='.$invoice['error'], 'SSL', true, false));
			return false;
		} else{
			if (isset($invoice["order_key"]))
			{
				$order_key = $invoice["order_key"];
				//$cavirtex_invoice_number = $invoice["invoice_number"];
				$url="https://www.cavirtex.com/merchant_invoice?merchant_key=".MODULE_PAYMENT_CAVIRTEX_MERCHANT_KEY."&order_key=".$order_key;
			} else {
				$errormsg = urlencode(print_r($response, TRUE));
				
				$lgfile = 'callback.php';
				$date = date('Y/m/d H:i:s');
				//file_put_contents($lgfile, $date." Error posting.\r\n".print_r($response, TRUE), FILE_APPEND | LOCK_EX);
				
				tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=No Order Key. '.$errormsg, 'SSL', true, false));
				return false;
			}
		}
		
		tep_db_query("update " . TABLE_ORDERS . " set cavirtex_order_key = '".$order_key."' where orders_id = ".$insert_id );
		
		$cart->reset(true);
		
		tep_redirect($url);
		
		return false;
		
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CAVIRTEX_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() { 
	  if (defined('MODULE_PAYMENT_CAVIRTEX_STATUS')) {
	  	$messageStack->add_session('CaVirtEx module already installed.', 'error');
	  	tep_redirect(tep_href_link(FILENAME_MODULES, 'set=payment&module=cavirtex', 'NONSSL'));
	  	return 'failed';
	  }
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable CaVirtEx Bitcoin Module', 'MODULE_PAYMENT_CAVIRTEX_STATUS', 'True', 'Do you want to accept Bitcoin payments?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('PCaVirtEx Merchant Key', 'MODULE_PAYMENT_CAVIRTEX_MERCHANT_KEY', 'merchant_key', 'Your Merchant Key, supplied by CaVirtEx', '6', '0', now())");
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CaVirtEx Merchant Secret Key', 'MODULE_PAYMENT_CAVIRTEX_MERCHANT_SECRET_KEY', 'secret_key', 'Your Secret Key, supplied by CaVirtEx', '6', '1', now())");
  	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status (unpaid)', 'MODULE_PAYMENT_CAVIRTEX_STATUS_ID', '0', 'Set the status of orders made with this payment module (but no payment confirmed) to this value', '6', '2', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
  	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status (paid)', 'MODULE_PAYMENT_CAVIRTEX_CONFIRMED_STATUS_ID', '0', 'Set the status of orders made with this payment module (when payment is confirmed) to this value', '6', '2', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
  	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_CAVIRTEX_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '3', now())");
	  // Check to see if the Paystation feilds are in the TABLE_ORDERS table, if not then add them.
	  // This stores the results from Paystation with the orders, which you could access from elsewhere
	  // if required (this module does nothing more with them other than store the valuse in them).
	  
	  $check_ok_query = tep_db_query("show columns from " . TABLE_ORDERS . " LIKE 'cavirtex_order_key'");
	  if (tep_db_num_rows($check_ok_query) < 1) {
		  tep_db_query("alter table " . TABLE_ORDERS . " add column cavirtex_order_key varchar(64) after payment_method");
	  }	  
   }

    function remove() {
      	tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
      	//keep extra column just in case 
    }

    function keys() {
      return array('MODULE_PAYMENT_CAVIRTEX_STATUS', 'MODULE_PAYMENT_CAVIRTEX_MERCHANT_KEY', 'MODULE_PAYMENT_CAVIRTEX_MERCHANT_SECRET_KEY', 'MODULE_PAYMENT_CAVIRTEX_STATUS_ID',  'MODULE_PAYMENT_CAVIRTEX_CONFIRMED_STATUS_ID', 'MODULE_PAYMENT_CAVIRTEX_SORT_ORDER');
    }
  }
  
  
  function getCountryCode($country){
  	
  	$COUNTRYCODES = array(
  			'AF' => 'Afghanistan' ,
  			'AX' => 'Aland Islands' ,
  			'AL' => 'Albania' ,
  			'DZ' => 'Algeria' ,
  			'AS' => 'American Samoa' ,
  			'AD' => 'Andorra' ,
  			'AO' => 'Angola' ,
  			'AI' => 'Anguilla' ,
  			'AQ' => 'Antarctica' ,
  			'AG' => 'Antigua and Barbuda' ,
  			'AR' => 'Argentina' ,
  			'AM' => 'Armenia' ,
  			'AW' => 'Aruba' ,
  			'AU' => 'Australia' ,
  			'AT' => 'Austria' ,
  			'AZ' => 'Azerbaijan' ,
  			'BS' => 'Bahamas the' ,
  			'BH' => 'Bahrain' ,
  			'BD' => 'Bangladesh' ,
  			'BB' => 'Barbados' ,
  			'BY' => 'Belarus' ,
  			'BE' => 'Belgium' ,
  			'BZ' => 'Belize' ,
  			'BJ' => 'Benin' ,
  			'BM' => 'Bermuda' ,
  			'BT' => 'Bhutan' ,
  			'BO' => 'Bolivia' ,
  			'BA' => 'Bosnia and Herzegovina' ,
  			'BW' => 'Botswana' ,
  			'BV' => 'Bouvet Island (Bouvetoya)' ,
  			'BR' => 'Brazil' ,
  			'IO' => 'British Indian Ocean Territory (Chagos Archipelago)' ,
  			'VG' => 'British Virgin Islands' ,
  			'BN' => 'Brunei Darussalam' ,
  			'BG' => 'Bulgaria' ,
  			'BF' => 'Burkina Faso' ,
  			'BI' => 'Burundi' ,
  			'KH' => 'Cambodia' ,
  			'CM' => 'Cameroon' ,
  			'CA' => 'Canada' ,
  			'CV' => 'Cape Verde' ,
  			'KY' => 'Cayman Islands' ,
  			'CF' => 'Central African Republic' ,
  			'TD' => 'Chad' ,
  			'CL' => 'Chile' ,
  			'CN' => 'China' ,
  			'CX' => 'Christmas Island' ,
  			'CC' => 'Cocos (Keeling) Islands' ,
  			'CO' => 'Colombia' ,
  			'KM' => 'Comoros the' ,
  			'CD' => 'Congo' ,
  			'CG' => 'Congo the' ,
  			'CK' => 'Cook Islands' ,
  			'CR' => 'Costa Rica' ,
  			'CI' => 'Cote d\'Ivoire' ,
  			'HR' => 'Croatia' ,
  			'CU' => 'Cuba' ,
  			'CY' => 'Cyprus' ,
  			'CZ' => 'Czech Republic' ,
  			'DK' => 'Denmark' ,
  			'DJ' => 'Djibouti' ,
  			'DM' => 'Dominica' ,
  			'DO' => 'Dominican Republic' ,
  			'EC' => 'Ecuador' ,
  			'EG' => 'Egypt' ,
  			'SV' => 'El Salvador' ,
  			'GQ' => 'Equatorial Guinea' ,
  			'ER' => 'Eritrea' ,
  			'EE' => 'Estonia' ,
  			'ET' => 'Ethiopia' ,
  			'FO' => 'Faroe Islands' ,
  			'FK' => 'Falkland Islands (Malvinas)' ,
  			'FJ' => 'Fiji the Fiji Islands' ,
  			'FI' => 'Finland' ,
  			'FR' => 'France, French Republic' ,
  			'GF' => 'French Guiana' ,
  			'PF' => 'French Polynesia' ,
  			'TF' => 'French Southern Territories' ,
  			'GA' => 'Gabon' ,
  			'GM' => 'Gambia the' ,
  			'GE' => 'Georgia' ,
  			'DE' => 'Germany' ,
  			'GH' => 'Ghana' ,
  			'GI' => 'Gibraltar' ,
  			'GR' => 'Greece' ,
  			'GL' => 'Greenland' ,
  			'GD' => 'Grenada' ,
  			'GP' => 'Guadeloupe' ,
  			'GU' => 'Guam' ,
  			'GT' => 'Guatemala' ,
  			'GG' => 'Guernsey' ,
  			'GN' => 'Guinea' ,
  			'GW' => 'Guinea-Bissau' ,
  			'GY' => 'Guyana' ,
  			'HT' => 'Haiti' ,
  			'HM' => 'Heard Island and McDonald Islands' ,
  			'VA' => 'Holy See (Vatican City State)' ,
  			'HN' => 'Honduras' ,
  			'HK' => 'Hong Kong' ,
  			'HU' => 'Hungary' ,
  			'IS' => 'Iceland' ,
  			'IN' => 'India' ,
  			'ID' => 'Indonesia' ,
  			'IR' => 'Iran' ,
  			'IQ' => 'Iraq' ,
  			'IE' => 'Ireland' ,
  			'IM' => 'Isle of Man' ,
  			'IL' => 'Israel' ,
  			'IT' => 'Italy' ,
  			'JM' => 'Jamaica' ,
  			'JP' => 'Japan' ,
  			'JE' => 'Jersey' ,
  			'JO' => 'Jordan' ,
  			'KZ' => 'Kazakhstan' ,
  			'KE' => 'Kenya' ,
  			'KI' => 'Kiribati' ,
  			'KP' => 'Korea' ,
  			'KR' => 'Korea' ,
  			'KW' => 'Kuwait' ,
  			'KG' => 'Kyrgyz Republic' ,
  			'LA' => 'Lao' ,
  			'LV' => 'Latvia' ,
  			'LB' => 'Lebanon' ,
  			'LS' => 'Lesotho' ,
  			'LR' => 'Liberia' ,
  			'LY' => 'Libyan Arab Jamahiriya' ,
  			'LI' => 'Liechtenstein' ,
  			'LT' => 'Lithuania' ,
  			'LU' => 'Luxembourg' ,
  			'MO' => 'Macao' ,
  			'MK' => 'Macedonia' ,
  			'MG' => 'Madagascar' ,
  			'MW' => 'Malawi' ,
  			'MY' => 'Malaysia' ,
  			'MV' => 'Maldives' ,
  			'ML' => 'Mali' ,
  			'MT' => 'Malta' ,
  			'MH' => 'Marshall Islands' ,
  			'MQ' => 'Martinique' ,
  			'MR' => 'Mauritania' ,
  			'MU' => 'Mauritius' ,
  			'YT' => 'Mayotte' ,
  			'MX' => 'Mexico' ,
  			'FM' => 'Micronesia' ,
  			'MD' => 'Moldova' ,
  			'MC' => 'Monaco' ,
  			'MN' => 'Mongolia' ,
  			'ME' => 'Montenegro' ,
  			'MS' => 'Montserrat' ,
  			'MA' => 'Morocco' ,
  			'MZ' => 'Mozambique' ,
  			'MM' => 'Myanmar' ,
  			'NA' => 'Namibia' ,
  			'NR' => 'Nauru' ,
  			'NP' => 'Nepal' ,
  			'AN' => 'Netherlands Antilles' ,
  			'NL' => 'Netherlands the' ,
  			'NC' => 'New Caledonia' ,
  			'NZ' => 'New Zealand' ,
  			'NI' => 'Nicaragua' ,
  			'NE' => 'Niger' ,
  			'NG' => 'Nigeria' ,
  			'NU' => 'Niue' ,
  			'NF' => 'Norfolk Island' ,
  			'MP' => 'Northern Mariana Islands' ,
  			'NO' => 'Norway' ,
  			'OM' => 'Oman' ,
  			'PK' => 'Pakistan' ,
  			'PW' => 'Palau' ,
  			'PS' => 'Palestinian Territory' ,
  			'PA' => 'Panama' ,
  			'PG' => 'Papua New Guinea' ,
  			'PY' => 'Paraguay' ,
  			'PE' => 'Peru' ,
  			'PH' => 'Philippines' ,
  			'PN' => 'Pitcairn Islands' ,
  			'PL' => 'Poland' ,
  			'PT' => 'Portugal, Portuguese Republic' ,
  			'PR' => 'Puerto Rico' ,
  			'QA' => 'Qatar' ,
  			'RE' => 'Reunion' ,
  			'RO' => 'Romania' ,
  			'RU' => 'Russian Federation' ,
  			'RW' => 'Rwanda' ,
  			'BL' => 'Saint Barthelemy' ,
  			'SH' => 'Saint Helena' ,
  			'KN' => 'Saint Kitts and Nevis' ,
  			'LC' => 'Saint Lucia' ,
  			'MF' => 'Saint Martin' ,
  			'PM' => 'Saint Pierre and Miquelon' ,
  			'VC' => 'Saint Vincent and the Grenadines' ,
  			'WS' => 'Samoa' ,
  			'SM' => 'San Marino' ,
  			'ST' => 'Sao Tome and Principe' ,
  			'SA' => 'Saudi Arabia' ,
  			'SN' => 'Senegal' ,
  			'RS' => 'Serbia' ,
  			'SC' => 'Seychelles' ,
  			'SL' => 'Sierra Leone' ,
  			'SG' => 'Singapore' ,
  			'SK' => 'Slovakia (Slovak Republic)' ,
  			'SI' => 'Slovenia' ,
  			'SB' => 'Solomon Islands' ,
  			'SO' => 'Somalia, Somali Republic' ,
  			'ZA' => 'South Africa' ,
  			'GS' => 'South Georgia and the South Sandwich Islands' ,
  			'ES' => 'Spain' ,
  			'LK' => 'Sri Lanka' ,
  			'SD' => 'Sudan' ,
  			'SR' => 'Suriname' ,
  			'SJ' => 'Svalbard & Jan Mayen Islands' ,
  			'SZ' => 'Swaziland' ,
  			'SE' => 'Sweden' ,
  			'CH' => 'Switzerland, Swiss Confederation' ,
  			'SY' => 'Syrian Arab Republic' ,
  			'TW' => 'Taiwan' ,
  			'TJ' => 'Tajikistan' ,
  			'TZ' => 'Tanzania' ,
  			'TH' => 'Thailand' ,
  			'TL' => 'Timor-Leste' ,
  			'TG' => 'Togo' ,
  			'TK' => 'Tokelau' ,
  			'TO' => 'Tonga' ,
  			'TT' => 'Trinidad and Tobago' ,
  			'TN' => 'Tunisia' ,
  			'TR' => 'Turkey' ,
  			'TM' => 'Turkmenistan' ,
  			'TC' => 'Turks and Caicos Islands' ,
  			'TV' => 'Tuvalu' ,
  			'UG' => 'Uganda' ,
  			'UA' => 'Ukraine' ,
  			'AE' => 'United Arab Emirates' ,
  			'GB' => 'United Kingdom' ,
  			'US' => 'United States of America' ,
  			'UM' => 'United States Minor Outlying Islands' ,
  			'VI' => 'United States Virgin Islands' ,
  			'UY' => 'Uruguay, Eastern Republic of' ,
  			'UZ' => 'Uzbekistan' ,
  			'VU' => 'Vanuatu' ,
  			'VE' => 'Venezuela' ,
  			'VN' => 'Vietnam' ,
  			'WF' => 'Wallis and Futuna' ,
  			'EH' => 'Western Sahara' ,
  			'YE' => 'Yemen' ,
  			'ZM' => 'Zambia' ,
  			'ZW' => 'Zimbabwe' );
  	 
  	foreach($COUNTRYCODES as $ck => $cv){
  		if($cv == $country) {
  			return $ck;
  		}
  	}
  	return "";
  }
?>