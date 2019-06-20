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

	//handle single product page display
	add_filter( 'the_content', array( $this, 'filter_post_type_content' ) );

	add_shortcode( 'paypal_for_digital_goods', array( $this, 'shortcode_paypal_for_digital_goods' ) );
	add_shortcode( 'ppdg_checkout', array( $this, 'shortcode_ppdg_checkout' ) );

	add_shortcode( 'paypal_express_checkout', array( $this, 'shortcode_paypal_express_checkout' ) );

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

    public static function filter_post_type_content( $content ) {
	global $post;
	if ( isset( $post ) ) {
	    if ( $post->post_type === PPECProducts::$products_slug ) {//Handle the content for product type post
		return do_shortcode( '[paypal_express_checkout product_id="' . $post->ID . '" is_post_tpl="1" in_the_loop="' . +in_the_loop() . '"]' );
	    }
	}
	return $content;
    }

    private function show_err_msg( $msg ) {
	return sprintf( '<div class="ppec-error-msg" style="color: red;">%s</div>', $msg );
    }

    function shortcode_paypal_express_checkout( $atts ) {
	if ( empty( $atts[ 'product_id' ] ) ) {
	    $error_msg	 = __( "Error: product ID is invalid.", 'paypal-express-checkout' );
	    $err		 = $this->show_err_msg( $error_msg );
	    return $err;
	}
	$post_id = intval( $atts[ 'product_id' ] );
	$post	 = get_post( $post_id );
	if ( ! $post || get_post_type( $post_id ) !== PPECProducts::$products_slug ) {
	    $error_msg	 = sprintf( __( "Can't find product with ID %s", 'paypal-express-checkout' ), $post_id );
	    $err		 = $this->show_err_msg( $error_msg );
	    return $err;
	}

	$title		 = get_the_title( $post_id );
	$price		 = get_post_meta( $post_id, 'ppec_product_price', true );
	$quantity	 = get_post_meta( $post_id, 'ppec_product_quantity', true );
	$custom_quantity = get_post_meta( $post_id, 'ppec_product_custom_quantity', true );
	$url		 = get_post_meta( $post_id, 'ppec_product_upload', true );
	$content	 = get_the_content( null, false, $post_id );
	$sc		 = sprintf( '[paypal_for_digital_goods name="%s" price="%s" quantity="%d" custom_quantity="%d" url="%s"]%s[/paypal_for_digital_goods]', $title, $price, $quantity, $custom_quantity, $url, $content );
	$output		 = do_shortcode( $sc );
	return $output;
    }

    function shortcode_paypal_for_digital_goods( $atts, $content = "" ) {

	extract( shortcode_atts( array(
	    'name'			 => 'Item Name',
	    'price'			 => '0',
	    'quantity'		 => 1,
	    'url'			 => '',
	    'custom_quantity'	 => 0,
	    'currency'		 => $this->ppdg->get_setting( 'currency_code' ),
	    'btn_shape'		 => $this->ppdg->get_setting( 'btn_shape' ) !== false ? $this->ppdg->get_setting( 'btn_shape' ) : 'pill',
	    'btn_type'		 => $this->ppdg->get_setting( 'btn_type' ) !== false ? $this->ppdg->get_setting( 'btn_type' ) : 'checkout',
	    'btn_height'		 => $this->ppdg->get_setting( 'btn_height' ) !== false ? $this->ppdg->get_setting( 'btn_height' ) : 'small',
	    'btn_width'		 => $this->ppdg->get_setting( 'btn_width' ) !== false ? $this->ppdg->get_setting( 'btn_width' ) : 0,
	    'btn_layout'		 => $this->ppdg->get_setting( 'btn_layout' ) !== false ? $this->ppdg->get_setting( 'btn_layout' ) : 'horizontal',
	    'btn_color'		 => $this->ppdg->get_setting( 'btn_color' ) !== false ? $this->ppdg->get_setting( 'btn_color' ) : 'gold',
	), $atts ) );
	if ( empty( $url ) ) {
	    $err_msg = __( "Please specify a digital url for your product", 'paypal-express-checkout' );
	    $err	 = $this->show_err_msg( $err_msg );
	    return $err;
	}
	$url			 = base64_encode( $url );
	$button_id		 = 'paypal_button_' . count( self::$payment_buttons );
	self::$payment_buttons[] = $button_id;

	$quantity = empty( $quantity ) ? 1 : $quantity;

	$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); //Create key using the item name.

	$trans_data = array(
	    'price'			 => $price,
	    'currency'		 => $currency,
	    'quantity'		 => $quantity,
	    'url'			 => $url,
	    'custom_quantity'	 => $custom_quantity,
	);

	set_transient( $trans_name, $trans_data, 2 * 3600 );

	$is_live = $this->ppdg->get_setting( 'is_live' );

	if ( $is_live ) {
	    $env		 = 'production';
	    $client_id	 = $this->ppdg->get_setting( 'live_client_id' );
	} else {
	    $env		 = 'sandbox';
	    $client_id	 = $this->ppdg->get_setting( 'sandbox_client_id' );
	}

	if ( empty( $client_id ) ) {
	    $err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'paypal-express-checkout' ), $env );
	    $err	 = $this->show_err_msg( $err_msg );
	    return $err;
	}

	$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );

	if ( isset( $btn_sizes[ $btn_height ] ) ) {
	    $btn_height = $btn_sizes[ $btn_height ];
	} else {
	    $btn_height = 25;
	}

	$output	 = '';
	$output	 .= '<div style="position: relative;"><div class="wp-ppec-overlay" style="z-index: 10000;display: none;background-color: white;width: 100%;opacity: 0.7;height: 100%;position: absolute;top: 0;left: 0;" data-ppce-button-id="' . $button_id . '"></div>';

	if ( count( self::$payment_buttons ) <= 1 ) {
	    // insert the below only once on a page
	    ob_start();
	    $args			 = array();
	    $args[ 'client-id' ]	 = $client_id;
	    $args[ 'intent' ]	 = 'capture';
	    $disabled_funding	 = $this->ppdg->get_setting( 'disabled_funding' );
	    if ( ! empty( $disabled_funding ) ) {
		$arg = '';
		foreach ( $disabled_funding as $funding ) {
		    $arg .= $funding . ',';
		}
		$arg				 = rtrim( $arg, ',' );
		$args[ 'disable-funding' ]	 = $arg;
	    }
	    //check if cards aren't disabled globally first
	    if ( ! in_array( 'card', $disabled_funding ) ) {
		$disabled_cards = $this->ppdg->get_setting( 'disabled_cards' );
		if ( ! empty( $disabled_cards ) ) {
		    $arg = '';
		    foreach ( $disabled_cards as $card ) {
			$arg .= $card . ',';
		    }
		    $arg			 = rtrim( $arg, ',' );
		    $args[ 'disable-card' ]	 = $arg;
		}
	    }
	    $script_url	 = add_query_arg( $args, 'https://www.paypal.com/sdk/js' );
	    printf( '<script src="%s"></script>', $script_url );
	    ?>
	    <div id="wp-ppdg-dialog-message" title="">
	        <p id="wp-ppdg-dialog-msg"></p>
	    </div>
	    <script>
	        function wp_ppdg_process_payment(payment, buttonId) {
	    	//	    	console.log("payment details:");
	    	//	    	console.log(payment);
	    	jQuery.post("<?php echo get_admin_url(); ?>admin-ajax.php", {action: "wp_ppdg_process_payment", wp_ppdg_payment: payment})
	    		.done(function (data) {
	    		    var ret = true;
	    		    try {
	    			var res = JSON.parse(data);
	    			dlgTitle = res.title;
	    			dlgMsg = res.msg;
	    		    } catch (e) {
	    			dlgTitle = "<?php _e( 'Error occurred', 'paypal-express-checkout' ); ?>";
	    			dlgMsg = data;
	    			ret = false;
	    		    }
	    		    jQuery('div#wp-ppdg-dialog-message').attr('title', dlgTitle);
	    		    jQuery('p#wp-ppdg-dialog-msg').html(dlgMsg);
	    		    jQuery("#wp-ppdg-dialog-message").dialog({
	    			modal: true,
	    			width: 'auto',
	    			draggable: false,
	    			resizable: false,
	    			buttons: {
	    			    Ok: function () {
	    				jQuery(this).dialog("close");
	    			    }
	    			}
	    		    });
	    		    jQuery('div.wp-ppec-overlay[data-ppce-button-id="' + buttonId + '"]').hide();
	    		    return ret;
	    		});
	        }
	    </script>
	    <?php
	    $output		 .= ob_get_clean();
	}

	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style( 'wp-ppdg-jquery-ui-style' );

	ob_start();

	//output content if needed

	global $wp_embed;
	if ( isset( $wp_embed ) && is_object( $wp_embed ) ) {
	    if ( method_exists( $wp_embed, 'autoembed' ) ) {
		$content = $wp_embed->autoembed( $content );
	    }
	    if ( method_exists( $wp_embed, 'run_shortcode' ) ) {
		$content = $wp_embed->run_shortcode( $content );
	    }
	}
	$content = wpautop( do_shortcode( $content ) );
	$output	 .= $content;

	$output	 .= sprintf( '<div id = "%s"%s data-ppec-custom-quantity = "%d"></div>', $button_id, $btn_width ? 'style="width: ' . $btn_width . 'px"' : '', $custom_quantity );
	?>
	<script>
	    var ppecStyleOpts = {
		height: <?php echo $btn_height; ?>,
		shape: '<?php echo $btn_shape; ?>',
		label: '<?php echo $btn_type; ?>',
		color: '<?php echo $btn_color; ?>',
		layout: '<?php echo $btn_layout; ?>'
	    };
	    if (ppecStyleOpts.layout === 'horizontal') {
		ppecStyleOpts.tagline = false;
	    }
	    paypal.Buttons({
		env: '<?php echo $env; ?>',
		client: {
	<?php echo $env; ?>: '<?php echo $client_id; ?>',
		},
		style: ppecStyleOpts,
		commit: true,
		createOrder: function (data, actions) {
		    return actions.order.create({
			purchase_units: [{
				amount: {
				    value: '<?php echo $price * $quantity; ?>',
				    currency_code: '<?php echo $currency; ?>',
				    breakdown: {
					item_total: {
					    currency_code: '<?php echo $currency; ?>',
					    value: '<?php echo $price * $quantity; ?>'
					}
				    }
				},
				items: [{
					name: '<?php echo esc_js( $name ); ?>',
					quantity: '<?php echo $quantity; ?>',
					unit_amount: {
					    value: '<?php echo $price; ?>',
					    currency_code: '<?php echo $currency; ?>'
					},
				    }]
			    }]
		    });
		},
		onApprove: function (data, actions) {
		    var buttonId = '<?php echo $button_id; ?>';
		    jQuery('div.wp-ppec-overlay[data-ppce-button-id="' + buttonId + '"]').show();
		    return actions.order.capture().then(function (details) {
			console.log(details);
			wp_ppdg_process_payment(details, buttonId);
		    });
		},
		payment: function (data, actions) {
		    var buttonId = '<?php echo $button_id; ?>';
		    return actions.order.create({
			payment: {
			    intent: 'sale',
			    transactions: [
				{
				    amount: {total: '<?php echo $price * $quantity; ?>', currency: '<?php echo $currency; ?>'},
				    description: '<?php echo sprintf( __( 'Payment for %s' ), esc_js( $name ) ); ?>',
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
	    }).render('#<?php echo $button_id; ?>');
	</script>
	<?php
	$output	 .= ob_get_clean();
	$output	 .= '</div></div>';
	return $output;
    }

    public function shortcode_ppdg_checkout() {

	return '';
    }

}
