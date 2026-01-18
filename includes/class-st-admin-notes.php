<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class ST_Admin_Notes {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'ST_ADMIN_NOTES_VERSION' ) ) {
			$this->version = ST_ADMIN_NOTES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'st-admin-notes';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-st-admin-notes-loader.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-st-admin-notes-i18n.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-st-admin-notes-admin.php';

		$this->loader = new ST_Admin_Notes_Loader();

	}

	private function set_locale() {

		$plugin_i18n = new ST_Admin_Notes_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_admin_hooks() {

		$plugin_admin = new ST_Admin_Notes_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_pages' );
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'render_canvas' );
		$this->loader->add_action( 'admin_bar_menu', $plugin_admin, 'add_admin_bar_items', 80 );
		$this->loader->add_action( 'wp_ajax_stn_update_note', $plugin_admin, 'ajax_update_note' );
		$this->loader->add_action( 'wp_ajax_stn_delete_note', $plugin_admin, 'ajax_delete_note' );
		$this->loader->add_action( 'wp_ajax_stn_add_note', $plugin_admin, 'ajax_add_note' );
		$this->loader->add_action( 'wp_ajax_stn_toggle_overlay', $plugin_admin, 'ajax_toggle_overlay' );
		$this->loader->add_action( 'wp_ajax_stn_fetch_notes', $plugin_admin, 'ajax_fetch_notes' );

	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

}

