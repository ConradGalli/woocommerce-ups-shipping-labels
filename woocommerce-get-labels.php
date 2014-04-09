<?php
/**
 * Basic example usage of the AWSP Shipping class to create a shipping label(s).
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/19/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

use \Awsp\Ship as Ship;

$config = array();

// require the config file and the autoloader file
define('SHIP_PATH', plugin_dir_path( __FILE__ ) . 'libs/');

require_once 'includes/config.php';
require_once 'libs/Awsp/Ship/LabelResponse.php';
require_once 'libs/Awsp/Ship/Package.php';
require_once 'libs/Awsp/Ship/RateResponse.php';
require_once 'libs/Awsp/Ship/Shipment.php';
require_once 'libs/Awsp/Ship/ShipperInterface.php';
require_once 'libs/Awsp/Ship/Ups.php';

class WC_UPS_Label {

	private $shipmentData,
			$address;

	public $Shipment,
		   $ShipperObj,
		   $service_code,
		   $shipper;

	public function __construct( array $address, $shipper, $service_code ) {
		global $config;

		$this->shipper = $shipper;
		$this->service_code = $service_code;

		// true for production or false for development
		$config['production_status'] = false; 

		// can be 'LB' for pounds or 'KG' for kilograms
		$config['weight_unit'] = 'LB'; 

		// can be 'IN' for inches or 'CM' for centimeters
		$config['dimension_unit'] = 'IN'; 

		// USD for US dollars
		$config['currency_code'] = 'USD'; 

		// if true and if a receiver email address is set, the tracking number will be emailed to the receiver by the shipping vendor
		$config['email_tracking_number_to_receiver'] = true; 

		/**
		 * Information pulled from WooCommerce settings
		 */
		$config['shipper_name'] 		  = get_option( WC_Shipping_Settings::$option_prefix . '_name' ); 
		$config['shipper_attention_name'] = get_option( WC_Shipping_Settings::$option_prefix . '_attention_name' ); 
		$config['shipper_phone'] 		  = get_option( WC_Shipping_Settings::$option_prefix . '_phone' ); 
		$config['shipper_email'] 		  = get_option( WC_Shipping_Settings::$option_prefix . '_email' );
		$config['shipper_address1'] 	  = get_option( WC_Shipping_Settings::$option_prefix . '_address' ); 
		$config['shipper_address2'] 	  = get_option( WC_Shipping_Settings::$option_prefix . '_address_2' );
		$config['shipper_address3'] 	  = get_option( WC_Shipping_Settings::$option_prefix . '_address_3' ); 
		$config['shipper_city'] 		  = get_option( WC_Shipping_Settings::$option_prefix . '_city' );
		$config['shipper_state'] 		  = get_option( WC_Shipping_Settings::$option_prefix . '_state' ); 
		$config['shipper_postal_code']    = get_option( WC_Shipping_Settings::$option_prefix . '_postcode' ); 
		$config['shipper_country_code']   = get_option( WC_Shipping_Settings::$option_prefix . '_country' ); 

		
		$this->shipping_address = $address;

		$this->getShipperData();
		$this->getReceiverData();
	}

	public function getShipperData() {
		global $config;

		//----------------------------------------------------------------------------------------------------------------------

		// UPS shipper configuration settings
		// sign up for credentials at: https://www.ups.com/upsdeveloperkit - Note: Chrome browser does not work for this page.
		$config['ups'] = array();
		$config['ups']['key'] = 'ACCD553AD50FFAC6';
		$config['ups']['user'] = 'dkjensen_';
		$config['ups']['password'] = 'Lolatu.1';
		$config['ups']['account_number'] = '81156R';
		$config['ups']['testing_url'] = 'https://wwwcie.ups.com/webservices';
		$config['ups']['production_url'] = 'https://onlinetools.ups.com/webservices'; 
		// absolute path to the UPS API files relateive to the Ups.php file
		$config['ups']['path_to_api_files'] = SHIP_PATH . 'Awsp/Ship/ups_api_files'; 

		// shipper information - make any necessary overrides
		// note: needs to match information on file with UPS or the API call will fail
		$config['ups']['shipper_name'] = $config['shipper_name']; 
		$config['ups']['shipper_attention_name'] = $config['shipper_attention_name']; 
		$config['ups']['shipper_phone'] = $config['shipper_phone']; 
		$config['ups']['shipper_email'] = $config['shipper_email'];
		$config['ups']['shipper_address1'] = $config['shipper_address1']; 
		$config['ups']['shipper_address2'] = $config['shipper_address2'];
		$config['ups']['shipper_address3'] = $config['shipper_address3']; 
		$config['ups']['shipper_city'] = $config['shipper_city'];
		$config['ups']['shipper_state'] = $config['shipper_state']; 
		$config['ups']['shipper_postal_code'] = $config['shipper_postal_code']; 
		$config['ups']['shipper_country_code'] = $config['shipper_country_code']; 

		/*
		01 - Daily Pickup (default)
		03 - Customer Counter
		06 - One Time Pickup
		07 - On Call Air
		19 - Letter Center
		20 - Air Service Center
		*/
		$config['ups']['pickup_type'] = '01'; 

		/*
		00 - Rates Associated with Shipper Number
		01 - Daily Rates
		04 - Retail Rates
		53 - Standard List Rates
		*/
		$config['ups']['rate_type'] = '00'; 
	}

	public function getReceiverData() {
		// receiver information
		$this->shipmentData['receiver_name'] = 'XYZ Corporation';
		$this->shipmentData['receiver_attention_name'] = 'Attn: Bill';
		$this->shipmentData['receiver_phone'] = '555-123-4567';
		$this->shipmentData['receiver_email'] = '';
		$this->shipmentData['receiver_address1'] = $this->shipping_address['address_1'];
		$this->shipmentData['receiver_address2'] = $this->shipping_address['address_2'];
		$this->shipmentData['receiver_address3'] = null; // not supported by USPS API
		$this->shipmentData['receiver_city'] = $this->shipping_address['city'];
		$this->shipmentData['receiver_state'] = $this->shipping_address['state'];
		$this->shipmentData['receiver_postal_code'] = $this->shipping_address['postcode'];
		$this->shipmentData['receiver_country_code'] = $this->shipping_address['country'];
		$this->shipmentData['receiver_is_residential'] = false; // true or false
	}

	/**
	 * Creates a Shipment object
	 * 
	 * @return type
	 */
	public function createShipment() {
		// create a Shipment object
		try {
		    $this->Shipment = new Ship\Shipment( $this->shipmentData ); 
		}
		// catch any exceptions 
		catch(\Exception $e) {
		    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
		}
	}

	/**
	 * Create 
	 * 
	 * @param type a shipment can have multiple packages
	 * @param type has dimensions of 10 x 6 x 12 inches 
	 * @param type has an insured value of $274.95 and is being sent 
	 * @return type
	 */
	public function createPackage() {
		try {
		    $Package1 = new Ship\Package(
		            24, // weight 
		            array(10, 6, 12), // dimensions
		            array( // options
		                'signature_required' => true, 
		                'insured_amount' => 274.95
		            )
		        );
		    $this->Shipment->addPackage( $Package1 );
		}
		// catch any exceptions 
		catch(\Exception $e) {
		    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
		}

		/*
		// optional - create additional Package(s) and add them to the Shipment
		// note: weight and dimensions can be integers or floats, although UPS alwasy rounds up to the next whole number
		// this package is 11.34 pounds and has dimensions of 14.2 x 16.8 x 26.34 inches
		try {
		    $Package2 = new Ship\Package(11.34, array(14.2, 16.8, 26.34));
		    $Shipment->addPackage($Package2);
		}
		// catch any exceptions 
		catch(\Exception $e) {
		    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
		}
		*/
	}

	// create the shipper object for the appropriate shipping vendor and pass it the shipment and config data
	// using UPS
	public function createShipper() {
		global $config;

		if($this->shipper == 'ups') {
		    $this->ShipperObj = new Ship\Ups( $this->Shipment, $config );
		}
		// unrecognized shipper
		else {
		    throw new \Exception('Unrecognized shipper (' . $this->shipper . ').');
		}
	}

	public function createLabel() {
		$this->createShipper( $this->shipper );

		try{
		    // build parameters array to send to the createLabel method
		    $params = array(
		        'service_code' => $this->service_code
		    );
		    // call the createLabel method - a LabelResponse object will be returned unless there is an exception
		    $Response = $this->ShipperObj->createLabel( $params );
		}
		// display any caught exception messages
		catch(\Exception $e){
		    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');
		}

		foreach( $Response->labels as $label ) {
			if($label['label_file_type'] == 'gif') {
                $output .= '<a href="data:image/gif;base64, ' . $label['label_image'] . '" target="_blank"><img src="data:image/gif;base64, ' . $label['label_image'] . '" /></a>';
            }
		}

		return $output;
	}

}