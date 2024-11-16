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

    public function add_reimbursement_tab_to_my_account($menu_links) {
        $user_id = get_current_user_id();
    
        // Check if the user has any reimbursement claims
        if ($this->user_has_reimbursement_claims($user_id)) {
            // Add the new tab link
            $menu_links['reimbursement-claims'] = 'Reimbursement Claims';
        }
    
        return $menu_links;
    }

    public function user_has_reimbursement_claims($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d", 
            $user_id
        ));
    
        return $result > 0;
    }

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
    
        echo '<h2>Your Reimbursement Claims</h2>';
    
        if ($claims) {
            echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Form ID</th>';
            echo '<th>Submit Date</th>';
            echo '<th>Status</th>';
            echo '<th>Form Type</th>';
            echo '<th>Purpose</th>';
            echo '<th>Transaction Details</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
    
            foreach ($claims as $claim) {
                $claim_meta = json_decode($claim->meta, true); // Decode the meta JSON
                $purpose = isset($claim_meta['purpose']) ? esc_html($claim_meta['purpose']) : 'N/A';
                $transaction_details = isset($claim_meta['transaction_details']) ? esc_html($claim_meta['transaction_details']) : 'N/A';
    
                echo '<tr>';
                echo '<td>' . esc_html($claim->id) . '</td>';
                echo '<td>' . esc_html(date('Y-m-d', strtotime($claim->submit_date))) . '</td>';
                echo '<td>' . MetaDataFormatter::format_status_colored($claim->status) . '</td>';
                echo '<td>' . MetaDataFormatter::format_form_type($claim->form_type) . '</td>';
                echo '<td>' . $purpose . '</td>';
                echo '<td>' . $transaction_details . '</td>';
                echo '</tr>';
            }
    
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>You have no reimbursement claims.</p>';
        }
    }

    public function add_reimbursement_claims_endpoint() {
        add_rewrite_endpoint('reimbursement-claims', EP_ROOT | EP_PAGES);
    }


}