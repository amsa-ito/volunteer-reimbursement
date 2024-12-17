<?php
class VR_Payment_Request_Form{
    public $form_type="payment_request";

	public function __construct(){
		add_filter("vr_parse_". $this->form_type, array($this,"parse_form") , 10, 2);
		add_filter("vr_check_valid_".$this->form_type, array($this,"check_valid_form_data") , 10, 1);

		add_filter('vr_get_transaction_'.$this->form_type, array($this, "get_transaction_record"), 10, 2);
		add_filter('vr_get_xero_bill_note_'.$this->form_type, array($this, "get_xero_bill_note"), 10, 2);

		add_filter('vr_display_public_'.$this->form_type, array($this,"display_public_form"), 10, 1);
		add_filter('vr_display_admin_'.$this->form_type, array($this,"display_admin_form"), 10, 2);

		add_filter('vr_display_submission_email_'.$this->form_type, array($this,"display_email_submission"), 10, 2);


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
        $required_fields=['payee_name','payee_email','payee_committee','payee_phone_number', 'business_name', 'supplier_email', 'purpose', 'transaction_details', 'due_date', 'supplier_bsb', 'supplier_account_number'];
		
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

	public function get_transaction_record($transaction, $claim){
		$claim_data = json_decode($claim->meta);

		$transaction->setAccountName($claim_data->business_name);
		$transaction->setAccountNumber($claim_data->supplier_account_number);
		if (preg_match('/^\d{6}$/', $claim_data->supplier_bsb)) {
			// Convert to XXX-XXX format
			$formatted_bsb = substr($claim_data->supplier_bsb, 0, 3) . '-' . substr($claim_data->supplier_bsb, 3, 3);
		}else{
			$formatted_bsb = $claim_data->supplier_bsb;
		}
		$transaction->setBsb($formatted_bsb);
		$transaction->setTransactionCode(53);
		$transaction->setReference($claim->id);

		$transaction->setAmount($claim_data->amount->dollars*100+ $claim_data->amount->cents);

		return $transaction;

	}

	public function get_xero_bill_note($xero_bill_note, $claim){
		$claim_data = json_decode($claim->meta);
		$xero_bill_note['*ContactName'] = $claim_data->business_name;
		$xero_bill_note['EmailAddress'] = $claim_data->supplier_email;
		$xero_bill_note['Description'] = $claim_data->purpose . $claim_data->transaction_details;

		$xero_bill_note['*UnitAmount'] = $claim_data->amount->dollars+ $claim_data->amount->cents/100;
		$xero_bill_note['Currency'] = $claim_data->currency ?? "AUD";

		$date = DateTime::createFromFormat('Y-m-d', $claim_data->due_date);

		$xero_bill_note['*DueDate'] = $date->format('d/m/Y');

		return $xero_bill_note;
	}

	public function display_public_form($content){
		ob_start();
		require_once(VR_PLUGIN_PATH . 'public/partials/payment-request.php');
		
		return ob_get_clean();
	}

	public function display_admin_form($content, $claim){
		ob_start();
		require_once(VR_PLUGIN_PATH . 'admin/partials/payment-request.php');
		
		return ob_get_clean();
	}

	public function display_email_submission($message, $form_data){
		$table_style = "width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;";
		$th_style = "background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd;";
		$td_style = "padding: 8px; border: 1px solid #ddd;";
		$ul_style = "margin: 0; padding-left: 20px;";

		$message .= "<table style='" . esc_attr($table_style) . "' class='vr-submit-confirm-email'>";

		foreach ($form_data as $key => $value) {
			if (!empty($value)) { // Skip empty values
				$label = esc_html(ucwords(str_replace('_', ' ', $key)));
				
				// Special handling for specific keys
				if ($key === 'amount' && is_array($value)) {
					// Combine dollars and cents
					$amount = number_format((float)$value['dollars'] + ($value['cents'] / 100), 2);
					$message .= "<tr><td style='" . esc_attr($th_style) . "'>" . $label . ":</td>";
					$message .= "<td style='" . esc_attr($td_style) . "'>$" . esc_html($amount) . "</td></tr>";
				}elseif ($key === 'attachments' && is_array($value)) {
					// Handle attachments array
					$message .= "<tr><td style='" . esc_attr($th_style) . "'>" . $label . ":</td>";
					$message .= "<td style='" . esc_attr($td_style) . "'>";
					$message .= "<ul style='" . esc_attr($ul_style) . "'>";
					foreach ($value as $attachment_id => $attachment_url) {
						if (isset($attachment_url, $attachment_id)) {
							$message .= "<li><a href='" . esc_url($attachment_url) . "'>Attachment " . esc_html($attachment_id) . "</a></li>";
						}
					}
					$message .= "</ul></td></tr>";
				}elseif (is_array($value)) {
					// General handling for arrays
					$message .= "<tr><td style='" . esc_attr($th_style) . "'>" . $label . ":</td>";
					$message .= "<td style='" . esc_attr($td_style) . "'>";
					$message .= "<ul style='" . esc_attr($ul_style) . "'>";
					foreach ($value as $item) {
						$message .= "<li>" . esc_html($item) . "</li>";
					}
					$message .= "</ul></td></tr>";
				} else {
					// Handle non-array values
					$message .= "<tr>";
					$message .= "<td style='" . esc_attr($th_style) . "'>" . $label . ":</td>";
					$message .= "<td style='" . esc_attr($td_style) . "'>" . esc_html($value) . "</td>";
					$message .= "</tr>";
				}
			}
		}
		$message .= "</table>";

		return $message;
	}
}