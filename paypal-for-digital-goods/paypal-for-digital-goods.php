<?php

/**
 * Plugin Name:       PayPal for Digital Goods
 * Description:       This plugin allows you to generate a customizable PayPal payment button that lets user pay instantly in a popup via PayPal.
 * Version:           1.5
 * Author:            Tips and Tricks HQ
 * Author URI:        https://www.tipsandtricks-hq.com/
 * Plugin URI:        https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin
 * Text Domain:       ppdg_locale
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
if ( ! defined( 'ABSPATH' ) )
    exit; //Exit if accessed directly

if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
    if ( session_status() == PHP_SESSION_NONE ) {
	session_start();
    }
} else {
    if ( session_id() == '' ) {
	session_start();
    }
}

/* ----------------------------------------------------------------------------*
 * Public-Facing Functionality
 * ---------------------------------------------------------------------------- */

require_once( plugin_dir_path( __FILE__ ) . 'public/class-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-shortcode-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-paypaldg.php' );
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
