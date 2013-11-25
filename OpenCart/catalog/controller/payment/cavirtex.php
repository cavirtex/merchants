<?php
class ControllerPaymentCavirtex extends Controller {
	protected function index() {
		$this->language->load('payment/cavirtex');
		
		$this->data['text_instruction'] = $this->language->get('text_instruction');
		$this->data['text_description'] = $this->language->get('text_description');
		$this->data['text_payment'] = $this->language->get('text_payment');

		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['continue'] = $this->url->link('payment/cavirtex/success');
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/cavirtex_instructions.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/cavirtex_instructions.tpl';
		} else {
			$this->template = 'default/template/payment/cavirtex_instructions.tpl';
		}	
		
		$this->render(); 
	}
	
	public function confirm() {
		$this->load->model('payment/cavirtex');
		$j = $this->model_payment_cavirtex->confirm($this->session->data['order_id']);
		
		$order_key = $j["order_key"];
		$btc_address = $j["btc_payment_address"];
		$btc_total = floatval($j["btc_total"]);
		$cad_total = floatval($j["cad_total"]);
		$exchange_rate = floatval($j["exchange_rate"]);

		$this->language->load('payment/cavirtex');
		$instructions = $this->language->get('text_payment_instruction') . "\n\n";
		$instructions .= $cad_total . "CAD @ " . $exchange_rate . "CAD/BTC = " . $btc_total . "BTC\n\n";
		$instructions .= $this->language->get('text_bitcoin_address') . " " . $btc_address;
		$this->session->data['cavirtex_instructions'] = $instructions;
	}

	public function success() {
		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);	
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
		}	
									   
		$this->language->load('checkout/success');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->data['breadcrumbs'] = array(); 

      	$this->data['breadcrumbs'][] = array(
        	'href'      => $this->url->link('common/home'),
        	'text'      => $this->language->get('text_home'),
        	'separator' => false
      	); 
		
      	$this->data['breadcrumbs'][] = array(
        	'href'      => $this->url->link('checkout/cart'),
        	'text'      => $this->language->get('text_basket'),
        	'separator' => $this->language->get('text_separator')
      	);
				
		$this->data['breadcrumbs'][] = array(
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'text'      => $this->language->get('text_checkout'),
			'separator' => $this->language->get('text_separator')
		);	
					
      	$this->data['breadcrumbs'][] = array(
        	'href'      => $this->url->link('checkout/success'),
        	'text'      => $this->language->get('text_success'),
        	'separator' => $this->language->get('text_separator')
      	);

		$this->data['heading_title'] = $this->language->get('heading_title');
		
		if ($this->customer->isLogged()) {
    		$this->data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
		} else {
    		$this->data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$this->language->load('payment/cavirtex');
		$instructions = $this->session->data['cavirtex_instructions'];
		$this->data['text_message'] .= "<h2>" . $this->language->get('text_instruction') . "</h2><p>" . nl2br($instructions) . "</p>";
		
    	$this->data['button_continue'] = $this->language->get('button_continue');

    	$this->data['continue'] = $this->url->link('common/home');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/common/success.tpl';
		} else {
			$this->template = 'default/template/common/success.tpl';
		}
		
		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'			
		);
				
		$this->response->setOutput($this->render());
	}

	public function ipn()
	{
		$data = file_get_contents('php://input');
		$j = json_decode($data, true);

		$this->load->model('payment/cavirtex');
		$this->model_payment_cavirtex->processIpn($j);
	}
}
?>
