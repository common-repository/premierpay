<?php
/**
 * 
 * Plugin Name: PremierPay
 * Author: PremierBank,PremierWallet
 * Author URI: https://premierwallets.com/
 * Description: PremierPay is an online payment service developed by premier Bank to power in-app,online and in-person contactless purchases on mobile devices,enabling users to make payments with Android phones,tablets or Watches. PremierPay is used for fast,simple and secure online payments.
 * Version: 0.1.0
 * 
 * WC requires at least: 3.0
 * WC tested up to: 4.3.0
 */ 

// Basic Security to avoid brute access to file.
defined( 'ABSPATH' ) or exit;

// Check if WooCommerce is installed.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

// Define constants to be used.
if( ! defined( 'PREMIERPAYBASE' ) ) {
	define( 'PREMIERPAYBASE', plugin_premierpaybase( __FILE__ ) );
}

if( ! defined( 'PREMIERPAY_PATH' ) ) {
	define( 'PREMIERPAY_PATH', plugin_premierpay_path( __FILE__ ) );
}

// When plugin is loaded. Call init functions.
add_action( 'plugins_loaded', 'prp_payment_init' );
add_filter( 'woocommerce_payment_gateways', 'prp_payment_gateway_add_to_woo');

/**
 * Add the gateway class.
 * Add function helpers.
 * 
 * @return void
 */
function prp_payment_init() {
	require_once DIR_PATH . 'includes/premierpay-initial-setup.php';
	require_once DIR_PATH . 'includes/class-premierpay-gateway.php';
	require_once DIR_PATH . 'includes/premierpay-checkout-page.php';
}

/**
 * Add Payment gateway to Woocommerce.
 *
 * @param array $gateways Existing Gateways in WC.
 * @return array $gateways Existing Gateways in WC + walletpay.
 */
function prp_payment_gateway_add_to_woo( $gateways ) {
    $gateways[] = 'WC_premierpay_Gateway';
    return $gateways;
}
