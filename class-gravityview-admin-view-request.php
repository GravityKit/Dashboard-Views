<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default Admin View Request class.
 */
class GravityView_Admin_View_Request extends \GV\Request {

	public function __construct() {
		add_filter( 'gravityview/request/is_renderable', array( $this, 'declare_renderable' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Declare this class as something that is renderable.
	 */
	public function declare_renderable( $is_renderable, $instance ) {
		return get_class( $this ) === get_class( $instance );
	}

	/**
	 * The current screen is an Admin View.
	 */
	public function is_admin_view() {
		return $this->is_admin() && 'adminview' === \GV\Utils::_GET( 'page' ) /** @todo current_screen */;
	}

	/**
	 * The current admin screen is a View.
	 *
	 * @return false|\GV\View
	 */
	public function is_view() {
		if ( ! $this->is_admin_view() ) {
			return false;
		}

		return \GV\View::by_id( \GV\Utils::_GET( 'id' ) );
	}

	/**
	 * The current entry.
	 *
	 * @return false|\GV\Entry
	 */
	public function is_entry( $form_id = 0 ) {
		if ( ! $view = $this->is_view() ) {
			return false;
		}

		return \GV\GF_Entry::by_id( \GV\Utils::_GET( 'entry_id' ) );
	}

	/**
	 * Is this an edit entry?
	 *
	 * @return bool Yes?
	 */
	public function is_edit_entry( $form_id = 0 ) {
		if ( ! $entry = $this->is_entry( $form_id ) ) {
			return false;
		}

		if ( empty( $_GET['edit'] ) ) {
			return false;
		}

		return true;
	}
}
