<?php 
class ModelPaymentCavirtex extends Model {
  	public function getMethod($address, $total) {
		$this->language->load('payment/cavirtex');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('cavirtex_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if ($this->config->get('cavirtex_total') > 0 && $this->config->get('cavirtex_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('cavirtex_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}	
		
		$method_data = array();
	
		if ($status) {
      		$method_data = array( 
        		'code'       => 'cavirtex',
        		'title'      => $this->language->get('text_title'),
						'sort_order' => $this->config->get('cavirtex_sort_order')
      		);
    	}
   
    	return $method_data;
  	}

		public function confirm($order_id) {
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($order_id);
			$key = $this->config->get("cavirtex_merchant_key");

			$data = "name=".urlencode("OpenCart Order");
			$data .= "&code=".urlencode($order_id);
			$data .= "&price=".urlencode($order["total"]);
			$data .= "&quantity=1";
			$data .= "&shipping_required=".urlencode($order_id);
			$data .= "&format=json";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://www.cavirtex.com/merchant_purchase/".$key);
			curl_setopt($ch, CURLOPT_POST, "1");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, "1");
			$res = curl_exec($ch);

			$j = json_decode($res, true);
			$order_key = $j["order_key"];
			$btc_address = $j["btc_payment_address"];
			$btc_total = floatval($j["btc_total"]);
			$cad_total = floatval($j["cad_total"]);
			$exchange_rate = floatval($j["exchange_rate"]);

			$this->language->load('payment/cavirtex');
			$comment = $this->language->get('text_payment_instruction') . "\n\n";
			$comment .= $cad_total . "CAD @ " . $exchange_rate . "CAD/BTC = " . $btc_total . "BTC\n\n";
			$comment .= $this->language->get('text_bitcoin_address') . " " . $btc_address;
			$this->model_checkout_order->confirm($order_id, $this->config->get('cavirtex_order_status_id'), $comment, true);

			$this->db->query("UPDATE " . DB_PREFIX . "order SET cavirtex_order_key = '" . $order_key . "' WHERE order_id = " . $order_id);

			return $j;
		}

		public function processIpn($j) {
			$secret_key = $this->config->get('cavirtex_merchant_secret');
			$order_key = $j['order_key'];
			$btc_received = $j['btc_received'];
			$invoice_no = $j['invoice_no'];

			$status_id = $this->config->get('cavirtex_ipn_status_id');
			$this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = '" . $status_id . "' WHERE cavirtex_order_key = '" . $order_key . "'");

			$data = "secret_key=" . urlencode($secret_key);
			$data .= "&order_key=" . urlencode($order_key);
			$data .= "&btc_received=" . urlencode($btc_received);
			$data .= "&invoice_number=" . urlencode($invoice_no);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://www.cavirtex.com/merchant_confirm_ipn");
			curl_setopt($ch, CURLOPT_POST, "1");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, "1");
			$res = curl_exec($ch);
		}
}
?>
