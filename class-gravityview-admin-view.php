<?php
class GravityView_Admin_View extends GravityView_Extension {

	protected $_title = 'Dashboard Views';

	protected $_version = 'develop';

	protected $_text_domain = 'gravityview-adminview';

	/**
	 * @var int The download ID on gravityview.co
	 */
	protected $_item_id = 999999; // FIX ME!

	protected $_min_gravityview_version = '2.3';

	protected $_path = __FILE__;

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

		add_filter( 'gravityview/entry/permalink', array( $this, 'entry_permalink' ), 10, 4 );
		add_filter( 'gravityview/widget/search/form/action', array( $this, 'search_action' ) );
		add_action( 'gravityview_search_widget_fields_before', array( $this, 'search_fields' ) );

		add_filter( 'gravityview_page_links_args', array( $this, 'page_links_args' ) );

		add_filter( 'gravityview/view/links/directory', array( $this, 'directory_link' ), 10, 2 );
		add_filter( 'gravityview/entry-list/link', array( $this, 'entry_list_link' ), 10, 3 );

		add_action( 'gravityview_before', array( $this, 'maybe_output_notices' ) );
	}

	/**
	 * Set the current request to the admin request.
	 *
	 * Caled from current_screen action.
	 *
	 * @return void
	 */
	public function set_request() {
		if ( ! $current_screen = get_current_screen() ) {
			return;
		}

		if ( 'gravityview_page_adminview' !== $current_screen->id ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-gravityview-admin-view-request.php';
		gravityview()->request = new GravityView_Admin_View_Request();
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

		$action_link = add_query_arg( 'page', 'adminview', admin_url( 'edit.php?post_type=gravityview' ) );

		$actions['adminview'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'id', urlencode( $post->ID ), $action_link ) ),
			__( 'View in Admin', 'gravityview-adminview' )
		);

		return $actions;
	}

	/**
	 * Add menu option.
	 *
	 * Called from `admin_menu` action.
	 *
	 * @return void
	 */
	public function add_submenu() {

		if( 'adminview' !== \GV\Utils::_GET( 'page' ) ) {
			return;
		} elseif ( ! isset( $_GET['id'] ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview' ) );
			exit();
		}

		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Admin View', 'gravityview-presets' ),
			__( 'Admin View', 'gravityview-presets' ),
			'manage_options',
			'adminview',
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
			gravityview()->log->error( 'View cannot be displayed in the admin; View with ID #{view_id} could not be found.', array( 'view_id' => $view_id = \GV\Utils::_GET( 'id' ) ) );

			printf( '<h1>%s</h1>', sprintf( esc_html__( 'View #%s not found.', 'gravityview-admin' ), intval( $view_id ) ) );

			return;
		}

		/**
		 * @filter `gravityview/admin/before` Before the admin renders.
		 * @param \GV\View $view The View.
		 */
		do_action( 'gravityview/admin/before', $view );

		$view_renderer = new \GV\View_Renderer();
		$entry_renderer = new \GV\Entry_Renderer();

		if ( ! class_exists( 'GravityView_View' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		echo '<div class="wrap">';

		/** Entry */
		if ( $entry = gravityview()->request->is_entry() ) {
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
		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $permalink;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
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
		if ( ! $request = gravityview()->request ) {
			return $url;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $url;
		}

		if ( ! $view = $request->is_view() ) {
			return $url;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
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
		if ( ! $request = gravityview()->request ) {
			return $url;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $url;
		}

		if ( ! $view = $request->is_view() ) {
			return $url;
		}

		$args = array(
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
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
		if ( ! $request = gravityview()->request ) {
			return $args;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $args;
		}

		if ( ! $view = $request->is_view() ) {
			return $args;
		}

		$url = admin_url( 'edit.php' );

		$args['base'] = add_query_arg( array(
			'pagenum' => '%#%',
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
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
		if ( ! $request = gravityview()->request ) {
			return $link;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $link;
		}

		if ( ! $view = $request->is_view() ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
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
		if ( ! $request = gravityview()->request ) {
			return $link;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return $link;
		}

		if ( ! $view = $request->is_view() ) {
			return $link;
		}

		$url = admin_url( 'edit.php' );

		$url = add_query_arg( array(
			'post_type' => 'gravityview',
			'page' => 'adminview',
			'id' => $view->ID,
			'entry_id' => $entry['id'],
		), $url );

		return $url;
	}

	/**
	 * Kick off notice sequences. Perhaps...
	 *
	 * Called from `gravityview/admin/before` action.
	 *
	 * @param \GV\View $view The View.
	 *
	 * @return void
	 */
	public function maybe_output_notices( $view ) {
		GravityView_Delete_Entry::getInstance()->display_message();
		GravityView_Duplicate_Entry::getInstance()->display_message();
	}

	/**
	 * Kick off delete/duplicate sequences. Perhaps...
	 *
	 * Called from `current_screen` action.
	 *
	 * @return void
	 */
	public function process_entry() {
		if ( ! $request = gravityview()->request ) {
			return;
		}

		if ( ! method_exists( $request, 'is_admin_view' ) || ! $request->is_admin_view() ) {
			return;
		}

		GravityView_Delete_Entry::getInstance()->process_delete();
		GravityView_Duplicate_Entry::getInstance()->process_duplicate();
	}
}

new GravityView_Admin_View;
