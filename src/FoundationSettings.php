<?php

namespace GravityKit\GravityView\DashboardViews;

use GravityKitFoundation;
use GV\Plugin_Settings as GravityViewPluginSettings;
use GV\Plugin as GravityViewPlugin;

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

		$admin_menu_choices = [];

		foreach ( AdminMenu::get_menus() as $menu ) {
			$admin_menu_choices[] = [
				'title' => $menu['title'],
				'value' => $menu['id'],
			];
		}

		$settings[ $gravityview_settings_id ]['defaults'] = array_merge(
			$settings[ $gravityview_settings_id ]['defaults'],
			[
				'dashboard_views_stylesheet'        => 'unstyled',
				'dashboard_views_stylesheet_custom' => $site_url,
				'dashboard_views_menu_name'         => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
				'dashboard_views_menu_position'     => 'toplevel_page_' . GravityKitFoundation::admin_menu()::WP_ADMIN_MENU_SLUG, // GravityKit menu.
			]
		);

		$dashboard_views_settings = [];

		if ( empty( Plugin::get_dashboard_views() ) ) {
			$notice = strtr(
				esc_html_x( 'You do not have any Views configured for display in the Dashboard. Visit the [url]list of Views[/url] and edit one or more to enable Dashboard display under the Dashboard Views tab.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
				[
					'[url]'  => '<a href="' . admin_url( 'admin.php?page=' . GravityViewPlugin::ALL_VIEWS_SLUG ) . '" class="gk-link">',
					'[/url]' => '</a>',
				]
			);

			$dashboard_views_settings = [
				[
					'id'   => 'legacy_settings_notice',
					'html' => <<<HTML
<div class="bg-yellow-50 p-4 rounded-md">
	<div class="flex">
		<div class="flex-shrink-0">
			<svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
				<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
			</svg>
		</div>
		<div class="ml-3">
			<p class="text-sm">
				{$notice}
			</p>
		</div>
	</div>
</div>
HTML
					,
				],
			];
		}

		$dashboard_views_settings = array_merge(
            $dashboard_views_settings,
            [
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
					'id'          => 'dashboard_views_menu_position',
					'type'        => 'select',
					'title'       => esc_html__( 'Menu Position', 'gk-gravityview-dashboard-views' ),
					'description' => esc_html__( 'Select the menu below which to place the Dashboard Views menu item.', 'gk-gravityview-dashboard-views' ),
					'value'       => $gravityview_settings['dashboard_views_menu_position'] ?? $settings['gravityview']['defaults']['dashboard_views_menu_position'],
					'choices'     => $admin_menu_choices,
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
			]
        );

		$settings[ $gravityview_settings_id ]['sections'][] = [
			'title'    => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			'settings' => $dashboard_views_settings,
		];

		return $settings;
	}
}
