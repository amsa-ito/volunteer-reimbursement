<?php 


class Volunteer_Reimbursement_Admin_Form_Details{
	private $plugin_name;
	private $version;


	public function __construct( $plugin_name, $version ) {
        add_action( 'admin_menu', array($this, 'vr_admin_detail_page') );
        
        add_action('wp_ajax_save_admin_request_form', array($this, 'save_vr_request_form'));
        add_action('wp_ajax_nopriv_save_admin_request_form', array($this, 'save_vr_request_form'));


		add_filter('vr_render_meta_field_comments', array($this, 'render_meta_comments'), 10, 3);
		add_filter('vr_render_meta_field_amount', array($this, 'render_meta_amount'), 10, 3);
		add_filter('vr_render_meta_field_attachments', array($this, 'render_meta_attachments'), 10, 3);
		add_filter('vr_render_meta_field_due_date', array($this, 'render_meta_due_date'), 10, 3);
		add_filter('vr_render_meta_field_payee_committee', array($this, 'render_meta_payee_committee'), 10, 3);


		add_filter('vr_render_meta_field_payee_email', array($this, 'render_meta_email_feild'), 10, 3);
		add_filter('vr_render_meta_field_supplier_email', array($this, 'render_meta_email_feild'), 10, 3);
		add_filter('vr_render_meta_field_additional_email', array($this, 'render_meta_email_feild'), 10, 3);




	
    }

	public function vr_admin_detail_page() {
        add_submenu_page(
			null, // No menu item in the sidebar
			'Reimbursement Form Details',
			'Reimbursement Form Details',
			'edit_posts',
			'vr_reimbursement_detail',
			array($this,'render_vr_reimbursement_detail_page')
		);
    }

