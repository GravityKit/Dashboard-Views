<?php

namespace GravityKit\GravityView\DashboardViews\Integrations;

use GravityKit\GravityView\DashboardViews\Request;
use GravityKit\GravityView\DashboardViews\View;

/**
 * Adds support for GravityFlow.
 *
 * @since 2.0.0
 */
class GravityMaps {
	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'gravityview/maps/marker/url', [ $this, 'rewrite_single_entry_link' ], 10, 2 );
	}

	/**
	 * Rewrites the single entry link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link   The single entry link.
	 * @param array  $entry  Gravity Forms entry object.
	 *
	 * @return string The updated single entry link.
	 */
	public function rewrite_single_entry_link( $link, $entry ) {
		return ! View::is_dashboard_view() ? $link : add_query_arg( [ 'entry_id' => $entry['id'] ], Request::get_base_url() );
	}
}
