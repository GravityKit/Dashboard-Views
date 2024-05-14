<?php

namespace GravityKit\GravityView\DashboardViews\Integrations;

/**
 * Adds support for GravityFlow.
 */
class GravityFlow {
	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		if ( ! function_exists( 'gravity_flow' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_ui_assets' ] );
	}

	/**
	 * Enqueues Gravity Flow assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_ui_assets() {
		// Taken from "gravityflow/includes/integrations/class-gravityview-approval-links.php".
		wp_enqueue_style(
			'gravityview-field-workflow-approval-links',
			gravity_flow()->get_base_url() .
			'/includes/integrations/css/gravity-view-flow-fields.css',
			[],
			gravity_flow()->get_version(),
			'screen'
		);
	}
}
