<?php

namespace GravityKit\GravityView\DashboardViews;

use GravityKitFoundation;
use GV\Plugin_Settings as GravityViewPluginSettings;

/**
 * Class to configure the Admin (Dashboard) menu.
 */
class AdminMenu {
	const WP_ADMIN_MENU_SLUG = '_gk_gravityview_dashboard_views';

	const DEFAULT_SUBMENU_GROUP = 'group1';

	/**
	 * Submenus of the top menu.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private static $_submenus = [
		'group1' => [],
		'group2' => [],
		'group3' => [],
		'group4' => [],
		'group5' => [],
	];

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 100 );
		add_action( 'network_admin_menu', [ $this, 'add_admin_menu' ], 100 );
	}

	/**
	 * Configures Dashboard Views top-level menu and submenus in WP admin.
	 *
	 * @since TBD
	 *
	 * @global array $menu
	 * @global array $submenu
	 *
	 * @retun void
	 */
	public function add_admin_menu() {
		global $menu, $submenu;

		// Make sure we're not adding a duplicate top-level menu.
		if ( strpos( wp_json_encode( $menu ?: [] ), self::WP_ADMIN_MENU_SLUG ) !== false ) { // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			return;
		}

		$_get_divider = function () {
			// Divider is added to the menu title; because WP wraps it in <a>, we need to first close the tag, then add the divider.
			return '</a> <hr style="margin: 10px 12px; border: none; height: 1px; background-color: hsla( 0, 0%, 100%, .2 );" tabindex="-1" />';
		};

		$submenus                  = self::get_submenus();
		$filtered_submenus         = [];
		$user_first_met_capability = false;

		// Filter submenus by removing those for which the user doesn't have the required capability.
		foreach ( $submenus as $submenu_data ) {
			if ( empty( $submenu_data ) ) {
				continue;
			}

			foreach ( array_values( $submenu_data ) as $index => $submenu_item ) {
				if ( ! current_user_can( $submenu_item['capability'] ) ) {
					continue;
				} elseif ( ! $user_first_met_capability ) {
					$user_first_met_capability = $submenu_item['capability'];
				}

				$submenu_item_id = $submenu_item['id'] ?? '';

				$filtered_submenu = [
					'id'                 => $submenu_item_id,
					'slug'               => self::WP_ADMIN_MENU_SLUG,
					'page_title'         => $submenu_item['page_title'] ?? '',
					'menu_title'         => $submenu_item['menu_title'] ?? '',
					'capability'         => $submenu_item['capability'] ?? '',
					'callback'           => $submenu_item['callback'] ?? '',
					'hide'               => $submenu_item['hide'] ?? '',
					'hide_admin_notices' => $submenu_item['hide_admin_notices'] ?? false,
				];

				if ( ( count( $submenu_data ) - 1 ) === $index ) {
					$filtered_submenu['divider'] = $_get_divider();
				}

				$filtered_submenus[] = $filtered_submenu;
			}
		}

		if ( empty( $filtered_submenus ) ) {
			return;
		}

		$gravityview_settings = GravityKitFoundation::settings()->get_plugin_settings( GravityViewPluginSettings::SETTINGS_PLUGIN_ID );

		// Add top-level menu.
		$page_title         = $gravityview_settings['dashboard_views_menu_name'] ?? esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' );
		$menu_title         = $gravityview_settings['dashboard_views_menu_name'] ?? esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' );
		$menu_temp_position = base_convert( substr( md5( self::WP_ADMIN_MENU_SLUG ), -4 ), 16, 10 ) * 0.00001; // Taken from WP's add_menu_page() code.

		/**
		 * Controls the position of the top-level admin menu.
		 *
		 * @filter 'gk/gravityview/dashboard-views/admin-menu/position'
		 *
		 * @since  TBD
		 *
		 * @param float $menu_position
		 */
		$menu_position = apply_filters(
			'gk/gravityview/dashboard-views/admin-menu/position',
			$this->get_menu_position_by_id( $gravityview_settings['dashboard_views_menu_position'] ?? '' )
		);

		add_menu_page(
			$page_title,
			$menu_title,
			$user_first_met_capability, // Use the first submenu capability that the user has met to display the main Dahboard Views menu.
			self::WP_ADMIN_MENU_SLUG,
			null,
			'data:image/svg+xml;base64,' . base64_encode( '<svg id="Artwork" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="#a7aaad" class="st0" d="M128 0C57.3 0 0 57.3 0 128s57.3 128 128 128 128-57.3 128-128S198.7 0 128 0zm0 243.2c-63.6 0-115.2-51.6-115.2-115.2S64.4 12.8 128 12.8 243.2 64.4 243.2 128 191.6 243.2 128 243.2zm7.9-172.5c-.8.1-1.4-.5-1.5-1.3V57.7c-.1-.9.4-1.8 1.3-2.1 7.8-4.2 10.6-13.9 6.4-21.7-4.2-7.8-13.9-10.6-21.7-6.4-7.8 4.2-10.6 13.9-6.4 21.7 1.5 2.7 3.7 4.9 6.4 6.4.8.3 1.4 1.2 1.3 2.1v11.4c.1.8-.4 1.5-1.2 1.6h-.3c-41 3-68.9 29.6-68.9 66.9 0 39.6 31.5 67.2 76.8 67.2s76.8-27.6 76.8-67.2c-.1-37.3-28-63.9-69-66.9zM128 182.4c-35.9 0-60.8-18.4-60.8-44.8S92.1 92.8 128 92.8s60.8 18.4 60.8 44.8-24.9 44.8-60.8 44.8zm53.8-44.8c0 22.3-22.1 37.8-53.8 37.8-5.1 0-10.2-.4-15.2-1.3-6.8-1.2-9.4-3.2-12-9.6-3.1-7.5-4.8-16.6-4.8-26.9s1.7-19.4 4.8-26.9c2.7-6.4 5.2-8.4 12-9.6 5-.9 10.1-1.3 15.2-1.3 31.7 0 53.8 15.5 53.8 37.8z"/></svg>' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$menu_temp_position
		);

		$menu_item = $menu[ (string) $menu_temp_position ];

		unset( $menu[ (string) $menu_temp_position ] );

		$menu = $this->insert_menu_item_after_position( $menu, $menu_item, $menu_position ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		uksort( $menu, 'strnatcmp' );

		// Add submenus.
		foreach ( $filtered_submenus as $index => $filtered_submenu ) {
			add_submenu_page(
				$filtered_submenu['slug'],
				$filtered_submenu['page_title'],
				$filtered_submenu['menu_title'],
				$filtered_submenu['capability'],
				$filtered_submenu['id'],
				$filtered_submenu['callback']
			);

			if ( isset( $filtered_submenu['hide_admin_notices'] ) ) {
				add_action(
					'in_admin_header',
					function () use ( $filtered_submenu ) {
						if ( ( $_REQUEST['page'] ?? '' ) !== $filtered_submenu['id'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							return;
						}

						remove_all_actions( 'user_admin_notices' );
						remove_all_actions( 'admin_notices' );
					},
					999
				);
			}

			// Add divider unless it's the last submenu item that we've added.
			if ( ! isset( $filtered_submenu['divider'] ) || ( count( $filtered_submenus ) - 1 ) === $index ) {
				continue;
			}

			$added_submenu_to_update     = array_pop( $submenu[ self::WP_ADMIN_MENU_SLUG ] );
			$added_submenu_to_update[0] .= $filtered_submenu['divider'];

			$submenu[ self::WP_ADMIN_MENU_SLUG ][] = $added_submenu_to_update; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// On a multisite the first submenu item equals the top-level menu.
		// Let's indiscriminately remove all submenu items that have the top-level menu's slug.
		foreach ( $submenu[ self::WP_ADMIN_MENU_SLUG ] as $key => $submenu_item ) {
			if ( self::WP_ADMIN_MENU_SLUG === $submenu_item[2] ) {
				unset( $submenu[ self::WP_ADMIN_MENU_SLUG ][ $key ] );
			}
		}

		add_filter(
			'gk/foundation/inline-styles',
			function ( $styles ) use ( $filtered_submenus ) {
				// Top-level menu item SVG icon style.
				$styles[] = [
					'style' => '#toplevel_page_' . self::WP_ADMIN_MENU_SLUG . ' div.wp-menu-image.svg { background-size: 1.5em auto; }',
				];

				// Hide the first submenu item or else it will appear empty.
				$styles[] = [
					'style' => '#toplevel_page_' . self::WP_ADMIN_MENU_SLUG . ' ul.wp-submenu li:nth-child(2) {
						display: none;
					}',
				];

				// Styles for submenus that should be hidden.
				$hide_styles = [];

				foreach ( $filtered_submenus as $submenu ) {
					if ( isset( $submenu['top_level_menu_action'] ) ) {
						$hide_styles[] = '#toplevel_page_' . self::WP_ADMIN_MENU_SLUG . ' ul.wp-submenu li:nth-child(2)';
					}

					if ( $submenu['hide'] ?? '' ) {
						$hide_styles[] = '#toplevel_page_' . self::WP_ADMIN_MENU_SLUG . ' ul.wp-submenu li a[href*="' . $submenu['id'] . '"]';
					}
				}

				if ( empty( $hide_styles ) ) {
					return $styles;
				}

				$styles[] = [ 'style' => join( ',', $hide_styles ) . ' { display: none !important; }' ];

				return $styles;
			}
		);
	}

	/**
	 * Adds a submenu to the top-level Dashboard Views admin menu.
	 *
	 * @since TBD
	 *
	 * @param array  $submenu  The submenu data.
	 * @param string $position The position of the submenu. Default: 'group1'.
	 *
	 * @retun void
	 */
	public static function add_submenu_item( $submenu, $position = self::DEFAULT_SUBMENU_GROUP ) {
		if ( ! isset( $submenu['id'] ) ) {
			return;
		}

		$submenus = self::get_submenus();

		$submenus[ $position ] = $submenus[ $position ] ?? [];

		if ( ! isset( $submenu['order'] ) ) {
			$order = array_column( $submenus[ $position ], 'order' );

			if ( empty( $order ) ) {
				$submenu['order'] = 1;
			} else {
				$submenu['order'] = max( $order ) + 100;
			}
		}

		$submenus[ $position ][ $submenu['id'] ] = $submenu;

		$order = array_column( $submenus[ $position ], 'order' );

		array_multisort( $submenus[ $position ], SORT_NUMERIC, $order );

		self::$_submenus = $submenus;
	}

	/**
	 * Returns submenus optionally modified by a filter.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_submenus() {
		$admin_menus = Plugin::get_dashboard_views();
		$submenus    = self::$_submenus;

		if ( empty( $admin_menus ) ) {
			return self::$_submenus;
		}

		foreach ( $admin_menus as $dashboard_view ) {
			$view_settings = gravityview_get_template_settings( $dashboard_view['id'] );

			if ( ! is_array( $view_settings['dashboard_views_user_roles'] ?? [] ) ) {
				continue;
			}

			$group = $view_settings[ ViewSettings::SETTINGS_PREFIX . '_group' ] ?? self::DEFAULT_SUBMENU_GROUP;
			$order = $view_settings[ ViewSettings::SETTINGS_PREFIX . '_group_order' ] ?? 1;

			$user_first_met_role = null;

			foreach ( $view_settings['dashboard_views_user_roles'] as $role ) {
				if ( current_user_can( $role ) ) {
					$user_first_met_role = $role;

					break;
				}
			}

			$submenus[ $group ][] = [
				'id'         => self::get_view_submenu_slug( (int) $dashboard_view['id'] ),
				'page_title' => $dashboard_view['title'],
				'menu_title' => $dashboard_view['title'],
				'capability' => $user_first_met_role ?? Plugin::DEFAULT_ACCESS_ROLE,
				'order'      => $order,
				'callback'   => function () use ( $dashboard_view ) {
					$_REQUEST['_dashboard_view'] = $dashboard_view['id']; // This is used by the Request class to determine the current View ID.

					gravityview()->request = new Request();

					echo Plugin::render_view(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				},
			];

			$order = array_column( $submenus[ $group ], 'order' );

			array_multisort( $submenus[ $group ], SORT_NUMERIC, $order );
		}

		/**
		 * Modifies the submenus object.
		 *
		 * @filter `gk/gravityview/dashboard-views/admin-menu/submenus`
		 *
		 * @since  TBD
		 *
		 * @param array $submenus Submenus.
		 */
		return apply_filters( 'gk/gravityview/dashboard-views/admin-menu/submenus', $submenus );
	}

	/**
	 * Removes a submenu from the top-level menu in WP admin and if the top-level menu is empty, removes it as well.
	 *
	 * @since TBD
	 *
	 * @global array $submenu
	 *
	 * @param string $id The submenu ID.
	 *
	 * @retun void
	 */
	public static function remove_submenu_item( $id ) {
		global $submenu;

		if ( ! isset( $submenu[ self::WP_ADMIN_MENU_SLUG ] ) ) {
			return;
		}

		foreach ( $submenu[ self::WP_ADMIN_MENU_SLUG ] as $index => $submenu_item ) {
			if ( $submenu_item[2] === $id ) {
				unset( $submenu[ self::WP_ADMIN_MENU_SLUG ][ $index ] );
			}
		}

		if ( ! empty( $submenu[ self::WP_ADMIN_MENU_SLUG ] ) ) {
			return;
		}

		self::remove_admin_menu();
	}

	/**
	 * Removes the top-level menu from WP admin.
	 *
	 * @since TBD
	 *
	 * @global array $menu
	 *
	 * @retun void
	 */
	public static function remove_admin_menu() {
		global $menu;

		foreach ( $menu as $index => $menu_item ) {
			if ( self::WP_ADMIN_MENU_SLUG === $menu_item[2] ) {
				unset( $menu[ $index ] );
			}
		}
	}

	/**
	 * Returns the View submenu prefix.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_view_submenu_prefix() {
		$gravityview_settings = GravityKitFoundation::settings()->get_plugin_settings( GravityViewPluginSettings::SETTINGS_PLUGIN_ID );

		$prefix = sanitize_title( $gravityview_settings['dashboard_views_menu_name'] ?? esc_html__( 'Dashboard Views', 'gk-gravityview-dashboard-views' ) );

		/**
		 * @filter `gk/gravityview/dashboard-views/admin-menu/submenu-prefix`
		 *
		 * @since  TBD
		 *
		 * @param string $prefix View prefix.
		 */
		return apply_filters( 'gk/gravityview/dashboard-views/admin-menu/submenu-prefix', $prefix );
	}

	/**
	 * Returns the submenu slug for a given View ID.
	 *
	 * @since TBD
	 *
	 * @param int $view_id The View ID.
	 *
	 * @return string
	 */
	public static function get_view_submenu_slug( int $view_id ) {
		return self::get_view_submenu_prefix() . "-{$view_id}";
	}

	/**
	 * Returns View ID that's part of the submenu slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug      The submenu slug.
	 *
	 * @return false|int|null
	 */
	public static function get_submenu_view_id( $slug ) {
		preg_match( '/' . self::get_view_submenu_prefix() . '-(\d+)/', $slug, $matches );

		return $matches[1] ?? null;
	}

	/**
	 * Returns WP admin menu items sorted alphabetically.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_menus() {
		global $menu;

		$menu_items = [];

		if ( empty( $menu ) ) {
			return $menu_items;
		}

		foreach ( $menu as $position => $item ) {
			if ( empty( $item[0] ) || strpos( $item[2], self::WP_ADMIN_MENU_SLUG ) !== false ) {
				continue;
			}

			$menu_items[] = [
				'id'             => $item[5],
				'title'          => preg_match( '/^(.*?)(?=<(?:a|b|code|div|em|h[1-6]|i|p|span|ul))/i', $item[0], $matches ) ? trim( $matches[1] ) : $item[0], // Titles can contain HTML markup with update count/etc., so this is a crude way of removing everything up to first most probable tag.
				'title_original' => $item[0],
				'position'       => $position,
			];
		}

		usort(
			$menu_items,
			function ( $a, $b ) {
				return strcasecmp( $a['title'], $b['title'] );
			}
		);

		return $menu_items;
	}

	/**
	 * Returns the position of a WP admin menu item by its ID.
	 *
	 * @since TBD
	 *
	 * @param string $id The menu item ID.
	 *
	 * @return int|string|null
	 */
	public static function get_menu_position_by_id( $id ) {
		$menus = array_filter(
			self::get_menus(),
			function ( $menu ) use ( $id ) {
				return ( $menu['id'] ?? '' ) === $id;
			}
		);

		$menu = reset( $menus );

		return $menu ? $menu['position'] : null;
	}

	/**
	 * Inserts a new menu item after a specified position:
	 * - If the position doesn't exist, the new menu item is added at the end.
	 * - If the position is an integer, a new float is created.
	 * - If the position is a float, the new menu item is added after it and subsequent floats are renumbered.
	 *
	 * The renumbering logic is not aggressive and only renumbers floats that are directly after the specified position, or creates a new float if the position is an integer.
	 *
	 * @todo  Use Foundation's `insert_menu_item_after_position` method once it's available (https://github.com/GravityKit/Foundation/commit/b0d8a9958e482de6cce8e78d6cf92d92e234bac4)
	 *
	 * @since TBD
	 *
	 * @param array      $menus          The menu items.
	 * @param mixed      $new_menu       The new menu item.
	 * @param int|string $after_position The position after which the new menu item should be inserted.
	 *
	 * @return array|mixed The updated menu items.
	 */
	public function insert_menu_item_after_position( array $menus, $new_menu, $after_position ) {
		if ( ! isset( $menus[ (string) $after_position ] ) ) {
			$menus[ (string) $after_position ] = $new_menu;

			return $menus;
		}

		$positions_to_renumber = array_filter(
			$menus,
			function ( $key ) use ( $after_position ) {
				return preg_match( '/^' . (int) $after_position . '(\..*)?$/', $key );
			},
			ARRAY_FILTER_USE_KEY
		);

		$menus = array_diff_key( $menus, $positions_to_renumber );

		$index = 0;

		foreach ( $positions_to_renumber as $current_position => $value ) {
			if ( version_compare( $after_position, $current_position ) === 0 ) {
				$menus[ (string) $current_position ] = $value;

				$new_key = array_keys( $positions_to_renumber )[ $index + 1 ] ?? $current_position + 0.01;

				$menus[ (string) $new_key ] = $new_menu;

				continue;
			}

			if ( ! preg_match( '/\./', $current_position ) ) {
				$current_position = (int) $current_position;
			} elseif ( version_compare( $after_position, $current_position ) < 0 ) {
				$current_position = $current_position + 0.01;
			}

			$menus[ (string) $current_position ] = $value;

			++$index;
		}

		return $menus;
	}
}
