<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://amsa.org.au
 * @since      1.0.0
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/includes
 * @author     Steven Zhang <stevenzhangshao@gmail.com>
 */
class Volunteer_Reimbursement_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		Volunteer_Reimbursement_Deactivator::remove_manage_volunteer_claims_capability();
	}

	public static function remove_manage_volunteer_claims_capability(){
		$roles = ['administrator', 'shop_manager']; // You can add more roles if needed

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->remove_cap('manage_volunteer_claims');
            }
        }
	}

}
