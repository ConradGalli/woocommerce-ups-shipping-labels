(function($) {

	function woocommerceCreateShippingLabel(e) {
		e.preventDefault();
		
		var carrier = $('#woocommerce-shipping-label-print-option').val();
		var orderID = $('.woocommerce-shipping-label-meta-box').attr('data-order');
		
		var data = {
			action: 'generateLabel',
			orderID: orderID,
			carrier: carrier
		};

		$.post(ajaxurl, data, function(response) {
			$('#woocommerce-shipping-label-message-area').html( response );
		});
		return false;
	}


	$('#woocommerce-shipping-label-option-button').on( 'click', woocommerceCreateShippingLabel );

})(jQuery);