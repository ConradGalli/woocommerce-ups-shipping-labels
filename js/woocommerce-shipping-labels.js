(function($) {

	// Package template
	var package_el = '<div class="package"><header class="hndle">Package</header><div class="inside"><label for="wcsl-option-package-weight">Package Weight</label><input type="text" name="wcsl_option_package_weight[]" id="wcsl-option-package-weight" placeholder="0.00" /><label for="wcsl-option-package-dimensions-length">Package Dimensions</label><input type="text" name="wcsl_option_package_dimensions_length[]" id="wcsl-option-package-dimensions-length" placeholder="L" /> x <input type="text" name="wcsl_option_package_dimensions_width[]" id="wcsl-option-package-dimensions-width" placeholder="W" /> x <input type="text" name="wcsl_option_package_dimensions_height[]" id="wcsl-option-package-dimensions-height" placeholder="H" /><label><input type="checkbox" name="wcsl_option_package_signature_required[]" /> Signature Required?</label></div></div>';
	var packages = $('.packages');


	function woocommerceCreateShippingLabel(e) {
		e.preventDefault();
		
		var carrier = $('#woocommerce-shipping-label-print-option').val();
		var orderID = $('.woocommerce-shipping-label-meta-box').attr('data-order');

		/* UPS */
		var UPS = {
			service_code: $('#wcsl-option-ups-service-code').val()
		};

		var data = {
			action: 'generateLabel',
			orderID: orderID,
			carrier: carrier,
			service_code: UPS.service_code
		};

		$.post(ajaxurl, data, function(response) {
			$('#woocommerce-shipping-label-message-area').html( response );
		});
		return false;
	}

	function togglePackageVisibility(e) {
		e.preventDefault();

		$(this).siblings('.inside').slideToggle();
	}

	function addPackage(e) {
		e.preventDefault();

		packages.append( package_el );
	}

	function getPackages() {
		var data = {
			action: 'wcsl_get_packages',
			post: wcsl_object.post_id
		};

		$.post(wcsl_object.ajaxurl, data, function(response) {
			alert('Got this from the server: ' + response);
		});
	}

	getPackages();


	$('#woocommerce-shipping-label-option-button').on( 'click', woocommerceCreateShippingLabel );

	$('#woocommerce-shipping-label-add-package-button').on( 'click', addPackage );

	// Toggle package details
	$('.package .hndle').on('click', togglePackageVisibility );

})(jQuery);