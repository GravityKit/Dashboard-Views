<?php

namespace GravityKit\GravityView\DashboardViews;

use Exception;
use GravityKitFoundation;
use GravityView_Edit_Entry;
use GravityView_Field_Notes;
use GravityView_Fields;
use GravityView_frontend;
use GravityView_View_Data;
use GV\Edit_Entry_Renderer;
use GV\Entry_Renderer;
use GV\View;
use GV\View_Renderer;
use GV\Plugin_Settings as GravityViewPluginSettings;

class Plugin {
	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'set_request' ], 1 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_ui_assets' ] );

		add_filter( 'gravityview/view/links/directory', [ $this, 'rewrite_directory_link' ], 10, 2 );
		add_filter( 'gravityview/entry/permalink', [ $this, 'rewrite_single_entry_link' ], 10, 3 );
		add_filter( 'gravityview/template/links/back/url', [ $this, 'rewrite_single_entry_back_link' ] );
		add_filter( 'gravityview/edit/link', [ $this, 'rewrite_edit_entry_link' ], 10, 3 );
		add_filter( 'gravityview/edit_entry/success', [ $this, 'rewrite_edit_entry_back_link' ], 10, 5 );
		add_filter( 'gravityview/edit_entry/cancel_link', [ $this, 'rewrite_edit_entry_cancel_link' ], 10, 3 );
		add_filter( 'gravityview/widget/search/form/action', [ $this, 'rewrite_search_action_link' ] );
		add_filter( 'gk/gravityview/widget/search/clear-button/params', [ $this, 'rewrite_search_clear_link' ] );
		add_filter( 'gravityview_page_links_args', [ $this, 'rewrite_pagination_links' ] );

		new FoundationSettings();
		new AdminMenu();
	}

	/**
	 * Returns Views configured for display in the Dashboard.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_dashboard_views() {
		$dashboard_views = [];
		$views           = get_posts(
			[
				'post_type'   => 'gravityview',
				'post_status' => 'any',
				'numberposts' => - 1,
			]
		);

		if ( is_wp_error( $views ) ) {
			return [];
		}

		foreach ( $views as $view ) {
			$dashboard_views[] = [
				'id'    => $view->ID,
				'link'  => add_query_arg(
					[ 'page' => AdminMenu::get_view_submenu_slug( (int) $view->ID ) ],
					admin_url( 'admin.php' )
				),
				'title' => get_the_title( $view ),
			];
		}

		return $dashboard_views;
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
	 * Adds a proxy for `$request->is_dashboard_view()`.
	 *
	 * @since 1.0.0
	 *
	 * @return null|false|Request null: Not GV request. false: Not an Admin View request. Otherwise, returns Dashboard Views request.
	 */
	public static function is_dashboard_view() {
		$request = gravityview()->request;

		if ( ! $request ) {
			return null;
		}

		if ( ! method_exists( $request, 'is_dashboard_view' ) || ! $request->is_dashboard_view() ) {
			return false;
		}

		return $request;
	}

	/**
	 * Renders the View.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_view() {
		echo '
		<style> 
 
		</style>';

		if ( version_compare( \GV\Plugin::$version, '2.16', '>=' ) ) {
			$view = gravityview()->request->is_view( true );
		} else {
			$view = gravityview()->request->is_view();
		}

		if ( ! $view ) {
			return;
		}

		/**
		 * Triggers before the View is rendered.
		 *
		 * @since  TBD
		 * @filter `gk/gravityview/dashboard-views/view/before`
		 *
		 * @param View $view The View.
		 */
		do_action( 'gk/gravityview/dashboard-views/view/before', $view );

		if ( ! did_action( 'gravityview_include_frontend_actions' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		if ( gravityview()->request->is_edit_entry() ) {
			$output = ( new Edit_Entry_Renderer() )->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( gravityview()->request->is_entry() ) {
			$output = ( new Entry_Renderer() )->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$output = ( new View_Renderer() )->render( $view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo "<div class='wrap dashboard-view'>{$output}</div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );

		GravityView_frontend::getInstance()->setGvOutputData( $view_data );
		GravityView_frontend::getInstance()->add_scripts_and_styles();

		wp_print_scripts();
		wp_print_styles();
	}

	/**
	 * Rewrites the View directory link.
	 *
	 * @since TBD
	 *
	 * @param string $link The directory link.
	 *
	 * @return string The update directory link.
	 */
	public function rewrite_directory_link( $link ) {
		return ! self::is_dashboard_view() ? $link : $this->add_query_args_to_url();
	}

	/**
	 * Rewrites the single entry link.
	 *
	 * @since TBD
	 *
	 * @param string $link  The single entry link.
	 * @param Entry  $entry The Gravity Forms entry.
	 *
	 * @return string The update single entry link.
	 */
	public function rewrite_single_entry_link( $link, $entry ) {
		return ! self::is_dashboard_view() ? $link : $this->add_query_args_to_url( [ 'entry_id' => $entry->ID ] );
	}

	/**
	 * Rewrites the edit entry link.
	 *
	 * @since TBD
	 *
	 * @param string $link  The edit entry link.
	 * @param array  $entry The Gravity Forms entry.
	 * @param View   $view  The View.
	 *
	 * @return string The updated edit entry link.
	 */
	public function rewrite_edit_entry_link( $link, $entry, $view ) {
		if ( ! self::is_dashboard_view() ) {
			return $link;
		}

		return $this->add_query_args_to_url(
			[
				'entry_id' => $entry['id'],
				'edit'     => wp_create_nonce( GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id'] ) ),
			]
		);
	}

	/**
	 * Rewrites the single entry back link.
	 *
	 * @since TBD
	 *
	 * @param string $link The single entry back link.
	 *
	 * @return string The updated single entry back link.
	 */
	public function rewrite_single_entry_back_link( $link ) {
		return ! self::is_dashboard_view() ? $link : $this->add_query_args_to_url();
	}

	/**
	 * Rewrites the edit entry back link.
	 *
	 * @since TBD
	 *
	 * @param string      $message      Entry update message.
	 * @param int         $view_id      View ID.
	 * @param array       $entry        Gravity Forms entry object.
	 * @param string      $back_link    URL to return to the original entry.
	 * @param string|null $redirect_url URL to return to after the update.
	 *
	 * @return string Entry update message with the updated back link.
	 */
	public function rewrite_edit_entry_back_link( $message, $view_id, $entry, $back_link, $redirect_url ) {
		if ( ! self::is_dashboard_view() || $redirect_url ) {
			return $message;
		}

		// Edit Entry breaks the back link by removing the "page" query arg.
		// https://github.com/GravityKit/GravityView/blob/0d1b5f21f4dc48feaac5197b74da4baf4f143b89/includes/extensions/edit-entry/class-edit-entry-render.php#L1213.
		return str_replace(
			$back_link,
			remove_query_arg( [ 'edit' ] ),
			$message
		);
	}

	/**
	 * Rewrites the edit entry cancel link.
	 *
	 * @since TBD
	 *
	 * @param string $link  The edit entry cancel link.
	 * @param array  $form  The Gravity Forms form.
	 * @param array  $entry The Gravity Forms entry.
	 *
	 * @return string The updated edit entry cancel link.
	 */
	public function rewrite_edit_entry_cancel_link( $link, $form, $entry ) {
		return ! self::is_dashboard_view() ? $link : $this->add_query_args_to_url( [ 'entry_id' => $entry['id'] ] );
	}

	/**
	 * Rewrites the search action link.
	 *
	 * @since TBD
	 *
	 * @param string $link The search action link.
	 *
	 * @return string The updated search action link.
	 */
	public function rewrite_search_action_link( $link ) {
		return ! self::is_dashboard_view() ? $link : $this->add_query_args_to_url();
	}

	/**
	 * Rewrites the search clear link.
	 *
	 * @since TBD
	 *
	 * @param array $params The search clear link parameters.
	 *
	 * @return array The updated search clear link parameters.
	 */
	public function rewrite_search_clear_link( $params ) {
		$params['url'] = ! self::is_dashboard_view() ? ( $params['url'] ?? '' ) : $this->add_query_args_to_url();

		return $params;
	}

	/**
	 * Rewrites pagination link.
	 *
	 * @since TBD
	 *
	 * @param array $params Pagination link parameters.
	 *
	 * @return array The updated pagination link parameters.
	 */
	public function rewrite_pagination_links( array $params ) {
		$params['base'] = ! self::is_dashboard_view() ? ( $param['base'] ?? '' ) : $this->add_query_args_to_url( [ 'pagenum' => '%#%' ] );

		return $params;
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

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		$this->enqueue_view_stylesheet();

		$dashboard_view_script = 'build/js/dashboard-view.min.js';

		if ( ! file_exists( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $dashboard_view_script ) ) {
			do_action( 'gravityview_log_warning', "Dashboard Views script {$dashboard_view_script} does not exist." );
		} else {
			wp_enqueue_script(
				'gravityview-dashboard-views-script',
				plugins_url( $dashboard_view_script, GV_DASHBOARD_VIEWS_PLUGIN_FILE ),
				[],
				filemtime( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $dashboard_view_script ),
				false
			);
		}

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

		$handle = 'gravityview-dashboard-views-stylesheet';

		$gravityview_settings = GravityKitFoundation::settings()->get_plugin_settings( GravityViewPluginSettings::SETTINGS_PLUGIN_ID );

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
				$handle,
				$gravityview_settings['dashboard_views_stylesheet_custom'],
				[],
				GV_DASHBOARD_VIEWS_VERSION
			);

			$unload_wp_styles();

			return;
		}

		$stylesheet = "build/css/{$gravityview_settings['dashboard_views_stylesheet']}.min.css";

		if ( ! file_exists( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $stylesheet ) ) {
			do_action( 'gravityview_log_warning', "Dashboard Views stylesheet {$stylesheet} does not exist." );

			return;
		}

		wp_enqueue_style(
			$handle,
			plugins_url( $stylesheet, GV_DASHBOARD_VIEWS_PLUGIN_FILE ),
			[],
			filemtime( plugin_dir_path( GV_DASHBOARD_VIEWS_PLUGIN_FILE ) . $stylesheet )
		);

		$unload_wp_styles();
	}

	/**
	 * Adds provided query args to the request URL.
	 * This also sets the "page" query arg so that requests are correctly routed to the Dashboard View {@see Request::is_dashboard_view()}.
	 *
	 * @since TBD
	 *
	 * @param array  $args (optional) The query args to add. If not provided, only the current Dashboard View "page" query arg will be set.
	 * @param string $url  (optional) The URL to add the query args to. Defaults to the admin URL.
	 *
	 * @return string The updated URL with the query args.
	 */
	private function add_query_args_to_url( $args = [], $url = '' ) {
		$view = gravityview()->request->is_view();

		if ( $view ) {
			$args = array_merge(
				[
					'page' => AdminMenu::get_view_submenu_slug( (int) $view->ID ),
				],
				$args
			);
		}

		return add_query_arg( $args, $url ?: admin_url( 'admin.php' ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
	}
}
