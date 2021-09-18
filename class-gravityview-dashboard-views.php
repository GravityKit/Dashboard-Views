<?php
class GravityView_Dashboard_Views extends \GV\Extension {

	protected $_title = 'Dashboard Views';

	protected $_version = GV_DASHBOARD_VIEWS_VERSION;

	protected $_text_domain = 'gravityview-dashboard-views';

	/**
	 * @var int The download ID on gravityview.co
	 */
	protected $_item_id = 672171;

	protected $_min_gravityview_version = '2.3';

	protected $_min_php_version = '5.4';

	protected $_path = __FILE__;

	const PAGE_SLUG = 'dashboard_views';

	/**
	 * Capability needed to see Dashboard Views
	 * @todo Move to per-View setting
	 */
	const SUBMENU_CAPABILITY = 'edit_gravityviews';

	/**
	 * Hooks.
	 *
	 * Anything that relies on gravityview()->request should be
	 * added on the `current_screen` action instead of the `admin_init` action.
	 */
	public function add_hooks() {

		add_action( 'admin_menu', array( $this, 'add_submenu' ), 1 );
		add_filter( 'post_row_actions', array( $this, 'view_admin_action' ), 10, 2 );

		add_action( 'current_screen', array( $this, 'set_request' ), 1 );
		add_action( 'current_screen', array( $this, 'process_entry' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'gravityview_noconflict_scripts', array( $this, 'noconflict_scripts' ) );
		add_action( 'gravityview_noconflict_styles', array( $this, 'noconflict_styles' ) );

		add_filter( 'gravityview/entry/permalink', array( $this, 'entry_permalink' ), 10, 4 );
		add_filter( 'gravityview/widget/search/form/action', array( $this, 'search_action' ) );
		add_action( 'gravityview_search_widget_fields_before', array( $this, 'search_fields' ) );

		add_filter( 'gravityview_page_links_args', array( $this, 'page_links_args' ) );

		add_filter( 'gravityview/view/links/directory', array( $this, 'directory_link' ), 10, 2 );
		add_filter( 'gravityview/entry-list/link', array( $this, 'entry_list_link' ), 10, 3 );
		add_filter( 'gravityview/edit/link', array( $this, 'edit_entry_link' ), 10, 3 );

		add_filter( 'gravityview/edit_entry/success', array( $this, 'edit_entry_success' ), 10, 4 );
		add_filter( 'gravityview_connected_form_links', array( $this, 'add_data_source_link' ), 20, 2 );

		$this->load_legacy();

		add_action( 'gravityview_before', array( $this, 'maybe_output_notices' ) );

		// Ratings & Reviews relies on priority 15
		add_action( 'gravityview_after', array( $this, 'set_post_global' ), -100 );
		add_action( 'gravityview_after', array( $this, 'unset_post_global' ), 10000 );

		// Support DataTables
		if ( class_exists( 'GV_Extension_DataTables' ) ) {
			$this->add_datatables_hooks();
		}

	}

	/**
	 * Adds hooks specific to DataTables
	 *
	 * @since 1.0
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

		// load DataTables core logic, not normally loaded in admin
		$GV_Extension_DataTables = new GV_Extension_DataTables;
		$GV_Extension_DataTables->core_actions();

		$DT = new GV_Extension_DataTables_Data();
		add_action( 'admin_enqueue_scripts', array( $DT, 'add_scripts_and_styles' ) );

		/**
		 * `GravityView_Template` class isn't loaded in the admin; CSS files won't get called
		 *  We need to manually enqueue CSS for now.
		 */
		add_action( 'gravityview/template/before', function( $gravityview ) {

			if( 'datatables_table' !== $gravityview->view->settings->get('template') ) {
				return;
			}

			$datatables_css_url = apply_filters( 'gravityview_datatables_style_src', plugins_url( 'assets/css/datatables.css', GV_DT_FILE ) );

			wp_enqueue_style( 'gravityview_style_datatables_table', $datatables_css_url );

		}, 100 );
	}

