<?php

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
	
	function virtexpay_callback()
	{				
        global $virtexOptions;
		if(isset($_GET['virtexpay_callback']))
		{
			vxlog(file_get_contents("php://input"));
			
			ini_set('error_log', plugin_dir_path(__FILE__).'vxlog.txt');
			ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_NOTICE);
			
			global $woocommerce;
			
			require(plugin_dir_path(__FILE__).'vx_lib.php');
			
			$gateways = $woocommerce->payment_gateways->payment_gateways();
			if (!isset($gateways['virtexpay']))
			{
				vxlog('virtexpay plugin not enabled in woocommerce');
				return;
			}
			$vx = $gateways['virtexpay'];
			$response = vxVerifyNotification( $vx->settings['apiKey'] );

			if (isset($response['error']))
				vxlog($response);
			else
			{
				$orderId = $response['posData'];
				$order = new WC_Order( $orderId );

				switch($response['status'])
				{
					case 'paid':
						break;
					case 'confirmed':
					case 'complete':
						
						if ( in_array($order->status, array('on-hold', 'pending', 'failed','not paid' ) ) )
						{
							$order->payment_complete();									
                            if (isset($response["order_key"]))
                            {
                                $url="https://www.cavirtex.com/merchant_invoice?merchant_key=".$virtexOptions['apiKey']."&order_key=".$response["order_key"];                        
                                header("Location: ".$url);
                                exit();
                            }
						}
						
						break;
				}
			}
		}
	}           	
	
	add_action('init', 'virtexpay_callback');
	
}