<?php

use GV\Entry;
use GV\Extension;
use GV\Request;
use GV\Utils;
use GV\View;

class GravityView_Dashboard_Views extends Extension {
	/**
	 * Extension title.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_title = 'Dashboard Views';

	/**
	 * Extension version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_version = GV_DASHBOARD_VIEWS_VERSION;

	/**
	 * Extension text domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_text_domain = 'gk-gravityview-dashboard-views';

	/**
	 * Download ID on gravitykit.com
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $_item_id = 672171;

	/**
	 * Minimum required GV version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_min_gravityview_version = '2.16';

	/**
	 * Minimum required PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_min_php_version = '5.6.20';

	/**
	 * The full path and filename of the extension file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_path = __FILE__;

	const PAGE_SLUG = 'dashboard_views';

	/**
	 * Capability needed to see Dashboard Views
	 *
	 * @todo Move to per-View setting
	 */
	const SUBMENU_CAPABILITY = 'read_private_gravityviews';

	/**
	 * Adds hooks.
	 *
	 * Anything that relies on gravityview()->request should be
	 * added on the `current_screen` action instead of the `admin_init` action.
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 1 );
		add_filter( 'post_row_actions', array( $this, 'view_admin_action' ), 10, 2 );

		add_action( 'current_screen', array( $this, 'set_request' ), 1 );
		add_action( 'current_screen', array( $this, 'process_entry' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'gravityview_noconflict_scripts', array( $this, 'noconflict_scripts' ) );
		add_action( 'gravityview_noconflict_styles', array( $this, 'noconflict_styles' ) );

		add_filter( 'gravityview/entry/permalink', array( $this, 'entry_permalink' ), 10, 3 );
		add_filter( 'gravityview/widget/search/form/action', array( $this, 'search_action' ) );
		add_action( 'gravityview_search_widget_fields_before', array( $this, 'search_fields' ) );

		add_filter( 'gravityview_page_links_args', array( $this, 'page_links_args' ) );

		add_filter( 'gravityview/view/links/directory', array( $this, 'directory_link' ), 10, 2 );
		add_filter( 'gravityview/entry-list/link', array( $this, 'entry_list_link' ), 10, 3 );
		add_filter( 'gravityview/edit/link', array( $this, 'edit_entry_link' ), 10, 3 );

		add_filter( 'gravityview/edit_entry/success', array( $this, 'edit_entry_success' ), 10, 4 );
		add_filter( 'gravitykit.comnnected_form_links', array( $this, 'add_data_source_link' ), 20 );

		$this->load_legacy();

		add_action( 'gravityview_before', array( $this, 'maybe_output_notices' ) );

		// Ratings & Reviews relies on priority 15.
		add_action( 'gravityview_after', array( $this, 'set_post_global' ), -100 );
		add_action( 'gravityview_after', array( $this, 'unset_post_global' ), 10000 );

		add_filter( 'wp_redirect', array( $this, 'handle_redirects' ) );

		// Support DataTables.
		if ( class_exists( 'GV_Extension_DataTables' ) ) {
			$this->add_datatables_hooks();
		}
	}

	/**
	 * Adds hooks specific to DataTables.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_datatables_hooks() {
		add_action( 'wp_ajax_nopriv_gv_datatables_data', array( $this, 'set_request' ), 1 );
		add_action( 'wp_ajax_gv_datatables_data', array( $this, 'set_request' ), 1 );
		add_action( 'gravityview/template/after', array( $this, 'set_post_global' ), -100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'set_post_global' ), -100 );
		add_action( 'gravityview/template/after', array( $this, 'unset_post_global' ), 10000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'unset_post_global' ), 10000 );

		// Load DataTables core logic, not normally loaded in admin.
		$gv_extension_data_tables = new GV_Extension_DataTables();
		$gv_extension_data_tables->core_actions();

		add_action( 'admin_enqueue_scripts', array( new GV_Extension_DataTables_Data(), 'add_scripts_and_styles' ) );

		/**
		 * `GravityView_Template` class isn't loaded in the admin; CSS files won't get called
		 *  We need to manually enqueue CSS for now.
		 */
		add_action(
			'gravityview/template/before',
			function ( $gravityview ) {

				if ( 'datatables_table' !== $gravityview->view->settings->get( 'template' ) ) {
					return;
				}

				$datatables_css_url = apply_filters( 'gravityview_datatables_style_src', plugins_url( 'assets/css/datatables.css', GV_DT_FILE ) );

				wp_enqueue_style( 'gravityview_style_datatables_table', $datatables_css_url ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			},
			100
		);
	}

