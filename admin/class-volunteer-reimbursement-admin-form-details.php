<?php 
/**
 * Volunteer_Reimbursement_Admin_Form_Details Class
 * 
 * This class provides the admin functionalities for managing volunteer reimbursement forms, 
 * including handling AJAX form submissions, rendering the claim details page, and managing 
 * meta-data-related rendering for attachments and committee selection fields.
 * 
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/admin
 * @author     Steven Zhang <stevenzhangshao@gmail.com>
 */

class Volunteer_Reimbursement_Admin_Form_Details{
    /**
     * Plugin name identifier.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;


	public function __construct( $plugin_name, $version ) {
        add_action( 'admin_menu', array($this, 'vr_admin_detail_page') );
        
        add_action('wp_ajax_save_admin_claim_form', array($this, 'save_vr_claim_form'));
        add_action('wp_ajax_nopriv_save_admin_claim_form', array($this, 'save_vr_claim_form'));

    }

    /**
     * Adds a hidden admin page for viewing claim details.
     */
	public function vr_admin_detail_page() {
        add_submenu_page(
			null, // No menu item in the sidebar
			'Claim Details',
			'CLaim Details',
			'manage_volunteer_claims',
			'vr-claim-detail',
			array($this,'render_vr_claim_detail_page')
		);
    }

	/**
     * Handles saving of the volunteer reimbursement form via AJAX.
     */
    public function save_vr_claim_form(){
		// check_ajax_referer($this->plugin_name.'-nonce', 'nonce');
		if (!current_user_can('manage_volunteer_claims')){
			wp_send_json_error([ 'status' => 'error', 'message' => 'You do not have sufficient permissions.' ] );
		}

        global $wpdb;
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';

        $claim_id = intval($_POST['claim_id']);
        
        $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $claim_id));

        if (!$claim) {
            wp_send_json_error([ 'status' => 'error', 'message' => 'Form not found.' ] );
        }

        $form_type = $claim->form_type;

        $form_data = apply_filters('vr_parse_'. $form_type ,$_POST, $_FILES);

        $new_status = sanitize_text_field($_POST['status']);
        
        $existing_meta = json_decode($claim->meta, true) ?: [];

        $new_form_data = array_replace_recursive($existing_meta, $form_data);

        $error_msg = apply_filters('vr_check_valid_'. $form_type, $new_form_data);
        
        if($error_msg){
			wp_send_json_error( [ 'status' => 'error', 'message' => $error_msg ] );
		}

		$user_id = $claim->id;
		if(isset($form_data['payee_email'])){
			$user_by_email = get_user_by('email', $form_data['payee_email']);
			if($user_by_email){
				$user_id = $user_by_email ->id;
			}
		}
        
        $result = $wpdb->update($table_name, [
            'status' => $new_status,
            'meta' => json_encode($new_form_data),
			'user_id' => $user_id,
        ], ['id' => $claim_id]);

        if ($result !== false) {
			$old_status = $claim->status;
			if($new_status != $old_status){
				do_action('vr_reimbursement_' . $old_status . '_to_' . $new_status, $claim, $new_status);
			}
            wp_send_json_success(['status' => 'success', 'message' => 'Claim saved successfully!']);

        } else {
            wp_send_json_error(['status'=>'error','message'=>'Failed to update the form.']);
        }

        wp_die();
    }

	/**
     * Renders the claim detail page in the admin interface.
     */
    public function render_vr_claim_detail_page(){
		if (!isset($_GET['claim_id'])) {
			wp_die('No form ID specified.');
		}
	
		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';
		$claim_id = intval($_GET['claim_id']);
		$claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $claim_id));
	
		if (!$claim) {
			wp_die('Claim not found.');
		}

		require_once(VR_PLUGIN_PATH . "admin/partials/claim-details-page.php");
	
	}


}