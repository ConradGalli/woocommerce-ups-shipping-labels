<?php

add_action( 'wp_ajax_my_action', 'my_action_callback' );

function my_action_callback() {
	global $wpdb; // this is how you get access to the database

	$whatever = intval( $_POST['whatever'] );

	$whatever += 10;

        echo $whatever;

	die(); // this is required to return a proper result
}

add_action( 'admin_footer', 'my_action_javascript' );

function my_action_javascript() {
?>
<script type="text/javascript" >

(function($) {


	function getPackages() {


		var data = {
			action: 'my_action',
			whatever: 1234
		};

		$.post(ajaxurl, data, function(response) {
			//alert('Got this from the server: ' + response);
		});
	}

	getPackages();

})(jQuery);

</script>
<?php
}