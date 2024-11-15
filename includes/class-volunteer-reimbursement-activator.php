<?php

/**
 * Fired during plugin activation
 *
 * @link       https://amsa.org.au
 * @since      1.0.0
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/includes
 * @author     Steven Zhang <stevenzhangshao@gmail.com>
 */
class Volunteer_Reimbursement_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'volunteer_reimbursements';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submit_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            form_type varchar(50) NOT NULL, 
            meta longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $result = dbDelta( $sql );
        

        
        // if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
        //     error_log('table '.$table_name.' was not created');
        // }else{
        //     error_log('table '.$table_name.' was created');
        // }
        
	}

}
