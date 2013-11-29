<?php

  class virtex {

    var $code, $title, $description, $enabled;

    //Class Constructor
    function virtex () {
      global $order;

      $this->code = 'virtex';
      $this->title = 'Bitcoin';
      $this->description = 'Use cavirtex.com\'s invoice processing server to automatically accept bitcoins.';
      $this->sort_order = MODULE_PAYMENT_VIRTEX_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_VIRTEX_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_VIRTEX_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_VIRTEX_ORDER_STATUS_ID;
        $payment='virtex';
      }
      else if ($payment=='virtex') {
        $payment='';
      }
      if (is_object($order)) {
        $this->update_status();
      }

      $this->email_footer = 'You just paid with bitcoins via virtex.com -- Thanks!';
    }

    // Class Methods
    function update_status () {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_VIRTEX_ZONE > 0) ) {
        $check_flag = false;
        $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . intval(MODULE_PAYMENT_VIRTEX_ZONE) . "' and zone_country_id = '" . intval($order->billing['country']['id']) . "' order by zone_id");
        while ($check = xtc_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          }
          elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }

      // check currency
      $currencies = array_map('trim',explode(",",MODULE_PAYMENT_VIRTEX_CURRENCIES));

      if (array_search($order->info['currency'], $currencies) === false) {
        $this->enabled = false;
      }

      // check that api key is not blank
      if (!MODULE_PAYMENT_VIRTEX_APIKEY OR !strlen(MODULE_PAYMENT_VIRTEX_APIKEY)) {
        print 'no secret '.MODULE_PAYMENT_VIRTEX_APIKEY;
        $this->enabled = false;
      }
    }

    function javascript_validation () {
      return false;
    }

    function selection () {
      return array('id' => $this->code, 'module' => $this->title, 'description' => $this->description);
    }

    function pre_confirmation_check () {
      return false;
    }

    function confirmation () {
      return false;
    }

    function process_button () {
      return false;
    }

    function before_process () {
      return false;
    }

    function after_process () {
		global $insert_id, $order, $bpOptions;
		require_once DIR_FS_CATALOG.'callback/virtex/library/bp_lib.php';


		// itemDesc
		if (count($order->products) == 1)
		{
			$item = $order->products[0];
			$name = $item['name'];
			if ( $item->qty > 1 )
				$name = $item['qty'].'x '.$name;
		}
		else
		{
			foreach($order->products as $item) 
				$quantity += $item['qty'];
			$name = $quantity.' items';
		}
		

		// change order status to value selected by merchant
		xtc_db_query("update ". TABLE_ORDERS. " set orders_status = " . intval(MODULE_PAYMENT_VIRTEX_UNPAID_STATUS_ID) . " where orders_id = ". intval($insert_id));
		$options = array(
		'name' => $name,
		'return_url' => xtc_href_link('account'),
		'shipping_required' => 0,
		'currency' => $order->info['currency'],
		'customer_name' => $order->customer['firstname'].' '.$order->customer['lastname'],
		'email' => $order->customer['email_address'],
		'address' => $order->customer['street_address'],
		'city' => $order->customer['city'],
		// 'province' => $order->customer['state'],
		'postal' => $order->customer['postcode'],
		// 'country' => $order->customer['country'],
		'apiKey' => MODULE_PAYMENT_VIRTEX_APIKEY,
		);
		bpLog($options);
		$decimal_place = (xtc_db_fetch_array(xtc_db_query("SELECT decimal_point FROM " . TABLE_CURRENCIES . " WHERE  code = '" . $order->info['currency'] . "'")));
		$thousands_place = (xtc_db_fetch_array(xtc_db_query("SELECT thousands_point FROM " . TABLE_CURRENCIES . " WHERE code = '" . $order->info['currency'] . "'")));
		$decimal_place = $decimal_place['decimal_point'];
		$thousands_place = $thousands_place['thousands_point'];

		$priceString = preg_replace('/[^0-9'.$decimal_place.']/', '', $order->info['total']);

		if ($decimal_place != '.') {
		$priceString = preg_replace('/['.$decimal_place.']/', '.', $priceString);
		}

		$price = floatval($priceString);

		$invoice = bpCreateInvoice($insert_id, $price, $insert_id, $options);
		bpLog($invoice);
		if (is_array($invoice) && isset($invoice['order_key']))
        {
            $url = $bpOptions['apiURL']."/merchant_invoice?merchant_key=".$invoice['merchant_key']."&order_key=".$invoice['order_key'];   
			xtc_redirect($url);
            die();
        }
		
		
		return false;
    }

    function output_error() { //****   was get_error()  ***
      return false;
    }

    function check () {
      if (!isset($this->_check)) {
        $check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_VIRTEX_STATUS'");
        $this->_check = xtc_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install () {

      $fields = " (configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) ";
      
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_STATUS', 'False', '6', '0', NULL, now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'),' )");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_APIKEY', '', '6', '0', NULL, now(), '', '')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_TRANSACTION_SPEED', 'Low-6 Confirmations', '6', '0', NULL, now(), '', 'xtc_cfg_SELECT_option(array(\'High-0 Confirmations\', \'Medium-1 Confirmations\', \'Low-6 Confirmations\'),')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_UNPAID_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', '6', '0', NULL, now(), 'xtc_get_order_status_name', 'xtc_cfg_pull_down_order_statuses(')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_PAID_STATUS_ID', '2', '6', '0', NULL, now(), 'xtc_get_order_status_name', 'xtc_cfg_pull_down_order_statuses(')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_CURRENCIES', 'BTC, USD, EUR, GBP, AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, HKD, HRK, HUF, IDR, ILS, INR, JPY, KRW, LTL, LVL, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, ZAR', '6', '0', NULL, now(), '', '')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_ZONE', '0', '6', '2', NULL, now(), 'xtc_get_zone_class_title', 'xtc_cfg_pull_down_zone_classes(')");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . $fields . "values ('MODULE_PAYMENT_VIRTEX_SORT_ORDER', '0', '6', '2', NULL, now(), '', '')");

    }

    function remove () {
      xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array(
        'MODULE_PAYMENT_VIRTEX_STATUS',
        'MODULE_PAYMENT_VIRTEX_APIKEY',
        'MODULE_PAYMENT_VIRTEX_TRANSACTION_SPEED',
        'MODULE_PAYMENT_VIRTEX_UNPAID_STATUS_ID',
        'MODULE_PAYMENT_VIRTEX_PAID_STATUS_ID',
        'MODULE_PAYMENT_VIRTEX_SORT_ORDER',
        'MODULE_PAYMENT_VIRTEX_ZONE',
        'MODULE_PAYMENT_VIRTEX_CURRENCIES',
      );
    }
  }
?>