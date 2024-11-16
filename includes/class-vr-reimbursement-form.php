<?php
class VR_Reimbursement_Form{
    public $form_type="reimbursement";

	public function __construct(){
		add_filter("vr_parse_". $this->form_type, array($this,"parse_form") , 10, 2);
		add_filter("vr_check_valid_".$this->form_type, array($this,"check_valid_form_data") , 10, 1);

		add_filter('vr_get_transaction_'.$this->form_type, array($this, "get_transaction_record"), 10, 2);
		add_filter('vr_get_xero_bill_note_'.$this->form_type, array($this, "get_xero_bill_note"), 10, 2);


	}

    public function parse_form($input, $files){
        $pay_committee = ($input['payee_committee'] !== "Other") ? $input['payee_committee'] : $input['payee_other_committee'];

        $form_data = [
			'payee_name' => sanitize_text_field($input['payee_name']),
			'payee_committee' => sanitize_text_field($pay_committee),
			'payee_email' => sanitize_email($input['payee_email']),
			'payee_phone_number' => sanitize_text_field($input['payee_phone_number']),
			'payee_bank_name' => sanitize_text_field($input['payee_bank_name']),
			'payee_bsb' => sanitize_text_field($input['payee_bsb']),
			'payee_account_number' => sanitize_text_field($input['payee_account_number']),
			'budget_reference' => sanitize_text_field($input['budget_reference']),
			'additional_email' => sanitize_email($input['additional_email']),

			'purpose' => sanitize_text_field($input['purpose']),
			'transaction_details' => sanitize_textarea_field($input['transaction_details']),

			'amount' => [
				'dollars' => intval($input['dollars']),
				'cents' => intval($input['cents']),
			],
		];

		// check for existing attachments
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
		$required_fields=['payee_name','payee_email','payee_committee','payee_phone_number', 'payee_bank_name', 'payee_bsb', 'payee_account_number', 'purpose', 'transaction_details', 'attachments'];
		
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

	public function get_transaction_record($transaction, $reimbursement){
		$reimbursement_data = json_decode($reimbursement->meta);

		$transaction->setAccountName($reimbursement_data->payee_name);
		$transaction->setAccountNumber($reimbursement_data->payee_account_number);
		if (preg_match('/^\d{6}$/', $reimbursement_data->payee_bsb)) {
			// Convert to XXX-XXX format
			$formatted_bsb = substr($reimbursement_data->payee_bsb, 0, 3) . '-' . substr($reimbursement_data->payee_bsb, 3, 3);
		}else{
			$formatted_bsb = $reimbursement_data->payee_bsb;
		}
		$transaction->setBsb($formatted_bsb);
		$transaction->setTransactionCode(53);
		$transaction->setReference($reimbursement->id);
		$transaction->setAmount($reimbursement_data->amount->dollars+ $reimbursement_data->amount->cents/100);

		return $transaction;

	}

	public function get_xero_bill_note($xero_bill_note, $reimbursement){
		$reimbursement_data = json_decode($reimbursement->meta);
		$xero_bill_note['*ContactName'] = $reimbursement_data->payee_name;
		$xero_bill_note['EmailAddress'] = $reimbursement_data->payee_email;
		$xero_bill_note['Description'] = $reimbursement_data->purpose . $reimbursement_data->transaction_details;

		$xero_bill_note['*UnitAmount'] = $reimbursement_data->amount->dollars+ $reimbursement_data->amount->cents/100;
		
		return $xero_bill_note;
	}
}