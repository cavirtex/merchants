<?php
/*
Plugin Name: Virtex Bitcoin Woocommerce plugin
Plugin URI: http://www.cavirtex.com
Description: This plugin adds the Bitcoin payment option (via <a href="https://www.cavirtex.com/">Virtex</a>) to your Woocommerce plugin. (Requires WooCommerce 2.0.0 or higher)
Version: 2.0
Author: Saucal
Author URI: http://www.saucal.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Enable Logging
 **/
function vrxlog($contents) {
	$file = plugin_dir_path(__FILE__).'vxlog.txt';
	file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
	if (is_array($contents))
		file_put_contents($file, var_export($contents, true)."\n", FILE_APPEND);		
	else if (is_object($contents))
		file_put_contents($file, json_encode($contents)."\n", FILE_APPEND);
	else
		file_put_contents($file, $contents."\n", FILE_APPEND);
}

/**
 * Init Virtex plugin
 **/
function declareWooVirtexpay() {
	
	if ( ! class_exists( 'WC_Payment_Gateways' ) ) 
		return;
	
	if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) )
		return;
	
	class WC_Gateway_Virtex extends WC_Payment_Gateway {
		
		var $notify_url;
		
		public function __construct() {
			global $woocommerce;
			
			$this->id = 'virtex';
			$this->icon = plugin_dir_url(__FILE__).'virtex-logo.png';
			$this->has_fields = false;
			$this->method_title = __( 'Bitcoin', 'woocommerce' );
			$this->notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_virtex', home_url( '/' ) ) ); //do not follow classname convention to maintain harmony in $_GET (in ipn address)
		 	
			// Define user set variables
			$this->title           = 'Bitcoin';
			$this->description     = $this->get_option( 'description' );
			$this->bill_as         = $this->get_option( 'bill_as' );
			$this->merchant_key    = $this->get_option( 'merchant_key' );
			$this->merchant_secret = $this->get_option( 'merchant_secret' );
			$this->vdubg           = $this->get_option( 'vdbug' );
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		 
			// save admin options
			add_action('woocommerce_update_options_payment_gateways_'.$this->id, array(&$this, 'process_admin_options'));
			
			// Payment listener/API hook
			// Redundant since we hook into action outside class. See hook function for explanation
			// add_action( 'woocommerce_api_wc_gateway_virtex', array( $this, 'process_ipn' ) ); // // notice the small caps! even when $_GET has Class name convention
		 
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
			
		}

	    public function is_valid_for_use() {
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_cavirtex_supported_currencies', array( 'CAD', 'BTC' ) ) ) ) return false;
			return true;
	    }
		
		
		public function admin_options() {
			?>
			<h3><?php _e('Bitcoin Payment', 'woocommerce'); ?></h3>
			<p><?php _e('Allows bitcoin payments via cavirtex.com', 'woocommerce'); ?></p>
            <?php if ( $this->is_valid_for_use() ) : ?>
            	<p><?php _e('Remember to update the IPN address at cavirtex.com in your Merchant Profile Page.', 'woocommerce'); ?><br /><code><?php echo $this->notify_url; ?></code></p>
                <table class="form-table">
                <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                ?>
                </table><!--/.form-table-->
            <?php else : ?>
                <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'VirtEx does not support your store currency. Please switch to CAD to accept payments via Virtex.', 'woocommerce' ); ?></p></div>
            <?php
            endif;
		} // End admin_options()
		
		
		function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable Virtex Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default' => __( $this->method_title, 'woocommerce' ),
				),
				'description' => array(
					'title' => __( 'Customer Message', 'woocommerce' ),
					'type' => 'textarea',
					'description' => __( 'Message to explain how the customer will be paying for the purchase.', 'woocommerce' ),
					'default' => 'You will be redirected to cavirtex.com to complete your purchase.'
				),
				'bill_as' => array(
					'title' => __( 'Bill As', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'This is what the customer will see their order listed as on the CaVirtEx invoice.', 'woocommerce' ),
					'default' => __( 'WooCommerce Order', 'woocommerce' ),
					'desc_tip' => TRUE,
				),
				'merchant_key' => array(
					'title' => __('Merchant Key', 'woocommerce'),
					'type' => 'text',
					'description' => __( 'Available on your <a href="https://www.cavirtex.com/merchant_information" target="_blank">Merchant Profile page</a>.', 'woocommerce' ),
					'default' => '',
				),
				'merchant_secret' => array(
					'title' => __( 'Merchant Secret Key', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'Available on your <a href="https://www.cavirtex.com/merchant_information" target="_blank">Merchant Profile page</a>.', 'woocommerce' ),
					'default' => '',
				),
				'vdbug' => array(
					'title' => __( 'Debug Log', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable logging', 'woocommerce' ),
					'default' => 'no',
					'description' => sprintf( __( 'Log Virtex events (such as IPN requests) inside <code>%swordpress-woocommerce/vxlog.txt</code>. This is helpful for development and case solving, but consider disabling this in case you are worried about privacy since these logs are publicly viewable.', 'woocommerce' ), home_url( '/' ) ),
				)
				
			);
		}
			
		
		public function get_virtex_params( WC_Order $order ) {
			$params = array(
				'name'     => $this->bill_as, //@todo $order->get_items add names
				'code'     => $order->id,
				'price'    => $order->get_total() - $order->get_shipping() /* - $order->get_total_tax() */,
				'shipping_required' => 0, // Let Woo handle shipping // $order->get_shipping() > 0 ? 1 : 0,
				'cancel_url'        => $order->get_cancel_order_url(),
				'return_url'        => get_permalink( woocommerce_get_page_id( 'myaccount' ) ),
				'email'		    => $order->billing_email,
				'customer_name' => $order->billing_first_name . ' ' . $order->billing_last_name,
				'format' => 'json',
				'custom_1' => json_encode( array('hash' => crypt( $this->merchant_secret, $this->merchant_key) ) ),
			);
			
			return $params;
		}

		function process_payment( $order_id ) {			
			global $woocommerce;

			$order  = new WC_Order( $order_id );
			$params = $this->get_virtex_params( $order );
			
			// send payment data to cavirtex
			require_once( plugin_dir_path( __FILE__ ) . 'virtex-lib.php' );
			$virtexapi = new VirtEx_Lib( $this->merchant_key, $this->merchant_secret );
			$merchant_purchase = $virtexapi->merchant_purchase( $params );
			
			if( $merchant_purchase['status'] == 'error' || $merchant_purchase['Status'] == 'error' ){ //RRR
				$this->handle_error( 'Error: '.$merchant_purchase['Message'], 'Error: Could not create virtex invoice. <code>'.json_encode($merchant_purchase).'</code>' );
				return;
			}
			
			if( empty( $merchant_purchase['order_key'] ) ) {
				$this->handle_error( __('Something went wrong. Please choose a different payment method.','woocommerce'), 'Error: Empty order key returned. Could not create virtex invoice. <code>'.json_encode($merchant_purchase).'</code>' );
				return;
			}
			
			// store cavirtex order key as post metadata
			update_post_meta( $order_id, 'cavirtex_order_key', $merchant_purchase['order_key'] );
			
			// clear cart
			$woocommerce->cart->empty_cart();
			
			//$order->update_status('on-hold', __('User redirected to Cavirtex invoice. Awaiting payment completion notification from cavirtex.com', 'woocommerce')); // if change status to on-hold, then user cant cancel.. "Your order is no longer pending and could not be cancelled. Please contact us if you need assistance."
			$order->add_order_note( __('User redirected to Cavirtex invoice. Awaiting payment completion notification from cavirtex.com', 'woocommerce') );
			
			// redirect to invoice
			return array(
				'result'   => 'success',
				'redirect' => 'https://www.cavirtex.com/merchant_invoice?merchant_key='.$this->merchant_key.'&order_key='.$merchant_purchase['order_key'],
			);
			
		}
		
		/**
		 * Check for IPN Response
		 */
		public function process_ipn() {
				
			require_once( plugin_dir_path( __FILE__ ) . 'virtex-lib.php' );
			$virtexapi = new VirtEx_Lib( $this->merchant_key, $this->merchant_secret );
			
			$rawData = file_get_contents("php://input");
			$params = json_decode($rawData, TRUE);
			
			if( ! $params ) {
				vrxlog( 'Empty IPN received. <code>'.$rawData.'</code>' );
				return;
			} else {
				vrxlog( 'IPN received. <code>'.$rawData.'</code>' );
			}
			
			$custom_1 = json_decode( $params['custom_1'], true );
			if ( $custom_1['hash'] != crypt( $this->merchant_secret, $this->merchant_key) ) {
				vrxlog( 'Authentication failed. Bad hash. <code>'.$rawData.'</code>' );
				return;
			}
			
			$merchant_confirm_ipn = $virtexapi->merchant_confirm_ipn( $params );
			if( $merchant_confirm_ipn['status'] != 'ok' ) { //RRR
				vrxlog( 'Authentication failed. IPN cannot be verified. <code>'.json_encode($merchant_confirm_ipn).'</code>' );
				return;
			}
			
			/* @todo vxCurl() currently doesnt work with GET. Since we have received IPN, mark payment complete for now
			// has the invoice been paid in full? if yes, success; if not, continue waiting
			$paid = $virtexapi->merchant_invoice_balance_check( $params['order_key'] );
			$paid_in_full = in_array($paid['status'], array('credited', 'confirmed', 'complete', 'paid')); //RRR
			if( $paid_in_full ) {
				return $this->payment_successful( $params['order_key'] );
			} else {
				vrxlog( 'Order not updated. Payment not complete. <code>'.json_encode($paid).'</code>' );
				return;
			}*/ return $this->payment_successful( $params['order_key'] );
			
		}
		
		/**
		 * Successful Payment!
		 */
		public function payment_successful( $cavirtex_order_key ) {
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
			$order_id = $post->ID;
			if( ! $order_id ){
				vrxlog( 'Order could not be found. <code>'.$cavirtex_order_key.'</code>' );
				return;
			}
			$order    = new WC_Order( $order_id );
			
			// mark as complete
			$order->payment_complete();
			
			// return thank you page redirect
			return array(
				'result'   => 'success',
				//'redirect' => $this->get_return_url( $order ),
			);
			
		}
		
		private function handle_error( $message, $vxerror = '' ) {
			global $woocommerce;
			if( !empty( $vxerror ) ) vrxlog($vxerror);
			elseif( !empty( $message ) ) vrxlog($message);
			$woocommerce->add_error( __( $message, 'woocommerce' ) );
		}
				
	}
}
add_action('plugins_loaded', 'declareWooVirtexpay', 0);


// notify WC about this custom payment gateway
function add_virtexpay_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Virtex'; 
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_virtexpay_gateway' );



// the API does try to create WC_Gateway_Virtex object, but the class isn't defined yet
// since declareWooVirtexpay is hooked to plugins_loaded action
// Hence we hook into woocommerce_api_WC_Gateway_Virtex directly and create object
// OK to create object since WC has run includes before new WC_API() in woocommerce.php
// But there is no action in between that we can hook into :( 
add_action( 'woocommerce_api_wc_gateway_virtex', 'woo_process_virtex_ipn' ); // notice the small caps! even when $_GET has Class name convention
function woo_process_virtex_ipn() {
	$apiClass = new WC_Gateway_Virtex();
	$apiClass->process_ipn();
}
