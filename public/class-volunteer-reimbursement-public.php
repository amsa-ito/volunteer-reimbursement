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
include VR_PLUGIN_PATH . "includes/class-vr-payment-request-form.php";
include VR_PLUGIN_PATH . "includes/class-vr-reimbursement-form.php";

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

		add_action( 'wp_ajax_submit_request_form', array($this, 'vr_handle_request_form_submission') );
		add_action( 'wp_ajax_nopriv_submit_request_form', array($this, 'vr_handle_request_form_submission') );

		new VR_Payment_Request_Form();
		new VR_Reimbursement_Form();

	}


	public function process_payment_type_form() {
		// Check if the payment type is set in the request
		if (isset($_POST['payment_type'])) {
			$payment_type = sanitize_text_field($_POST['payment_type']);
			
			// Load the appropriate template based on payment type
			if ($payment_type === 'volunteer_reimbursement') {
				include VR_PLUGIN_PATH . 'public/partials/reimbursement-form.php'; // Adjust the path as needed
			} elseif ($payment_type === 'payment_request') {
				include VR_PLUGIN_PATH . 'public/partials/payment-request.php'; // Adjust the path as needed
			} else {
				
				wp_send_json_error('<p style="color:red;">Invalid payment type selected.</p>');
			}
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

	public function vr_handle_request_form_submission() {
		check_ajax_referer($this->plugin_name.'-nonce', 'nonce');

		$user_id = get_current_user_id();
		$submit_date = current_time('mysql');
		$status = 'pending';

		if ( !isset($_POST['form_type']) || !$_POST['form_type'] ) {
			wp_send_json_error( ['status'=> 'error','message'=> 'Missing form type field in form, must be a mis-created form'] );
		}

		$form_type = $_POST['form_type'];
		
		$form_data = apply_filters('vr_parse_'. $form_type ,$_POST, $_FILES);

		$error_msg = apply_filters('vr_check_valid_'. $form_type ,$form_data);

		if($error_msg){
			wp_send_json_error( [ 'status' => 'error', 'message' => $error_msg ] );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'volunteer_reimbursements';

		$wpdb->insert(
			$table_name,
			[
				'submit_date' => $submit_date,
				'user_id' => $user_id,
				'status' => $status,
				'meta' => json_encode($form_data),
				'form_type' => $form_type,
			]
		);
	
		// Respond with success message
		wp_send_json_success(['status' => 'success', 'message' => 'Reimbursement submitted successfully!']);
		wp_die();
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
