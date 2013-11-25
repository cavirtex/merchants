<?php 
class ControllerPaymentCavirtex extends Controller {
	private $error = array(); 

	public function index() {
		$this->language->load('payment/cavirtex');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('cavirtex', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		
		$this->data['entry_merchant_key'] = $this->language->get('entry_merchant_key');
		$this->data['entry_merchant_secret'] = $this->language->get('entry_merchant_secret');
		$this->data['entry_total'] = $this->language->get('entry_total');	
		$this->data['entry_order_status'] = $this->language->get('entry_order_status');		
		$this->data['entry_ipn_status'] = $this->language->get('entry_ipn_status');		
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
		
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		$this->load->model('localisation/language');
		
		$languages = $this->model_localisation_language->getLanguages();
		
		
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/cavirtex', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
				
		$this->data['action'] = $this->url->link('payment/cavirtex', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['cavirtex_merchant_key'])) {
			$this->data['cavirtex_merchant_key'] = $this->request->post['cavirtex_merchant_key'];
		} else {
			$this->data['cavirtex_merchant_key'] = $this->config->get('cavirtex_merchant_key');
		}

		if (isset($this->request->post['cavirtex_merchant_secret'])) {
			$this->data['cavirtex_merchant_secret'] = $this->request->post['cavirtex_merchant_secret'];
		} else {
			$this->data['cavirtex_merchant_secret'] = $this->config->get('cavirtex_merchant_secret');
		}

		if (isset($this->request->post['cavirtex_total'])) {
			$this->data['cavirtex_total'] = $this->request->post['cavirtex_total'];
		} else {
			$this->data['cavirtex_total'] = $this->config->get('cavirtex_total'); 
		} 
				
		if (isset($this->request->post['cavirtex_order_status_id'])) {
			$this->data['cavirtex_order_status_id'] = $this->request->post['cavirtex_order_status_id'];
		} else {
			$this->data['cavirtex_order_status_id'] = $this->config->get('cavirtex_order_status_id'); 
		} 

		if (isset($this->request->post['cavirtex_ipn_status_id'])) {
			$this->data['cavirtex_ipn_status_id'] = $this->request->post['cavirtex_ipn_status_id'];
		} else {
			$this->data['cavirtex_ipn_status_id'] = $this->config->get('cavirtex_ipn_status_id'); 
		} 
		
		$this->load->model('localisation/order_status');
		
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['cavirtex_geo_zone_id'])) {
			$this->data['cavirtex_geo_zone_id'] = $this->request->post['cavirtex_geo_zone_id'];
		} else {
			$this->data['cavirtex_geo_zone_id'] = $this->config->get('cavirtex_geo_zone_id'); 
		} 
		
		$this->load->model('localisation/geo_zone');
										
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['cavirtex_status'])) {
			$this->data['cavirtex_status'] = $this->request->post['cavirtex_status'];
		} else {
			$this->data['cavirtex_status'] = $this->config->get('cavirtex_status');
		}
		
		if (isset($this->request->post['cavirtex_sort_order'])) {
			$this->data['cavirtex_sort_order'] = $this->request->post['cavirtex_sort_order'];
		} else {
			$this->data['cavirtex_sort_order'] = $this->config->get('cavirtex_sort_order');
		}
		

		$this->template = 'payment/cavirtex.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/cavirtex')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// TODO Validate config data

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

	public function install() {
		if (!$this->user->hasPermission('modify', 'payment/cavirtex')) {
			$this->redirect($this->url->link('common/home'));
		} else {
			// Install
			$this->load->model('payment/cavirtex');
			$this->model_payment_cavirtex->install();
			echo "Installation was successful!";
		}
	}

	public function uninstall() {
		if (!$this->user->hasPermission('modify', 'payment/cavirtex')) {
			$this->redirect($this->url->link('common/home'));
		} else {
			// Uninstall
			$this->load->model('payment/cavirtex');
			$this->model_payment_cavirtex->uninstall();
		}
	}
}
?>
