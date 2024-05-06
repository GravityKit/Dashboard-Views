<?php

namespace GravityKit\GravityView\DashboardViews;

use GV\Plugin_Settings as GravityViewPluginSettings;
use GravityView_Render_Settings;
use WP_Post;
use GravityKitFoundation;

/**
 * View-specific settings.
 */
class ViewSettings {
	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_filter( 'gravityview/metaboxes/default', [ $this, 'add_dashboard_views_settings_tab' ] );
		add_filter( 'gravityview/view/settings/defaults', [ $this, 'add_settings_to_the_dashboards_view_tab' ] );
	}

	/**
	 * Adds the Dashboard Views settings tab to the View editor.
	 *
	 * @since TBD
	 *
	 * @param array $tabs Existing View editor tabs.
	 *
	 * @return array
	 */
	public function add_dashboard_views_settings_tab( $tabs ) {
		$tabs[] = [
			'id'         => 'dashboard_views',
			'title'      => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			'file'       => null,
			'icon-class' => 'dashicons-admin-generic', // @todo Change to a more appropriate icon.
			'callback'   => [ $this, 'render_settings' ],
		];

		return $tabs;
	}

	/**
	 * Add settings to the Dashboard Views tab in the View editor.
	 *
	 * @since TBD
	 *
	 * @param array $settings Existing View settings.
	 *
	 * @return array
	 */
	public function add_settings_to_the_dashboards_view_tab( $settings ) {
		return array_merge(
			$settings,
			[
				'dashboard_views_enable' => [
					'label' => esc_html__( 'Show in Dashboard?', 'gk-gravityview-dashboard-views' ),
					'desc'  => strtr(
						esc_html__( 'This will make the View accessible in the WordPress Dashboard. Visit [url]GravityView settings[/url] for additional configuration options that apply to all Dashboard Views.', 'gk-gravityview-dashboard-views' ),
						[
							'[url]'  => '<a href="' . esc_url( GravityKitFoundation::settings()->get_plugin_settings_url( GravityViewPluginSettings::SETTINGS_PLUGIN_ID ) . '&s=3' ) . '">',
							'[/url]' => '</a>',
						]
					),
					'type'  => 'checkbox',
					'value' => 0,
				],
			]
		);
	}

	/**
	 * Render the Dashboard Views settings tab content.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function render_settings( $post ) {
		$view_settings      = gravityview_get_template_settings( $post->ID );
		$settings_to_render = [
			'dashboard_views_enable',
		];

		/**
		 * Modifies the list of settings to render inside the Dashboard Views View editor tab.
		 *
		 * @filter 'gk/gravityview/dashboard-views/view-editor/settings'
		 *
		 * @since  TBD
		 *
		 * @param array $settings_to_render Settings to render.
		 */
		$settings_to_render = apply_filters( 'gk/gravityview/dashboard-views/view-editor/settings', $settings_to_render );

		echo '<table class="form-table">';

		foreach ( $settings_to_render as $setting ) {
			GravityView_Render_Settings::render_setting_row( $setting, $view_settings );
		}

		echo '</table>';
	}
}