    public function save_vr_request_form(){
		// check_ajax_referer($this->plugin_name.'-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['status'=>'error', 'message'=>'You do not have permission to edit this form.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'volunteer_reimbursements';

        $form_id = intval($_POST['form_id']);
        
        $reimbursement = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id));

        if (!$reimbursement) {
            debug_print($reimbursement);
            wp_send_json_error([ 'status' => 'error', 'message' => 'Form not found.' ] );
        }

        $form_type = $reimbursement->form_type;

        $form_data = apply_filters('vr_parse_'. $form_type ,$_POST['meta'], $_FILES);

        $new_status = sanitize_text_field($_POST['status']);
        

        
        $existing_meta = json_decode($reimbursement->meta, true) ?: [];
        
        debug_print($_POST);
        
        debug_print($existing_meta);
        debug_print($form_data);

        $new_form_data = array_replace_recursive($existing_meta, $form_data);

        $error_msg = apply_filters('vr_check_valid_'. $form_type, $new_form_data);
        
        if($error_msg){
			wp_send_json_error( [ 'status' => 'error', 'message' => $error_msg ] );
		}
        debug_print($new_form_data);
        
        $result = $wpdb->update($table_name, [
            'status' => $new_status,
            'meta' => json_encode($new_form_data)
        ], ['id' => $form_id]);

        if ($result !== false) {
            wp_send_json_success(['status' => 'success', 'message' => 'Request submitted successfully!']);

        } else {
            wp_send_json_error(['status'=>'error','message'=>'Failed to update the form.']);
        }
        wp_send_json_success(['status' => 'success', 'message' => 'Request submitted successfully!']);

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

		$meta_data = json_decode($reimbursement->meta, true) ?: [];

		if (!array_key_exists('comments', $meta_data)) {
			$meta_data['comments'] = '';
		}
	
		echo '<div class="wrap">';
		echo '<h1>Reimbursement Form Details</h1>';
		echo '<form method="post" id="vr-reimbursement-form">';
        echo '<input type="hidden" name="action" value="save_admin_request_form">';
        echo '<input type="hidden" name="form_id" value="'.$form_id.'">';

	
		echo '<table class="form-table">';

		$user_name = get_userdata($reimbursement->user_id)->display_name;
		$user_profile_url = get_edit_user_link($reimbursement->user_id);

		echo '<tr><th>User</th><td>' . sprintf('<a href="%s" target="_blank">%s</a>', esc_url($user_profile_url), esc_html($user_name)) . '</td></tr>';
		echo '<tr><th>Submit Date</th><td>' . esc_html(date('Y-m-d', strtotime($reimbursement->submit_date))) . '</td></tr>';
		echo '<tr><th>Status</th><td>';
		echo '<select name="status">';
		echo '<option value="pending" ' . selected($reimbursement->status, 'pending', false) . '>Pending</option>';
		echo '<option value="approved" ' . selected($reimbursement->status, 'approved', false) . '>Approved</option>';
		echo '<option value="paid" ' . selected($reimbursement->status, 'paid', false) . '>Paid</option>';
		echo '</select></td></tr>';
		echo '</table>';
	
		// Display meta fields with recursive function
		echo '<h2>Form data</h2>';
		echo '<table class="form-table">';
		$this->render_meta_fields($meta_data);
		echo '</table>';
	
		echo '<p class="submit"><input type="submit" class="button-primary" value="Save Changes"></p>';
		echo '</form>';
        echo '<div id="form-response"></div>';
		echo '</div>';
	}

    function render_meta_fields($meta_data) {
		foreach ($meta_data as $field_name => $value) {
			
			echo '<tr><th>' . esc_html(ucwords(str_replace('_', ' ', $field_name))) . '</th><td>';
	
			$field_html = apply_filters("vr_render_meta_field_$field_name", '<input type="text" id="'.esc_attr($field_name).'" name="meta[' . esc_attr($field_name) . ']" value="' . esc_attr($value) . '" />', $field_name, $value);
			echo $field_html;
	
			echo '</td></tr>';
		}
	}

    public function render_meta_comments($default_html, $field_name, $value) {
		return '<textarea name="meta['.esc_attr($field_name).']">' . esc_textarea($value) . '</textarea>'; 
	}

	public function render_meta_amount($default_html, $field_name, $value){
		ob_start();
		?>
		<div class="amount-claimed-container">
        <label for="dollars">Dollars </label><label for="cents">Cents </label>
        <div class="amount-inputs">
            <span>$</span>
            <input type="number" name="meta[dollars]" id="dollars" class="amount-input" placeholder="0" min="0" required value="<?php echo esc_attr($value['dollars']); ?>">
            <span class="separator">.</span>
            <input type="number" name="meta[cents]" id="cents" class="amount-input" placeholder="00" min="0" max="99" required value="<?php echo esc_attr($value['cents']); ?>">
        </div>
    </div>
	<?php
	return ob_get_clean();
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
		<input type="file" id="rv-multiple-file-input" name="meta[<?php echo esc_attr($field_name) ?>][]" accept="image/*,.pdf" multiple>
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

	public function render_meta_due_date($default_html, $field_name, $value){
		return '<input type="date" name="meta['.esc_attr($field_name).']" id="'.esc_attr($field_name).'" required value="'.$value.'" />';
	}

	public function render_meta_email_feild($default_html, $field_name, $value){
		return '<input type="email" name="meta['.esc_attr($field_name).']" id="'.esc_attr($field_name).'" value="'.$value.'" />';
	}

	public function render_meta_payee_committee($default_html, $field_name, $value){
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
		<select id="payee_committee" name="meta[payee_committee]" required>
			<?php foreach ($options as $option): ?>
				<option value="<?php echo esc_attr($option); ?>" 
					<?php echo ($value === $option || ($isOther && $option === "Other")) ? 'selected' : ''; ?>>
					<?php echo esc_html($option); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input type="text" id="payee-other-committee" 
           name="meta[payee_other_committee]" 
           placeholder="Please specify the committee"
           style="display: <?php echo $isOther ? 'block' : 'none'; ?>; margin-top: 10px;"
           value="<?php echo $isOther ? esc_attr($value) : ''; ?>">

		<?php
		return ob_get_clean();
	}



}