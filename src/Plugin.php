<?php

namespace GravityKit\GravityView\DashboardViews;

use Exception;
use GravityKitFoundation;
use GravityView_Field_Notes;
use GravityView_Fields;
use GV\Plugin_Settings as GravityViewPluginSettings;
use WP_Post;

class Plugin {
	const UI_ASSETS_PREFIX = 'dashboard-view';

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'set_request' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_ui_assets' ] );

		new View();
		new FoundationSettings();
		new ViewSettings();
		new AdminMenu();

		new Integrations\GravityFlow();
	}

	/**
	 * Sets the current request to the admin request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function set_request() {
		try {
			$dashboard_request = new Request();

			if ( ! $dashboard_request->is_dashboard_view() ) {
				return;
			}

			gravityview()->request = $dashboard_request;
		} catch ( Exception $exception ) {
			gravityview()->log->debug( 'Could not set the Dashboard View request.', [ 'data' => $exception ] );
		}
	}

	/**
	 * Enqueues UI assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_ui_assets() {
		global $wp_filter;
		global $post;

		if ( $post instanceof WP_Post && 'gravityview' === $post->post_type && 'edit' === $post->filter ) {
			$asset_prefix = self::UI_ASSETS_PREFIX . '-editor';

			$this->enqueue_scripts(
				[
					$asset_prefix => "build/js/{$asset_prefix}.min.js",
				]
			);

			$this->enqueue_styles(
				[
					$asset_prefix => "build/css/{$asset_prefix}.min.css",
				]
			);

			return;
		}

		if ( ! View::is_dashboard_view() ) {
			return;
		}

		$this->enqueue_view_stylesheet();

		$this->enqueue_scripts(
			[
				self::UI_ASSETS_PREFIX => 'build/js/' . self::UI_ASSETS_PREFIX . '.min.js',
			]
		);

		// Entry Approval assets.
		$approval_field = GravityView_Fields::get_instance( 'entry_approval' );
		$approval_field->register_scripts_and_styles();

		// Field Notes assets.
		$enqueue_scripts = $wp_filter['wp_enqueue_scripts'];
		if ( ! $enqueue_scripts ) {
			return;
		}

		// @todo Do this the getInstance() way or something, or convert the method to static.
		if ( $enqueue_scripts->callbacks['10'] ) {
			foreach ( $enqueue_scripts->callbacks['10'] as $id => $callback ) {
				if ( strpos( $id, 'register_scripts' ) && ( $callback['function'][0] ?? '' ) instanceof GravityView_Field_Notes ) {
					call_user_func( $callback['function'] );
				}
			}
		}
	}

	/**
	 * Helper method that enqueues multiple scripts at once.
	 *
	 * @since TBD
	 *
	 * @param array[] $scripts The scripts to enqueue.
	 *
	 * @return void
	 */
	private function enqueue_scripts( $scripts ) {
		foreach ( $scripts as $handle => $script ) {
			if ( ! file_exists( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $script ) ) {
				do_action( 'gravityview_log_warning', "Dashboard Views script {$script} does not exist." );
			} else {
				wp_enqueue_script(
					$handle,
					plugins_url( $script, GV_DASHBOARD_VIEWS_PLUGIN_FILE ),
					[],
					filemtime( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $script ),
					false
				);
			}
		}
	}

	/**
	 * Helper method that enqueues multiple styles at once.
	 *
	 * @since TBD
	 *
	 * @param array[] $styles The scripts to enqueue.
	 *
	 * @return void
	 */
	private function enqueue_styles( $styles ) {
		foreach ( $styles as $handle => $style ) {
			if ( ! file_exists( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $style ) ) {
				do_action( 'gravityview_log_warning', "Dashboard Views style {$style} does not exist." );
			} else {
				wp_enqueue_style(
					$handle,
					plugins_url( $style, GV_DASHBOARD_VIEWS_PLUGIN_FILE ),
					[],
					filemtime( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $style )
				);
			}
		}
	}

	/**
	 * Enqueues the View stylesheet that's configured in GravityView settings.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_view_stylesheet() {
		if ( ! class_exists( 'GravityKitFoundation' ) ) {
			return;
		}

		$gravityview_settings = GravityKitFoundation::settings()->get_plugin_settings( GravityViewPluginSettings::SETTINGS_PLUGIN_ID );

		$stylesheet_prefix = self::UI_ASSETS_PREFIX;

		$unload_wp_styles = function () {
			// Unload unnecessary WP styles that may cause interference.
			foreach ( [ 'forms', 'buttons' ] as $style ) {
				wp_deregister_style( $style );
				wp_register_style( $style, 'false' ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			}
		};

		if ( empty( $gravityview_settings['dashboard_views_stylesheet'] ) || 'unstyled' === $gravityview_settings['dashboard_views_stylesheet'] ) {
			return;
		}

		if ( 'custom' === ( $gravityview_settings['dashboard_views_stylesheet'] ?? '' ) && ! empty( $gravityview_settings['dashboard_views_stylesheet_custom'] ) ) {
			wp_enqueue_style(
				$stylesheet_prefix,
				$gravityview_settings['dashboard_views_stylesheet_custom'],
				[],
				GV_DASHBOARD_VIEWS_VERSION
			);

			$unload_wp_styles();

			return;
		}

		$stylesheet = "build/css/{$stylesheet_prefix}-{$gravityview_settings['dashboard_views_stylesheet']}.min.css";

		if ( ! file_exists( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $stylesheet ) ) {
			do_action( 'gravityview_log_warning', "Dashboard Views stylesheet {$stylesheet} does not exist." );

			return;
		}

		wp_enqueue_style(
			$stylesheet_prefix,
			plugins_url( $stylesheet, GV_DASHBOARD_VIEWS_PLUGIN_FILE ),
			[],
			filemtime( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $stylesheet )
		);

		$unload_wp_styles();
	}

	/**
	 * Returns the base URL for the Dashboard View.
	 *
	 * @since TBD
	 *
	 * @return string The base URL for the Dashboard View.
	 */
	public static function get_base_url() {
		$url = admin_url( 'admin.php' );

		$page = $_REQUEST['page'] ?? false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $page ? add_query_arg( [ 'page' => $page ], $url ) : $url;
	}
}
