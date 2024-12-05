<?php
/**
 * Displays the reimbursement claims for the current user in the WooCommerce My Account page.
 *
 * This partial outputs a table of claims, showing key details such as claim ID, submission date,
 * status, claim type, purpose, transaction details, and comments. If no claims are found, a 
 * message indicating the absence of claims is displayed.
 *
 * @since 1.0.0
 *
 * Variables:
 * @var array $claims An array of reimbursement claim objects for the logged-in user.
 * Each object contains:
 *      - id (int): The unique identifier of the claim.
 *      - submit_date (string): The date the claim was submitted.
 *      - status (string): The current status of the claim (e.g., "Pending", "Approved").
 *      - form_type (string): The type of the claim (e.g., "Travel", "Supplies").
 *      - meta (string): A JSON-encoded string containing additional claim metadata, which may include:
 *          - purpose (string): The purpose of the claim.
 *          - transaction_details (string): Details of the associated transaction.
 *          - comments (string): Any comments or notes related to the claim.
 *
 * External Dependencies:
 * - MetaDataFormatter: A utility class used for formatting status and form type fields.
 *   - MetaDataFormatter::format_status_colored(): Formats the status field with appropriate styling.
 *   - MetaDataFormatter::format_form_type(): Formats the form type field.
 *
 * HTML Structure:
 * - A table (`woocommerce-orders-table`) is displayed when claims are available, with columns:
 *      - Claim ID, Submit Date, Status, Claim Type, Purpose, Transaction Details, and Comments.
 * - Each comment is shown in a tooltip if available, or displays "No comments available" otherwise.
 * - If no claims exist, a paragraph message is displayed: "You have no reimbursement claims."
 *
 */

?>

<h2>Your Reimbursement Claims</h2>
<?php

if ($claims) {

    ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
    <thead>
    <tr>
    <th>Claim ID</th>
    <th>Submit Date</th>
    <th>Status</th>
    <th>Claim Type</th>
    <th>Purpose</th>
    <th>Transaction Details</th>
    <th>Comments</th>
    </tr>
    </thead>
    <tbody>

    <?php
    foreach ($claims as $claim) {
        $claim_meta = json_decode($claim->meta, true); // Decode the meta JSON
        $purpose = isset($claim_meta['purpose']) ? esc_html($claim_meta['purpose']) : 'N/A';
        $transaction_details = isset($claim_meta['transaction_details']) ? esc_html($claim_meta['transaction_details']) : 'N/A';
        $comments = (isset($claim_meta['comments']) && $claim_meta['comments']) ? '<div class="vr-comments-container" title="'.esc_html($claim_meta['comments']).'">Hover to view</div>' : 'No comments available'; // Fetch comments
        ?>
        <tr>
        <td><?php echo esc_html($claim->id) ?></td>
        <td><?php echo esc_html(date('Y-m-d', strtotime($claim->submit_date))) ?></td>
        <td><?php echo MetaDataFormatter::format_status_colored($claim->status) ?></td>
        <td><?php echo  MetaDataFormatter::format_form_type($claim->form_type) ?></td>
        <td><?php echo  $purpose ?></td>
        <td><?php echo  $transaction_details ?></td>
        <td><?php echo ($comments)?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
    </table>
    <?php
} else {
    ?>
    <p>You have no reimbursement claims.</p>
    <?php
}