<?php

class WC_Shipping_Labels {

	//WooCommerce settings tab name
	public static $tab_name = 'shipping_labels';

	// Prefix for options
	public static $option_prefix = 'woocommerce_ups';

	// Folder to store generated shipping labels
	public static $labels_directory = 'shipping-labels';

	// List of carriers
	public static $carriers = array(
		'ups' => array(
			'enabled' => true,
			'label' => 'UPS',
			'object' => 'WC_UPS_Label' )
	);

	/**
	 * Initiation
	 * 
	 * @return type
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ) );

		add_action( 'woocommerce_settings_tabs_shipping_labels', array( $this, 'add_settings_page' ) );
		add_action( 'woocommerce_update_options_' . self::$tab_name, array( $this, 'update_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_generateLabel', array( $this, 'generateLabel' ) );
	}

	/**
	 * Function to be called on plugin activation
	 * 
	 * @return type
	 */
	public function activate() {
		// Determine content directory
		$content_directory = WP_CONTENT_DIR;
		$content_directory = $content_directory . DIRECTORY_SEPARATOR . self::$labels_directory;

		// Create shipping label directory
		if( ! file_exists( $content_directory ) || ! is_dir( $content_directory ) ) {
			if( ! mkdir( $content_directory ) ) {
				WC_Shipping_Labels_Error( 'Could not create shipping label directory. Please create a directory located at {$content_directory} and <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">modify the permissions to 0777</a>.' );
			}
		}
	}

	/**
	 * Adds a tab to the WooCommerce settings page
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[self::$tab_name] = __( 'Shipping Labels', 'woocommerce-ups' );

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
	 * Add a meta box to the orders page
	 * Generate UPS shipping label
	 */
	public function add_meta_box() {
		add_meta_box( 'woocommerce-ups-shipping-label', __( 'UPS Shipping Label', 'woocommerce-ups' ), array( $this, 'meta_box' ), 'shop_order', 'side', 'default' );
	}

	/**
	 * Output for orders meta box
	 * 
	 * @return type
	 */
	public function meta_box() {
		global $post; ?>
		<div class="woocommerce-shipping-label-meta-box" data-order="<?php print $post->ID; ?>">
			<div id="woocommerce-shipping-label-message-area"></div>
			<select name="wc_shipping_label_carrier" id="woocommerce-shipping-label-print-option" class="woocommerce-shipping-label-print-option">
				<option value="">Select Shipping Carrier</option>
				<?php foreach( self::$carriers as $carrier => $options ) : ?>
					<option value="<?php print $carrier; ?>"><?php print $options['label']; ?></option>
				<?php endforeach; ?>
			</select>

			<button type="button" class="button button-primary woocommerce-shipping-label-option-button" id="woocommerce-shipping-label-option-button"><?php _e( 'Generate Label', 'woocommerce-ups' ); ?></button>
		</div>
	<?php
	}

	public function admin_scripts( $hook ) {
		if( 'post.php' != $hook ) return;

		wp_enqueue_style( 'woocommerce-shipping-labels-css', plugins_url( 'css/woocommerce-shipping-labels.css', WC_SHIPPING_LABELS_DIR ) );

		wp_enqueue_script( 'woocommerce-shipping-labels-js', plugins_url( 'js/woocommerce-shipping-labels.js', WC_SHIPPING_LABELS_DIR ), array( 'jquery' ), '1.0', true );
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
			WC_Shipping_Labels_Error( $message );

		return false;
	}

	public function generateLabel() {
		$orderID = $_POST['orderID'];
		$carrier = $_POST['carrier'];

		// Can we get the order ID?
		if( ! isset( $orderID ) || empty( $orderID ) ) {
			return WC_Shipping_Labels_Error( 'An error occured while attempting to retrieve the order ID.' );
		}

		// Verify that the carrier is set
		if( ! isset( $carrier ) || empty( $carrier ) ) {
			return WC_Shipping_Labels_Error( 'Please select a carrier.' );
		}

		// Get carrier stuff
		$carrier = self::$carriers[$carrier];

		// Make sure the carrier is ready and enabled
		if( isset( $carrier ) && $carrier['enabled'] === true ) {
			// Does the carriers class exist?
			if( ! class_exists( $carrier['object'] ) ) {
				return $this->meta_error( 'The specified carrier object does not exist.' );
			}

			// If all is well, grab the carrier object
			$carrierObj = $carrier['object'];
		}

		$this->validateUPSCredentials();

		// Order details
		$order = new WC_Order( $orderID );

		// Verify shipping details are set
		if( ! $order->get_shipping_address() ) {
			return $this->meta_error( 'Shipping details required.' );
		}

		$address = array(
			'address_1' => $order->shipping_address_1,
			'address_2' => $order->shipping_address_2,
			'city' => $order->shipping_city,
			'state' => $order->shipping_state,
			'postcode' => $order->shipping_postcode,
			'country' => $order->shipping_country );


		$label = new $carrierObj( $address );
		$label->createShipment();
		$label->createPackage();

		print '<div class="wc_ups_labels">' . $label->createLabel() . '</div>';
		exit;
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