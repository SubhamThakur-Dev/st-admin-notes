<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wordpress.org/plugins/
 * @since             1.0.0
 * @package           ST_Admin_Notes
 *
 * @wordpress-plugin
 * Plugin Name:       ST Admin Notes
 * Plugin URI:        https://wordpress.org/plugins/
 * Description:       Simple draggable admin notes overlay. Create/edit notes in the admin, and they persist per site.
 * Version:           1.0.0
 * Author:            Subham Thakur
 * Author URI:        https://profiles.wordpress.org/subhamt411/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       st-admin-notes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'ST_ADMIN_NOTES_VERSION', '1.0.0' );


function st_admin_notes_activate_() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-st-admin-notes-activator.php';
	ST_Admin_Notes_Activator::activate();
}


function st_admin_notes_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-st-admin-notes-deactivator.php';
	ST_Admin_Notes_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'st_admin_notes_activate_' );
register_deactivation_hook( __FILE__, 'st_admin_notes_deactivate' );


require plugin_dir_path( __FILE__ ) . 'includes/class-st-admin-notes.php';


function st_admin_notes_run() {

	$plugin = new ST_Admin_Notes();
	$plugin->run();

}
st_admin_notes_run();

