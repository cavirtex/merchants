<?php
class Bitcoin_VirtEx_PaymentController extends Mage_Core_Controller_Front_Action
{
	public function payAction()
	{
		$model = Mage::getModel("virtex/payment");
		$data = $model->createVirtexInvoice();
		$btc_data = array(
			"time_left" => empty($data["time_left"]) ? NULL : $data["time_left"],
			"exchange_rate" => empty($data["exchange_rate"]) ? NULL : $data["exchange_rate"],
			"exchange_rate_2" => empty($data["exchange_rate"]) ? NULL : $data["exchange_rate"],
			"cad_total" => empty($data["cad_total"]) ? NULL : $data["cad_total"],
			"btc_total" => empty($data["btc_total"]) ? NULL : $data["btc_total"],
			"btc_address" => empty($data["btc_payment_address"]) ? NULL : $data["btc_payment_address"],
			"order_key" => empty($data["order_key"]) ? NULL : $data["order_key"],
		);
		
		$btc_order_data = Mage::getSingleton('core/session')->getBitcoinOrderData();
		if( !empty( $btc_order_data['addspread'] ) && $btc_order_data['addspread'] != (float)100 ) {
			$btc_data['exchange_rate'] = $btc_data['exchange_rate'] * ($btc_order_data['addspread'] / 100);
			$btc_data['cad_total'] = $btc_order_data['display_price'] * $btc_order_data['cadconversionrate'];
		}
		
		Mage::getSingleton('core/session')->setBitcoinData($btc_data);
		$this->loadLayout();
		$this->renderLayout();
	}

	public function ipnAction()
	{
		$data = file_get_contents("php://input");
		$j = json_decode($data, true);
		Mage::getModel('virtex/payment')->confirmIpn($j);
		if( empty($data) ) {
			$this->loadLayout();
			$this->renderLayout();
		} else {
			Mage::log('VirtEx: IPN data received :: '.$data, null, 'virtex.log');
		}
	}
	
	public function ajaxAction()
	{
		$model = Mage::getModel("virtex/payment");
		$order_key = $this->getRequest()->getParam('order_key');
		if( empty( $order_key ) ) {
			//$this->loadLayout();
			//$this->renderLayout();
			exit;
		}
		$checkIpn = $model->checkIpn($order_key);
		echo $checkIpn;
	}

}
