<?php

class Bitcoin_VirtEx_PaymentController extends Mage_Core_Controller_Front_Action
{
	public function payAction()
	{
		$model = Mage::getModel("virtex/payment");
		$data = $model->requestBitcoinAddress();
		
		$btc_data = array(
			"exchange_rate" => $data["exchange_rate"],
			"cad_total" => $data["cad_total"],
			"btc_total" => $data["btc_total"],
			"btc_address" => $data["btc_payment_address"]
		);
		Mage::getSingleton('core/session')->setBitcoinData($btc_data);
		$this->loadLayout();
		$this->renderLayout();
	}

	public function ipnAction()
	{
		$data = file_get_contents("php://input");
		$j = json_decode($data, true);
		Mage::getModel('virtex/payment')->confirmIpn($j);
	}
}
