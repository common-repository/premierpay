<?php
/**
 * premierpay Gateway Class
 */

/**
 * premierpay Gateway.
 *
 * Provides a premierpay Payment Gateway.
 *
 * @class       WC_premierpay_Gateway
 * @extends     WC_Payment_Gateway
 * @version     0.1.0
 */

if ( ! class_exists( 'WC_premierpay_Gateway' ) && class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_premierpay_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			// Setup general properties.
			$this->setup_properties();
	
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'prp_thank_you' ) );
			add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );
			// walletpayer Emails.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                   = 'premier_pay';
			$this->icon                 = '';
			$this->has_fields           = false;
			$this->method_title         = __( 'Premier Pay', 'premier-pay-woo');
			$this->method_description   = __( 'Premier Pay.', 'premier-pay-woo');
	
			// Get settings.
			$this->title              ='Premier Pay';
			$this->description        = $this->get_option( 'description' );
			$this->instructions       = $this->get_option( 'instructions' );
			$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
			
			// Extra settings
			$this->Username      = $this->get_option( 'Username' );
			$this->Password   = $this->get_option( 'Password' );
			$this->MerchantID = $this->get_option( 'MerchantID' );

// 			//Check if the gateway can be used
// 			if ( ! $this->is_valid_for_use() ) {
// 				$this->enabled = false;
// 			}
		}
	
		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'premier-pay-woo'),
					'type' => 'checkbox',
					'label' => __( 'Enable or Disable Premier Pay', 'premier-pay-woo'),
					'description' => __("Premier Pay is an online payment service developed by premier Bank to power in-app,online and in-person contactless purchases on mobile devices,enabling users to make payments with Android phones,tablets or Watches. Premier Pay is used for fast,simple and secure online payments."),
					'default' => 'no'
				),
				'title' => array(
					'title' => __( 'Title', 'premier-pay-woo'),
					'type' => 'text',
					'default' => 'Premier Pay',
					'desc_tip' => true,
					'description' => __( 'This controls the title which the user sees during checkout.', 'premier-pay-woo')
				),
				'description' => array(
					'title' => __( 'Checkout Description', 'premier-pay-woo'),
					'type' => 'textarea',
					'default' => __( 'Pay using  your Mobile Phone.', 'premier-pay-woo'),
					'desc_tip' => true,
					'description' => __( 'This controls the description which the user sees during checkout.', 'premier-pay-woo')
				),
				'instructions' => array(
					'title' => __( ' Instructions', 'premier-pay-woo'),
					'type' => 'textarea',
					'default' => __( 'Your order has been received. Please check your Mobile Money Account to complete the transaction.', 'premier-pay-woo'),
					
					'description' => __( 'Instructions that will be added to the thank you page and order email', 'premier-pay-woo')
				),
				'Username' => array(
					'title' => __( 'Username', 'premier-pay-woo'),
					
					'type' => 'text',
					
					
				),
				'Password' => array(
					'title' => __( 'Password', 'premier-pay-woo'),
					
					'type' => 'password',
					
					'default' => __( '', 'premier-pay-woo'),
				),
				'MerchantID' => array(
					'title'   => __( 'MerchantID', 'premier-pay-woo' ),
    				
					'type'    => 'text',
				
				),
				'enable_for_virtual' => array(
					'title'   => __( 'Accept for virtual orders', 'premier-pay-woo' ),
					'label'   => __( 'Accept premier if the order is virtual', 'premier-pay-woo' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
			);
		}
	
	
		/**
		 * Check If The Gateway Is Available For Use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$order          = null;
			$needs_shipping = false;
	
			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );
	
				// Test if order needs shipping.
				if ( 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			}
	
			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
	
			// Virtual order, with virtual disabled.
			if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
				return false;
			}
	
			// Only apply if all packages are being shipped via chosen method, or order is virtual.
			if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
				$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );
	
				if ( $order_shipping_items ) {
					$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
				} else {
					$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
				}
	
				if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
					return false;
				}
			}
			
			if ( $this->enabled == "yes" ) {
				if ( ! $this->Username ) {
					return false;
				}
				return true;
			}
			return false;
	
			return parent::is_available();
		}
	
		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
    	

			public	function process_payment($order_id)
		{
			global $woocommerce;
			$walletpayerWalletID = sanitize_text_field( $_POST['walletpayerWalletID'] );
			$order = new WC_Order($order_id);
			$orderarray = json_decode($order, true);	
			$billing_address = $orderarray['billing'];
			$currency_code   = $order->get_currency();
    		$currency_symbol = get_woocommerce_currency_symbol( $currency_code );
			$total_order_amount = (int)$order->order_total;
			// WalletPay payment processing
			$response = $this->prp_process_payment($this->Username, $this->Password,$total_order_amount,$walletpayerWalletID,$this->MerchantID) ;
			if ($response == "success") {
				// Mark as on-hold 
				$order->update_status('processing', __('Payment Recieved', 'woocommerce'));
				//stock reduce
				  $order->reduce_order_stock();
				// // Remove cart
				$woocommerce->cart->empty_cart();
				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url($order)
				);
			}
		}


		public	function prp_process_payment($username,$password,$total_order_amount,$CustomerWalletID,$MerchantID)
		{
				//echo $CustomerWalletID;
			require_once DIR_PATH . 'includes/restapi.php';
			$res = prp_Pay_rest($username,$password,(float)$total_order_amount,$CustomerWalletID,$MerchantID) ;
			if ($res['restcode'] == '001') {
			       if($res['reststatus'] == 'Executed' ){
			           return "success";
			       }else{
			           	wc_add_notice(  'Transaction is Failed. Please try again', 'error' );
				         return "fail";
			       }
				//success response
				
			} else {
				wc_add_notice($res['error_message'], 'error' );
				return "fail";
				//echo  $res['error_message'];
			}
		}
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}
		}
	
		public function prp_thank_you() {
			$added_text = $this->instructions;
			return $added_text ;
		}
	
		/**
		 * Change payment complete order status to completed for COD orders.
		 *
		 * @since  3.1.0
		 * @param  string         $status Current order status.
		 * @param  int            $order_id Order ID.
		 * @param  WC_Order|false $order Order object.
		 * @return string
		 */
		public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
			if ( $order && 'premier_pay' === $order->get_payment_method() ) {
				$status = 'wc-invoiced';
			}
			return $status;
		}
	
		/**
		 * Add content to the WC emails.
		 *
		 * @param WC_Order $order Order object.
		 * @param bool     $sent_to_admin  Sent to admin.
		 * @param bool     $plain_text Email format: plain text or HTML.
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}
		
		/**
		 * Check if currency in use is allowed
		 */
		public function is_valid_for_use() {
			if( ! in_array( get_woocommerce_currency(), array( 'USD' ) ) ) {
				$this->msg = 'premierpay does not support your store currency, set it to USD <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">here</a>';
				return false;    
			}
			return true;
		}
		
			
	}
}