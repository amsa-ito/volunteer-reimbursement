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
		add_action( 'admin_menu', array($this, 'vr_admin_menu') );

		add_action('admin_init', array($this, 'vr_register_settings'));

		add_action('wp_ajax_submit_aba_export', array($this, 'generate_aba_export'));
        add_action('wp_ajax_nopriv_submit_aba_export', array($this, 'generate_aba_export'));

		add_action('wp_ajax_export_xero', array($this, 'generate_xero_export'));
        add_action('wp_ajax_nopriv_export_xero', array($this, 'generate_xero_export'));

		add_action('vr_reimbursement_pending_to_approved', array($this, 'claim_approved_email'), 10, 2);
		add_action('vr_reimbursement_pending_to_paid', array($this, 'claim_paid_email'), 10, 2);
		add_action('vr_reimbursement_approved_to_paid', array($this, 'claim_paid_email'), 10, 2);

		new Volunteer_Reimbursement_Admin_Form_Details($plugin_name, $version);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function vr_admin_menu() {
		add_menu_page(
			'Volunteer Reimbursement',
			'Reimbursement',
			'edit_posts',
			'volunteer-reimbursement',
			array($this, 'vr_admin_page'),
			'dashicons-admin-users',
			20
		);

		add_submenu_page(
			'volunteer-reimbursement',
			'Reimbursement Settings',
			'Settings',
			'manage_options',
			'volunteer-reimbursement-settings',
			array($this, 'vr_settings_page')
		);

	}

	public function vr_admin_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';

		$selected_status = $_GET['status'] ?? '';
		
		// Retrieve counts for each status
		$statuses = [
			'All' => '',
			'Pending' => 'pending',
			'Approved' => 'approved',
			'Paid' => 'paid'
		];
	
		$status_counts = [];
		foreach ($statuses as $status_name => $status_value) {
			if ($status_value) {
				// Count for specific status
				$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", $status_value));
			} else {
				// Total count for all statuses
				$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
			}
			$status_counts[$status_name] = $count;
		}

		// Retrieve reimbursements with optional status filtering
		$query = "SELECT * FROM $table_name";
		if ($selected_status) {
			$query .= $wpdb->prepare(" WHERE status = %s", $selected_status);
		}
		$query .= " ORDER BY submit_date DESC";
		$reimbursements = $wpdb->get_results($query);

		if (isset($_POST['action']) && $_POST['action'] !== -1) {
			$action = $_POST['action'];
			$reimbursement_ids = $_POST['reimbursement_ids'] ?? [];

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
				$ids_placeholder = implode(',', array_fill(0, count($reimbursement_ids), '%d'));
				$sql = "DELETE FROM {$table_name} WHERE id IN ($ids_placeholder)";
				$wpdb->query($wpdb->prepare($sql, $reimbursement_ids));

			} elseif ($new_status) {
				$ids_placeholder = implode(',', array_fill(0, count($reimbursement_ids), '%d'));
				$sql = "UPDATE {$table_name} SET status = %s WHERE id IN ($ids_placeholder)";
				$parameters = array_merge([$new_status], $reimbursement_ids);

				$wpdb->query($wpdb->prepare($sql, $parameters));

				// Create an indexed array of reimbursements by their ID
				$indexed_reimbursements = [];
				foreach ($reimbursements as $reimbursement) {
					$indexed_reimbursements[$reimbursement->id] = $reimbursement;
				}

				$missing_ids=[];
				foreach ($reimbursement_ids as $id) {
					if (isset($indexed_reimbursements[$id])) {
						$reimbursement = $indexed_reimbursements[$id];
						$old_status = $reimbursement->status;

						if($old_status===$new_status){
							continue;
						}
						// debug_print('vr_reimbursement_' . $old_status . '_to_' . $new_status);
						do_action('vr_reimbursement_' . $old_status . '_to_' . $new_status, $reimbursement, $new_status);
					} else {
						$missing_ids[] = $id;
					}
				}
				if($missing_ids){
					?>
					<div class="error notice">
						<p>Claims with ID <?php echo(implode(", ", $missing_ids))?> not found.</p>
					</div>
					<?php
				}else{
					?>
					<div class="success notice">
						<p>Claim status changed to <?php echo(esc_attr($new_status))?> </p>
					</div>
					<?php
				}
			}
	
			// Reload the page after performing actions
			echo '<script>location.reload();</script>';
		}

		echo '<div class="wrap">';
		echo '<h1>Volunteer Reimbursements</h1>';

		// Filter by form type
		echo '<form method="get">';
		echo '<select name="form_type">';
		echo '<option value="">All Form Types</option>';
		foreach ($wpdb->get_col("SELECT DISTINCT form_type FROM $table_name") as $type) {
			printf('<option value="%s" %s>%s</option>', esc_attr($type), selected($_GET['form_type'] ?? '', $type, false), esc_html($type));
		}
		echo '</select>';
		echo '<input type="submit" class="button" value="Filter">';
		echo '</form>';

		echo '<ul class="subsubsub">';
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
		$reimbursements_table = new VR_Reimbursements_Table($reimbursements);
		$reimbursements_table->prepare_items();
		echo '<form method="post" id="vr_reimbursement_table">';
		$reimbursements_table->display();
		echo '</form>';
	
		echo '</div>';

		ob_start();
		?>
		<script>
		var modalHtml = `
			<div id="export-aba-modal">
				<h2>Export ABA Details</h2>
				<div class="form-row">
					<div class="form-group">
						<label for="bsb">BSB:</label>
						<input type="text" id="bsb" name="description[bsb]" required>
					</div>
					<div class="form-group">
						<label for="account_number">Account Number:</label>
						<input type="text" id="account_number" name="description[account_number]" required>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group">
						<label for="bank_name">Bank Name:</label>
						<input type="text" id="bank_name" name="description[bank_name]" required value=<?php echo esc_attr(get_option('vr_default_bank_name', ''));?>>
					</div>
					<div class="form-group">
						<label for="user_name">User Name:</label>
						<input type="text" id="user_name" name="description[user_name]" required value=<?php echo esc_attr(wp_get_current_user()->display_name);?>>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group">
						<label for="remitter">Remitter:</label>
						<input type="text" id="remitter" name="description[remitter]" required>
					</div>
					<div class="form-group">
						<label for="entry_id">Entry ID:</label>
						<input type="text" id="entry_id" name="description[entry_id]" required>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description[description]" required>
					</div>
				</div>
				<div class="form-actions">
					<button class="button action" id="submit_aba_export">Export to ABA</button>
				</div>
				<div id="form-response"></div>
			</div>`;
		</script>

		<?php
		echo ob_get_clean();
	
	}

	public function generate_aba_export(){
		$reimbursement_ids = $_POST['reimbursement_ids'] ?? [];
		if(empty($reimbursement_ids)){
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

		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';

		$query = sprintf(
			"SELECT * FROM {$table_name} WHERE id IN (%s)",
			implode(',', array_map('intval', $reimbursement_ids))
		);
		$reimbursements = $wpdb->get_results($query);

		if (empty($reimbursements)) {
			wp_send_json_error(['status' => 'error', 'message' => 'No valid reimbursements found']);
		}

		$transactions = [];
		foreach($reimbursements as $reimbursement) {
			$reimbursement_data = json_decode($reimbursement->meta);

			$transaction = new Transaction();
			$transaction = apply_filters('vr_get_transaction_'.$reimbursement->form_type, $transaction, $reimbursement);

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

	
	public function generate_xero_export() {
		$reimbursement_ids = $_POST['reimbursement_ids'] ?? [];
		if(empty($reimbursement_ids)){
			wp_send_json_error([ 'status' => 'error', 'message' => 'No forms selected' ] );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';

		$query = sprintf(
			"SELECT * FROM {$table_name} WHERE id IN (%s)",
			implode(',', array_map('intval', $reimbursement_ids))
		);
		$reimbursements = $wpdb->get_results($query);
		
		if (empty($reimbursements)) {
			wp_send_json_error(['status' => 'error', 'message' => 'No valid reimbursements found']);
		}
		ob_start();
		$output = fopen('php://output', 'w');
	
		// Write header row
		$headers = [
			'*ContactName', 'EmailAddress', 'POAddressLine1', 'POAddressLine2', 'POAddressLine3', 'POAddressLine4',
			'POCity', 'PORegion', 'POPostalCode', 'POCountry', '*InvoiceNumber', '*InvoiceDate', '*DueDate',
			'InventoryItemCode', 'Description', '*Quantity', '*UnitAmount', '*AccountCode', '*TaxType',
			'TrackingName1', 'TrackingOption1', 'TrackingName2', 'TrackingOption2', 'Currency'
		];
		fputcsv($output, $headers);

		foreach ($reimbursements as $reimbursement) {
			$reimbursement_data = json_decode($reimbursement->meta, true); // Decode meta field

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
                '*InvoiceNumber' => $reimbursement->id,
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
			$filtered_xero_bill_note = apply_filters('vr_get_xero_bill_note_'.$reimbursement->form_type, $xero_bill_note, $reimbursement);

			if(count(array_intersect_key($filtered_xero_bill_note, $xero_bill_note)) != count($xero_bill_note)){
				wp_send_json_error(['status' => 'error', 'message' => 'xero bill note filtering for '.$reimbursement->form_type.'changed the number of keys in the array']);
			}

			fputcsv($output, $filtered_xero_bill_note);
		}

		fclose($output);

		// Capture the output buffer and clean it
		$csv_content = ob_get_clean();

		// Send CSV file as response
		wp_send_json_success([
			'status' => 'success',
			'message' => 'Xero export generated successfully.',
			'file_content' => base64_encode($csv_content), // Base64 encode for safe transfer
		]);
		wp_die();

	}

	public function vr_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Volunteer Reimbursement Settings', 'text-domain'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('vr_settings_group');
				do_settings_sections('volunteer-reimbursement-settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function vr_register_settings() {
		// Register the settings
		register_setting(
			'vr_settings_group', 
			'vr_default_bank_name', 
			array(
				'type' => 'string',
				'sanitize_callback' => array($this, 'vr_sanitize_bank_name'),
				'default' => ''
			)
		);
		register_setting(
			'vr_settings_group', 
			'vr_allow_notification_emails', 
			array(
				'type' => 'string',
				'sanitize_callback' => function ($value) {
					return $value === 'yes' ? 'yes' : 'no';
				},
				'default' => 'yes'
			)
		);
	
		// Add settings section
		add_settings_section(
			'vr_settings_section',
			'General Settings',
			function () {
				echo '<p>' . __('Configure the default settings for Volunteer Reimbursement.', 'text-domain') . '</p>';
			},
			'volunteer-reimbursement-settings'
		);
	
		// Add Default Bank Name field
		add_settings_field(
			'vr_default_bank_name',
			'Default Bank Name',
			function () {
				$value = get_option('vr_default_bank_name', '');
				echo '<input type="text" id="vr_default_bank_name" maxlength="3" name="vr_default_bank_name" value="' . esc_attr($value) . '" class="regular-text">';
				echo '<p class="description">Enter a bank name abbreviation (e.g., CBA for Commonwealth Bank). Must be 3 capital letters.</p>';
			},
			'volunteer-reimbursement-settings',
			'vr_settings_section'
		);
	
		// Add Allow Notification Emails field
		add_settings_field(
			'vr_allow_notification_emails',
			'Allow Notification Emails',
			function () {
				$value = get_option('vr_allow_notification_emails', 'yes');
				echo '<label><input type="checkbox" id="vr_allow_notification_emails" name="vr_allow_notification_emails" value="yes" ' . checked($value, 'yes', false) . '> Enable notification emails</label>';
				echo '<p class="description">Enable email notifications for when the status of a claim changes</p>';

			},
			'volunteer-reimbursement-settings',
			'vr_settings_section'
		);
	}

	public function vr_sanitize_bank_name($input) {
		if (preg_match('/^[A-Z]{3}$/', $input)) {
			return $input; // Valid format
		}
	
		// Add admin notice for invalid input
		add_settings_error(
			'vr_default_bank_name',
			'invalid_bank_name',
			'Default Bank Name must be exactly 3 capital letters (e.g., CBA).',
			'error'
		);
	
		return get_option('vr_default_bank_name', ''); // Fallback to the existing value
	}


	public function claim_approved_email($reimbursement, $new_status) {
		if(get_option('vr_allow_notification_emails', 'yes')!=='yes'){
			return;
		}
		$meta = json_decode($reimbursement->meta, true);
		$payee_name = $meta['payee_name'];
		$payee_email = $meta['payee_email'];
		$purpose = $meta['purpose'];
		$amount = number_format($meta['amount']['dollars'] + $meta['amount']['cents'] / 100, 2);
	
		$subject = sprintf('Your %s claim #%d for %s has been approved', 
						$reimbursement->form_type,
						 $reimbursement->id,
						 $purpose);
		$message = sprintf(
			"Dear %s,\n\nYour reimbursement claim submitted on %s has been approved.\n\nPurpose: %s\n Description: %s\nAmount: $%s",
			$payee_name,
			date('d/m/Y', strtotime($reimbursement->submit_date)),
			$purpose,
			$transaction_details,
			$amount
		);
		if($reimbursement->user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "\nTo track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}

		$message .="\n\nThank you.\nAMSA Treasurer";
	
		$email_status = wp_mail($payee_email, $subject, $message);

		error_log($email_status);
	}

	public function claim_paid_email($reimbursement, $new_status) {
		if(get_option('vr_allow_notification_emails', 'yes')!=='yes'){
			return;
		}
		$meta = json_decode($reimbursement->meta, true);
		$payee_name = $meta['payee_name'];
		$payee_email = $meta['payee_email'];
		$purpose = $meta['purpose'];
		$amount = number_format($meta['amount']['dollars'] + $meta['amount']['cents'] / 100, 2);
		$transaction_details = $meta['transaction_details'] ?? 'N/A';
	
		$subject = sprintf('Your %s claim #%d for %s has been paid', 
						$reimbursement->form_type,
						$reimbursement->id,
						$purpose);

		$message = sprintf(
			"Dear %s,\n\nYour reimbursement claim submitted on %s has been paid.\n\nPurpose: %s\n Description: %s\nAmount: $%s",
			$payee_name,
			date('d/m/Y', strtotime($reimbursement->submit_date)),
			$purpose,
			$transaction_details,
			$amount
		);
		if($reimbursement->user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "\nTo track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}

		$message .="\n\nThank you.\nAMSA Treasurer";
	
		$email_status = wp_mail($payee_email, $subject, $message);

		error_log($email_status);
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
