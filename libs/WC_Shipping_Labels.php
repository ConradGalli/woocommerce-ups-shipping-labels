<?php

namespace Awsp\Ship;

class WC_Shipping_Labels {

	// Folder to store generated shipping labels
	public static $labels_directory = 'shipping-labels';

	// List of carriers
	public static $carriers = array(
		'ups' => array(
			'enabled' => true,
			'slug' => 'ups',
			'label' => 'UPS',
			'object' => 'Awsp\\Ship\\UPS' ),
		'fedex' => array(
			'enabled' => true,
			'slug' => 'fedex',
			'label' => 'Fedex',
			'object' => 'Awsp\\Ship\\Fedex' )
	);

	/**
	 * Initiation
	 * 
	 * @return type
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_generateLabel', array( $this, 'generateLabel' ) );
		add_action( 'save_post', array( $this, 'save_packages' ), 10, 2 );

		add_action( 'wp_ajax_wcsl_get_packages', 'Awsp\Ship\WC_Shipping_Labels::get_packages_json' );
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
	 * Add a meta box to the orders page
	 * Generate UPS shipping label
	 */
	public function add_meta_box() {
		// Packages
		add_meta_box( 'woocommerce-shipping-packages', __( 'Shipping Packages', 'woocommerce-wcsl' ), array( $this, 'shipping_packages_meta_box' ), 'shop_order', 'side', 'default' );

		// Shipping Label
		add_meta_box( 'woocommerce-shipping-label', __( 'Shipping Label', 'woocommerce-wcsl' ), array( $this, 'shipping_label_meta_box' ), 'shop_order', 'normal', 'default' );
	}

	/**
	 * Output for orders meta box
	 * 
	 * @return type
	 */
	public function shipping_label_meta_box() {
		global $post; ?>
		<div class="woocommerce-shipping-label-meta-box" data-order="<?php print $post->ID; ?>">
			<div id="woocommerce-shipping-label-message-area"></div>
			<label for="woocommerce-shipping-label-print-option"><strong><?php _e( 'Shipping Carrier', 'woocommerce-wcsl' ); ?></strong></label>
			<select name="wc_shipping_label_carrier" id="woocommerce-shipping-label-print-option" class="woocommerce-shipping-label-print-option">
				<?php foreach( self::$carriers as $carrier => $options ) : ?>
					<option value="<?php print $carrier; ?>"><?php print $options['label']; ?></option>
				<?php endforeach; ?>
			</select>

			<div class="woocommerce-shipping-label-meta-box-carrier">
				<label for="wcsl-option-ups-service-code"><strong><?php _e( 'Shipping Service', 'woocommerce-wcsl' ); ?></strong></label>
				<select name="wcsl_option_ups_service_code" id="wcsl-option-ups-service-code">
					<?php foreach( \Awsp\Ship\UPS::$services as $service_code => $label ) : ?>
						<option value="<?php print $service_code; ?>"><?php _e( $label, 'woocommerce-wcsl' ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<button type="button" class="button button-primary woocommerce-shipping-label-option-button" id="woocommerce-shipping-label-option-button"><?php _e( 'Generate Label', 'woocommerce-wcsl' ); ?></button>
		</div>
	<?php
	}

	/**
	 * Meta box for the packages
	 * 
	 * @return type
	 */
	public function shipping_packages_meta_box() { ?>
		<div class="woocommerce-shipping-packages-box">
			<p><?php _e( 'Packages <em>will not</em> be saved until you click Save Order.', 'woocommerce-wcsl' ); ?></p>
			<div class="packages"></div>
			<p>
				<button type="button" class="button" id="woocommerce-shipping-label-add-package-button"><?php _e( 'Add Package', 'woocommerce-wcsl' ); ?></button>
			</p>
		</div>
	<?php	
	}

	/**
	 * Get packages in JSON format
	 * Used for AJAX call
	 * 
	 * @return type
	 */
	public static function get_packages_json() {
		$meta = woocommerce_get_order_item_meta( $_REQUEST['post'], '_wcsl_packages', true  );

		die( json_encode( $meta ) );
	}

	/**
	 * Returns array of packages
	 * 
	 * @return type
	 */
	public function get_packages( $id = '' ) {
		global $post;

		return woocommerce_get_order_item_meta( $id, '_wcsl_packages', true );
	}

	/**
	 * Save packages when Save Order is clicked
	 * 
	 * @return type
	 */
	public function save_packages() {
		global $post;

		// Are we saving an order?
		if( get_post_type( $post->ID ) != 'shop_order' )
			return;

		// Determine count from package weight
		$count    = count( $_POST['wcsl_option_package_weight'] );
		$packages = array();

		for( $i = 0; $i < $count; $i++ ) {
			$packages[] = array(
				'weight' => $_POST['wcsl_option_package_weight'][$i],
				'length' => $_POST['wcsl_option_package_dimensions_length'][$i],
				'width' => $_POST['wcsl_option_package_dimensions_width'][$i],
				'height' => $_POST['wcsl_option_package_dimensions_height'][$i],
				'signature' => $_POST['wcsl_option_package_signature_required'][$i]
			);
		}

		woocommerce_update_order_item_meta( $post->ID, '_wcsl_packages', $packages );
	}

	public function admin_scripts( $hook ) {
		global $post;

		if( 'post.php' != $hook ) return;

		wp_enqueue_style( 'woocommerce-shipping-labels-css', plugins_url( 'css/woocommerce-shipping-labels.css', WC_SHIPPING_LABELS_DIR ) );

		wp_register_script( 'woocommerce-shipping-labels-js', plugins_url( 'js/woocommerce-shipping-labels.js', WC_SHIPPING_LABELS_DIR ), array( 'jquery' ), '1.0', true );

		wp_localize_script( 'woocommerce-shipping-labels-js', 'wcsl_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'post_id' => $post->ID ) );

		wp_enqueue_script( 'woocommerce-shipping-labels-js' );
	}

	public function order_address( \WC_ORDER $order ) {
		return array(
			'address_1' => $order->shipping_address_1,
			'address_2' => $order->shipping_address_2,
			'city' => $order->shipping_city,
			'state' => $order->shipping_state,
			'postcode' => $order->shipping_postcode,
			'country' => $order->shipping_country );
	}

	public function generateLabel() {
		$orderID = $_POST['orderID'];
		$carrier = $_POST['carrier'];

		if( ! function_exists( 'openssl_encrypt' ) ) {
			return WC_Shipping_Labels_Error( 'PHP extension <a href="http://www.php.net/manual/en/book.openssl.php" target="_blank">OpenSSL</a> must be installed to use this plugin.' );
		}

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
				return WC_Shipping_Labels_Error( 'The specified carrier object does not exist.' );
			}

			// If all is well, grab the carrier object
			$carrierObj = $carrier['object'];
		}

		// Order details
		$order = new \WC_Order( $orderID );

		// Verify shipping details are set
		if( ! $order->get_shipping_address() ) {
			return WC_Shipping_Labels_Error( 'Shipping details required.' );
		}

		$label = new WC_Shipping( $order, $carrier['slug'], $_POST['service_code'] );
		$label->createShipment();
		$label->createPackage();

		print '<div class="wc_ups_labels">' . $label->createLabel() . '</div>';
		exit;
	}

}