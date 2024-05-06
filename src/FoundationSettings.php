<?php

namespace GravityKit\GravityView\DashboardViews;

use GravityKitFoundation;
use GV\Plugin_Settings as GravityViewPluginSettings;

/**
 * Global GravityView settings in Foundation.
 */
class FoundationSettings {
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

		$supported_css_frameworks = [
			'chota'  => [
				'link' => 'https://jenil.github.io/chota/',
				'name' => 'Chota',
			],
			'cirrus' => [
				'link' => 'https://www.cirrus-ui.com/',
				'name' => 'Cirrus UI',
			],
			'marx'   => [
				'link' => 'https://mblode.github.io/marx/',
				'name' => 'Marx',
			],
			'mvp'    => [
				'link' => 'https://andybrewer.github.io/mvp/',
				'name' => 'MVP.css',
			],
			'picnic' => [
				'link' => 'https://picnicss.com/',
				'name' => 'Picnic CSS',
			],
			'pico'   => [
				'link' => 'https://picocss.com/',
				'name' => 'Pico',
			],
			'pure'   => [
				'link' => 'https://purecss.io/',
				'name' => 'Pure',
			],
			'sakura' => [
				'link' => 'https://oxal.org/projects/sakura/',
				'name' => 'Sakura',
			],
		];

		$css_framework_choices = [];

		foreach ( $supported_css_frameworks as $value => $framework ) {
			$css_framework_choices[] = [
				'title' => strtr(
					esc_html_x( 'CSS framework: [framework]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
					[ '[framework]' => $framework['name'] ]
				),
				'value' => $value,
			];
		}

		$settings[ $gravityview_settings_id ]['defaults'] = array_merge(
			$settings[ $gravityview_settings_id ]['defaults'],
			[
				'dashboard_views_stylesheet'        => 'unstyled',
				'dashboard_views_stylesheet_custom' => $site_url,
				'dashboard_views_menu_name'         => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			]
		);

		$settings[ $gravityview_settings_id ]['sections'][] = [
			'title'    => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			'settings' => [
				[
					'id'          => 'dashboard_views_menu_name',
					'title'       => esc_html__( 'Menu Name', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Enter the name of the Dashboard menu item under which the Views will appear.', 'gk-gravityview-dashboard-views' ),
					'type'        => 'text',
					'value'       => $gravityview_settings['dashboard_views_menu_name'] ?? $settings['gravityview']['defaults']['dashboard_views_menu_name'],
					'required'    => true,
					'validation'  => [
						[
							'rule'    => 'required',
							'message' => esc_html__( 'Please enter a menu name', 'gk-gravityview-dashboard-views' ),
						],
					],
				],
				[
					'id'          => 'dashboard_views_stylesheet',
					'type'        => 'select',
					'title'       => esc_html__( 'Default Style', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Choose how you would like to style your Views.', 'gk-gravityview-dashboard-views' ),
					'value'       => $gravityview_settings['dashboard_views_stylesheet'] ?? $settings['gravityview']['defaults']['dashboard_views_stylesheet'],
					'choices'     => array_merge(
						[
							[
								'title' => esc_html__( 'Unstyled', 'gk-gravityview-dashboard-views' ),
								'value' => 'unstyled',
							],
							[
								'title' => esc_html__( 'Custom Stylesheet', 'gk-gravityview-dashboard-views' ),
								'value' => 'custom',
							],
						],
						$css_framework_choices
					),
				],
				[
					'id'          => 'dashboard_views_stylesheet_custom',
					'title'       => esc_html__( 'Custom Stylesheet URL', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Enter the URL of a custom stylesheet.', 'gk-gravityview-dashboard-views' ),
					'type'        => 'text',
					'placeholder' => $site_url,
					'value'       => $gravityview_settings['dashboard_views_stylesheet_custom'] ?? $settings['gravityview']['defaults']['dashboard_views_stylesheet_custom'],
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
