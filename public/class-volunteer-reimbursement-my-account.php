<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://amsa.org.au
 * @since      1.0.0
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/public
 * @author     Steven Zhang <stevenzhangshao@gmail.com>
 */


class Volunteer_Reimbursement_My_Account {
    	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
		$this->version = $version;
        add_filter('woocommerce_account_menu_items', array($this, 'add_reimbursement_tab_to_my_account'), 10, 1);

        add_action('woocommerce_account_reimbursement-claims_endpoint', array($this, 'display_reimbursement_claims_tab_content'));

        add_action('init', array($this, 'add_reimbursement_claims_endpoint'));
    }

    /**
     * Add a "Reimbursement Claims" tab to the WooCommerce My Account menu.
     *
     * @since 1.0.0
     * @param array $menu_links The current WooCommerce My Account menu links.
     * @return array The modified menu links with the new tab added if applicable.
     */
    public function add_reimbursement_tab_to_my_account($menu_links) {
        $user_id = get_current_user_id();
    
        // Check if the user has any reimbursement claims
        if ($this->user_has_reimbursement_claims($user_id)) {
            // Add the new tab link
            $menu_links['reimbursement-claims'] = 'Reimbursement Claims';
        }
    
        return $menu_links;
    }

    /**
     * Check if a user has any reimbursement claims.
     *
     * @since 1.0.0
     * @param int $user_id The user ID to check for reimbursement claims.
     * @return bool True if the user has reimbursement claims, false otherwise.
     */
    public function user_has_reimbursement_claims($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d", 
            $user_id
        ));
    
        return $result > 0;
    }

    /**
     * Display the content for the "Reimbursement Claims" tab in the My Account page.
     *
     * Retrieves the claims data for the current user and loads the appropriate view template.
     *
     * @since 1.0.0
     */
    public function display_reimbursement_claims_tab_content() {
        global $wpdb;
        $user_id = get_current_user_id();
        if(!$user_id){
            return;
        }
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';
    
        // Get the reimbursement claims for the user
        $claims = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY submit_date DESC",
            $user_id
        ));
        
        require_once VR_PLUGIN_PATH . 'public/partials/my-account.php';

    }

    /**
     * Register the "Reimbursement Claims" endpoint in WordPress.
     *
     * This endpoint allows the WooCommerce My Account page to display the custom tab's content.
     *
     * @since 1.0.0
     */
    public function add_reimbursement_claims_endpoint() {
        add_rewrite_endpoint('reimbursement-claims', EP_ROOT | EP_PAGES);
    }


}