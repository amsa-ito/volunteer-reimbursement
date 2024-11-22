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
require_once VR_PLUGIN_PATH . "includes/class-vr-payment-request-form.php";
require_once VR_PLUGIN_PATH . "includes/class-vr-reimbursement-form.php";
require_once VR_PLUGIN_PATH . "includes/class-volunteer-reimbursement-string-formatter.php";
require_once VR_PLUGIN_PATH . "public/class-volunteer-reimbursement-my-account.php";


class Volunteer_Reimbursement_Public {

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

		add_shortcode( 'vr_reimbursement_form', array($this, 'vr_display_reimbursement_form') );
		
		add_action('wp_ajax_payment_type_selection', array($this, 'process_payment_type_form'));
		add_action('wp_ajax_nopriv_payment_type_selection', array($this, 'process_payment_type_form'));

		add_action( 'wp_ajax_submit_claim_form', array($this, 'vr_handle_claim_form_submission') );
		add_action( 'wp_ajax_nopriv_submit_claim_form', array($this, 'vr_handle_claim_form_submission') );

		new Volunteer_Reimbursement_My_Account($plugin_name, $version);
		new VR_Payment_Request_Form();
		new VR_Reimbursement_Form();

	}


	public function process_payment_type_form() {
		// Check if the payment type is set in the request
		if (isset($_POST['payment_type'])) {
			$payment_type = sanitize_text_field($_POST['payment_type']);

			$content = "";
			$content = apply_filters('vr_display_public_'.$payment_type, $content);
			debug_print('vr_display_public_'.$payment_type);
			wp_send_json_success(['status' => 'success', 'content' => $content]);
			// // Load the appropriate template based on payment type
			// if ($payment_type === 'volunteer_reimbursement') {
			// 	include VR_PLUGIN_PATH . 'public/partials/reimbursement-form.php'; // Adjust the path as needed
			// } elseif ($payment_type === 'payment_request') {
			// 	include VR_PLUGIN_PATH . 'public/partials/payment-request.php'; // Adjust the path as needed
			// } else {
				
			// 	wp_send_json_error('<p style="color:red;">Invalid payment type selected.</p>');
			// }
		} else {
			wp_send_json_error('<p style="color:red;">No payment type selected.</p>');
		}
	
		// Terminate to prevent further execution
		wp_die();
	}

	public function vr_display_reimbursement_form(){
		ob_start();
		include VR_PLUGIN_PATH . 'public/partials/reimbursement-type-selection.php';
		return ob_get_clean();
	}

	public function vr_handle_claim_form_submission() {
		check_ajax_referer($this->plugin_name.'-nonce', 'nonce');

		$user_id = get_current_user_id();
		$submit_date = current_time('mysql');
		$status = 'pending';

		if ( !isset($_POST['form_type']) || !$_POST['form_type'] ) {
			wp_send_json_error( ['status'=> 'error','message'=> 'Missing claim type field in form, must be a mis-created form'] );
		}

		$form_type = $_POST['form_type'];
		
		$form_data = apply_filters('vr_parse_'. $form_type ,$_POST, $_FILES);

		$error_msg = apply_filters('vr_check_valid_'. $form_type ,$form_data);

		if($error_msg){
			wp_send_json_error( [ 'status' => 'error', 'message' => $error_msg ] );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';

		if($user_id<=0 && isset($form_data['payee_email'])){
			$user_by_email = get_user_by('email', $form_data['payee_email']);
			if($user_by_email){
				$user_id = $user_by_email ->id;
			}
		}

		$result=$wpdb->insert(
			$table_name,
			[
				'submit_date' => $submit_date,
				'user_id' => $user_id,
				'status' => $status,
				'meta' => json_encode($form_data),
				'form_type' => $form_type,
			]
		);

		if($result){
			$this->send_claim_submission_email($wpdb->insert_id, $submit_date, $user_id, $status, $form_data, $form_type);
			wp_send_json_success(['status' => 'success', 'message' => 'Reimbursement submitted successfully!']);
		}else{
			wp_send_json_error(['status' => 'error', 'message' => 'Form submission failed!']);
		}
	
		// Respond with success message
		wp_die();
	}

	public function send_claim_submission_email($claim_id, $submit_date, $user_id, $status, $form_data, $form_type){
		$payee_name = $form_data['payee_name'];
		$payee_email = $form_data['payee_email'];
		$purpose = $form_data['purpose'];

		
		$subject = sprintf('Your %s claim #%d for %s has been paid', 
						$form_type,
						$claim_id,
						$purpose);

		$message = sprintf(
			"Dear %s,\n\nYour reimbursement claim has been successfully submitted on %s",
			$payee_name,
			date('d/m/Y', strtotime($submit_date)),
		);
		if($user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "\nTo track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}
		$message .= "\n\nHere are the details of your claim submission:\n\n";
		$message .= "<table border='1' cellpadding='5' cellspacing='0'>";
		foreach ($form_data as $key => $value) {
			if (!empty($value)) { // Skip empty values
				// Check if value is an array
				if (is_array($value)) {
					$message .= "<tr><td><strong>" . esc_html(ucwords(str_replace('_', ' ', $key))) . ":</strong></td><td>";
					$message .= "<ul>"; // Start an unordered list
					foreach ($value as $item) {
						$message .= "<li>" . esc_html($item) . "</li>"; // List each item
					}
					$message .= "</ul></td></tr>"; // Close the unordered list
				} else {
					// Handle non-array value
					$message .= "<tr><td><strong>" . esc_html(ucwords(str_replace('_', ' ', $key))) . ":</strong></td><td>" . esc_html($value) . "</td></tr>";
				}
			}
		}
		$message .= "</table>";
		
		// add the rest of form_data

		$message .="\n\nThank you.\nAMSA Treasurer";

		$email_status = wp_mail($payee_email, $subject, $message);

		error_log($email_status);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/volunteer-reimbursement-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/volunteer-reimbursement-public.js', array( 'jquery' ), time(), true );
		$variable_to_js = [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce($this->plugin_name.'-nonce')
		];
		wp_localize_script($this->plugin_name, 'Theme_Variables', $variable_to_js);


	}

}
