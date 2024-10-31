<?php
/**
 * Things that run before the default plugin functions.
 * 
 * Add all as a currency in WC
 * Enable the USD currency symbol in WC
 */
if( ! function_exists( 'prp_add_all_currency' ) ) {
	add_filter( 'woocommerce_currencies', 'prp_add_all_currency' );
}

if( ! function_exists( 'prp_add_all_currency_symbol' ) ) {
	add_filter('woocommerce_currency_symbol', 'prp_add_all_currency_symbol', 10, 2);
}

/**
 * Add all Currency if that does not exist.
 *
 * @param array $currencies All old currencies.
 * @return array $currencies All new currencies + all.
 */
function prp_add_all_currency( $currencies ) {
	$currencies['USD'] = __( 'United States Dollar', 'premier-pay-woo' );
	return $currencies;
}

/**
 * Add Currency symbol for all if that does not exist.
 *
 * @param array $currencies All old Currency symbol.
 * @return array $currencies All new Currency symbol + all.
 */
function prp_add_all_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		case 'USD': $currency_symbol = '$ '; break;
	}
	return $currency_symbol;
}

/**
 * Adds plugin page links
 * 
 * @since 0.1.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our premierpay links (i.e., "Settings")
 */
function wc_prp_gateway_plugin_links( $links ) {

    // TODO: change the docs link for the plugin
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=premierpay_payment' ) . '">' . __( 'Configure Gateway', 'premier-pay-woo' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . BASENAME, 'wc_prp_gateway_plugin_links' );




