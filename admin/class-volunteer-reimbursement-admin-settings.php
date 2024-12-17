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

 class Volunteer_Reimbursement_Admin_Settings {

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

    public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'admin_menu', array($this, 'vr_admin_settings') );
		add_action('admin_init', array($this, 'vr_register_settings'));


    }

	public function vr_admin_settings() {
        add_submenu_page(
			'volunteer-reimbursement',
			'Plugin Settings',
			'Settings',
			'manage_volunteer_claims',
			'volunteer-reimbursement-settings',
			array($this, 'vr_settings_page')
		);
    }

    public function vr_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo 'Volunteer Reimbursement Settings' ?></h1>
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
		register_setting(
			'vr_settings_group', 
			'vr_form_submit_notification_recipients', 
			array(
				'type' => 'array',
				'sanitize_callback' => function ($value) {
					// Sanitize the list of emails
					if (is_array($value)) {
						// Filter the array to keep only valid email addresses
						return array_filter($value, function ($email) {
							return filter_var($email, FILTER_VALIDATE_EMAIL);
						});
					}else{
						$emails = array_map('trim', explode(',', $value));
						$valid_emails = [];

						foreach ($emails as $email) {
							// Validate each email
							if (is_email($email)) {
								$valid_emails[] = sanitize_email($email); // Sanitize and store valid emails
							}
						}
						return $valid_emails;
					}
					// If not an array, return an empty array
					return [];
				},
				'default' => []
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
				echo '<p class="description">Enable email notifications to volunteer for when the status of a claim changes</p>';

			},
			'volunteer-reimbursement-settings',
			'vr_settings_section'
		);

		add_settings_field(
			'vr_form_submit_notification_recipients',
			'Claim Submission Notification Recipients',
			function () {
				$value = get_option('vr_form_submit_notification_recipients', []);
				echo '<input type="text" name="vr_form_submit_notification_recipients" value="' . ($value ? implode(',', $value) : '') . '" class="regular-text">';
				echo '<p class="description">Email notifications for when a claim is submitted</p>';


			},
			'volunteer-reimbursement-settings',
			'vr_settings_section'
		);

		// Register the setting to store committee options
		register_setting('vr_settings_group', 'vr_committee_options');

		// Add a settings section
		add_settings_section(
			'vr_committee_section',
			'Form Options',
			null,
			'volunteer-reimbursement-settings'
		);
	
		// Add a text area field for editing the committee options
		add_settings_field(
			'vr_committee_options_field',
			'Committee Options',
			array($this, 'vr_committee_options_callback'),
			'volunteer-reimbursement-settings',
			'vr_committee_section'
		);
	}

	public function vr_committee_options_callback() {
		$options = get_option('vr_committee_options', "AMSA Reps\nAMSA Global Health Committee\nOther"); // Default options
		?>
		<p class="description">Enter one committee option per line. These options will appear in the "Select Committee" dropdown.</p>

		<textarea name="vr_committee_options" rows="10" cols="20" class="large-text"><?php echo esc_textarea($options); ?></textarea>
		<?php
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





 }
 