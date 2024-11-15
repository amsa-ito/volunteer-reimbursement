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
				foreach ($reimbursement_ids as $id) {
					$wpdb->delete($table_name, ['id' => $id]);
				}
			} elseif ($new_status) {
				foreach ($reimbursement_ids as $id) {
					$wpdb->update($table_name, ['status' => $new_status], ['id' => $id]);
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
		echo '<form method="post">';
		$reimbursements_table->display();
		echo '</form>';
	
		echo '</div>';
	
	
		// ob_start();

		// include VR_PLUGIN_PATH . 'admin/partials/vr-admin-table.php';
		// echo ob_get_clean();
	
		// Display data in a table and provide options to edit status, export, etc.
	}


	public function vr_export_commbank() {
		// Code to export data for Commbank CSV
	}
	
	public function vr_export_xero() {
		// Code to export data for Xero CSV
	}

	function vr_update_reimbursement_status( $id, $new_status ) {
		global $wpdb;
		$table_name=$wpdb->prefix."volunteer_reimbursements";
		$wpdb->update(
			$table_name,
			['status' => $new_status],
			['id' => $id]
		);

		$reimbursement = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
	

	
		if ( $new_status === 'paid' ) {
			// Fetch user email and send a notification
			$user_info = get_userdata($reimbursement->user_id);
			$user_email = $user_info->user_email;
			wp_mail( $user_email, "Reimbursement Paid", "Your reimbursement request has been paid." );
		}
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
