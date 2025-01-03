# Volunteer Reimbursement Plugin

The Volunteer Reimbursement Plugin is a custom WordPress extension designed to streamline the submission and management of reimbursement and payment requests for volunteers. It offers distinct functionalities for both volunteers and administrators, enhancing efficiency and transparency in financial processes.

## Features

### Volunteer Interface

- **Submit Requests**: Volunteers can fill out and submit reimbursement or payment request forms directly through the website.

- **Email Notifications**: Automated emails inform volunteers of any status changes to their requests.

- **Status Tracking**: Volunteers can monitor the status of their submissions via the "My Account" page, provided their email matches a registered user on the website.

### Administrator Interface

- **Manage Requests**: View and edit submitted reimbursement and payment requests.

- **Update Statuses**: Change request statuses among "Pending," "Approved," "Rejected," and "Paid."

- **Bulk Export**: Export claims in Xero and ABA formats for streamlined bulk payments.

### Frontend Management

- **Dashboard Overview**: A comprehensive dashboard table displays all claims, categorized by type (payment requests or reimbursements) and status (pending, approved, rejected, paid).

- **Detailed View and Comments**: Administrators can view detailed claim information and leave comments, which are visible to volunteers in their account and via email notifications.

- **Email Notifications**: Status changes trigger email notifications to volunteers, unless disabled in the plugin settings.

## Technical Details

- **Database Structure**: Upon activation, the plugin creates a custom database table with columns for ID, submission date, approval date, payment date, user ID, status, form type, and metadata.

- **User Permissions**: A custom permission, "manage_volunteer_claims," is assigned to administrators and shop managers, granting access to the reimbursement admin dashboard.

- **Data Access**: Form data is stored outside of the standard WordPress post types and is accessed via direct database queries. Ensure queries are constructed carefully to maintain data integrity.

## Installation

1. **Download the Plugin**: Clone or download the plugin from the [GitHub repository](https://github.com/amsa-ito/volunteer-reimbursement).

2. **Upload to WordPress**: Navigate to the WordPress admin dashboard, go to Plugins > Add New > Upload Plugin, and upload the plugin ZIP file.

3. **Activate the Plugin**: After installation, activate the plugin through the 'Plugins' menu in WordPress.

## Usage

- **For Volunteers**: Access the reimbursement form through the form provided by shortcode `vr_reimbursement_form`, submit requests, and monitor statuses via the "My Account" page.

- **For Administrators**: Manage submissions through the admin dashboard, update statuses, leave comments, and export claims as needed.

## Contributing

Contributions are welcome. Please fork the repository and submit pull requests for any enhancements or bug fixes.

## License

This plugin is licensed under the GPLv2 or later. See the [LICENSE.txt](https://github.com/amsa-ito/volunteer-reimbursement/blob/main/LICENSE.txt) file for details.

For more information or support, please refer to the [GitHub repository](https://github.com/amsa-ito/volunteer-reimbursement). 
