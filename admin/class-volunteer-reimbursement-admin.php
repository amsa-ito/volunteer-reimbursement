<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://amsa.org.au
 * @since      1.0.0
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Volunteer_Reimbursement
 * @subpackage Volunteer_Reimbursement/admin
 * @author     Steven Zhang <stevenzhangshao@gmail.com>
 */
require_once VR_PLUGIN_PATH . "admin/class-volunteer-reimbursement-admin-table.php";
require_once VR_PLUGIN_PATH . "admin/class-volunteer-reimbursement-admin-form-details.php";
require_once VR_PLUGIN_PATH . "admin/class-volunteer-reimbursement-admin-settings.php";
require_once VR_PLUGIN_PATH . "includes/aba/Generator/AbaFileGenerator.php";
require_once VR_PLUGIN_PATH . "includes/aba/Model/Transaction.php";


// use VR\AbaFileGenerator\Model\Transaction;
// use VR\AbaFileGenerator\Generator\AbaFileGenerator;

class Volunteer_Reimbursement_Admin {

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

	private $table_name;

	private $wpdb;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $this->wpdb->prefix . 'volunteer_reimbursements';

		add_action( 'admin_menu', array($this, 'vr_admin_menu') );


		add_action('wp_ajax_submit_aba_export', array($this, 'generate_aba_export'));
        add_action('wp_ajax_nopriv_submit_aba_export', array($this, 'generate_aba_export'));

		// add_action('wp_ajax_export_xero', array($this, 'generate_xero_export'));
        // add_action('wp_ajax_nopriv_export_xero', array($this, 'generate_xero_export'));

		add_action('vr_reimbursement_pending_to_approved', array($this, 'claim_approved_email'), 10, 2);
		add_action('vr_reimbursement_pending_to_paid', array($this, 'claim_paid_email'), 10, 2);
		add_action('vr_reimbursement_approved_to_paid', array($this, 'claim_paid_email'), 10, 2);

		add_action('vr_reimbursement_pending_to_approved', array($this, 'log_claim_approved_time'), 10, 2);
		add_action('vr_reimbursement_pending_to_paid', array($this, 'log_claim_paid_time'), 10, 2);
		add_action('vr_reimbursement_pending_to_paid', array($this, 'log_claim_paid_time'), 10, 2);



		new Volunteer_Reimbursement_Admin_Form_Details($plugin_name, $version);
		new Volunteer_Reimbursement_Admin_Settings($plugin_name, $version);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function vr_admin_menu() {
		add_menu_page(
			'Claims',
			'Reimbursement',
			'manage_volunteer_claims',
			'volunteer-reimbursement',
			array($this, 'vr_admin_page'),
			'dashicons-admin-users',
			20
		);
	}

