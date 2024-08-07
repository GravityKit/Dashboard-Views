<?php

namespace GravityKit\GravityView\DashboardViews;

use Exception;
use GravityKitFoundation;
use GravityView_Field_Notes;
use GravityView_Fields;
use GravityView_Lightbox_Provider_FancyBox;
use GV\Plugin_Settings as GravityViewPluginSettings;
use WP_Post;

/**
 * The main Dashboard Views plugin class that initializes the required components, loads assets, etc.
 *
 * @since 2.0.0
 */
class Plugin {
	const UI_ASSETS_PREFIX = 'dashboard-view';

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'set_request' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_ui_assets' ] );

		new View();
		new FoundationSettings();
		new ViewSettings();
		new AdminMenu();

		new Integrations\DataTables();
		new Integrations\GravityMaps();
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
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function enqueue_ui_assets() {
		global $post;

		if ( $post instanceof WP_Post && 'gravityview' === $post->post_type && 'edit' === $post->filter ) {
			$asset_prefix = self::UI_ASSETS_PREFIX . '-editor';

			$this->enqueue_scripts(
				[
					$asset_prefix => "build/js/{$asset_prefix}.min.js",
				]
			);

			wp_localize_script( $asset_prefix, 'gkDashboardViews', apply_filters( 'gk/gravityview/dashboard-views/view/editor/localization', [] ) );

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

		// Lightbox assets.
		do_action( 'gravityview/lightbox/provider' );

		$lightbox = new GravityView_Lightbox_Provider_FancyBox();

		$lightbox->enqueue_scripts();
		$lightbox->enqueue_styles();
	}

	/**
	 * Helper method that enqueues multiple scripts at once.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
}
