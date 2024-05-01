<?php
/**
 * Plugin Name:         GravityView - Dashboard Views
 * Plugin URI:          https://www.gravitykit.com/products/dashboard-views/
 * Description:         Display Views in the WordPress Dashboard.
 * Version:             1.1-beta
 * Author:              GravityKit
 * Author URI:          https://www.gravitykit.com
 * Text Domain:         gk-gravityview-dashboard-views
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.en.html
 */

require_once __DIR__ . '/vendor/autoload.php';

define( 'GV_DASHBOARD_VIEWS_VERSION', '1.1-beta' );

add_action( 'plugins_loaded', 'gv_extension_dashboard_views_load', 100 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 *
 * @return void
 */
function gv_extension_dashboard_views_load() {
	if ( ! class_exists( 'GFAPI' ) || ! function_exists( 'gravityview' ) ) {
		return;
	}

	( new GravityKit\GravityView\DashboardViews\Plugin() )->add_hooks();
}