	/**
	 * Sets the $post global in the admin screen.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Post|null $post
	 *
	 * @param int|View $view View being rendered.
	 */
	public function set_post_global( $view = 0 ) {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		global $post;

		if ( $post ) {
			return;
		}

		$backup_view_id = $view instanceof View ? $view->ID : $view;

		$post = get_post( Utils::_GET( 'gvid', $backup_view_id ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Modifies the links shown in the Connected Form links in the Data Source box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Links to show.
	 */
	public function add_data_source_link( $links ) {
		global $post;

		if ( 'single' !== gravityview()->request->is_admin( '' ) ) {
			return $links;
		}

		$links[] = $this->get_admin_link( $post->ID );

		return $links;
	}

	/**
	 * Unsets the $post global in the admin screen.
	 *
	 * @since 1.0.0
	 */
	public function unset_post_global() {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		global $post;

		$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Sets the current request to the admin request.
	 * Called from the `current_screen` action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function set_request() {
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-gravityview-dashboard-views-request.php';

			$dashboard_request = new GravityView_Dashboard_Views_Request();

			if ( ! $dashboard_request->is_dashboard_view() ) {
				return;
			}

			gravityview()->request = $dashboard_request;

		} catch ( Exception $exception ) {

			gravityview()->log->debug( 'The Dashboard Request failed to be set.', array( 'data' => $exception ) );
		}
	}

	/**
	 * Adds a proxy for `$request->is_dashboard_view()`.
	 *
	 * @since 1.0.0
	 *
	 * @return null|false|GravityView_Dashboard_Views_Request null: Not GV request. false: Not an Admin View request. Otherwise, returns Admin View request.
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
	 * Enqueues required scripts sometimes.
	 * Called from the `admin_enqueue_scripts` action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		// Add approval scripts and styles.
		$approval_field = \GravityView_Fields::get_instance( 'entry_approval' );
		$approval_field->register_scripts_and_styles();

		// Add notes scripts and styles.
		global $wp_filter;

		$enqueue_scripts = $wp_filter['wp_enqueue_scripts'];

		if ( ! $enqueue_scripts ) {
			return;
		}

		// @todo Do this the getInstance() way or something, or convert the method to static.
		if ( $enqueue_scripts->callbacks['10'] ) {
			foreach ( $enqueue_scripts->callbacks['10'] as $id => $callback ) {
				if ( strpos( $id, 'register_scripts' ) && is_array( $callback['function'] ) ) {
					if ( $callback['function'][0] instanceof GravityView_Field_Notes ) {
						call_user_func( $callback['function'] );
					}
				}
			}
		}
	}

	/**
	 * Returns a list of scripts that can be loaded in the admin interface.
	 * Called via the `gravityview_noconflict_scripts` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $handles List of allowed script handles.
	 *
	 * @return array The allowed handles.
	 */
	public function noconflict_scripts( $handles ) {
		if ( ! self::is_dashboard_view() ) {
			return $handles;
		}

		$handles = array_merge(
			$handles,
			array(
				// Approval.
				'gravityview-field-approval',
				'gravityview-field-approval-tippy',
				'gravityview-field-approval-popper',

				// Notes.
				'gravityview-notes',
			)
		);

		return $handles;
	}

	/**
	 * Returns a list of styles that can be loaded in the admin interface.
	 * Called via the `gravityview_noconflict_styles` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $handles List of allowed style handles.
	 *
	 * @return array The allowed handles.
	 */
	public function noconflict_styles( $handles ) {
		if ( ! self::is_dashboard_view() ) {
			return $handles;
		}

		$handles = array_merge(
			$handles,
			array(
				// Approval.
				'gravityview-field-approval',
				'gravityview-field-approval-tippy',

				// Notes.
				'gravityview-notes',
			)
		);

		return $handles;
	}

	/**
	 * Outputs the View in Admin links in row actions.
	 * Called from `post_row_actions` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $actions The actions.
	 * @param WP_Post $post    The post.
	 *
	 * @return array The actions.
	 */
	public function view_admin_action( $actions, $post ) {
		if ( 'gravityview' !== get_post_type( $post ) ) {
			return $actions;
		}

		$actions[ self::PAGE_SLUG ] = $this->get_admin_link( $post->ID );

		return $actions;
	}

	/**
	 * Generates a link to the Dashboard View.
	 *
	 * @since 1.0.0
	 *
	 * @param int $view_id The View ID.
	 *
	 * @return string HTML anchor tag.
	 */
	private function get_admin_link( $view_id = 0 ) {
		$base = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'edit.php?post_type=gravityview' ) );

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'gvid', rawurlencode( $view_id ), $base ) ),
			esc_html__( 'View in Dashboard', 'gk-gravityview-dashboard-views' )
		);
	}

	/**
	 * Adds a menu option.
	 * Called from `admin_menu` action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu() {

		if ( self::PAGE_SLUG !== Utils::_GET( 'page' ) ) {
			return;
		} elseif ( ! isset( $_GET['gvid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview' ) );
			exit();
		}

		$view = get_post( $_GET['gvid'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $view ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview' ) );
			exit();
		}

		if ( ! GravityView_Roles_Capabilities::has_cap( array( self::SUBMENU_CAPABILITY ), $view ) ) {

			$message = esc_html__( 'The user must have the {cap} capability. {link}Read a guide about modifying capabilities{/link}.', 'gk-gravityview-dashboard-views' );

			$message = strtr(
				$message,
				array(
					'{cap}'   => '<code>read_private_gravityviews</code>',
					'{link}'  => '<a href="https://docs.gravitykit.com/article/333-modifying-user-role-capabilities">',
					'{/link}' => '</a>',
				)
			);

			wp_die( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		add_submenu_page(
			'admin.php?page=_gk_admin_menu',
			// translators: %s: View title.
			sprintf( __( '%s &lsaquo; Admin View', 'gk-gravityview-dashboard-views' ), $view->post_title ),
			esc_html__( 'Dashboard View', 'gk-gravityview-dashboard-views' ),
			self::SUBMENU_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_screen' )
		);
	}

	/**
	 * Renders the View.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_screen() {
		echo '
		<style> 
			body { background:  white; }
			.update-nag { display: none; } 
		</style>';

		if ( version_compare( \GV\Plugin::$version, '2.16', '>=' ) ) {
			$view = gravityview()->request->is_view( true );
		} else {
			$view = gravityview()->request->is_view();
		}

		if ( ! $view ) {
			gravityview()->log->error( 'View cannot be displayed in the admin; View with ID #{view_id} could not be found.', array( 'view_id' => $view_id = Utils::_GET( 'gvid' ) ) );

			// translators: %s: View ID.
			printf( '<h1>%s</h1>', sprintf( esc_html__( 'View #%s not found.', 'gk-gravityview-dashboard-views' ), intval( $view_id ) ) );

			return;
		}

		/**
		 * Triggered before the admin renders.
		 *
		 * @filter `gravityview/dashboard-view/before`
		 *
		 * @param View $view The View.
		 */
		do_action( 'gravityview/dashboard-view/before', $view );

		$view_renderer  = new \GV\View_Renderer();
		$entry_renderer = new \GV\Entry_Renderer();
		$edit_renderer  = new \GV\Edit_Entry_Renderer();

		if ( ! did_action( 'gravityview_include_frontend_actions' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		echo '<div class="wrap">';

		/** Edit */
		if ( gravityview()->request->is_edit_entry() ) {
			echo $edit_renderer->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			/** Entry */
		} elseif ( gravityview()->request->is_entry() ) {
			echo $entry_renderer->render( gravityview()->request->is_entry(), $view, gravityview()->request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			/** View */
		} else {
			echo $view_renderer->render( $view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );
		GravityView_frontend::getInstance()->setGvOutputData( $view_data );

		GravityView_frontend::getInstance()->add_scripts_and_styles();

		wp_print_scripts();
		wp_print_styles();
	}

	/**
	 * Filters the entry permalink to lead to the Admin View page.
	 * Called via `gravityview/entry/permalink` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $permalink The permalink.
	 * @param Entry  $entry     The entry.
	 * @param View   $view      The View.
	 *
	 * @return string The URL.
	 */
	public function entry_permalink( $permalink, $entry, $view ) {
		if ( ! self::is_dashboard_view() ) {
			return $permalink;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg(
			array(
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'gvid'      => Utils::get( $view, 'ID', Utils::_GET( 'gvid' ) ),
				'entry_id'  => $entry->ID,
			),
			$url
		);

		return $url;
	}

	/**
	 * Filters the search action to stay in the admin.
	 * Called from `gravityview/widget/search/form/action` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL.
	 *
	 * @return string The URL.
	 */
	public function search_action( $url ) {
		if ( ! self::is_dashboard_view() ) {
			return $url;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view ) {
			return $url;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg(
			array(
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'gvid'      => $view->ID,
			),
			$url
		);

		return $url;
	}

	/**
	 * Outputs tracking fields as necessary to stay on the same page.
	 * Called via `gravityview_search_widget_fields_before` action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function search_fields() {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view ) {
			return;
		}

		$args = array(
			'post_type' => 'gravityview',
			'page'      => self::PAGE_SLUG,
			'gvid'      => $view->ID,
		);

		foreach ( $args as $field => $value ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $field ), esc_attr( $value ) );
		}
	}

	/**
	 * Fixes pagination links.
	 * Called from `gravityview_page_links_args` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The paginate_links arguments.
	 *
	 * @return array $args The arguments.
	 */
	public function page_links_args( $args ) {
		if ( ! self::is_dashboard_view() ) {
			return $args;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view ) {
			return $args;
		}

		$url = admin_url( 'edit.php' );

		$args['base'] = add_query_arg(
			array(
				'pagenum'   => '%#%',
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'gvid'      => $view->ID,
			),
			$url
		);

		return $args;
	}

	/**
	 * Globally modifies directory link in the admin.
	 * Called from `gravityview/view/links/directory` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link The URL.
	 *
	 * @return string The URL.
	 */
	public function directory_link( $link ) {
		if ( ! self::is_dashboard_view() ) {
			return $link;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg(
			array(
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'gvid'      => $view->ID,
			),
			$url
		);

		return $url;
	}

	/**
	 * Outputs the correct link to the other entries.
	 * Called from the `gravityview/entry-list/link` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link  The link to filter.
	 * @param array  $entry The entry.
	 *
	 * @return string The link.
	 */
	public function entry_list_link( $link, $entry ) {
		$request = self::is_dashboard_view();

		if ( ! $request ) {
			return $link;
		}

		$view = $request->is_view();

		if ( ! $view ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg(
			array(
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'id'        => $view->ID,
				'entry_id'  => $entry['id'],
			),
			$url
		);

		return $url;
	}

	/**
	 * Outputs the correct edit entry link.
	 * Called from the `gravityview/edit/link` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url   The link.
	 * @param array  $entry The entry.
	 * @param View   $view  The View.
	 *
	 * @return string The corrected link.
	 */
	public function edit_entry_link( $url, $entry, $view ) {
		if ( ! self::is_dashboard_view() ) {
			return $url;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg(
			array(
				'post_type' => 'gravityview',
				'page'      => self::PAGE_SLUG,
				'gvid'      => $view->ID,
				'entry_id'  => $entry['id'],
				'edit'      => wp_create_nonce( GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id'] ) ),
			),
			$url
		);

		return $url;
	}

	/**
	 * Fixes the edit entry success message correctly.
	 * Called from the `gravityview/edit_entry/success` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message   The message.
	 * @param int    $view_id   The View ID.
	 * @param array  $entry     The entry.
	 * @param string $back_link The return URL.
	 *
	 * @return string The fixed success message.
	 */
	public function edit_entry_success( $message, $view_id, $entry, $back_link ) {
		return str_replace( 'edit.php?post_type', 'edit.php?page=' . self::PAGE_SLUG . '&post_type', $message );
	}

	/**
	 * Kicks off notice sequences. Perhaps...
	 * Called from `gravityview/dashboard-view/before` action.
	 *
	 * @since 1.0.0
	 *
	 * @param View $view The View.
	 *
	 * @return void
	 */
	public function maybe_output_notices( $view ) {
		GravityView_Delete_Entry::getInstance()->display_message();

		if ( class_exists( 'GravityView_Duplicate_Entry' ) ) {
			GravityView_Duplicate_Entry::getInstance()->display_message();
		}
	}

	/**
	 * Prevents fatal error "GravityView_View not found" in gravityview/includes/class-frontend-views.php on line 1170
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_legacy() {
		// GravityView_View not loaded in AJAX, but used by GravityView_Edit_Entry.
		if ( ! class_exists( 'GravityView_View' ) && ! class_exists( '\GravityView_View' ) && defined( 'GRAVITYVIEW_DIR' ) ) {
			include_once GRAVITYVIEW_DIR . 'includes/class-template.php';
		}
	}

	/**
	 * Kicks off delete/duplicate sequences. Perhaps...
	 * Called from `current_screen` action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process_entry() {
		if ( ! self::is_dashboard_view() ) {
			return;
		}

		GravityView_Delete_Entry::getInstance()->process_delete();
		GravityView_Duplicate_Entry::getInstance()->process_duplicate();
	}

	/**
	 * Ensures that all redirects to the Dashboard View have the `gvid` parameter set after interacting with an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $redirect_url Redirect URL.
	 *
	 * @return string
	 */
	public function handle_redirects( $redirect_url ) {
		if ( function_exists( 'gravityview' ) && ! gravityview()->request instanceof GravityView_Dashboard_Views_Request ) {
			return $redirect_url;
		}

		// If we're not redirecting to the Dashboard View, leave the location as-is.
		if ( strpos( $redirect_url, 'page=dashboard_views' ) === false ) {
			return $redirect_url;
		}

		// Set the gvid parameter that's removed by Delete Entry, Update Entry, etc.
		$location = add_query_arg( 'gvid', $_GET['gvid'] ?? '', $redirect_url ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $location;
	}
}

new GravityView_Dashboard_Views();
