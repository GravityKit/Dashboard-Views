<?php
/**
 * Plugin Name: GravityView - Views in Dashboard
 * Description: Show Views in the WordPress Dashboard
 * Version: develop
 * Author: GravityView
 * Author URI: https://gravityview.co
 * Text Domain: gravityview-adminview
 * Domain Path: /languages/
 */

add_action( 'plugins_loaded', 'gv_extension_adminview_load', 100 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_adminview_load() {

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

	include_once plugin_dir_path( __FILE__ ) . 'class-gravityview-admin-view.php';
}
