(function($) {

	var package_el = $('#wcsl-package-template').html()

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

		$('.packages').hide().append( package_el ).fadeIn();
	}


	$('#woocommerce-shipping-label-option-button').on( 'click', woocommerceCreateShippingLabel );

	$('#woocommerce-shipping-label-add-package-button').on( 'click', addPackage );

	// Toggle package details
	$('.package .hndle').on('click', togglePackageVisibility );

})(jQuery);