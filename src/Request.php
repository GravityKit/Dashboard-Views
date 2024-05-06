<?php

namespace GravityKit\GravityView\DashboardViews;

use GV\Entry;
use GV\GF_Entry;
use GV\Utils;
use GV\View;
use GV\Request as GravityViewRequest;

/**
 * The default Dashboard View Request class.
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
		global $current_screen;

		if ( $current_screen && ! get_current_screen() ) {
			return false;
		}

		if ( $current_screen && ! preg_match( AdminMenu::WP_ADMIN_MENU_PAGE_PREFIX_REGEX, $current_screen->id ) ) {
			return false;
		}

		if ( $this->doing_datatables_ajax_request() ) {
			return true;
		}

		return $this->is_admin() && preg_match( AdminMenu::WP_ADMIN_MENU_PAGE_PREFIX_REGEX, $_REQUEST['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Checks whether we're in a DT Ajax request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True: We're inside a DataTables request in an admin View. False: We're not!
	 */
	private function doing_datatables_ajax_request() {
		return 'gv_datatables_data' === Utils::_REQUEST( 'action' );
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
		if ( ! $this->is_dashboard_view() ) {
			return false;
		}

		return View::by_id( Utils::_GET( 'gvid' ) );
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

		return GF_Entry::by_id( Utils::_GET( 'entry_id' ) );
	}

	/**
	 * Checks whether this an edit entry request.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @return bool Yes?
	 */
	public function is_edit_entry( $form_id = 0 ) {
		if ( ! $this->is_entry( $form_id ) ) {
			return false;
		}

		if ( empty( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		return true;
	}
}