	/**
	 * Modify the links shown in the Connected Form links in the Data Source box
	 *
	 * @param array $links Links to show
	 * @param array $form Gravity Forms form array
	 */
	public function add_data_source_link( $links, $form = array() ) {
		global $post;

		if ( 'single' !== gravityview()->request->is_admin('') ) {
			return $links;
		}

		$links[] = $this->get_admin_link( $post->ID );

		return $links;
	}

	/**
	 * Set the $post global in the admin screen
	 *
	 * @global \WP_Post|null $post
	 *
	 * @param int|\GV\View $view View being rendered
	 */
	public function set_post_global( $view = 0 ) {

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		global $post;

		if ( $post ) {
			return;
		}

		$backup_view_id = $view instanceof \GV\View ? $view->ID : $view;

		$post = get_post( \GV\Utils::_GET( 'gvid', $backup_view_id ) );
	}

	/**
	 * @param int|\GV\View $view View being rendered
	 */
	public function unset_post_global( $view ) {

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		global $post;

		$post = null;
	}

	/**
	 * Set the current request to the admin request.
	 *
	 * Caled from current_screen action.
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
	 * A small `$request->is_dashboard_view()` proxy.
	 *
	 * @return null|false|GravityView_Dashboard_Views_Request null: Not GV request. false: Not an Admin View request. Otherwise, returns Admin View request.
	 */
	static public function is_dashboard_view() {

		if ( ! $request = gravityview()->request ) {
			return null;
		}

		if ( ! method_exists( $request, 'is_dashboard_view' ) || ! $request->is_dashboard_view() ) {
			return false;
		}

		return $request;
	}

	/**
	 * Equeue required scripts sometimes.
	 *
	 * Called from the `admin_enqueue_scripts` action.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		// Add approval scripts and styles
		$approval_field = \GravityView_Fields::get_instance( 'entry_approval' );
		$approval_field->register_scripts_and_styles();

		// Add notes scripts and styles
		global $wp_filter;
		if ( $enqueue_scripts = $wp_filter['wp_enqueue_scripts'] ) {
			// @todo Do this the getInstance() way or something, or convert the method to static
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
	}

	/**
	 * Scripts to load on the admin interface.
	 *
	 * Called via the `gravityview_noconflict_scripts` filter.
	 *
	 * @param array $handles The script handles safelist.
	 *
	 * @return array The allowed handles.
	 */
	public function noconflict_scripts( $handles ) {
		if ( ! self::is_dashboard_view() ) {
			return $handles;
		}

		$handles = array_merge( $handles, array(
			// Approval
			'gravityview-field-approval',
			'gravityview-field-approval-tippy',
			'gravityview-field-approval-popper',

			// Notes
			'gravityview-notes'
		) );

		return $handles;
	}

	/**
	 * Styles to load on the admin interface.
	 *
	 * Called via the `gravityview_noconflict_styles` filter.
	 *
	 * @param array $handles The style handles safelist.
	 *
	 * @return array The allowed handles.
	 */
	public function noconflict_styles( $handles ) {
		if ( ! self::is_dashboard_view() ) {
			return $handles;
		}

		$handles = array_merge( $handles, array(
			// Approval
			'gravityview-field-approval',
			'gravityview-field-approval-tippy',

			// Notes
			'gravityview-notes'
		) );

		return $handles;
	}

