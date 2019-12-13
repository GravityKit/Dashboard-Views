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

	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 1 );
		add_filter( 'post_row_actions', array( $this, 'view_admin_action' ), 10, 2 );
	}

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

		echo '<style> body { background:  white; } </style>';

		$view_id = \GV\Utils::_GET( 'id' );

		if ( empty( $view_id ) ) {
			return;
		}

		if ( ! $view = \GV\View::by_id( $view_id ) ) {

			gravityview()->log->error( 'View cannot be displayed in the admin; View with ID #{view_id} could not be found.', array( 'view_id' => $view_id ) );

			printf( '<h1>%s</h1>', sprintf( esc_html__( 'View #%s not found.', 'gravityview-admin' ), esc_html( $view_id ) ) );

			return;
		}

		$renderer = new \GV\View_Renderer();

		if ( ! class_exists( 'GravityView_View' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		gravityview()->request                     = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		echo '<div class="wrap">';

		echo $renderer->render( $view );

		echo '</div>';

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view_id );
		GravityView_frontend::getInstance()->setGvOutputData( $view_data );

		//GravityView_frontend::getInstance()->setGvOutputData( $view_data );

		GravityView_frontend::getInstance()->add_scripts_and_styles();

		wp_print_scripts();
		wp_print_styles();
	}
}

new GravityView_Admin_View;
