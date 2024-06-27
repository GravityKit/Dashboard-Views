<?php

namespace GravityKit\GravityView\DashboardViews;

use GV\Entry;
use GV\GF_Entry;
use GV\View;
use GV\Request as GravityViewRequest;

/**
 * The default Dashboard View Request class.
 *
 * It's initialized in the Plugin class on the 'current_screen' action, which only runs in the admin.
 *
 * {@see Plugin::set_request()}
 *
 * @since TBD
 */
class Request extends GravityViewRequest {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'gravityview/request/is_renderable', [ $this, 'declare_renderable' ], 10, 2 );

		parent::__construct();
	}

	/**
	 * Declares this class as something that is renderable.
	 *
	 * @since 1.0.0
	 *
	 * @param bool    $is_renderable Whether the request is renderable.
	 * @param Request $instance      Request instance.
	 *
	 * @return bool
	 */
	public function declare_renderable( $is_renderable, $instance ) {
		return get_class( $this ) === get_class( $instance );
	}

	/**
	 * Checks if the current screen is a Dashboard View.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_dashboard_view() {
		return ! ! AdminMenu::get_submenu_view_id( $_REQUEST['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Returns false to fake that we're not inside an admin screen unless it's an Ajax request.
	 * This response is expected by various GravityKit plugins/extensions to determine whether to initialize, load their assets, etc.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function is_admin() {
		return wp_doing_ajax() || false;
	}

	/**
	 * Checks if the current admin screen is a View.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $return_view Whether to return the View object.
	 *
	 * @return false|View
	 */
	public function is_view( $return_view = true ) {
		static $view;

		if ( $view ) {
			return $return_view ? $view : true;
		}

		if ( ! $this->is_dashboard_view() ) {
			return false;
		}

		$view = View::by_id( AdminMenu::get_submenu_view_id( $_REQUEST['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $return_view ? $view : ! ! $view;
	}

	/**
	 * Checks whether this is a single entry request.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @return false|Entry
	 */
	public function is_entry( $form_id = 0 ) {
		if ( ! $this->is_view() ) {
			return false;
		}

		return GF_Entry::by_id( $_GET['entry_id'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Checks whether this an edit entry request.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @return Entry|false The entry requested or false.
	 */
	public function is_edit_entry( $form_id = 0 ) {
		$entry = $this->is_entry( $form_id );

		if ( ! $entry ) {
			return false;
		}

		return ! empty( $_GET['edit'] ) ? $entry : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
