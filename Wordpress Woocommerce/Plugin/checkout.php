<?php
/*
Plugin Name: Virtex Bitcoin Woocommerce plugin
Plugin URI: http://www.cavirtex.com
Description: This plugin adds the Bitcoin payment gateway to your Woocommerce plugin.  Woocommerce is required.
Version: 1.0
Author: 
Author URI: http://www.cavirtex.com
License: 
*/

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
	function vxlog($contents)
	{
		$file = plugin_dir_path(__FILE__).'vxlog.txt';
		file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
		if (is_array($contents))
			file_put_contents($file, var_export($contents, true)."\n", FILE_APPEND);		
		else if (is_object($contents))
			file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
		else
			file_put_contents($file, $contents."\n", FILE_APPEND);
	}

	function declareWooVirtexpay() 
	{
		if ( ! class_exists( 'WC_Payment_Gateways' ) ) 
			return;

		class WC_Virtex extends WC_Payment_Gateway 
		{
		
			public function __construct() 
			{
				$this->id = 'virtex';
				$this->icon = plugin_dir_url(__FILE__).'virtex-logo.png';
				$this->has_fields = false;
			 
				// Load the form fields.
				$this->init_form_fields();
			 
				// Load the settings.
				$this->init_settings();
			 
				// Define user set variables
				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];
			 
				// Actions
				add_action('woocommerce_update_options_payment_gateways_'.$this->id, array(&$this, 'process_admin_options'));
				//add_action('woocommerce_thankyou_cheque', array(&$this, 'thankyou_page'));
			 
				// Customer Emails
				add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 2);
			}
			
			function init_form_fields() 
			{
				$this->form_fields = array(
					'enabled' => array(
						'title' => __( 'Enable/Disable', 'woothemes' ),
						'type' => 'checkbox',
						'label' => __( 'Enable Virtex Payment', 'woothemes' ),
						'default' => 'yes'
					),
					'title' => array(
						'title' => __( 'Title', 'woothemes' ),
						'type' => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
						'default' => __( 'Bitcoins', 'woothemes' )
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woothemes' ),
						'type' => 'textarea',
						'description' => __( 'Message to explain how the customer will be paying for the purchase.', 'woothemes' ),
						'default' => 'You will be redirected to cavirtex.com to complete your purchase.'
					),
					'apiKey' => array(
						'title' => __('API Key', 'woothemes'),
						'type' => 'text',
						'description' => __('Enter the API key you created at  cavirtex.com'),
					)
					
				);
			}
				
			public function admin_options() {
				?>
				<h3><?php _e('Bitcoin Payment', 'woothemes'); ?></h3>
				<p><?php _e('Allows bitcoin payments via cavirtex.com.', 'woothemes'); ?></p>
				<table class="form-table">
				<?php
					// Generate the HTML For the settings form.
					$this->generate_settings_html();
				?>
				</table>
				<?php
			} // End admin_options()
			
			public function email_instructions( $order, $sent_to_admin ) {
				return;
			}

			function payment_fields() {
				if ($this->description) echo wpautop(wptexturize($this->description));
			}
			 
			function thankyou_page() {
				if ($this->description) echo wpautop(wptexturize($this->description));
			}

			function process_payment( $order_id ) {
				require 'vx_lib.php';
				
				global $woocommerce, $wpdb;

				$order = &new WC_Order( $order_id );

				// Mark as on-hold (we're awaiting the coins)
				$order->update_status('on-hold', __('Awaiting payment notification from cavirtex.com', 'woothemes'));
				
				// invoice options
				$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
				
				$notificationURL = get_option('siteurl')."/?virtexpay_callback=1";
				
				$currency = get_woocommerce_currency();
				
				
				$prefix = 'billing_';
				$options = array(
					'apiKey' => $this->settings['apiKey'],					
					'currency' => $currency,
					'redirectURL' => $redirect,
					'notificationURL' => $notificationURL,					
					'buyerName' => $order->{$prefix.first_name}.' '.$order->{$prefix.last_name},
					'buyerAddress1' => $order->{$prefix.address_1},
					'buyerAddress2' => $order->{$prefix.address_2},
					'buyerCity' => $order->{$prefix.city},
					'buyerState' => $order->{$prefix.state},
					'buyerZip' => $order->{$prefix.postcode},
					'buyerCountry' => $order->{$prefix.country},
					'buyerPhone' => $order->billing_phone,
					'buyerEmail' => $order->billing_email,
					);
					
				if (strlen($order->{$prefix.company}))
					$options['buyerName'] = $order->{$prefix.company}.' c/o '.$options['buyerName'];
				
				foreach(array('buyerName', 'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerCountry', 'buyerPhone', 'buyerEmail') as $trunc)
					$options[$trunc] = substr($options[$trunc], 0, 100); // api specifies max 100-char len
                 
				$invoice = vxCreateInvoice($order_id, $order->order_total, $order_id, $options );
				if ($invoice['Status']=="error")
				{
					vxlog($invoice);
					$order->add_order_note(var_export($invoice['Message']));
					$woocommerce->add_error(__('Error creating virtex invoice.  Please try again or try another payment method.'));
				}
				else
				{
					$woocommerce->cart->empty_cart();
				    
					return array(
						'result'    => 'success',
						'redirect'  => $invoice['url'],
					);
				}			 
			}
		}
	}

	include plugin_dir_path(__FILE__).'callback.php';

	function add_virtexpay_gateway( $methods ) {
		$methods[] = 'WC_Virtex'; 
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'add_virtexpay_gateway' );

	add_action('plugins_loaded', 'declareWooVirtexpay', 0);
	
	
}