<?php
/**
 * Plugin Name: WooCommerce UPS Shipping Labels
 * Plugin URI: http://www.dkjensen.com/
 * Description: View and print UPS shipping labels automatically
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
 * @package		WooCommerce UPS Shipping Labels
 * @author		David Jensen
 * @since		1.0
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) || ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

if( ! extension_loaded( 'soap' ) ) {
	WC_UPS_SL::admin_error( 'The <a href="http://www.php.net/manual/en/book.soap.php" target="_blank">SOAP extension</a> must be enabled to use WooCommerce UPS Shipping Labels.' );

	return;
}

add_action( 'admin_notices', WC_UPS_SL . '::admin_error' );

/**
 * Check if WooCommerce is active, and if it isn't, disable Subscriptions.
 *
 * @since 1.0
 */
if ( ! is_woocommerce_active() ) {
	add_action( 'admin_notices', 'WC_UPS_SL::woocommerce_inactive_notice' );
	return;
}

class WC_UPS_SL {

	/**
	 * WooCommerce settings tab name
	 */
	public static $tab_name = 'shipping_labels';

	/**
	 * Prefix for options
	 */
	public static $option_prefix = 'woocommerce_ups';

	public static function admin_error( $message ) {
		if( empty( $message ) || ! isset( $message ) ) return;

		printf( '<div class="error"><p>%s</p></div>', __( $message, 'woocommerce-ups' ) );
	}

