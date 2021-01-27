<?php
/**
 * Plugin Name: GravityView - Dashboard Views
 * Description: Display Views in the WordPress Dashboard.
 * Version: 1.0
 * Author: GravityView
 * Author URI: https://gravityview.co
 * Text Domain: gravityview-dashboard-views
 * Domain Path: /languages/
 */

/**
 * The plugin version.
 */
define( 'GV_DASHBOARD_VIEWS_VERSION', '1.0' );

add_action( 'plugins_loaded', 'gv_extension_dashboard_views_load', 100 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_dashboard_views_load() {

	if ( ! class_exists( 'GFAPI' ) ) {
		return;
	}

	if ( ! class_exists( 'GravityView_Extension' ) ) {

		if ( class_exists( 'GravityView_Plugin' ) && is_callable( array( 'GravityView_Plugin', 'include_extension_framework' ) ) ) {
			GravityView_Plugin::include_extension_framework();
		} else {
			// We prefer to use the one bundled with GravityView, but if it doesn't exist, go here.
			include_once plugin_dir_path( __FILE__ ) . 'lib/class-gravityview-extension.php';
		}
	}

	if ( ! class_exists( '\GV\Extension' ) ) {
		return;
	}

	include_once plugin_dir_path( __FILE__ ) . 'class-gravityview-dashboard-views.php';
}
