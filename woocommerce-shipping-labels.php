<?php
/**
 * Plugin Name: WooCommerce Shipping Labels
 * Plugin URI: http://www.dkjensen.com/
 * Description: View and print shipping labels automatically
 * Author: David Jensen
 * Author URI: http://dkjensen.com/
 * Version: 1.0
 *
 * Copyright 2013  Leonard's Ego Pty. Ltd.  (email : freedoms@leonardsego.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		WooCommerce Shipping Labels
 * @author		David Jensen
 * @since		1.0
 */

namespace Awsp\Ship;

define( 'WC_SHIPPING_LABELS_DIR', __FILE__ );
define( 'WC_SHIPPING_LABELS_OPTIONS_PREFIX', 'woocommerce_ups' );

// Include Woo functions
if ( ! function_exists( 'woothemes_queue_update' ) || ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Check if SOAP is enabled
if( ! extension_loaded( 'soap' ) ) {
	WC_Shipping_Labels_Error( 'The <a href="http://www.php.net/manual/en/book.soap.php" target="_blank">SOAP extension</a> must be enabled to use WooCommerce UPS Shipping Labels.' );
	return;
}

// Check if WooCommerce is active
if ( ! is_woocommerce_active() ) {
	WC_Shipping_Labels_Error( 'WooCommerce Shipping Labels is inactive. WooCommerce must be active in order to use WooCommerce Shipping Labels.' );
	return;
}

// Function to display an admin error notice
function WC_Shipping_Labels_Error( $message = '' ) {
	if( ! empty( $message ) ) {
		return printf( '<div class="error"><p>%s</p></div>', __( $message, 'woocommerce-wcsl' ) );
	}
}
add_action( 'admin_notices', 'Awsp\Ship\WC_Shipping_Labels_Error' );

require_once 'woocommerce-shipping.php';
require_once 'libs/WC_Shipping_Labels.php';
require_once 'libs/WC_Shipping_Settings.php';

$UPS = new WC_Shipping_Labels;

\register_activation_hook( __FILE__, array( 'WC_Shipping_Labels', 'activate' ) );