<?php

/**
 * Plugin Name:       PayPal for Digital Goods
 * Description:       This plugin allows you to generate a customizable PayPal payment button that lets user pay instantly in a popup via PayPal.
 * Version:           1.6
 * Author:            Tips and Tricks HQ
 * Author URI:        https://www.tipsandtricks-hq.com/
 * Plugin URI:        https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin
 * Text Domain:       ppdg_locale
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
//NEW slug wp_ppec_
//OLD slug - wp_ppdg_

if ( ! defined( 'ABSPATH' ) ) {
    exit; //Exit if accessed directly
}

//PHP session
if ( ! is_admin() || wp_doing_ajax() ) {
    //Only use session for front-end and ajax.
    if ( session_status() == PHP_SESSION_NONE ) {
	session_start();
    }
}

/* ----------------------------------------------------------------------------*
 * Public-Facing Functionality
 * ---------------------------------------------------------------------------- */

require_once( plugin_dir_path( __FILE__ ) . 'public/class-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-shortcode-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/class-order.php' );


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 */

register_activation_hook( __FILE__, array( 'PPDG', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PPDG', 'deactivate' ) );

/*
 */
add_action( 'plugins_loaded', array( 'PPDG', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'PPDGShortcode', 'get_instance' ) );
add_action( 'wp_ajax_wp_ppdg_process_payment', 'wp_ppdg_process_payment' );
add_action( 'wp_ajax_nopriv_wp_ppdg_process_payment', 'wp_ppdg_process_payment' );

/* ----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 * ---------------------------------------------------------------------------- */

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-ppdg-admin.php' );
    add_action( 'plugins_loaded', array( 'PPDG_Admin', 'get_instance' ) );
}

function wp_ppdg_process_payment() {
    if ( ! isset( $_POST[ 'wp_ppdg_payment' ] ) ) {
	//no payment data provided
	echo 'No payment data!';
	wp_die();
    }
    $payment = $_POST[ 'wp_ppdg_payment' ];

    if ( $payment[ 'state' ] !== 'approved' ) {
	//payment is unsuccessful
	echo 'Payment failed! State: ' . $payment[ 'state' ];
	wp_die();
    }

    // get item name
    $item_name	 = $payment[ 'transactions' ][ 0 ][ 'item_list' ][ 'items' ][ 0 ][ 'name' ];
    // let's check if the payment matches transient data
    $trans_name	 = 'wp-ppdg-' . sanitize_title_with_dashes( $item_name );
    $price		 = get_transient( $trans_name . '-price' );
    if ( $price === false ) {
	//no price set
	echo 'No price set in transient!';
	wp_die();
    }
    $quantity	 = get_transient( $trans_name . '-quantity' );
    $currency	 = get_transient( $trans_name . '-currency' );
    $url		 = get_transient( $trans_name . '-url' );

    $amount = $payment[ 'transactions' ][ 0 ][ 'amount' ][ 'total' ];

    //check if amount paid matches price x quantity
    if ( $amount != $price * $quantity ) {
	//payment amount mismatch
	echo 'Payment amount mismatch!';
	wp_die();
    }

    //check if payment currency matches
    if ( $payment[ 'transactions' ][ 0 ][ 'amount' ][ 'currency' ] !== $currency ) {
	//payment currency mismatch
	echo 'Payment currency mismatch!';
	wp_die();
    }

    //if code execution got this far, it means everything is ok with payment
    //let's insert order
    $order = OrdersPPDG::get_instance();

    $order->insert( array(
	'item_name'	 => $item_name,
	'price'		 => $price,
	'quantity'	 => $quantity,
	'amount'	 => $amount,
	'currency'	 => $currency,
	'state'		 => $payment[ 'state' ],
	'id'		 => $payment[ 'id' ],
	'create_time'	 => $payment[ 'create_time' ],
    ), $payment[ 'payer' ] );

    do_action( 'ppdg_payment_completed', $payment );

    $res		 = array();
    $res[ 'title' ]	 = 'Payment Completed';

    $thank_you_msg	 = '<div class="wp_ppdg_thank_you_message"><p>Thank you for your purchase.</p><br /><p>Please <a href="' . base64_decode( $url ) . '">сlick here</a> to download the file.</p></div>';
    $thank_you_msg	 = apply_filters( 'wp_ppdg_thank_you_message', $thank_you_msg );
    $res[ 'msg' ]	 = $thank_you_msg;

    echo json_encode( $res );

    wp_die();
}
