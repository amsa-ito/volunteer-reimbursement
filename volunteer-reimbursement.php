<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://amsa.org.au
 * @since             1.0.0
 * @package           Volunteer_Reimbursement
 *
 * @wordpress-plugin
 * Plugin Name:       Volunteer Reimbursement
 * Plugin URI:        https://amsa.org.au
 * Description:       Manages reimbursement for volunteers
 * Version:           1.0.0
 * Author:            Steven Zhang
 * Author URI:        https://amsa.org.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       volunteer-reimbursement
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
function debug_print($content){
	error_log(print_r($content,true));
}
define( 'VR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VOLUNTEER_REIMBURSEMENT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-volunteer-reimbursement-activator.php
 */
function activate_volunteer_reimbursement() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-volunteer-reimbursement-activator.php';
	Volunteer_Reimbursement_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-volunteer-reimbursement-deactivator.php
 */
function deactivate_volunteer_reimbursement() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-volunteer-reimbursement-deactivator.php';
	Volunteer_Reimbursement_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_volunteer_reimbursement' );
register_deactivation_hook( __FILE__, 'deactivate_volunteer_reimbursement' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-volunteer-reimbursement.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_volunteer_reimbursement() {

	$plugin = new Volunteer_Reimbursement();
	$plugin->run();

}
run_volunteer_reimbursement();
