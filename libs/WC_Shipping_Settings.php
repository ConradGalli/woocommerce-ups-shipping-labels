<?php

namespace Awsp\Ship;

class WC_Shipping_Settings {

	protected $id, 
			  $label;

	// Prefix for options
	public static $option_prefix = 'woocommerce_ups';

	public function __construct() {
		global $current_section;

		$current_section = $_GET['section'];

		$this->id = __( 'shipping_labels', 'woocommerce-wcsl' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_tabs_' . $this->id, array( $this, 'add_settings_page' ) );
		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'update_settings' ) );
	}


	public function get_sections() {
		$sections = array(
			''          => __( 'General Settings', 'woocommerce-wcsl' ),
			'ups' => __( 'UPS', 'woocommerce-wcsl' )
		);

		return $sections;
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) )
			return;

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label )
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';

		echo '</ul><br class="clear" />';
	}

	/**
	 * Adds a tab to the WooCommerce settings page
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[$this->id] = __( 'Shipping Labels', 'woocommerce-wcsl' );

		return $settings_tabs;
	}

	/**
	 * The content for the settings page
	 */
	public function add_settings_page() {
		woocommerce_admin_fields( self:: get_settings() );
	}

	/**
	 * Update settings page fields
	 */
	public function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * 
	 */
	public function get_settings() {
		global $woocommerce, $current_section;

		if ( $current_section == '' ) {

			return apply_filters( 'woocommerce_ups_sl_settings', array(

				array(
					'name'     => __( 'Shipper Information', 'woocommerce-wcsl' ),
					'type'     => 'title',
					'desc'	   => __( 'Information must match what is on file with UPS or the API call will fail.', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_shipper_information'
				),

				array(
					'name'     => __( 'Name', 'woocommerce-wcsl' ),
					'desc'     => __( 'A product displays a button with the text "Add to Cart". By default, a subscription changes this to "Sign Up Now". You can customise the button text for subscriptions here.', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_name',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Attention name', 'woocommerce-wcsl' ),
					'desc'     => __( 'A product displays a button with the text "Add to Cart". By default, a subscription changes this to "Sign Up Now". You can customise the button text for subscriptions here.', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_attention_name',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Phone', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_phone',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Email', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_email',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Address 1', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_address',
					'css'      => 'min-width:300px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Address 2', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_address_2',
					'css'      => 'min-width:300px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Address 3', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_address_3',
					'css'      => 'min-width:300px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'City', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_city',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'State', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_state',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Postal Code', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_postcode',
					'css'      => 'max-width:80px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Country', 'woocommerce-wcsl' ),
					'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
					'id'       => self::$option_prefix . '_country',
					'css'      => 'min-width:150px;',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_shipper_information' )

			) );



	}elseif( $current_section == 'ups' ) {

		return apply_filters( 'woocommerce_ups_sl_settings', array(

			array(
				'name'     => __( 'UPS Shipper Configuration Settings', 'woocommerce-wcsl' ),
				'type'     => 'title',
				'desc'     => __( 'Choose the default roles to assign to active and inactive subscribers. For record keeping purposes, a user account must be created for subscribers. Users with the <em>administrator</em> role, such as yourself, will never be allocated these roles to prevent locking out administrators.', 'woocommerce-wcsl' ),
				'id'       => self::$option_prefix . '_ups_shipper_configuration_settings'
			),

			array(
				'name'     => __( 'UPS Account Number', 'woocommerce-wcsl' ),
				'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
				'id'       => self::$option_prefix . '_ups_account_number',
				'css'      => 'max-width:100px;',
				'type'     => 'text',
				'desc_tip' => true,
			),

			array(
				'name'     => __( 'UPS Access Key', 'woocommerce-wcsl' ),
				'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
				'id'       => self::$option_prefix . '_ups_access_key',
				'css'      => 'min-width:150px;',
				'type'     => 'text',
				'desc_tip' => true,
			),

			array(
				'name'     => __( 'UPS Username', 'woocommerce-wcsl' ),
				'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
				'id'       => self::$option_prefix . '_ups_username',
				'css'      => 'min-width:150px;',
				'type'     => 'text',
				'desc_tip' => true,
			),

			array(
				'name'     => __( 'UPS Password', 'woocommerce-wcsl' ),
				'desc'     => __( 'Use this field to customise the text displayed on the checkout button when an order contains a subscription. Normally the checkout submission button displays "Place Order". When the cart contains a subscription, this is changed to "Sign Up Now".', 'woocommerce-wcsl' ),
				'id'       => self::$option_prefix . '_ups_password',
				'css'      => 'min-width:150px;',
				'type'     => 'password',
				'desc_tip' => true,
			),

			array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_ups_shipper_configuration_settings' ),

			array(
				'name'          => __( 'UPS API URL', 'woocommerce-wcsl' ),
				'type'          => 'title',
				'desc'          => sprintf( __( "Only use when you are not in production. Testing URL is located at %s", 'woocommerce-wcsl' ), 'https://wwwcie.ups.com/webservices' ),
				'id'            => self::$option_prefix . '_ups_api_url'
			),

			array(
				'name'            => __( 'Enable Testing Mode', 'woocommerce-wcsl' ),
				'desc'            => __( 'Use Testing URL', 'woocommerce-wcsl' ),
				'id'              => self::$option_prefix . '_enable_testing_mode',
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start'
			),


			array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_ups_api_url' )

		) );

	}
}

}

new WC_Shipping_Settings;

