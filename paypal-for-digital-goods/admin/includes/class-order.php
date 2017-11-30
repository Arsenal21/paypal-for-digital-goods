<?php

class OrdersPPDG {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	function __construct()
	{
		$this->ppdg = PPDG::get_instance();
		$this->text_domain = $this->ppdg->get_plugin_slug();
	}

	public function register_post_type()
	{
		$text_domain = $this->ppdg->get_plugin_slug();
		$labels = array(
			'name'                => _x( 'Orders', 'Post Type General Name', $text_domain ),
			'singular_name'       => _x( 'Order', 'Post Type Singular Name', $text_domain ),
			'menu_name'           => __( 'Digital Goods Orders', $text_domain ),
			'parent_item_colon'   => __( 'Parent Order:', $text_domain ),
			'all_items'           => __( 'All Orders', $text_domain ),
			'view_item'           => __( 'View Order', $text_domain ),
			'add_new_item'        => __( 'Add New Order', $text_domain ),
			'add_new'             => __( 'Add New', $text_domain ),
			'edit_item'           => __( 'Edit Order', $text_domain ),
			'update_item'         => __( 'Update Order', $text_domain ),
			'search_items'        => __( 'Search Order', $text_domain ),
			'not_found'           => __( 'Not found', $text_domain ),
			'not_found_in_trash'  => __( 'Not found in Trash', $text_domain ),
		);
		$args = array(
			'label'               => __( 'orders', $text_domain ),
			'description'         => __( 'PPDG Orders', $text_domain ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields', ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 80,
			'menu_icon'           => 'dashicons-clipboard',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'capabilities' => array(
   				'create_posts' => false, // Removes support for the "Add New" function
  			),
  			'map_meta_cap' => true,
		);

		register_post_type( 'ppdgorder', $args );
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
	/**
	 * Receive Response of GetExpressCheckout and ConfirmPayment function returned data.
	 * Returns the order ID.
	 *
	 * @since     1.0.0
	 *
	 * @return    Numeric    Post or Order ID.
	 */
	public function insert($EC_details, $ConfirmPayment_details)
	{
		$post = array();
		$post['post_title'] = $ConfirmPayment_details['L_QTY0'].' '.$EC_details['L_NAME0'].' - '.$EC_details['ACK'];
		$post['post_status'] = 'pending';

		$ack = strtoupper($ConfirmPayment_details["ACK"]);
		$output = '';

		// Add error info in case of failure
		if( $ack != "SUCCESS" && $ack != "SUCCESSWITHWARNING" ) {

			$ErrorCode = urldecode($ConfirmPayment_details["L_ERRORCODE0"]);
			$ErrorShortMsg = urldecode($ConfirmPayment_details["L_SHORTMESSAGE0"]);
			$ErrorLongMsg = urldecode($ConfirmPayment_details["L_LONGMESSAGE0"]);
			$ErrorSeverityCode = urldecode($ConfirmPayment_details["L_SEVERITYCODE0"]);

			$output .= "<h2>Payment Failure Details</h2>"."\n";
			$output .= __("Payment API call failed. ");
			$output .= __("Detailed Error Message: ") . $ErrorLongMsg;
			$output .= __("Short Error Message: ") . $ErrorShortMsg;
			$output .= __("Error Code: ") . $ErrorCode;
			$output .= __("Error Severity Code: ") . $ErrorSeverityCode;
			$output .= "\n\n";
		}

		$output .= __("<h2>Order Details</h2>")."\n";
		$output .= __("Order Time: ").date("F j, Y, g:i a",strtotime($EC_details['TIMESTAMP']))."\n";
		$output .= __("Transaction ID: ").$ConfirmPayment_details['PAYMENTINFO_0_TRANSACTIONID']."\n";
		$output .= "--------------------------------"."\n";
		$output .= __("Product Name: ").$EC_details['L_PAYMENTREQUEST_0_NAME0']."\n";
		$output .= __("Quantity:"). $EC_details['L_PAYMENTREQUEST_0_QTY0']."\n";
		$output .= __("Amount:"). $EC_details['L_PAYMENTREQUEST_0_AMT0'].' '.$EC_details['CURRENCYCODE']."\n";
		$output .= "--------------------------------"."\n";
		$output .= __("Total Amount:"). $EC_details['AMT'].' '.$EC_details['CURRENCYCODE']."\n";

		
		$output .= "\n\n";

		$output .= __("<h2>Customer Details</h2>")."\n";
		$output .= __("Name: ").$EC_details['FIRSTNAME'].' '.$EC_details['LASTNAME']."\n";
		$output .= __("Payer ID: ").$EC_details['PAYERID']."\n";
		$output .= __("Payer Status: ").$EC_details['PAYERSTATUS']."\n";
		$output .= __("E-Mail Address: ").$EC_details['EMAIL']."\n";
		$output .= __("Country Code: ").$EC_details['COUNTRYCODE']."\n";

		$post['post_content'] = $output;//..var_export($ConfirmPayment_details, true)'<br/><br/>'.var_export($EC_details, true);
		$post['post_type'] = 'ppdgorder';

		# code...
		return wp_insert_post( $post );
	}

}
?>
