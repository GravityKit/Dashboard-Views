<?php

namespace GravityKit\GravityView\DashboardViews;

use GV\Plugin_Settings as GravityViewPluginSettings;
use GravityView_Render_Settings;
use GV\View_Settings;
use WP_Post;
use GravityKitFoundation;

/**
 * View-specific settings.
 *
 * @since 2.0.0
 */
class ViewSettings {
	const SETTINGS_PREFIX = 'dashboard_views';

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'gravityview/metaboxes/default', [ $this, 'add_dashboard_views_settings_tab' ], 11 );
		add_filter( 'gravityview/view/settings/defaults', [ $this, 'add_settings_to_the_dashboards_view_tab' ], 11 );
		add_filter( 'gravityview_template_field_options', [ $this, 'modify_view_field_options' ] );
	}

	/**
	 * Adds the Dashboard Views settings tab to the View editor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tabs Existing View editor tabs.
	 *
	 * @return array
	 */
	public function add_dashboard_views_settings_tab( $tabs ) {
		$tabs[] = [
			'id'            => self::SETTINGS_PREFIX,
			'title'         => esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ),
			'file'          => null,
			'icon-class'    => 'dashicons-welcome-widgets-menus',
			'callback'      => [ $this, 'render_settings' ],
			'callback_args' => null,
		];

		return $tabs;
	}

	/**
	 * Adds settings to the Dashboard Views tab in the View editor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $settings Existing View settings.
	 *
	 * @return array
	 */
	public function add_settings_to_the_dashboards_view_tab( $settings ) {
		global $wp_roles;

		$roles = [];

		foreach ( $wp_roles->roles as $role => $data ) {
			// Exclude the default access role from selectable roles.
			if ( View::DEFAULT_ACCESS_ROLE === $role ) {
				continue;
			}

			$roles[ $role ] = $data['name'];
		}

		return array_merge(
			$settings,
			[
				self::SETTINGS_PREFIX . '_enable'        => [
					'label'      => esc_html__( 'Show in Dashboard', 'gk-gravityview-dashboard-views' ),
					'desc'       => strtr(
						esc_html_x( 'This will make the View accessible in the WordPress Dashboard. Visit [url]GravityView settings[/url] for additional configuration options that apply to all Dashboard Views.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview-dashboard-views' ),
						[
							'[url]'  => '<a href="' . esc_url( GravityKitFoundation::settings()->get_plugin_settings_url( GravityViewPluginSettings::SETTINGS_PLUGIN_ID ) . '&s=3' ) . '">',
							'[/url]' => '</a>',
						]
					),
					'type'       => 'checkbox',
					'class'      => 'widefat',
					'full_width' => true,
					'value'      => 0,
				],
				self::SETTINGS_PREFIX . '_internal_only' => [
					'label'      => esc_html__( 'Restrict to Internal-Only View', 'gk-gravityview-dashboard-views' ),
					'desc'       => esc_html__( 'Enable this setting to prevent the View from ever rendering in the frontend.', 'gk-gravityview-dashboard-views' ),
					'requires'   => self::SETTINGS_PREFIX . '_enable',
					'type'       => 'checkbox',
					'class'      => 'widefat',
					'full_width' => true,
					'value'      => 0,
				],
				self::SETTINGS_PREFIX . '_user_roles'    => [
					'label'       => esc_html__( 'Limit Access to User Role(s)', 'gk-gravityview-dashboard-views' ),
					'placeholder' => esc_html__( 'Select user role(s)…', 'gk-gravityview-dashboard-views' ),
					'desc'        => esc_html__( 'The View will only be accessible to users with the selected role(s). Administrators always have access.', 'gk-gravityview-dashboard-views' ),
					'roles'       => $roles,
					'value'       => [],
					'type'        => 'custom',
					'requires'    => self::SETTINGS_PREFIX . '_enable',
					'callback'    => [ $this, 'render_limit_access_to_user_roles_setting' ],
				],
				self::SETTINGS_PREFIX . '_custom_name'   => [
					'label'      => esc_html__( 'Customize the Menu Link', 'gk-gravityview-dashboard-views' ),
					'desc'       => esc_html__( 'Override the View name as it appears in the Dashboard. The default View title will be used if left blank.', 'gk-gravityview-dashboard-views' ),
					'requires'   => self::SETTINGS_PREFIX . '_enable',
					'type'       => 'text',
					'class'      => 'widefat',
					'full_width' => true,
					'value'      => '',
				],
			]
		);
	}

	/**
	 * Renders the Dashboard Views settings tab content.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function render_settings( $post ) {
		$settings = array_filter(
			View_Settings::with_defaults( true )->all(),
			function ( $setting ) {
				return strpos( $setting, self::SETTINGS_PREFIX ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		$settings_values = array_filter(
			gravityview_get_template_settings( $post->ID ),
			function ( $setting ) {
				return strpos( $setting, self::SETTINGS_PREFIX ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		$settings_to_render = array_keys( $settings );

		/**
		 * Modifies the list of settings to render inside the Dashboard Views View editor tab.
		 *
		 * @filter 'gk/gravityview/dashboard-views/view-editor/settings'
		 *
		 * @since  2.0.0
		 *
		 * @param array $settings_to_render Settings to render.
		 */
		$settings_to_render = apply_filters( 'gk/gravityview/dashboard-views/view-editor/settings', $settings_to_render );

		echo '<table class="form-table">';

		foreach ( $settings_to_render as $setting ) {
			$setting_callback = $settings[ $setting ]['callback'] ?? '';

			if ( is_callable( $setting_callback ) ) {
				call_user_func( $setting_callback, $setting, $settings, $settings_values );

				continue;
			}

			GravityView_Render_Settings::render_setting_row( $setting, $settings_values );
		}

		echo '</table>';
	}

	/**
	 * Renders the "Limit Access to User Role(s)" setting.
	 *
	 * @since 2.0.0
	 *
	 * @param string $setting_key     The setting key.
	 * @param array  $settings        The Dashboard Views settings object.
	 * @param array  $settings_values The Dashboard Views settings values.
	 *
	 * @return void
	 */
	public function render_limit_access_to_user_roles_setting( $setting_key, $settings, $settings_values ) {
		$label          = $settings[ $setting_key ]['label'] ?? '';
		$description    = $settings[ $setting_key ]['desc'] ?? '';
		$placeholder    = $settings[ $setting_key ]['placeholder'] ?? '';
		$requires       = $settings[ $setting_key ]['requires'] ?? '';
		$roles          = $settings[ $setting_key ]['roles'] ?? [];
		$selected_roles = $settings_values[ $setting_key ] ?? [];

		?>
		<tr style="vertical-align: top;" class="alternate">
			<td colspan="2">
				<div class="gv-setting-container gv-setting-container-<?php echo esc_attr( $setting_key ); ?>" <?php echo $requires ? 'data-requires=' . esc_attr( $requires ) : ''; ?>>
					<label for="gravityview_se_<?php echo esc_attr( $setting_key ); ?>">
						<?php echo esc_html( $label ); ?> <span class="howto"><?php echo esc_html( $description ); ?></span>
						<select id="gravityview_se_<?php echo esc_attr( $setting_key ); ?>" name="template_settings[<?php echo esc_attr( $setting_key ); ?>][]" multiple placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
							<?php
							foreach ( $roles as $role => $title ) {
								?>
								<option value="<?php echo esc_html( $role ); ?>" <?php echo in_array( $role, $selected_roles, true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $title ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</label>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Adds View field option to exclude it from being displayed in the Dashboard.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field_options The field options.
	 *
	 * @return array The modified field options.
	 */
	public function modify_view_field_options( $field_options ) {
		return array_merge(
			$field_options,
			[
				self::SETTINGS_PREFIX . '_show_field' => [
					'type'     => 'checkbox',
					'label'    => esc_html__( 'Show in Dashboard', 'gk-gravityview-dashboard-views' ),
					'value'    => true,
					'priority' => 4000,
					'group'    => 'visibility',
				],
				self::SETTINGS_PREFIX . '_exclude_from_frontend' => [
					'type'     => 'checkbox',
					'label'    => esc_html__( 'Exclude from Frontend', 'gk-gravityview-dashboard-views' ),
					'value'    => false,
					'priority' => 4000,
					'group'    => 'visibility',
				],
			]
		);
	}
}
