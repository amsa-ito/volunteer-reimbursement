<?php 


class Volunteer_Reimbursement_Admin_Form_Details{
	private $plugin_name;
	private $version;


	public function __construct( $plugin_name, $version ) {
        add_action( 'admin_menu', array($this, 'vr_admin_detail_page') );
        
        add_action('wp_ajax_save_admin_claim_form', array($this, 'save_vr_claim_form'));
        add_action('wp_ajax_nopriv_save_admin_claim_form', array($this, 'save_vr_claim_form'));

    }

	public function vr_admin_detail_page() {
        add_submenu_page(
			null, // No menu item in the sidebar
			'Reimbursement Form Details',
			'Reimbursement Form Details',
			'manage_volunteer_claims',
			'vr_reimbursement_detail',
			array($this,'render_vr_reimbursement_detail_page')
		);
    }

    public function save_vr_claim_form(){
		// check_ajax_referer($this->plugin_name.'-nonce', 'nonce');
		if (!current_user_can('manage_volunteer_claims')){
			wp_send_json_error([ 'status' => 'error', 'message' => 'You do not have sufficient permissions.' ] );
		}

        global $wpdb;
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';

        $form_id = intval($_POST['form_id']);
        
        $reimbursement = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id));

        if (!$reimbursement) {
            wp_send_json_error([ 'status' => 'error', 'message' => 'Form not found.' ] );
        }

        $form_type = $reimbursement->form_type;

        $form_data = apply_filters('vr_parse_'. $form_type ,$_POST, $_FILES);

        $new_status = sanitize_text_field($_POST['status']);
        
        $existing_meta = json_decode($reimbursement->meta, true) ?: [];

        $new_form_data = array_replace_recursive($existing_meta, $form_data);

        $error_msg = apply_filters('vr_check_valid_'. $form_type, $new_form_data);
        
        if($error_msg){
			wp_send_json_error( [ 'status' => 'error', 'message' => $error_msg ] );
		}

		$user_id = $reimbursement->id;
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
        ], ['id' => $form_id]);

        if ($result !== false) {
			$old_status = $reimbursement->status;
			if($new_status != $old_status){
				do_action('vr_reimbursement_' . $old_status . '_to_' . $new_status, $reimbursement, $new_status);
			}
            wp_send_json_success(['status' => 'success', 'message' => 'Claim saved successfully!']);

        } else {
            wp_send_json_error(['status'=>'error','message'=>'Failed to update the form.']);
        }

        wp_die();
    }

    public function render_vr_reimbursement_detail_page(){
		if (!isset($_GET['form_id'])) {
			wp_die('No form ID specified.');
		}
	
		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';
		$form_id = intval($_GET['form_id']);
		$reimbursement = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id));
	
		if (!$reimbursement) {
			wp_die('Reimbursement form not found.');
		}

		require_once(VR_PLUGIN_PATH . "admin/partials/claim-details-page.php");
	
	}


	public function render_meta_attachments($default_html, $field_name, $value){

        $uploaded_files = [];
        foreach($value as $attachement_id => $file_url){
            $attachment = get_attached_file( $attachement_id );
			if($attachment){
				$attachment_title = basename ( $attachment );
				$file_size = filesize( $attachment );
				
				$uploaded_files[$attachement_id] = [
					'name' => $attachment_title,
					'url' => $file_url,
					'size' => $file_size
				];
			}

        }

		ob_start();
		?>
		<label for="rv-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.<span class="required">*</span></label>

		<input type="file" id="rv-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
		<!-- List of uploaded files -->
		<ul id="rv-file-list">

		</ul>

        <script type="text/javascript">
		// Pass PHP $value array to JavaScript as initial uploadedFiles
		    let uploadedFiles = <?php echo json_encode($uploaded_files); ?> || [];
	    </script>
		<?php
		return ob_get_clean();

	}
	public function render_meta_payee_committee($value){
		$options = [
			"AMSA Reps", "AMSA Global Health Committee", "AMSA Rural Health Committee", "Board of Directors",
			"Convention 2022", "Convention 2023", "Executive", "Careers Conference 23", "AMSA Indigenous Health",
			"Med Ed", "AMSA ISN", "AMSA Projects", "NLDS 2022", "RHS 2022", "AMSA Queer", "Vampire Cup",
			"Mental Health", "Gender Equity", "National Council", "Other"
		];
	
		// Determine if the value is in the predefined options
		$isOther = !in_array($value, $options);
		
		ob_start();
		?>
		<label for="payee_committee">Select Committee<span class="required">*</span></label>
		<select id="payee_committee" name="payee_committee" required>
			<?php foreach ($options as $option): ?>
				<option value="<?php echo esc_attr($option); ?>" 
					<?php echo ($value === $option || ($isOther && $option === "Other")) ? 'selected' : ''; ?>>
					<?php echo esc_html($option); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input type="text" id="payee-other-committee" 
           name="payee_other_committee" 
           placeholder="Please specify the committee"
           style="display: <?php echo $isOther ? 'block' : 'none'; ?>; margin-top: 10px;"
           value="<?php echo $isOther ? esc_attr($value) : ''; ?>">

		<?php
		return ob_get_clean();
	}



}