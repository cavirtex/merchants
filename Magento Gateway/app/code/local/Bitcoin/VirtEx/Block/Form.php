<?php

class Bitcoin_VirtEx_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        $this->setTemplate('virtex/form.phtml');
        return parent::_construct();
    }
	
	public function getFormDescription()
	{
		$return = array();
		$currencyCode = Mage::app()->getBaseCurrencyCode();
		if( strtoupper($currencyCode) !== 'CAD' ) {
			$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
			$rates = Mage::getModel('directory/currency')->getCurrencyRates($currencyCode, array_values($allowedCurrencies));
			$return[] = sprintf( __('Please note, %s will be converted automatically to CAD at an exchange rate of %s'), $currencyCode, (float)$rates['CAD'] );
		}

		if( !empty( $return ) ) return $return;
		else return false;
	}
	
}