	/**
	 * 
	 * 
	 * @return type
	 */
	public function init() {
		// Include required libs
		require_once 'woocommerce-get-labels.php';

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_ups_settings_tab' ) );

		add_action( 'woocommerce_settings_tabs_shipping_labels', array( $this, 'add_ups_settings_page' ) );
		add_action( 'woocommerce_update_options_' . self::$tab_name, array( $this, 'update_ups_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_ups_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
	}

	/**
	 * Adds a tab to the WooCommerce settings page
	 */
	public function add_ups_settings_tab( $settings_tabs ) {
		$settings_tabs[self::$tab_name] = __( 'Shipping Labels', 'woocommerce-ups' );

		return $settings_tabs;
	}

	/**
	 * The content for the settings page
	 */
	public function add_ups_settings_page() {
		woocommerce_admin_fields( self:: get_settings() );
	}

	/**
	 * Update settings page fields
	 */
	public function update_ups_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Add a meta box to the orders page
	 * Generate UPS shipping label
	 */
	public function add_ups_meta_box() {
		add_meta_box( 'woocommerce-ups-shipping-label', __( 'UPS Shipping Label', 'woocommerce-ups' ), array( $this, 'ups_meta_box' ), 'shop_order', 'side', 'default' );
	}

	/**
	 * Output for orders meta box
	 * 
	 * @return type
	 */
	public function ups_meta_box() {
		global $post; ?>
		<div class="woocommerce-shipping-label-meta-box">
			<select name="" id="woocommerce-shipping-label-print-option" class="woocommerce-shipping-label-print-option">
				<option value="">Select Shipping Carrier</option>
				<option value="fedex">Fedex</option>
				<option value="ups">UPS</option>
				<option value="usps">USPS</option>
			</select>

			<button type="button" class="button button-primary woocommerce-shipping-label-option-button" id="woocommerce-shipping-label-option-button"><?php _e( 'Generate Label', 'woocommerce-ups' ); ?></button>
		</div>
	<?php
	}

	public function add_admin_scripts( $hook ) {
		if( 'post.php' != $hook ) return;

		wp_enqueue_style( 'woocommerce-shipping-labels-css', plugins_url( 'css/woocommerce-shipping-labels.css', __FILE__ ) );

		wp_enqueue_script( 'woocommerce-shipping-labels-js', plugins_url( 'js/woocommerce-shipping-labels.js', __FILE__ ), array( 'jquery' ), '1.0', true );
	}

	public function validateUPSCredentials() {
		$errors = array();

		switch( true ) {

			// UPS account number empty
			case get_option( self::$option_prefix . '_ups_account_number' ) == '':
				$errors[] = 'UPS account number required.';
				break;

			// UPS account number empty
			case get_option( self::$option_prefix . '_ups_access_key' ) == '':
				$errors[] = 'UPS access key required.';
				break;

			// UPS account number empty
			case get_option( self::$option_prefix . '_ups_username' ) == '':
				$errors[] = 'UPS account username required.';
				break;

			// UPS account number empty
			case get_option( self::$option_prefix . '_ups_password' ) == '':
				$errors[] = 'UPS account password required.';
				break;

			default:
				//
		}

		// If no errors return true
		if( empty( $errors ) )
			return true;

		// Admin message for each error
		foreach( $errors as $message )
			WC_UPS_SL::admin_error( $message );

		return false;
	}

	public function generateLabel() {
		$this->validateUPSCredentials();

		// Order details
		$order = new WC_Order( $post->ID );

		// Verify shipping details are set
		if( ! $order->get_shipping_address() )
			return printf( '<p><em>%s</em></p>', __( 'Shipping details required.', 'woocommerce-ups' ) );

		$address = array(
			'address_1' => $order->shipping_address_1,
			'address_2' => $order->shipping_address_2,
			'city' => $order->shipping_city,
			'state' => $order->shipping_state,
			'postcode' => $order->shipping_postcode,
			'country' => $order->shipping_country );


		$label = new WC_UPS_Label( $address );
		$label->createShipment();
		$label->createPackage();

		print '<div class="wc_ups_labels">' . $label->createLabel() . '</div>';
	}

	public function get_settings() {
		global $woocommerce;

		
		return apply_filters( 'woocommerce_ups_sl_settings', array(

		array(
			'name'     => __( 'Shipper Information', 'woocommerce-ups' ),
			'type'     => 'title',
			'desc'	   => __( 'Information must match what is on file with UPS or the API call will fail.', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_shipper_information'
		),

		array(
			'name'     => __( 'Name', 'woocommerce-ups' ),
			'desc'     => __( 'A product displays a button with the text "Add to Cart". By default, a subscription changes this to "Sign Up Now". You can customise the button text for subscriptions here.', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_name',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Attention name', 'woocommerce-ups' ),
			'desc'     => __( 'A product displays a button with the text "Add to Cart". By default, a subscription changes this to "Sign Up Now". You can customise the button text for subscriptions here.', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_attention_name',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Phone', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_phone',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Email', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_email',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Address 1', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_address',
			'css'      => 'min-width:300px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Address 2', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_address_2',
			'css'      => 'min-width:300px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Address 3', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_address_3',
			'css'      => 'min-width:300px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'City', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_city',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'State', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_state',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Postal Code', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_postcode',
			'css'      => 'max-width:80px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'Country', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_country',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_shipper_information' ),

		array(
			'name'     => __( 'UPS Shipper Configuration Settings', 'woocommerce-ups' ),
			'type'     => 'title',
			'desc'     => __( 'Choose the default roles to assign to active and inactive subscribers. For record keeping purposes, a user account must be created for subscribers. Users with the <em>administrator</em> role, such as yourself, will never be allocated these roles to prevent locking out administrators.', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_ups_shipper_configuration_settings'
		),

		array(
			'name'     => __( 'UPS Account Number', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_ups_account_number',
			'css'      => 'max-width:100px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'UPS Access Key', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_ups_access_key',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'UPS Username', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_ups_username',
			'css'      => 'min-width:150px;',
			'type'     => 'text',
			'desc_tip' => true,
		),

		array(
			'name'     => __( 'UPS Password', 'woocommerce-ups' ),
			'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-ups' ),
			'id'       => self::$option_prefix . '_ups_password',
			'css'      => 'min-width:150px;',
			'type'     => 'password',
			'desc_tip' => true,
		),

		array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_ups_shipper_configuration_settings' ),

		array(
			'name'          => __( 'UPS API URL', 'woocommerce-ups' ),
			'type'          => 'title',
			'desc'          => sprintf( __( "Only use when you are not in production. Testing URL is located at %s", 'woocommerce-ups' ), 'https://wwwcie.ups.com/webservices' ),
			'id'            => self::$option_prefix . '_ups_api_url'
		),

		array(
			'name'            => __( 'Enable Testing Mode', 'woocommerce-ups' ),
			'desc'            => __( 'Use Testing URL', 'woocommerce-ups' ),
			'id'              => self::$option_prefix . '_enable_testing_mode',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'start'
		),


		array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_ups_api_url' ) ) );

	}

}


$UPS = new WC_UPS_SL;
$UPS->init();