<?php


class Bitcoin_VirtEx_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'virtex';
	protected $_isInitializeNeeded = TRUE;
	protected $_canUseCheckout = TRUE;
	protected $_canUseInternal = FALSE;
	protected $_canUseForMultishipping = FALSE;

	protected $_order;
	protected $_merchantKey;
	protected $_merchantSecret;

	public function canUseForCurrency($currencyCode)
	{
		return (strtoupper($currencyCode) == "CAD");
	}

	public function getOrderPlaceRedirectUrl()
	{
			return Mage::getUrl('virtex/payment/pay');
	}

	public function initialize($paymentAction, $stateObject)
	{
		$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$stateObject->setState($state);
		$stateObject->setStatus('pending_payment');
		$stateObject->setIsNotified(FALSE);
	}

	public function getOrder()
	{
		if (!$this->_order) {
			$this->_order = $this->getInfoInstance()->getOrder();
		}
		return $this->_order;
	}

	public function getMerchantKey()
	{
		if(!$this->_merchantKey) {
			$this->_merchantKey = Mage::getStoreConfig('payment/virtex/merchantkey');
		}
		return $this->_merchantKey;
	}

	public function getMerchantSecret()
	{
		if(!$this->_merchantSecret) {
			$this->_merchantSecret = Mage::getStoreConfig('payment/virtex/merchantsecret');
		}
		return $this->_merchantSecret;
	}

	public function requestBitcoinAddress()
	{
		$httpClient = Mage_HTTP_Client::getInstance();
		$checkout = Mage::getSingleton('checkout/session');
		$orderIncrementId = $checkout->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		$params = array(
			"shipping_required" => "0",
			"format" => "json",
			"name" => "magento_cart_total",
			"price" => $order->getBaseTotalDue(),
			"code" => $order->getRealOrderId()
		);
		$httpClient->post("https://www.cavirtex.com/merchant_purchase/".$this->getMerchantKey(), $params);
		$body = $httpClient->getBody();
		$j = json_decode($body, true);
		return $j;
	}

	public function confirmIpn($data)
	{
		$httpClient = Mage_HTTP_Client::getInstance();
		$params = array(
			"secret_key" => $this->getMerchantSecret(),
			"order_key" => $data["order_key"],
			"btc_received" => $data["btc_received"],
			"invoice_number" => $data["invoice_number"]
		);
		$httpClient->post("https://www.cavirtex.com/merchant_confirm_ipn", $params);
	}
}