	public function vr_admin_page() {
		// Handle delete action
		if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['claim_id'])) {
			$claim_id = absint($_GET['claim_id']);
	
			// Verify nonce
			if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_claim_' . $claim_id)) {
				wp_die(__('Invalid nonce specified.', 'vr-plugin'));
			}
	
			// Perform the delete action
			$this->delete_claims([$claim_id]);
	
			// Redirect back to prevent duplicate actions on refresh
			wp_redirect(remove_query_arg(['action', 'claim_id', '_wpnonce']));
			exit;
		}

		$selected_status = $_GET['status'] ?? '';
		
		// Retrieve counts for each status
		$statuses = [
			'All' => '',
			'Pending' => 'pending',
			'Approved' => 'approved',
			'Paid' => 'paid'
		];
	
		// Retrieve reimbursements with optional status filtering
		$query = "SELECT * FROM $this->table_name";
		if ($selected_status) {
			$query .= $this->wpdb->prepare(" WHERE status = %s", $selected_status);
		}
		$query .= " ORDER BY submit_date DESC";

		if (isset($_POST['action']) && $_POST['action'] !== -1 && $_POST['claim_ids']) {
			$action = $_POST['action'];
			$claim_ids = $_POST['claim_ids'] ?? [];

			$new_status = null;
			if ($action === 'status_pending') {
				$new_status = 'pending';
			} elseif ($action === 'status_approved') {
				$new_status = 'approved';
			} elseif ($action === 'status_paid') {
				$new_status = 'paid';
			}

			// Apply the status change or delete as necessary
			if ($action === 'delete') {
				$this->delete_claims($claim_ids);

			} elseif ($action === 'export_xero'){
				$zip_file = $this->generate_xero_export($claim_ids);
				
				header('Content-Type: application/zip');
				header('Content-Transfer-Encoding: binary');
				header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
				header('Content-Length: ' . filesize($zip_file));
				ob_clean();
				flush();
				readfile($zip_file);

				$this->rrmdir(dirname($zip_file));

			}elseif ($new_status) {
				$this->update_new_status($new_status, $claim_ids);
			}
	
			// refresh page data after performing actions
			$claims = $this->wpdb->get_results($query);
		}else{
			$claims = $this->wpdb->get_results($query);

		}	

		echo '<div class="wrap">';
		echo '<h1>Claims</h1>';

		// Filter by form type
		echo '<form method="get">';
		echo '<select name="form_type">';
		echo '<option value="">All Claim Types</option>';
		foreach ($this->wpdb->get_col("SELECT DISTINCT form_type FROM $this->table_name") as $type) {
			printf('<option value="%s" %s>%s</option>', esc_attr($type), selected($_GET['form_type'] ?? '', $type, false), esc_html($type));
		}
		echo '</select>';
		echo '<input type="submit" class="button" value="Filter">';
		echo '</form>';

		echo '<ul class="subsubsub">';

		$status_counts = [];
		foreach ($statuses as $status_name => $status_value) {
			if ($status_value) {
				// Count for specific status
				$count = $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE status = %s", $status_value));
			} else {
				// Total count for all statuses
				$count = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
			}
			$status_counts[$status_name] = $count;
		}

		foreach ($statuses as $label => $status) {
			$class = ($selected_status === $status) ? 'current' : '';
			$status_url = add_query_arg(['status' => $status], remove_query_arg('paged'));
			printf(
				'<li class="%s"><a href="%s" class="%s">%s <span class="count">(%d)</span></a></li> ',
				esc_attr(strtolower($label)),
				esc_url($status_url),
				$class,
				esc_html($label),
				$status_counts[$label]
			);
		}
		echo '</ul>';
	
		// Display the list table
		$claims_table = new VR_Reimbursements_Table($claims);
		$claims_table->prepare_items();
		echo '<form method="post" id="vr_reimbursement_table">';
		$claims_table->display();
		echo '</form>';
	
		echo '</div>';


		ob_start();
		include_once(VR_PLUGIN_PATH . 'admin/partials/volunteer-reimbursement-aba-modal.php');
		$modalContent = ob_get_clean();

		echo "<script>var modalHtml =`".$modalContent."`</script>";
	
	}

	public function generate_aba_export(){
		if (!current_user_can('manage_volunteer_claims')){
			wp_send_json_error([ 'status' => 'error', 'message' => 'You do not have sufficient permissions.' ] );
		}

		$claim_ids = $_POST['claim_ids'] ?? [];
		if(empty($claim_ids)){
			wp_send_json_error([ 'status' => 'error', 'message' => 'No forms selected' ] );
		}

		$required_keys = [
			'bsb',
			'account_number',
			'bank_name',
			'user_name',
			'remitter',
			'entry_id',
			'description',
		];
		
		// Check if all required keys exist
		$missing_keys = [];
		foreach ($required_keys as $key) {
			if (empty($_POST['description'][$key])) {
				$missing_keys[] = $key;
			}
		}
		
		if (!empty($missing_keys)) {
			wp_send_json_error([
				'status' => 'error',
				'message' => 'Missing required fields: ' . implode(', ', $missing_keys),
			]);
		}

		$bsb = $_POST['description']['bsb'];

		if (preg_match('/^\d{6}$/', $bsb)) {
			// Convert to XXX-XXX format
			$formatted_bsb = substr($bsb, 0, 3) . '-' . substr($bsb, 3, 3);
		}else{
			$formatted_bsb = $bsb;
		}

		$generator = new AbaFileGenerator(
			sanitize_text_field($formatted_bsb),
			sanitize_text_field($_POST['description']['account_number']),
			sanitize_text_field($_POST['description']['bank_name']),
			sanitize_text_field($_POST['description']['user_name']),
			sanitize_text_field($_POST['description']['remitter']),
			sanitize_text_field($_POST['description']['entry_id']),
			sanitize_text_field($_POST['description']['description']),			
		);

		$query = sprintf(
			"SELECT * FROM {$this->table_name} WHERE id IN (%s)",
			implode(',', array_map('intval', $claim_ids))
		);
		$claims = $this->wpdb->get_results($query);

		if (empty($claims)) {
			wp_send_json_error(['status' => 'error', 'message' => 'No valid claims found']);
		}

		$transactions = [];
		foreach($claims as $claim) {
			$claim_data = json_decode($claim->meta);

			$transaction = new Transaction();
			$transaction = apply_filters('vr_get_transaction_'.$claim->form_type, $transaction, $claim);

			$transactions[] = $transaction;
			// $generator->addTransaction($transaction);
		}


		try {
			$file_content = $generator->generate($transactions);
			wp_send_json_success([
				'status' => 'success',
				'message' => 'ABA file generated successfully.',
				'file_content' => $file_content,
			]);
		} catch (Exception $e) {
			wp_send_json_error([
				'status' => 'error',
				'message' => 'Failed to generate ABA file: ' . $e->getMessage(),
			]);
		} finally {
			wp_die(); // Ensure the script terminates properly
		}
		wp_die();
	}

	public function update_new_status($new_status, $claim_ids){
		$ids_placeholder = implode(',', array_fill(0, count($claim_ids), '%d'));
		$sql = "UPDATE {$this->table_name} SET status = %s WHERE id IN ($ids_placeholder)";
		$parameters = array_merge([$new_status], $claim_ids);

		$result = $this->wpdb->query($this->wpdb->prepare($sql, $parameters));

		// Create an indexed array of reimbursements by their ID
		$sql = "SELECT id, meta FROM {$this->table_name} WHERE id IN ($ids_placeholder)";
		$claims = $this->wpdb->get_results($this->wpdb->prepare($sql, $claim_ids));

		$missing_ids=[];
		foreach ($claims as $claim) {

			$old_status = $claim->status;

			if($old_status===$new_status){
				continue;
			}
			// debug_print('vr_reimbursement_' . $old_status . '_to_' . $new_status);
			do_action('vr_reimbursement_' . $old_status . '_to_' . $new_status, $claim, $new_status);
		}
		if($result){
			?>
			<div class="notice-success notice">
				<p>Claim status changed to <?php echo(esc_attr($new_status))?> </p>
			</div>
			<?php
		}else{
			?>
			<div class="notice-error notice">
				<p>There was an error in changing the claim status to <?php echo $new_status?></p>
			</div>
			<?php
		}

	}

	public function delete_claims($claim_ids){
		if (empty($claim_ids)) {
			?>
			<div class="notice-error notice">
				<p>No claims were provided for deletion.</p>
			</div>
			<?php
			return;
		}

		$ids_placeholder = implode(',', array_fill(0, count($claim_ids), '%d'));
		$sql = "SELECT id, meta FROM {$this->table_name} WHERE id IN ($ids_placeholder)";
		$claims = $this->wpdb->get_results($this->wpdb->prepare($sql, $claim_ids));

		// Delete attachments listed in the meta field
		foreach ($claims as $claim) {
			$meta = json_decode($claim->meta, true); // Decode JSON meta column
			if (!empty($meta['attachments']) && is_array($meta['attachments'])) {
				foreach ($meta['attachments'] as $attachment_id => $attachment_url) {
					// Use WordPress function to delete the attachment
					if (wp_delete_attachment($attachment_id, true) === false) {
						error_log("Failed to delete attachment ID: $attachment_id for claim ID: $claim->id");
					} else {
						error_log("Successfully deleted attachment ID: $attachment_id for claim ID: $claim->id");
					}
				}
			}
		}

		$delete_sql = "DELETE FROM {$this->table_name} WHERE id IN ($ids_placeholder)";
		$result = $this->wpdb->query($this->wpdb->prepare($delete_sql, $claim_ids));

		if($result){
			$num_deleted = count($claim_ids);
			?>
			<div class="notice-success notice">
				<p><?php echo $num_deleted?> claim<?php ($num_deleted > 1) ? 's' : ''?> deleted </p>
			</div>
			<?php
		}else{
			?>
			<div class="notice-error notice">
				<p>There was an error deleting these claims</p>
			</div>
			<?php
		}

	}
	
	public function generate_xero_export($claim_ids) {
		if(empty($claim_ids)){
			throw new Exception('No forms selected');
		}

		$query = sprintf(
			"SELECT * FROM {$this->table_name} WHERE id IN (%s)",
			implode(',', array_map('intval', $claim_ids))
		);
		$claims = $this->wpdb->get_results($query);
		
		if (empty($claims)) {
			throw new Exception('No valid claims found');
		}

		$temp_dir_suffix =  '/xero_export_temp_' . time();
		$temp_dir = wp_upload_dir()['basedir'] .$temp_dir_suffix;
		if (!mkdir($temp_dir, 0755, true)) {
			throw new Exception('Failed to create temporary directory');
		}

		$csv_file = $temp_dir . '/xero_export.csv';
		$output = fopen($csv_file, 'w');
		// ob_start();
		// $output = fopen('php://output', 'w');
	
		// Write header row
		$headers = [
			'*ContactName', 'EmailAddress', 'POAddressLine1', 'POAddressLine2', 'POAddressLine3', 'POAddressLine4',
			'POCity', 'PORegion', 'POPostalCode', 'POCountry', '*InvoiceNumber', '*InvoiceDate', '*DueDate',
			'InventoryItemCode', 'Description', '*Quantity', '*UnitAmount', '*AccountCode', '*TaxType',
			'TrackingName1', 'TrackingOption1', 'TrackingName2', 'TrackingOption2', 'Currency'
		];
		fputcsv($output, $headers);

		foreach ($claims as $claim) {
			$claim_data = json_decode($claim->meta, true); // Decode meta field

			$xero_bill_note = [
                '*ContactName' => "",
                'EmailAddress' => "",
                'POAddressLine1' => "",
                'POAddressLine2' => '',
                'POAddressLine3' => '',
                'POAddressLine4' => '',
                'POCity' => "",
                'PORegion' => "",
                'POPostalCode' =>  '',
                'POCountry' =>  '',
                '*InvoiceNumber' => $claim->id,
                '*InvoiceDate' => date('d/m/Y'),
                '*DueDate' => date('d/m/Y'),
                'InventoryItemCode' => '', // Optional field, set to empty
                'Description' => "",
                '*Quantity' => '1',
                '*UnitAmount' => '0.00',
                '*AccountCode' => 'EVT-E', // Default account code
                '*TaxType' => 'GST on Expenses',
                'TrackingName1' => '',
                'TrackingOption1' => '',
                'TrackingName2' => '',
                'TrackingOption2' => '',
                'Currency' => ''
            ];
			$filtered_xero_bill_note = apply_filters('vr_get_xero_bill_note_'.$claim->form_type, $xero_bill_note, $claim);

			if(count(array_intersect_key($filtered_xero_bill_note, $xero_bill_note)) != count($xero_bill_note)){
				throw new Exception('xero bill note filtering for '.$claim->form_type.'changed the number of keys in the array');
			}

			fputcsv($output, $filtered_xero_bill_note);

			$claim_folder = $temp_dir . '/' . $claim->id;
			if (!mkdir($claim_folder, 0755, true)) {
				throw new Exception('Failed to create claim folder');
			}

			$attachments = $claim_data['attachments'];
			if (!empty($attachments)) {
				foreach ($attachments as $attachment_id => $attachment_url) {
					$attachment_content = file_get_contents($attachment_url);
					if ($attachment_content === false) {
						continue; // Skip if download fails
					}

					$attachment_file = $claim_folder . '/' . basename($attachment_url);
					file_put_contents($attachment_file, $attachment_content);
				}
			}
	
		}

		fclose($output);
		// Capture the output buffer and clean it
		$zip_file = $temp_dir . '/xero_export.zip';

		$zip = new ZipArchive();
		if ($zip->open($zip_file, ZipArchive::CREATE) === true) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ($files as $file) {
				if($file->isDir()){
					continue;
				}
				$file_path = realpath($file);
				debug_print($file_path);
				$zip->addFile($file_path, substr($file_path, strlen($temp_dir) + 1));
			}
			$zip->close();
		} else {
			throw new Exception('Failed to create ZIP file');
		}

		// array_map('unlink', glob("$temp_dir/*"));
		// rmdir($temp_dir);

		// Send CSV file as response
		return $zip_file;
	}


	public function claim_approved_email($claim, $new_status) {
		if(get_option('vr_allow_notification_emails', 'yes')!=='yes'){
			return;
		}
		$meta = json_decode($claim->meta, true);
		$payee_name = $meta['payee_name'];
		$payee_email = $meta['payee_email'];
		$purpose = $meta['purpose'];
		$transaction_details = $meta['transaction_details'] ?? 'N/A';
		$amount = number_format($meta['amount']['dollars'] + $meta['amount']['cents'] / 100, 2);
	
		$subject = sprintf('Your %s claim #%d for %s has been approved', 
						$claim->form_type,
						 $claim->id,
						 $purpose);
		$message = sprintf(
			"Dear %s,\n\nYour claim submitted on %s has been approved.\n\nPurpose: %s\n Description: %s\nAmount: $%s",
			$payee_name,
			date('d/m/Y', strtotime($claim->submit_date)),
			$purpose,
			$transaction_details,
			$amount
		);
		if($claim->user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "\nTo track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}

		$message .="\n\nThank you.\nAMSA Treasurer";
	
		$email_status = wp_mail($payee_email, $subject, $message);

		error_log($email_status);
	}

	public function claim_paid_email($claim, $new_status) {
		if(get_option('vr_allow_notification_emails', 'yes')!=='yes'){
			return;
		}
		$meta = json_decode($claim->meta, true);
		$payee_name = $meta['payee_name'];
		$payee_email = $meta['payee_email'];
		$purpose = $meta['purpose'];
		$amount = number_format($meta['amount']['dollars'] + $meta['amount']['cents'] / 100, 2);
		$transaction_details = $meta['transaction_details'] ?? 'N/A';
	
		$subject = sprintf('Your %s claim #%d for %s has been paid', 
						$claim->form_type,
						$claim->id,
						$purpose);

		$message = sprintf(
			"Dear %s,\n\nYour claim submitted on %s has been paid.\n\nPurpose: %s\n Description: %s\nAmount: $%s",
			$payee_name,
			date('d/m/Y', strtotime($claim->submit_date)),
			$purpose,
			$transaction_details,
			$amount
		);
		if($claim->user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "\nTo track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}

		$message .="\n\nThank you.\nAMSA Treasurer";
	
		$email_status = wp_mail($payee_email, $subject, $message);

		error_log($email_status);
	}

	public function log_claim_approved_time($claim, $new_status){
		if ($new_status === 'approved') {
			// Update the approve_date to the current timestamp
			$result = $this->wpdb->update(
				$this->table_name, // Table name
				['approve_date' => current_time('mysql')], // Data to update
				['id' => $claim->id], // Where clause
				['%s'], // Format for the approve_date column
				['%d']  // Format for the id column
			);
	
			// Check if the update was successful
			if ($result === false) {
				error_log('Failed to update approve_date for claim ID: ' . $claim->id);
			} else {
				error_log('Successfully updated approve_date for claim ID: ' . $claim->id);
			}
		}
	}

	public function log_claim_paid_time($claim, $new_status){
		if ($new_status === 'paid') {
			// Update the approve_date to the current timestamp
			$result = $this->wpdb->update(
				$this->table_name, // Table name
				['paid_date' => current_time('mysql')], // Data to update
				['id' => $claim->id], // Where clause
				['%s'], // Format for the approve_date column
				['%d']  // Format for the id column
			);
	
			// Check if the update was successful
			if ($result === false) {
				error_log('Failed to update approve_date for claim ID: ' . $claim->id);
			} else {
				error_log('Successfully updated approve_date for claim ID: ' . $claim->id);
			}
		}
	}

	public function rrmdir(string $directory): bool
	{
		array_map(fn (string $file) => is_dir($file) ? $this->rrmdir($file) : unlink($file), glob($directory . '/' . '*'));

		return rmdir($directory);
	}


	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Volunteer_Reimbursement_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Volunteer_Reimbursement_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/volunteer-reimbursement-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Volunteer_Reimbursement_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Volunteer_Reimbursement_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		 wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/volunteer-reimbursement-admin.js', array( 'jquery' ), time(), true );
		 $variable_to_js = [
			 'ajax_url' => admin_url('admin-ajax.php'),
			 'nonce' => wp_create_nonce($this->plugin_name.'-nonce')
		 ];
		 wp_localize_script($this->plugin_name, 'Theme_Variables', $variable_to_js);
 
 

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/volunteer-reimbursement-admin.js', array( 'jquery' ), time(), false );

	}

}
