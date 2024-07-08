<?php

namespace GravityKit\GravityView\DashboardViews\Integrations;

/**
 * Adds support for GravityFlow.
 *
 * @since 2.0.0
 */
class GravityFlow {
	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
