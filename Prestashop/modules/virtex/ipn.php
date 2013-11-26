<?php
	include(dirname(__FILE__).'/../../config/config.inc.php');
	include(dirname(__FILE__).'/../../header.php');
	include(dirname(__FILE__).'/virtex.php');		
	$handle = fopen('php://input','r');
	$jsonInput = fgets($handle);
	vxlog($jsonInput);
	$decoded = json_decode($jsonInput, true);
	fclose($handle);
	$posData = json_decode($decoded['posData']);	
	if ($posData->hash == crypt($posData->cart_id, Configuration::get('virtex_APIKEY')))
	{	
        $virtex = new virtex();		
		
		if (in_array($decoded['status'], array('paid', 'confirmed', 'complete')))
		{
			if (empty(Context::getContext()->link))
				Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent 
			$key = $posData->key;
			$virtex->validateOrder($posData->cart_id, Configuration::get('PS_OS_PAYMENT'), $decoded['price'], $virtex->displayName, null, array(), null, false, $key);
		}
		$virtex->writeDetails($virtex->currentOrder, $posData->cart_id, $decoded['id'], $decoded['status']);
		
	}
	else 
	{
		vxlog('Hash does not match');
		vxlog($posData);
	}
