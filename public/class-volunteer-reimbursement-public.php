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
 * - Displaying the reimbursement form using a shortcode.
 * - Processing and validating AJAX form submissions.
 * - Sending notification emails for successful reimbursement claims.
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
		
		add_action('wp_ajax_claim_type_selection', array($this, 'process_claim_type_form'));
		add_action('wp_ajax_nopriv_claim_type_selection', array($this, 'process_claim_type_form'));

		add_action( 'wp_ajax_submit_claim_form', array($this, 'vr_handle_claim_form_submission') );
		add_action( 'wp_ajax_nopriv_submit_claim_form', array($this, 'vr_handle_claim_form_submission') );

		new Volunteer_Reimbursement_My_Account($plugin_name, $version);
		new VR_Payment_Request_Form();
		new VR_Reimbursement_Form();

	}

    /**
     * Process the selection of a claim type during form submission.
     *
     * Validates the claim type sent via AJAX, retrieves the corresponding content using a filter,
     * and returns the generated content to the client.
     *
     * @since 1.0.0
     */
	public function process_claim_type_form() {
		// Check if the payment type is set in the request
		if (isset($_POST['claim_type'])) {
			$claim_type = sanitize_text_field($_POST['claim_type']);

			$content = "";
			$content = apply_filters('vr_display_public_'.$claim_type, $content);

			wp_send_json_success(['status' => 'success', 'content' => $content]);

		} else {
			wp_send_json_error('<p style="color:red;">No payment type selected.</p>');
		}
	
		// Terminate to prevent further execution
		wp_die();
	}

    /**
     * Display the reimbursement form using a shortcode.
     *
     * Outputs the form template that allows users to select a claim type and submit their reimbursement details.
     *
     * @since 1.0.0
     * @return string The rendered form content.
     */
	public function vr_display_reimbursement_form(){
		ob_start();
		include VR_PLUGIN_PATH . 'public/partials/claim-type-selection.php';
		return ob_get_clean();
	}

    /**
     * Handle the submission of the reimbursement form.
     *
     * Validates and processes the submitted form data, stores it in the database, and sends
     * an email notification to the payee. Returns a JSON response indicating success or error.
     *
     * @since 1.0.0
     */
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

		if($wpdb->last_error){
			wp_send_json_error(['status' => 'error', 'message' => 'Form submission failed!']);


		}else{
			$claim_id = $wpdb->insert_id;
			$this->send_claim_submission_email($claim_id, $submit_date, $user_id, $status, $form_data, $form_type);
			$this->send_claim_submission_admin_notification($claim_id, $submit_date, $user_id, $status, $form_data, $form_type);
			wp_send_json_success(['status' => 'success', 'message' => 'Reimbursement submitted successfully!']);
		}
	
		// Respond with success message
		wp_die();
	}

	/**
     * Send a notification email upon successful reimbursement form submission.
     *
     * Generates and sends an email to the payee with details of their claim submission,
     * including a link to track the status if applicable.
     *
     * @since 1.0.0
     * @param int $claim_id The unique ID of the claim.
     * @param string $submit_date The date the claim was submitted.
     * @param int $user_id The ID of the user submitting the claim.
     * @param string $status The initial status of the claim (e.g., "pending").
     * @param array $form_data An associative array of form data.
     * @param string $form_type The type of the claim (e.g., "Travel", "Supplies").
     */
	public function send_claim_submission_email($claim_id, $submit_date, $user_id, $status, $form_data, $form_type){
		$payee_name = $form_data['payee_name'];
		$payee_email = $form_data['payee_email'];
		$purpose = $form_data['purpose'];

		
		$subject = sprintf('Your %s claim #%d for %s has been submitted', 
						$form_type,
						$claim_id,
						$purpose);

		$message = sprintf(
			"Dear %s,<br><br>Your reimbursement claim has been successfully submitted on %s",
			$payee_name,
			date('d/m/Y', strtotime($submit_date)),
		);
		if($user_id>0){
			$claim_url = wc_get_account_endpoint_url( 'reimbursement-claims' );
			$message .= "<br>To track the status of your claim, please visit your <a href='" . esc_url($claim_url) . "'>account page</a>";
		}
		$message .= "<br><br>Here are the details of your claim submission:<br><br>";
		$message = apply_filters("vr_display_submission_email_".$form_type, $message, $form_data);
		
		$message .="<br><br>Thank you.<br>AMSA Treasurer";

		$email_status = wp_mail($payee_email, $subject, $message, $headers="Content-type: text/html");

		error_log($email_status);
	}

	public function send_claim_submission_admin_notification($claim_id, $submit_date, $user_id, $status, $form_data, $form_type){
		$admin_emails = get_option('vr_form_submit_notification_recipients', []);
		if(empty($admin_emails)){
			return;
		}

		$claim_page_url = add_query_arg(['page' => 'vr-claim-detail', 'claim_id' => $claim_id], admin_url('admin.php'));
		$payee_name = $form_data['payee_name'];
		$payee_email = $form_data['payee_email'];
		$purpose = $form_data['purpose'];

		$subject = "New ".MetaDataFormatter::format_form_type($form_type)." Submission: Claim ID #".$claim_id;

		$message =  sprintf(
			"A new claim has been submitted on %s.\n\n".
			"Claim Details:\n".
			"-----------------\n".
			"Claim ID: %d\n".
			"Submit Date: %s\n".
			"Claim Status: %s\n\n".
			"Payee Information:\n".
			"---------------------\n".
			"Name: %s\n".
			"Email: %s\n\n".
			"Purpose: %s\n\n".
			"You can view and manage this claim here: %s\n\n".
			"Please review and take the necessary action.\n\n".
			"Best regards,\n".
			"AMSA IT",
			date('j F, Y, g:i a', strtotime($submit_date)),  // Format the submission date
			$claim_id,
			date('j F, Y, g:i a', strtotime($submit_date)),
			MetaDataFormatter::format_status($status),  // Capitalize the status (e.g., 'pending', 'approved')
			$payee_name,
			$payee_email,
			$purpose,
			$claim_page_url
		);

		
		$email_status = wp_mail($admin_emails, $subject, $message);

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
