<?php

namespace GravityKit\GravityView\DashboardViews;

use GravityKitFoundation;
use GravityView_Delete_Entry;
use GravityView_Duplicate_Entry;
use GravityView_Edit_Entry;
use GravityView_frontend;
use GravityView_View_Data;
use GV\Edit_Entry_Renderer;
use GV\Entry_Renderer;
use GV\Field_Collection;
use GV\Frontend_Request;
use GV\GF_Entry;
use GV\GF_Field;
use GV\Shortcodes\gravityview;
use GV\View as GV_View;
use GV\View_Renderer;
use WP_Post;

/**
 * This class modifies GravityView's View object and its output.
 *
 * @since 2.0.0
 */
class View {
	const DEFAULT_ACCESS_ROLE = 'administrator';

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'gravityview/view/get', [ $this, 'modify_view' ] );
		add_filter( 'the_posts', [ $this, 'filter_posts' ], 10, 2 );
		add_filter( 'pre_do_shortcode_tag', [ $this, 'prevent_gravityview_shortcode_output' ], 10, 3 );

		// Rewrite links.
		add_filter( 'gravityview/view/links/directory', [ $this, 'rewrite_directory_link' ], 10, 2 );
		add_filter( 'gravityview/entry/permalink', [ $this, 'rewrite_single_entry_link' ], 10, 3 );
		add_filter( 'gravityview/template/links/back/url', [ $this, 'rewrite_single_entry_back_link' ] );
		add_filter( 'gravityview/edit/link', [ $this, 'rewrite_edit_entry_link' ], 10, 3 );
		add_filter( 'gravityview/edit_entry/success', [ $this, 'rewrite_edit_entry_back_link' ], 10, 5 );
		add_filter( 'gravityview/edit_entry/cancel_link', [ $this, 'rewrite_edit_entry_cancel_link' ], 10, 3 );
		add_filter( 'gravityview/widget/search/form/action', [ $this, 'rewrite_search_action_link' ] );
		add_filter( 'gk/gravityview/widget/search/clear-button/params', [ $this, 'rewrite_search_clear_link' ] );
		add_filter( 'wp_redirect', [ $this, 'rewrite_entry_duplication_redirect_link' ] );
		add_filter( 'wp_redirect', [ $this, 'rewrite_entry_deletion_redirect_link' ] );
		add_filter( 'gravityview_page_links_args', [ $this, 'rewrite_pagination_links' ] );

		// Modify GravityView's View list table.
		add_filter( 'post_row_actions', [ $this, 'modify_gravityview_list_table_view_actions' ], 10, 2 );

		// Handle entry duplication/deletion.
		add_action( 'current_screen', [ $this, 'handle_entry_duplication_and_deletion' ] );

		// Localize the View editor script.
		add_filter( 'gk/gravityview/dashboard-views/view/editor/localization', [ $this, 'localize_view_editor' ] );

		add_filter( 'get_sample_permalink_html', [ $this, 'filter_sample_permalink_html' ], 10, 2 );

		// Filter messages when updating Views.
		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ], 20 );
		add_filter( 'bulk_post_updated_messages', [ $this, 'post_updated_messages' ], 20 );
	}

	/**
	 * Filters the posts to remove internal-only Views.
	 *
	 * @since 2.0.0
	 *
	 * @param array $posts The posts.
	 *
	 * @return array The updated posts.
	 */
	public function filter_posts( $posts ) {
		if ( is_admin() || ! is_array( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $key => $post ) {
			if ( $this->is_internal_only( $post->ID, true ) ) {
				unset( $posts[ $key ] );
			}
		}

		return $posts;
	}

	/**
	 * Check if the View is internal-only.
	 *
	 * @since 2.0.0
	 *
	 * @param int  $view_id         The View ID.
	 * @param bool $check_post_type Whether to confirm that "gravityview" is the post type.
	 *
	 * @return bool Whether the View is internal-only.
	 */
	private function is_internal_only( $view_id, $check_post_type = false ) {
		if ( $check_post_type && 'gravityview' !== get_post_type( $view_id ) ) {
			return false;
		}

		$view_settings = gravityview_get_template_settings( $view_id );

		return ( $view_settings[ ViewSettings::SETTINGS_PREFIX . '_enable' ] ?? '' ) && ! empty( $view_settings[ ViewSettings::SETTINGS_PREFIX . '_internal_only' ] ?? 1 );
	}

	/**
	 * Filters the sample permalink HTML to remove the front-end link if the View is internal-only.
	 *
	 * @since 2.0.0
	 *
	 * @param string $permalink_html The sample permalink HTML.
	 * @param int    $post_id        The post ID.
	 *
	 * @return string The updated sample permalink HTML.
	 */
	public function filter_sample_permalink_html( $permalink_html, $post_id ) {
		if ( $this->is_internal_only( $post_id, true ) ) {
			return '';
		}

		return $permalink_html;
	}

	/**
	 * Filters admin messages to remove the front-end link if the View is internal-only.
	 *
	 * @since 2.0.0
	 *
	 * @param array $messages    Existing messages.
	 * @param array $bulk_counts Array of status => count of items being modified.
	 *
	 * @return array Messages with GravityView messages added, if Internal-Only is enabled.
	 */
	public function post_updated_messages( $messages, $bulk_counts = null ) {
		global $post;

		$post_id = get_the_ID();

		if ( ! $this->is_internal_only( $post_id ) ) {
			return $messages;
		}

		// By default, there will only be one item being modified.
		// When in the `bulk_post_updated_messages` filter, there will be passed a number
		// of modified items that will override this array.
		$bulk_counts = is_null( $bulk_counts ) ? [
			'updated'   => 1,
			'locked'    => 1,
			'deleted'   => 1,
			'trashed'   => 1,
			'untrashed' => 1,
		] : $bulk_counts;

		$messages['gravityview'] = [
			0           => '', // Unused. Messages start at index 1.
			1           => __( 'View updated.', 'gk-gravityview-dashboard-views' ),
			2           => __( 'View updated.', 'gk-gravityview-dashboard-views' ),
			3           => __( 'View deleted.', 'gk-gravityview-dashboard-views' ),
			4           => __( 'View updated.', 'gk-gravityview-dashboard-views' ),
			/* translators: %s: date and time of the revision */
			5           => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'gk-gravityview-dashboard-views' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6           => __( 'View published.', 'gk-gravityview-dashboard-views' ),
			7           => __( 'View saved.', 'gk-gravityview-dashboard-views' ),
			8           => __( 'View submitted.', 'gk-gravityview-dashboard-views' ),
			9           => sprintf(
			/* translators: Date and time the View is scheduled to be published */
				__( 'View scheduled for: %1$s.', 'gk-gravityview-dashboard-views' ),
				// translators: Publish box date format, see http://php.net/date.
				date_i18n( __( 'M j, Y @ G:i', 'gk-gravityview-dashboard-views' ), strtotime( $post->post_date ?? '' ) )
			),
			/* translators: %s and %s are HTML tags linking to the View on the website */
			10          => __( 'View draft updated.', 'gk-gravityview-dashboard-views' ),

			/**
			 * These apply to `bulk_post_updated_messages`
			 *
			 * @file wp-admin/edit.php
			 */
			// translators: %s: number of Views.
			'updated'   => _n( '%s View updated.', '%s Views updated.', $bulk_counts['updated'], 'gk-gravityview-dashboard-views' ),
			// translators: %s: number of Views.
			'locked'    => _n( '%s View not updated, somebody is editing it.', '%s Views not updated, somebody is editing them.', $bulk_counts['locked'], 'gk-gravityview-dashboard-views' ),
			// translators: %s: number of Views.
			'deleted'   => _n( '%s View permanently deleted.', '%s Views permanently deleted.', $bulk_counts['deleted'], 'gk-gravityview-dashboard-views' ),
			// translators: %s: number of Views.
			'trashed'   => _n( '%s View moved to the Trash.', '%s Views moved to the Trash.', $bulk_counts['trashed'], 'gk-gravityview-dashboard-views' ),
			// translators: %s: number of Views.
			'untrashed' => _n( '%s View restored from the Trash.', '%s Views restored from the Trash.', $bulk_counts['untrashed'], 'gk-gravityview-dashboard-views' ),
		];

		return $messages;
	}

	/**
	 * Returns Views configured for display in the Dashboard.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function get_dashboard_views() {
		$dashboard_views = [];

		$views = get_posts(
			[
				'post_type'   => 'gravityview',
				'post_status' => 'any',
				'numberposts' => -1,
			]
		);

		if ( is_wp_error( $views ) ) {
			return [];
		}

		foreach ( $views as $view ) {
			// gravityview_get_template_settings() can be used, but it adds hooks that result in degraded performance with some plugins.
			$view_settings = get_post_meta( $view->ID, '_gravityview_template_settings', true );

			if ( empty( $view_settings['dashboard_views_enable'] ) ) {
				continue;
			}

			$user_required_roles = array_merge(
				$view_settings['dashboard_views_user_roles'] ?? [],
				[ self::DEFAULT_ACCESS_ROLE ]
			);

			$filtered_roles = array_values(
				array_filter( $user_required_roles, 'current_user_can' )
			);

			$user_first_met_role = array_shift( $filtered_roles );

			$dashboard_views[ $view->ID ] = [
				'id'                      => $view->ID,
				'link'                    => add_query_arg(
					[ 'page' => AdminMenu::get_view_submenu_slug( $view->ID ) ],
					admin_url( 'admin.php' )
				),
				'title'                   => $view_settings[ ViewSettings::SETTINGS_PREFIX . '_custom_name' ] ?: $view->post_title, // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
				'current_user_accessible' => ! ! $user_first_met_role,
				'current_user_role_match' => $user_first_met_role,
				'settings'                => $view_settings,
			];
		}

		return $dashboard_views;
	}

	/**
	 * Adds a proxy for `$request->is_dashboard_view()`.
	 *
	 * @since 1.0.0
	 *
	 * @return false|Request null: Not GV request. false: Not an Admin View request. Otherwise, returns Dashboard Views request.
	 */
	public static function is_dashboard_view() {
		static $response;

		if ( ! $response ) {
			$request = gravityview()->request;

			$response = $request instanceof Request && $request->is_dashboard_view() ? $request : false;
		}

		return $response;
	}

	/**
	 * Modifies the View object when it's retrieved by GravityView.
	 *
	 * @since 2.0.0
	 *
	 * @param GV_View $view The View.
	 *
	 * @return mixed The updated View.
	 */
	public function modify_view( $view ) {
		// Prevent rendering in the frontend.
		if ( ! self::is_dashboard_view() ) {
			if ( $this->is_internal_only( $view->ID ) ) {
				add_filter( 'gravityview/request/is_renderable', '__return_false' );
			}

			return $view;
		}

		// Optionally hide fields.
		$updated_fields = new Field_Collection();

		foreach ( $view->fields->all() as $field ) {
			if ( self::is_dashboard_view() ) {
				$is_visible = ! ! empty( $field->{ViewSettings::SETTINGS_PREFIX . '_show_field'} );

				/**
				 * Sets the View's field visibility (hidden or not).
				 *
				 * @since  2.0.0
				 * @filter `gk/gravityview/dashboard-views/view/field/visibility`
				 *
				 * @param bool     $is_visible Whether the field is visible.
				 * @param GF_Field $field      The field.
				 * @param GV_View  $view       The View.
				 */
				$is_visible = apply_filters( 'gk/gravityview/dashboard-views/view/field/visibility', $is_visible, $field, $view );

				if ( $is_visible ) {
					continue;
				}
			}

			if ( gravityview()->request instanceof Frontend_Request && $field->{ViewSettings::SETTINGS_PREFIX . '_exclude_from_frontend'} ) {
				continue;
			}

			$updated_fields->add( $field );
		}

		$view->fields = $updated_fields;

		if ( self::is_dashboard_view() ) {
			/**
			 * Modifies the View object.
			 *
			 * @since  2.0.0
			 * @filter `gk/gravityview/dashboard-views/view`
			 *
			 * @param GV_View $view The View.
			 */
			$view = apply_filters( 'gk/gravityview/dashboard-views/view', $view );
		}

		return $view;
	}

	/**
	 * Renders the View.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_view() {
		global $post, $current_screen, $wp_current_filter;

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view || empty( self::get_dashboard_views()[ $view->ID ]['current_user_accessible'] ) ) {
			return;
		}

		$_current_screen    = $current_screen;
		$_wp_current_filter = $wp_current_filter;
		$_post              = $post;

		set_current_screen( 'front' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $view->ID ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_enqueue_scripts();

		/**
		 * Triggers before the View is rendered.
		 *
		 * @since  2.0.0
		 * @filter `gk/gravityview/dashboard-views/view/before`
		 *
		 * @param GV_View $view The View.
		 */
		do_action( 'gk/gravityview/dashboard-views/view/before', $view );

		if ( ! did_action( 'gravityview_include_frontend_actions' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		$layout = 'directory';

		if ( gravityview()->request->is_edit_entry() ) {
			$layout = 'edit_entry';

			$output = ( new Edit_Entry_Renderer() )->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( gravityview()->request->is_entry() ) {
			$layout = 'single_entry';

			$output = ( new Entry_Renderer() )->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$output = ( new View_Renderer() )->render( $view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Modifies the View output.
		 *
		 * @since  2.0.0
		 * @filter `gk/gravityview/dashboard-views/view/output`
		 *
		 * @param string  $view_template The View template.
		 * @param GV_View $view          The View.
		 * @param string  $layout        The layout.
		 */
		$output = apply_filters( 'gk/gravityview/dashboard-views/view/output', $output, $view, $layout );

		$view_template = '<div class="wrap dashboard-view">[output]</div>';

		/**
		 * Modifies the View template.
		 *
		 * @since  2.0.0
		 * @filter `gk/gravityview/dashboard-views/view/template`
		 *
		 * @param string  $view_template The View template.
		 * @param GV_View $view          The View.
		 */
		$view_template = apply_filters( 'gk/gravityview/dashboard-views/view/template', $view_template, $view );

		echo str_replace( '[output]', $output, $view_template ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );

		$wp_current_filter = [ 'wp_print_footer_scripts' ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		GravityView_frontend::getInstance()->setGvOutputData( $view_data );
		GravityView_frontend::getInstance()->add_scripts_and_styles();

		wp_print_scripts();
		wp_print_styles();

		$current_screen    = $_current_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post              = $_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_current_filter = $_wp_current_filter; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Conditionally prevents the [gravityview] shortcode output.
	 *
	 * @since 2.0.0
	 *
	 * @param string $output The shortcode output.
	 * @param string $tag    The shortcode tag.
	 * @param array  $attr   The shortcode attributes array.
	 *
	 * @return string The shortcode output.
	 */
	public function prevent_gravityview_shortcode_output( $output, $tag, $attr ) {
		if ( 'gravityview' !== $tag || empty( $attr['id'] ) ) {
			return $output;
		}

		$view_settings = gravityview_get_template_settings( $attr['id'] );

		if ( ! empty( $view_settings[ ViewSettings::SETTINGS_PREFIX . '_enable' ] ) && ! empty( $view_settings[ ViewSettings::SETTINGS_PREFIX . '_internal_only' ] ) ) {
			return '';
		}

		return $output;
	}


	/**
	 * Rewrites the View directory link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link The directory link.
	 *
	 * @return string The updated directory link.
	 */
	public function rewrite_directory_link( $link ) {
		return ! self::is_dashboard_view() ? $link : Request::get_base_url();
	}

	/**
	 * Rewrites the single entry link.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $link  The single entry link.
	 * @param GF_Entry $entry The Gravity Forms entry.
	 *
	 * @return string The updated single entry link.
	 */
	public function rewrite_single_entry_link( $link, $entry ) {
		return ! self::is_dashboard_view() ? $link : add_query_arg( [ 'entry_id' => $entry->ID ], Request::get_base_url() );
	}

	/**
	 * Rewrites the single entry back link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link The single entry back link.
	 *
	 * @return string The updated single entry back link.
	 */
	public function rewrite_single_entry_back_link( $link ) {
		return ! self::is_dashboard_view() ? $link : Request::get_base_url();
	}

	/**
	 * Rewrites the edit entry link.
	 *
	 * @since 2.0.0
	 *
	 * @param string  $link  The edit entry link.
	 * @param array   $entry The Gravity Forms entry.
	 * @param GV_View $view  The View.
	 *
	 * @return string The updated edit entry link.
	 */
	public function rewrite_edit_entry_link( $link, $entry, $view ) {
		if ( ! self::is_dashboard_view() ) {
			return $link;
		}

		return add_query_arg(
			[
				'entry_id' => $entry['id'],
				'edit'     => wp_create_nonce( GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id'] ) ),
			],
			Request::get_base_url()
		);
	}

	/**
	 * Rewrites the edit entry back link.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @param string $link  The edit entry cancel link.
	 * @param array  $form  The Gravity Forms form.
	 * @param array  $entry The Gravity Forms entry.
	 *
	 * @return string The updated edit entry cancel link.
	 */
	public function rewrite_edit_entry_cancel_link( $link, $form, $entry ) {
		return ! self::is_dashboard_view() ? $link : add_query_arg( [ 'entry_id' => $entry['id'] ], Request::get_base_url() );
	}

	/**
	 * Rewrites the search action link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link The search action link.
	 *
	 * @return string The updated search action link.
	 */
	public function rewrite_search_action_link( $link ) {
		return ! self::is_dashboard_view() ? $link : Request::get_base_url();
	}

	/**
	 * Rewrites the search clear link.
	 *
	 * @since 2.0.0
	 *
	 * @param array $params The search clear link parameters.
	 *
	 * @return array The updated search clear link parameters.
	 */
	public function rewrite_search_clear_link( $params ) {
		$params['url'] = ! self::is_dashboard_view() ? ( $params['url'] ?? '' ) : Request::get_base_url();

		return $params;
	}

	/**
	 * Rewrites pagination link.
	 *
	 * @since 2.0.0
	 *
	 * @param array $params Pagination link parameters.
	 *
	 * @return array The updated pagination link parameters.
	 */
	public function rewrite_pagination_links( array $params ) {
		$params['base'] = ! self::is_dashboard_view() ? ( $params['base'] ?? '' ) : add_query_arg( [ 'pagenum' => '%#%' ], Request::get_base_url() );

		return $params;
	}

	/**
	 * Rewrites entry deletion redirect link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link The entry deletion redirect link.
	 *
	 * @return string The updated entry deletion redirect link.
	 */
	public function rewrite_entry_deletion_redirect_link( $link ) {
		if ( ! self::is_dashboard_view() || ! preg_match( '/(?=.*delete=)(?=.*status=)/', $link ) ) {
			return $link;
		}

		return Request::get_base_url();
	}

	/**
	 * Rewrites entry duplication redirect link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link The entry duplication redirect link.
	 *
	 * @return string The updated entry duplication redirect link.
	 */
	public function rewrite_entry_duplication_redirect_link( $link ) {
		if ( ! self::is_dashboard_view() || ! preg_match( '/(?=.*duplicate=)(?=.*status=)/', $link ) ) {
			return $link;
		}

		return Request::get_base_url();
	}

	/**
	 * Calls the entry duplication and deletion handlers if these actions are being performed.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function handle_entry_duplication_and_deletion() {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		GravityView_Delete_Entry::getInstance()->process_delete();
		GravityView_Duplicate_Entry::getInstance()->process_duplicate();
	}

	/**
	 * Adds a "View in Dashboard" link to View actions in the GravityView list table.
	 *
	 * @since 2.0.0
	 *
	 * @param array   $actions View actions.
	 * @param WP_Post $post    The post.
	 *
	 * @return array The updated View actions.
	 */
	public function modify_gravityview_list_table_view_actions( $actions, $post ) {
		if ( 'gravityview' !== get_post_type( $post ) ) {
			return $actions;
		}

		foreach ( self::get_dashboard_views() as $dashboard_view ) {
			if ( $dashboard_view['id'] !== $post->ID || ! $dashboard_view['current_user_accessible'] ) {
				continue;
			}

			$position = array_search( 'view', array_keys( $actions ), true ) + 1;

			$actions = array_merge(
				array_slice( $actions, 0, $position ),
				[
					'view_in_dashboard' => sprintf(
						'<a href="%s">%s</a>',
						esc_url( $dashboard_view['link'] ),
						esc_html__( 'View in Dashboard', 'gk-gravityview-dashboard-views' )
					),
				],
				array_slice( $actions, $position )
			);

			if ( $dashboard_view['settings'][ ViewSettings::SETTINGS_PREFIX . '_internal_only' ] ?? 1 ) {
				unset( $actions['view'] );
			}

			break;
		}

		return $actions;
	}

	/**
	 * Localizes the View editor script {@see Plugin::enqueue_ui_assets}.
	 *
	 * @since 2.0.0
	 *
	 * @param array $localization Localization strings.
	 *
	 * @return array Updated localization strings.
	 */
	public function localize_view_editor( $localization ) {
		return array_merge(
			$localization,
			[
				'frontend_display_disabled_notice' => esc_html__( 'This View is configured for display in the Dashboard only. You can disable this under the Dashboard Views tab.', 'gk-gravityview-dashboard-views' ),
			]
		);
	}
}
