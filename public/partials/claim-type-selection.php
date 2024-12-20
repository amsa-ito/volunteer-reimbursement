
<?php
/**
 * Claim Type Selection Partial
 *
 * This template displays the "Claim Type Selection" form and associated instructions for users.
 * The form allows users to choose between a reimbursement claim or a payment request and provides
 * relevant guidance on each option.
 *
 * Features:
 * - Two main sections: Reimbursement Request and Payment Request.
 * - Detailed instructions and warnings for submitting claims correctly.
 * - Handles logged-in and logged-out user states, displaying appropriate messages.
 * - Includes a form for selecting the claim type, with AJAX functionality for dynamic content loading.
 *
 * Sections:
 * - **Reimbursement Request**: Guidelines for users who have paid out-of-pocket for AMSA-related expenses and wish to be reimbursed.
 * - **Payment Request**: Instructions for users requesting AMSA to pay an invoice directly, with specific use cases outlined.
 * - **Warnings**: Highlights potential issues, such as submitting duplicate requests or providing insufficient documentation.
 *
 * @since 1.0.0
 *
 * Usage:
 * - Included in the `vr_display_reimbursement_form` method of the `Volunteer_Reimbursement_Public` class.
 * - Dynamically loads additional form content via the `form-content` container based on the selected claim type.
 *
 * Note:
 * - Users must be logged in to access and track their reimbursement claims.
 * - For logged-out users, a prompt is displayed to log in and track claims.
 */
?>
<div class="reimburse_title">
  <h2>AMSA Treasury</h2>
</div>

<div class="container reimbursement_css">
    <!-- Reimbursement Request Section -->
    <div class="form-section">
        <h2>Reimbursement Request</h2>
        <p>Use this form if you have paid for something for AMSA and want to be reimbursed.</p>
        <div class="instructions">
            <h3>Instructions:</h3>
            <ul>
                <li>Check with your Treasurer that your expenditure is approved before you pay for anything.</li>
                <li>Keep all paperwork: invoices AND receipts.</li>
                <li>To be reimbursed, you must have an invoice AND a receipt for each transaction.</li>
                <li>Scan or take a photo of the paperwork with your phone as soon as you can.</li>
                <li>Fill in the form. AMSA will get back to you with approval or asking for more information.</li>
            </ul>
            <p><strong>Note:</strong> You will not be reimbursed unless tax invoices and payment evidence are attached. Tax invoice MUST include all required information.</p>
        </div>
    </div>

    <!-- Payment Request Section -->
    <div class="form-section">
        <h2>Payment Request</h2>
        <p>Use this form if you need AMSA to pay for something. Usually, this will be when you have received an invoice from an external supplier that has not yet been paid. If this is the case, just fill in the form.</p>
        <div class="additional-info">
            <p>However, there are a few situations where you might not have an invoice:</p>
            <ul>
                <li><strong>Prizes:</strong> If you have awarded a cash prize, include documentation of the competition or award terms and conditions.</li>
                <li><strong>Suppliers without an ABN:</strong> These suppliers can complete a "Statement by a Supplier" form, available <a href="#">here</a>.</li>
                <li><strong>Donations:</strong> Include documentation of why the donation is being made, such as email communication with the recipient or fundraising event information.</li>
            </ul>
        </div>
        <div class="warning">
            <h3>Reimbursement Warning:</h3>
            <p>Do not submit a payment request if you have already paid for something yourself and require a reimbursement. AMSA is unable to reimburse you if the invoice has been processed for payment via this form.</p>
        </div>
    </div>
</div>

<div class="reimbursement-log">
  <?php
  // Check if the user is logged in
  if ( !is_user_logged_in() ){
      echo '<p>Have an AMSA account? <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">Log in to track your reimbursement ticket</a>.</p>';
  }else{
          // Get the current user's display name
      $current_user = wp_get_current_user();
      $display_name = $current_user->display_name;

      // Display a message for logged-in users
      echo '<p>You are currently logged in as <strong>' . esc_html( $display_name ) . '</strong>. Check your <a href="'.wc_get_account_endpoint_url( 'reimbursement-claims' ).'">existing claims here</a>.</p>';
  }
  ?>
</div>

<form id="claim-type-form" method="post">
    <input type="hidden" name="action" value="claim_type_selection">
    <label for="claim_type">Select payment type:</label>
    <select id="claim_type" name="claim_type" required>
        <option value="">Select an option</option>
        <option value="reimbursement">Volunteer Reimbursement</option>
        <option value="payment_request">Payment Request</option>
    </select>
    <button type="submit">Submit</button>
</form>
<div id="vr-form-content"></div>


<?php
// TODO form validate as you type
// TODO what to do about Optional additional email for receipt
// TODO payment request what to do about bpay number and stuff
?>
