<?php
class VR_Payment_Request_Form{
    public $form_type="payment_request";

	public function __construct(){
		add_filter("vr_parse_". $this->form_type, array($this,"parse_form") , 10, 2);
		add_filter("vr_check_valid_".$this->form_type, array($this,"check_valid_form_data") , 10, 1);

	}

    public function parse_form($input, $files){
        $pay_committee = ($input['payee_committee'] !== "Other") ? $input['payee_committee'] : $input['payee_other_committee'];

		$form_data = [
			'payee_name' => sanitize_text_field($input['payee_name']),
			'payee_committee' => sanitize_text_field($pay_committee),
			'payee_email' => sanitize_email($input['payee_email']),
			'payee_phone_number' => sanitize_text_field($input['payee_phone_number']),
			// 'budget_reference' => sanitize_text_field($input['budget_reference']),
			// 'additional_email' => sanitize_email($input['additional_email']),
			'business_name' => sanitize_text_field($input['business_name']),
			'contact_name' => sanitize_text_field($input['contact_name']),
			'supplier_email' => sanitize_email($input['supplier_email']),
			'supplier_phone' => sanitize_text_field($input['supplier_phone']),
			'supplier_bank_name' => sanitize_text_field($input['supplier_bank_name']),
			'supplier_bsb' => sanitize_text_field($input['supplier_bsb']),
			'supplier_account_number' => sanitize_text_field($input['supplier_account_number']),
			'supplier_bpay' => sanitize_text_field($input['supplier_bpay']),
			'supplier_bpay_reference' => sanitize_text_field($input['supplier_bpay_reference']),
			'purpose' => sanitize_text_field($input['purpose']),
			'transaction_details' => sanitize_textarea_field($input['transaction_details']),
			'due_date' => sanitize_text_field($input['due_date']),
			'amount' => [
				'dollars' => intval($input['dollars']),
				'cents' => intval($input['cents']),
			],
			'currency' => sanitize_text_field($input['currency']),
		];

		$attachments = isset($input['attachments']) ? $input['attachments'] : [];

		if (!empty($files['attachments']['name'][0])) {
			foreach ($files['attachments']['name'] as $index => $filename) {
				// Format each file as individual for media_handle_upload
				$_FILES['individual_file'] = [
					'name'     => $_FILES['attachments']['name'][$index],
					'type'     => $_FILES['attachments']['type'][$index],
					'tmp_name' => $_FILES['attachments']['tmp_name'][$index],
					'error'    => $_FILES['attachments']['error'][$index],
					'size'     => $_FILES['attachments']['size'][$index],
				];
		
				// Attempt to upload the file
				$attachment_id = media_handle_upload('individual_file', 0);
				
				// Check if upload was successful and get URL
				if (!is_wp_error($attachment_id)) {
					$attachments[$attachment_id] = wp_get_attachment_url($attachment_id);
				}
			}
		}

		$form_data['attachments'] = $attachments;

		$form_data['comments'] = array_key_exists('comments', $input) ? sanitize_textarea_field($input['comments']) : "";


        return $form_data;
    }

    public function check_valid_form_data($form_data){
        $required_fields=['payee_name','payee_email','payee_committee','payee_phone_number', 'business_name', 'supplier_email', 'purpose', 'transaction_details', 'due_date', 'attachments'];
		
        foreach ( $required_fields as $field ) {
			if ( empty( $form_data[$field] ) ) {
				// If a required field is missing, throw an AJAX error and exit
				return "The field '$field' is required.";
			}
		}

		if ($form_data['amount']['dollars'] <0 || $form_data['amount']['cents'] <0 || ($form_data['amount']['dollars']==0 && $form_data['amount']['cents']==0 )){
			return "Dollar cents must be more than 0";
		}

        return false;
    }
}