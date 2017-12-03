<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 */
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to access this settings page.' );
}
?>

<style>
    #wp-ppdg-preview-container {
	margin-top: 10px; width: 500px; height: 100px; padding: 10px;
	position: relative;
    }
    #wp-ppdg-preview-protect {
	width: 100%;
	height: 100%;
	position: absolute;
	top: 0;
	left: 0;
	z-index: 1000;
    }
    .wp-ppdg-button-style {
	min-width: 150px;
    }
</style>

<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <form method="post" action="options.php">

	<?php settings_fields( 'ppdg-settings-group' ); ?>

	<?php do_settings_sections( 'paypal-for-digital-goods' ); ?>
	<div style="background-color: white; padding: 10px; border: 1px dashed silver;">
	    To get your client ID or set up a new one:<br/>
	    <ol>
		<li>Navigate to <a href="https://developer.paypal.com/developer/applications/" target="_blank">My Apps &amp; Credentials</a> and click <strong>Log into Dashboard</strong> in the top, right corner of the page.</li>
		<li>Scroll down to <strong>REST API Apps</strong> and click the name of your app to see the app's details. If you don't have any apps, create one now:<br>
		    a. Click <strong>Create App</strong>.<br>
		    b. In <strong>App Name</strong>, enter a name and then click <strong>Create App</strong> again. The app is created and your client ID is displayed.</li>
		<li>Click the <strong>Sandbox</strong> / <strong>Live</strong> toggle to display and copy the client ID for each environment.</li>
	    </ol>
	</div>

	<script src="https://www.paypalobjects.com/api/checkout.js"></script>

	<script>
	    var wp_ppdg = {
		btn_size: 'small',
		btn_color: 'gold',
		btn_type: 'checkout',
		btn_shape: 'pill'
	    };
	    function wp_ppdg_render_preview() {
		jQuery('#paypal-button-container').html('');
		paypal.Button.render({

		    env: 'sandbox',

		    style: {
			tagline: false,
			branding: true,
			shape: wp_ppdg.btn_shape,
			label: wp_ppdg.btn_type,
			size: wp_ppdg.btn_size,
			color: wp_ppdg.btn_color
		    },

		    client: {
			sandbox: '123',
		    },

		    payment: function (data, actions) {

		    },

		    onAuthorize: function (data, actions) {

		    }

		}, '#paypal-button-container');
	    }

	    jQuery('.wp-ppdg-button-style').change(function () {
		wp_ppdg.btn_size = jQuery('#wp-ppdg-btn_size').val();
		wp_ppdg.btn_color = jQuery('#wp-ppdg-btn_color').val();
		wp_ppdg.btn_type = jQuery('#wp-ppdg-btn_type').val();
		wp_ppdg.btn_shape = jQuery('#wp-ppdg-btn_shape').val();
		wp_ppdg_render_preview();
	    });
	    jQuery('#wp-ppdg-btn_size').change();

	</script>
	<?php submit_button(); ?>
    </form>
</div>
