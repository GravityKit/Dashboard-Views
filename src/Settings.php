<?php

namespace GravityKit\GravityView\DashboardViews;

use GravityKitFoundation;
use GV\Plugin_Settings as GravityViewPluginSettings;

/**
 * Global GravityView and View-specific settings.
 */
class Settings {
	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_filter( 'gk/foundation/settings/data/plugins', [ $this, 'add_global_gravityview_settings' ], 11 );
	}

	/**
	 * Adds Dashboard Views settings to the GravityView settings in Foundation.
	 *
	 * @since TBD
	 *
	 * @param array $settings The settings object.
	 *
	 * @return array The modified settings object.
	 */
	public function add_global_gravityview_settings( $settings ) {
		if ( empty( $settings['gravityview']['sections'] ) || ! class_exists( 'GravityKitFoundation' ) ) {
			return $settings;
		}

		$gravityview_settings_id = GravityViewPluginSettings::SETTINGS_PLUGIN_ID;
		$gravityview_settings    = GravityKitFoundation::settings()->get_plugin_settings( $gravityview_settings_id );
		$site_url                = is_multisite() ? network_home_url() : home_url();

		$settings[ $gravityview_settings_id ]['defaults']['dashboard_views_stylesheet'] = 'unstyled';

		$settings[ $gravityview_settings_id ]['sections'][] = [
			'title'    => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			'settings' => [
				[
					'id'          => 'dashboard_views_stylesheet',
					'type'        => 'select',
					'title'       => esc_html__( 'Default Style', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Choose how you would like to style your Views.', 'gk-gravityview-dashboard-views' ),
					'value'       => $gravityview_settings['dashboard_views_stylesheet'] ?? $settings['gravityview']['defaults']['dashboard_views_stylesheet'],
					'choices'     => [
						[
							'title' => esc_html__( 'Unstyled', 'gk-gravityview-dashboard-views' ),
							'value' => 'unstyled',
						],
						[
							'title' => esc_html__( 'Custom Stylesheet', 'gk-gravityview-dashboard-views' ),
							'value' => 'custom',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Chota' ]
							),
							'value' => 'chota',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Cirrus UI' ]
							),
							'value' => 'cirrus',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Marx' ]
							),
							'value' => 'marx',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'MVP.css' ]
							),
							'value' => 'mvp',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Picnic CSS' ]
							),
							'value' => 'picnic',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Pure' ]
							),
							'value' => 'pure',
						],
						[
							'title' => strtr(
								esc_html_x( 'Framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
								[ '[framework]' => 'Sakura' ]
							),
							'value' => 'sakura',
						],
					],
				],
				[
					'id'          => 'dashboard_views_stylesheet_custom',
					'title'       => esc_html__( 'Custom Stylesheet URL', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Enter the URL of a custom stylesheet.', 'gk-gravityview-dashboard-views' ),
					'type'        => 'text',
					'placeholder' => $site_url,
					'value'       => $gravityview_settings['dashboard_views_stylesheet_custom'] ?? $site_url,
					'required'    => true,
					'requires'    => [
						'id'       => 'dashboard_views_stylesheet',
						'operator' => '=',
						'value'    => 'custom',
					],
					'validation'  => [
						[
							// For some reason valid URLs don't pass Laravel's URL validation method, so this is a workaround.
							'rule'    => 'save_settings' !== ( $_REQUEST['ajaxRoute'] ?? '' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								? 'url' // UI validation method.
								: 'regex:/^https?:\/\/[^\/]+/', // Simple backend validation via regex.
							'message' => esc_html__( 'Please enter a valid URL', 'gk-gravityview-dashboard-views' ),
						],
					],
				],
			],
		];

		return $settings;
	}
}
