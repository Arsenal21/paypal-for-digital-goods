<?php

class PPDGShortcode {

    var $ppdg	 = null;
    var $paypaldg = null;

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance		 = null;
    protected static $payment_buttons	 = array();

    function __construct() {
	$this->ppdg = PPDG::get_instance();

	add_shortcode( 'paypal_for_digital_goods', array( &$this, 'shortcode_paypal_for_digital_goods' ) );
	add_shortcode( 'ppdg_checkout', array( &$this, 'shortcode_ppdg_checkout' ) );
	if ( ! is_admin() ) {
	    add_filter( 'widget_text', 'do_shortcode' );
	}
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

	// If the single instance hasn't been set, set it now.
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}

	return self::$instance;
    }

    function shortcode_paypal_for_digital_goods( $atts, $content = "" ) {

	extract( shortcode_atts( array(
	    'name'		 => 'Item Name',
	    'price'		 => '0',
	    'quantity'	 => '1',
	    'url'		 => '',
	    'currency'	 => $this->ppdg->get_setting( 'currency_code' ),
	    'btn_shape'	 => $this->ppdg->get_setting( 'btn_shape' ) !== false ? $this->ppdg->get_setting( 'btn_shape' ) : 'pill',
	    'btn_type'	 => $this->ppdg->get_setting( 'btn_type' ) !== false ? $this->ppdg->get_setting( 'btn_type' ) : 'checkout',
	    'btn_size'	 => $this->ppdg->get_setting( 'btn_size' ) !== false ? $this->ppdg->get_setting( 'btn_size' ) : 'small',
	    'btn_color'	 => $this->ppdg->get_setting( 'btn_color' ) !== false ? $this->ppdg->get_setting( 'btn_color' ) : 'gold',
	), $atts ) );
	if ( empty( $url ) ) {
	    return '<div style="color:red;">Please specify a digital url for your product </div>';
	}
	$url			 = base64_encode( $url );
	$button_id		 = 'paypal_button_' . count( self::$payment_buttons );
	self::$payment_buttons[] = $button_id;

	$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); //Create key using the item name.

	set_transient( $trans_name . '-price', $price, 2 * 3600 ); //Save the price for this item for 2 hours.
	set_transient( $trans_name . '-currency', $currency, 2 * 3600 );
	set_transient( $trans_name . '-quantity', $quantity, 2 * 3600 );
	set_transient( $trans_name . '-url', $url, 2 * 3600 );

	$is_live = $this->ppdg->get_setting( 'is_live' );

	if ( $is_live ) {
	    $env		 = 'production';
	    $client_id	 = $this->ppdg->get_setting( 'live_client_id' );
	} else {
	    $env		 = 'sandbox';
	    $client_id	 = $this->ppdg->get_setting( 'sandbox_client_id' );
	}

	if ( empty( $client_id ) ) {
	    return '<div style="color:red;">Please enter ' . $env . ' Client ID in the settings.</div>';
	}

	$output = '';

	if ( count( self::$payment_buttons ) <= 1 ) {
	    // insert the below only once on a page
	    ob_start();
	    ?>
	    <div id="wp-ppdg-dialog-message" title="">
	        <p id="wp-ppdg-dialog-msg"></p>
	    </div>
	    <script src="https://www.paypalobjects.com/api/checkout.js"></script>
	    <script>
	        function wp_ppdg_process_payment(payment) {
	    	console.log("payment details:");
	    	console.log(payment);
	    	jQuery.post("<?php echo get_admin_url(); ?>admin-ajax.php", {action: "wp_ppdg_process_payment", wp_ppdg_payment: payment})
	    		.done(function (data) {
	    		    var ret = true;
	    		    try {
	    			var res = JSON.parse(data);
	    			dlgTitle = res.title;
	    			dlgMsg = res.msg;
	    		    } catch (e) {
	    			dlgTitle = "Error Occured";
	    			dlgMsg = data;
	    			ret = false;
	    		    }
	    		    jQuery('div#wp-ppdg-dialog-message').attr('title', dlgTitle);
	    		    jQuery('p#wp-ppdg-dialog-msg').html(dlgMsg);
	    		    jQuery("#wp-ppdg-dialog-message").dialog({
	    			modal: true,
	    			buttons: {
	    			    Ok: function () {
	    				jQuery(this).dialog("close");
	    			    }
	    			}
	    		    });
	    		    return ret;
	    		});
	        }
	    </script>
	    <?php
	    $output .= ob_get_clean();
	}

	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style( 'wp-ppdg-jquery-ui-style' );

	ob_start();
	?>
	<div id="<?php echo $button_id; ?>"></div>

	<script>
	    paypal.Button.render({

		env: '<?php echo $env; ?>',
		client: {
	<?php echo $env; ?>: '<?php echo $client_id; ?>',
		},

		style: {
		    tagline: false,
		    branding: true,
		    shape: '<?php echo $btn_shape; ?>',
		    label: '<?php echo $btn_type; ?>',
		    size: '<?php echo $btn_size; ?>',
		    color: '<?php echo $btn_color; ?>'
		},

		commit: true,

		payment: function (data, actions) {
		    return actions.payment.create({
			payment: {
			    intent: 'sale',
			    transactions: [
				{
				    amount: {total: '<?php echo $price * $quantity; ?>', currency: '<?php echo $currency; ?>'},
				    description: 'Payment for <?php echo esc_js( $name ); ?>',
				    item_list: {
					items: [
					    {
						name: '<?php echo esc_js( $name ); ?>',
						quantity: '<?php echo $quantity; ?>',
						price: '<?php echo $price; ?>',
						currency: '<?php echo $currency; ?>'
					    }
					]
				    }
				}
			    ]
			}
		    });
		},
		onAuthorize: function (data, actions) {
		    return actions.payment.execute().then(function (payment) {
			return wp_ppdg_process_payment(payment);
		    });
		},
		onError: function (err) {
		    alert(err);
		}

	    }, '#<?php echo $button_id; ?>');
	</script>
	<?php
	$output .= ob_get_clean();
	return $output;
    }

    public function shortcode_ppdg_checkout() {

	return '';
    }

}
