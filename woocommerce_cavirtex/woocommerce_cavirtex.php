<?php
/**
 * Plugin Name: Bitcoin Payment Gateway for WooCommerce
 * Description: A WooCommerce payment gateway that allows customers to pay using Bitcoin via the Canadian Virtual Exchange (CaVirtEx).
 * Version: 1.0
 * Author: mismith
 * Author URI: http://mismith.info/
 * License: GPL2
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

// initialize custom payment gateway on plugins_loaded hook
function init_cavirtex_payment_gateway(){
	
	class WC_Gateway_Cavirtex extends WC_Payment_Gateway {
	
	    /**
	     * Constructor for the gateway.
	     */
		public function __construct() {
			$this->id                 = 'cavirtex';
			$this->icon               = apply_filters( 'woocommerce_' . $this->id . '_icon', plugins_url( 'assets/bitcoin-logo.png', __FILE__ ) );
			$this->has_fields         = FALSE;
			$this->method_title       = __( 'Bitcoin', 'woocommerce' );
			$this->method_description = __( 'Pay using Bitcoin via the <a href="https://www.cavirtex.com/" target="_blank">Canadian Virtual Exchange</a> (CaVirtEx).', 'woocommerce' );
	
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			// Define user set variables
			$this->enabled         = $this->is_valid_for_use();
			$this->title           = $this->get_option( 'title' );
			$this->description     = $this->get_option( 'description' );
			$this->bill_as         = $this->get_option( 'bill_as' );
			$this->merchant_key    = $this->get_option( 'merchant_key' );
			$this->merchant_secret = $this->get_option( 'merchant_secret' );
			//$this->ipn_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Cavirtex', home_url( '/' ) ) );
	
			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'process_ipn' ) );
	    }
	
	
	    /**
	     * Check if this gateway is enabled and available in the user's country
	     */
	    public function is_valid_for_use() {
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_cavirtex_supported_currencies', array( 'CAD', 'BTC' ) ) ) ) return FALSE;
	
			return TRUE;
	    }
	
	
	    /**
	     * Initialise Gateway Settings Form Fields
	     */
	    public function init_form_fields() {
	
	    	$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable ' . $this->method_title, 'woocommerce' ),
					'default'     => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( $this->method_title, 'woocommerce' ),
					'desc_tip'    => TRUE,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( $this->method_description, 'woocommerce' ),
					'desc_tip'    => TRUE,
				),
				'bill_as' => array(
					'title'       => __( 'Bill As', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This is what the customer will see their order listed as on the CaVirtEx invoice.', 'woocommerce' ),
					'default'     => __( 'WooCommerce Order', 'woocommerce' ),
					'desc_tip'    => TRUE,
				),
				'merchant_key' => array(
					'title'       => __( 'CaVirtEx Merchant Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Available on your <a href="https://www.cavirtex.com/merchant_information" target="_blank">CaVirtEx Merchant Profile page</a>.', 'woocommerce' ),
					'default'     => '',
				),
				'merchant_secret' => array(
					'title'       => __( 'CaVirtEx Merchant Secret Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Available on your <a href="https://www.cavirtex.com/merchant_information" target="_blank">CaVirtEx Merchant Profile page</a>.', 'woocommerce' ),
					'default'     => '',
				),
			);
	
	    }
	    
	
		/**
		 * Get cavirtex params for passing to Merchant API
		 */
		public function get_cavirtex_params( WC_Order $order ) {
			$params = array(
				'code'     => $order->id,
				'name'     => $this->bill_as,
				'price'    => $order->get_total() - $order->get_shipping() /* - $order->get_total_tax() */,
				'quantity' => 1,
				
				'email'		    => $order->billing_email,
				'customer_name' => $order->billing_first_name . ' ' . $order->billing_last_name,
				'address'	    => $order->billing_address_1,
				'address2'	    => $order->billing_address_2,
				'city'		    => $order->billing_city,
				'province'      => $order->billing_state,
				'postal'	    => str_replace( ' ', '', $order->billing_postcode ),
				'country'	    => $order->billing_country,
				
				//'tax'               => $order->get_total_tax(),
				'shipping'          => $order->get_shipping(),
				'shipping_required' => $order->get_shipping() > 0 ? 1 : 0,
				'return_url'        => get_permalink( woocommerce_get_page_id( 'myaccount' ) ),
				'cancel_url'        => $order->get_cancel_order_url(),
				
				'format' => 'json',
			);
			
			return $params;
		}
		
	
	    /**
	     * Process the payment and return the result
	     */
		public function process_payment( $order_id ) {
			global $woocommerce;
			
			try {
				
				// fetch the order and generate filtered/formatted cavirtex params
				$order  = new WC_Order( $order_id );
				$params = $this->get_cavirtex_params( $order );
				
				// send payment data to cavirtex
				require_once( plugin_dir_path( __FILE__ ) . 'cavirtex_merchant_api.php' );
				$cavirtex = new Cavirtex_Merchant_Api( $this->merchant_key, $this->merchant_secret );
				$purchase = $cavirtex->merchant_purchase( $params );
				
				// throw and error if cavirtex returns one
				if( $purchase['Status'] == 'error' ){
					throw new Exception( current( current( $purchase['ErrorList'][0] ) ) );
				}
				
				// generate invoice url
				$invoice_url = $cavirtex->merchant_invoice( $purchase['order_key'], TRUE );
				
				// store cavirtex order key as post metadata
				update_post_meta( $order_id, 'cavirtex_order_key', $purchase['order_key'] );
				
				// clear cart
				$woocommerce->cart->empty_cart();
				
				// redirect to invoice
				return array(
					'result'   => 'success',
					'redirect' => $invoice_url,
				);
				
			}catch ( Exception $e ){
				return $this->handle_error( $e->getMessage() );
			}
	
		}
		
		
		/**
		 * Check for IPN Response
		 */
		public function process_ipn() {
			
			try {
				
				// setup api library
				require_once( plugin_dir_path( __FILE__ ) . '/cavirtex_merchant_api.php' );
				$cavirtex = new Cavirtex_Merchant_Api( $this->merchant_key, $this->merchant_secret );
				
				// parse IPN data
				$params = $cavirtex->process_ipn();
				if( ! $params ) throw new Exception( 'Could not parse IPN data.' );
				
				// check that the data is correct
				$valid = $cavirtex->merchant_confirm_ipn( $params, TRUE );
				if( ! $valid ) throw new Exception( 'Invalid IPN data.' );
				
				// has the invoice been paid in full? if yes, success; if not, continue waiting
				$paid_in_full = $cavirtex->merchant_invoice_balance_check( $params['order_key'], TRUE );
				if( $paid_in_full ) return $this->payment_successful( $params['order_key'] );
				
			} catch( Exception $e ){
				return $this->handle_error( $e->getMessage() );
			}
			
		}
	
	
		/**
		 * Successful Payment!
		 */
		public function payment_successful( $cavirtex_order_key ) {
			
			try {
				
				// fetch the order based on it's cavirtex_order_key metadata
				$posts = get_posts( array(
					'post_type'  => 'shop_order',
					'meta_query' => array(
						array(
							'key'   => 'cavirtex_order_key',
							'value' => $cavirtex_order_key,
						)
					),
				) );
				$post     = $posts[0];
				$order_id = $post->ID; if( ! $order_id ) throw new Exception( 'Order could not be found.' );
				$order    = new WC_Order( $order_id );
				
				// mark as complete
				$order->payment_complete();
				
				// return thank you page redirect
				return array(
					'result'   => 'success',
					//'redirect' => $this->get_return_url( $order ),
				);
				
			}catch( Exception $e ){
				return $this->handle_error( $e->getMessage() );
			}
			
		}
		
		
		/**
		 * Handle errors consistently
		 */
		private function handle_error( $message ) {
			global $woocommerce;
			
			$woocommerce->add_error( __( $message, 'woothemes' ) );
		}
	
	}
	
}
add_action( 'plugins_loaded', 'init_cavirtex_payment_gateway' );

// notify WC about this custom payment gateway
function add_cavirtex_payment_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Cavirtex'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_cavirtex_payment_gateway' );