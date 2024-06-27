<?php

namespace GravityKit\GravityView\DashboardViews\Integrations;

use Exception;
use GravityKit\GravityView\DashboardViews\Plugin;
use GravityKit\GravityView\DashboardViews\Request;
use GV\Template_Context;
use GV_Extension_DataTables_Data;

/**
 * Adds support for DataTables.
 *
 * @since TBD
 */
class DataTables {
	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		add_action( 'gravityview/template/after', [ $this, 'enqueue_ui_assets' ] );
		add_action( 'wp_ajax_gv_datatables_data', [ $this, 'set_request' ] );
	}

	/**
	 * Enqueues DataTables assets.
	 *
	 * @since TBD
	 *
	 * @param Template_Context $gravityview_context The GravityView template context.
	 *
	 * @return void
	 */
	public function enqueue_ui_assets( $gravityview_context ) {
		if ( 'datatables_table' !== $gravityview_context->view->settings->get( 'template' ) ) {
			return;
		}

		( new GV_Extension_DataTables_Data() )->add_scripts_and_styles( $gravityview_context );

		$datatables_css_url = apply_filters( 'gravityview_datatables_style_src', plugins_url( 'assets/css/datatables.css', GV_DT_FILE ) );

		wp_enqueue_style( 'gravityview_style_datatables_table', $datatables_css_url ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	/**
	 * Decodes the DataTables Ajax data and optionally sets the request to the admin request.
	 * This is needed because {@see Plugin::set_request()} hooks to 'current_screen' that's not triggered in Ajax requests.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function set_request() {
		if ( isset( $_REQUEST['page'] ) || empty( $_REQUEST['getData'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		try {
			$data = json_decode( wp_unslash( $_REQUEST['getData'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( empty( $data['page'] ) ) {
				return;
			}

			$_REQUEST['page'] = $data['page'];

			$dashboard_request = new Request();

			if ( ! $dashboard_request->is_dashboard_view() ) {
				return;
			}

			gravityview()->request = $dashboard_request;
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}
	}
}