	/**
	 * Output the View in Admin links in row actions.
	 *
	 * Called from `post_row_actions` filter.
	 *
	 * @param array $actions The actions.
	 * @param WP_Post $post The post.
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
	 * Generates a link to the Dashboard View
	 *
	 * @param int $view_id
	 *
	 * @return string HTML anchor tag
	 */
	private function get_admin_link( $view_id = 0 ) {

		$base = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'edit.php?post_type=gravityview' ) );

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'gvid', urlencode( $view_id ), $base ) ),
			esc_html__( 'View in Dashboard', 'gravityview-dashboard-views' )
		);
	}

	/**
	 * Add menu option.
	 *
	 * Called from `admin_menu` action.
	 *
	 * @return void
	 */
	public function add_submenu() {

		if( self::PAGE_SLUG !== \GV\Utils::_GET( 'page' ) ) {
			return;
		} elseif ( ! isset( $_GET['gvid'] ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview' ) );
			exit();
		}

		$view = get_post( $_GET['gvid'] );

		if ( ! $view ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview' ) );
			exit();
		}

		if( ! GravityView_Roles_Capabilities::has_cap( 'edit_gravityview', $view ) ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=gravityview',
			sprintf( __( '%s &lsaquo; Admin View', 'gravityview-presets' ), $view->post_title ),
			__( 'Dashboard View', 'gravityview-presets' ),
			self::SUBMENU_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_screen' )
		);
	}

	/**
	 * Renders the View
	 *
	 * @return void
	 */
	public function render_screen() {

		echo '
		<style> 
			body { background:  white; }
			.update-nag { display: none; } 
		</style>';

		if ( ! $view = gravityview()->request->is_view() ) {
			gravityview()->log->error( 'View cannot be displayed in the admin; View with ID #{view_id} could not be found.', array( 'view_id' => $view_id = \GV\Utils::_GET( 'gvid' ) ) );

			printf( '<h1>%s</h1>', sprintf( esc_html__( 'View #%s not found.', 'gravityview-admin' ), intval( $view_id ) ) );

			return;
		}

		/**
		 * @filter `gravityview/dashboard-view/before` Before the admin renders.
		 * @param \GV\View $view The View.
		 */
		do_action( 'gravityview/dashboard-view/before', $view );

		$view_renderer = new \GV\View_Renderer();
		$entry_renderer = new \GV\Entry_Renderer();
		$edit_renderer = new \GV\Edit_Entry_Renderer();

		if ( ! did_action( 'gravityview_include_frontend_actions' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		echo '<div class="wrap">';

		/** Edit */
		if ( gravityview()->request->is_edit_entry() ) {
			echo $edit_renderer->render( gravityview()->request->is_entry(), $view, gravityview()->request );
		/** Entry */
		} elseif ( $entry = gravityview()->request->is_entry() ) {
			echo $entry_renderer->render( $entry, $view, gravityview()->request );
			/** View */
		} else {
			echo $view_renderer->render( $view );
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
	 * Filter the entry permalink to lead to the Admin View page.
	 *
	 * Called via `gravityview/entry/permalink` filter.
	 *
	 * @param string $permalink The permalink.
	 * @param \GV\Entry $entry The entry.
	 * @param \GV\View $view The View.
	 * @param \GV\Request $request The request.
	 *
	 * @return string The URL.
	 */
	public function entry_permalink( $permalink, $entry, $view, $request ) {

		if ( ! $request = self::is_dashboard_view() ) {
			return $permalink;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => \GV\Utils::get( $view, 'ID', \GV\Utils::_GET( 'gvid' ) ),
			'entry_id' => $entry->ID,
		), $url );

		return $url;
	}

	/**
	 * Filter the search action to stay in the admin.
	 *
	 * Called from `gravityview/widget/search/form/action` filter.
	 *
	 * @param string $url The URL.
	 *
	 * @return string The URL.
	 */
	public function search_action( $url ) {

		if ( ! self::is_dashboard_view() ) {
			return $url;
		}

		if ( ! $view = gravityview()->request->is_view() ) {
			return $url;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => $view->ID,
		), $url );

		return $url;
	}

	/**
	 * Output tracking fields as necessary to stay on the same page.
	 *
	 * Called via `gravityview_search_widget_fields_before` action.
	 *
	 * @return void
	 */
	public function search_fields() {

		if ( ! self::is_dashboard_view() ) {
			return;
		}

		if ( ! $view = gravityview()->request->is_view() ) {
			return;
		}

		$args = array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => $view->ID,
		);

		foreach ( $args as $field => $value ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $field ), esc_attr( $value ) );
		}
	}

	/**
	 * Fix pagination links.
	 *
	 * Called from `gravityview_page_links_args` filter.
	 *
	 * @param array $args The paginate_links arguments.
	 *
	 * @return array $args The arguments.
	 */
	public function page_links_args( $args ) {

		if ( ! self::is_dashboard_view() ) {
			return $args;
		}

		if ( ! $view = gravityview()->request->is_view() ) {
			return $args;
		}

		$url = admin_url( 'edit.php' );

		$args['base'] = add_query_arg( array(
			'pagenum' => '%#%',
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => $view->ID,
		), $url );

		return $args;
	}

	/**
	 * Global modification of the directory link in the admin.
	 *
	 * Called from `gravityview/view/links/directory` filter.
	 *
	 * @param string $link The URL.
	 * @param \GV\Context $context The context.
	 *
	 * @return string The URL.
	 */
	public function directory_link( $link, $context ) {

		if ( ! self::is_dashboard_view() ) {
			return $link;
		}

		if ( ! $view = gravityview()->request->is_view() ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => $view->ID,
		), $url );

		return $url;
	}

	/**
	 * Output the correct link to the other entries.
	 *
	 * Called from the `gravityview/entry-list/link` filter.
	 *
	 * @param string $link The link to filter.
	 * @param array $entry The entry.
	 * @param \GravityView_Entry_List $list The list object.
	 *
	 * @return string The link.
	 */
	public function entry_list_link( $link, $entry, $list ) {

		if ( ! $request = self::is_dashboard_view() ) {
			return $link;
		}

		if ( ! $view = $request->is_view() ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'id' => $view->ID,
			'entry_id' => $entry['id'],
		), $url );

		return $url;
	}

	/**
	 * Output the correct edit entry link.
	 *
	 * Called from the `gravityview/edit/link` filter.
	 *
	 * @param string $url The link.
	 * @param array $entry The entry.
	 * @param \GV\View $view The View.
	 *
	 * @return string The corrected link.
	 */
	public function edit_entry_link( $url, $entry, $view ) {

		if ( ! self::is_dashboard_view() ) {
			return $url;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => self::PAGE_SLUG,
			'gvid' => $view->ID,
			'entry_id' => $entry['id'],
			'edit' => wp_create_nonce( GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id'] ) ),
		), $url );

		return $url;
	}

	/**
	 * Fix the edit entry success message correctly.
	 *
	 * Called from the `gravityview/edit_entry/success` filter.
	 *
	 * @param string $message The message.
	 * @param int $view_id The View ID.
	 * @param array $entry The entry.
	 * @param string $back_link The return URL.
	 *
	 * @return string The fixed success message.
	 */
	public function edit_entry_success( $message, $view_id, $entry, $back_link ) {
		return str_replace( 'edit.php?post_type', 'edit.php?page=' . self::PAGE_SLUG . '&post_type', $message );
	}

	/**
	 * Kick off notice sequences. Perhaps...
	 *
	 * Called from `gravityview/dashboard-view/before` action.
	 *
	 * @param \GV\View $view The View.
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
	 * Prevent fatal error "GravityView_View not found" in gravityview/includes/class-frontend-views.php on line 1170
	 */
	private function load_legacy() {

		// GravityView_View not loaded in AJAX, but used by GravityView_Edit_Entry
		if ( ! class_exists( 'GravityView_View' ) && ! class_exists( '\GravityView_View' ) && defined('GRAVITYVIEW_DIR') ) {
			include_once( GRAVITYVIEW_DIR .'includes/class-template.php' );
		}
	}

	/**
	 * Kick off delete/duplicate sequences. Perhaps...
	 *
	 * Called from `current_screen` action.
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
}

new GravityView_Dashboard_Views();
