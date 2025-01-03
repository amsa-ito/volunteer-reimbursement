<?php
/**
 * Reimbursement Form Partial
 *
 * This partial displays the form and instructions for submitting a reimbursement request. 
 * Users can provide details of their out-of-pocket expenses for AMSA-related activities 
 * and attach the required documentation for reimbursement processing.
 *
 * Key Components:
 * - **Instructions Section**: Explains the required documentation and process for reimbursement.
 * - **Warnings**: Ensures users understand the need for tax invoices and payment evidence.
 * - **Form**: Collects necessary data for reimbursement, such as transaction details and uploaded documents.
 *
 * Usage:
 * - Loaded dynamically as part of the `vr_handle_claim_form_submission` workflow.
 *
 *
 * @since 1.0.0
 */

$current_user = wp_get_current_user();
?>

<form id="reimbursement-form" enctype="multipart/form-data" class="vr-claim-form">
    <input type="hidden" name="action" value="submit_claim_form">
    <input type="hidden" name="form_type" value="reimbursement">

    <h2>Reimbursement Form</h2>
    <p>Please complete the form below to submit your reimbursement request.</p>

    <fieldset>
        <legend>Your Details</legend>

        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="payee_name">Payee Name <span class="required">*</span></label>
                <input type="text" name="payee_name" id="payee_name" required <?php if($current_user){echo 'value="' . esc_attr( $current_user->display_name ) . '"'; }?>>
            </div>

            <div class="vr-form-group">
                <label for="payee_email">Email <span class="required">*</span></label>
                <input type="email" name="payee_email" id="payee_email" required <?php if($current_user){echo 'value="' . esc_attr( $current_user->user_email ) . '"'; }?>>
            </div>
        </div>

        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="payee_phone_number">Phone number<span class="required">*</span></label>
                <input type="text" name="payee_phone_number" id="payee_phone_number" required>
            </div>

            <div class="vr-form-group">
            <label for="payee_committee">Select Committee<span class="required">*</span></label>
            <select id="payee_committee" name="payee_committee" required>
            <?php
                    $saved_options = get_option('vr_committee_options', "");
                    $committee_options = array_filter(array_map('trim', explode("\n", $saved_options))); // Split into array and trim spaces

                    foreach ($committee_options as $option) {
                        echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                    }
                    ?>
            </select>

            <!-- Text input for specifying "Other" committee, hidden initially -->
            <input type="text" id="payee-other-committee" name="payee_other_committee" placeholder="Please specify the committee" style="display: none; margin-top: 10px;">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Bank Details</legend>

        <div class="vr-form-row">

            <div class="vr-form-group">
                <label for="payee_bank_name">Bank name<span class="required">*</span></label>
                <input type="text" name="payee_bank_name" id="payee_bank_name" required>
            </div>

            <div class="vr-form-group">
                <label for="payee_bsb">BSB<span class="required">*</span></label>
                <input type="text" name="payee_bsb" id="payee_bsb" required pattern="\d{6}" data-helper="Just the numerals, no hyphen.">
            </div>
        </div>

        <div class="vr-form-row">

            <div class="vr-form-group">
                <label for="payee_account_number">Bank account Number<span class="required">*</span></label>
                <input type="text" name="payee_account_number" id="payee_account_number" required pattern="\d{5,9}">
            </div>

            <div class="vr-form-group">
                <label for="budget_reference">Budget reference</label>
                <input type="text" name="budget_reference" id="budget_reference" data-helper="Please ask your Treasurer to tell you where this transaction has been accounted for.">
            </div>
        </div>

        <div class="vr-form-group">
            <label for="additional_email">Optional additional email for receipt</label>
            <input type="email" name="additional_email" id="additional_email" data-helper="A copy of the reimbursement will be CC'ed to this email address once processed">
        </div>
    </fieldset>


    <fieldset>
        <legend>Reimbursement Details</legend>

        <div class="vr-form-group">
            <label for="purpose">Activity/event/project<span class="required">*</span></label>
            <input type="text" name="purpose" id="purpose" required data-helper="In 10 words or less; this will be used in the email subject.">
        </div>

        <div class="vr-form-group">
            <label for="transaction_details">Transaction details<span class="required">*</span></label>
            <textarea name="transaction_details" id="transaction_details" required data-helper="Please describe each transaction that makes up this claim. For each transaction this should include:<br>- what you bought<br>- who you bought it from<br>- how much it cost"></textarea>
        </div>


        <div class="amount-claimed-container vr-form-group">
            <label for="dollars">Total amount claimed <span class="required">*</span></label>
            <div class="amount-inputs">
                <span>$</span>
                <input type="number" name="dollars" id="dollars" class="amount-input" placeholder="0" min="0" required value="0">
                <span class="separator">.</span>
                <input type="number" name="cents" id="cents" class="amount-input" placeholder="00" min="0" max="99" required value="00" maxlength="2">
            </div>
            <div class="amount-labels">
                <span>Dollars</span>
                <span>Cents</span>
            </div>
        </div>
    </fieldset>
    
    <fieldset>
        <legend>Attachments</legend>

        <div class="vr-form-group">
            <label for="vr-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.<span class="required">*</span></label>

            <div class="vr-example-invoice">
                Example of a valid tax invoice:
                <img 
                src="<?php echo VR_PLUGIN_PATH . 'public/assets/example_tax_invoice.png'; ?>" 
                alt="Example Tax Invoice" 
                class="vr-thumbnail" 
                id="vr-example-invoice-thumbnail">
            </div>

            <input type="file" id="vr-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
            <ul id="vr-file-list"></ul>
        </div>

            <!-- Modal Container -->
        <div id="vr-example-invoice-modal" class="vr-modal">
            <div class="vr-modal-content">
                <span class="vr-modal-close">&times;</span>
                <img 
                    src="<?php echo VR_PLUGIN_PATH . 'public/assets/example_tax_invoice.png'; ?>" 
                    alt="Full Example Tax Invoice" 
                    class="vr-modal-image">
            </div>
        </div>
        
    
    </fieldset>

    <div class="vr-form-group">
        <button type="submit">Submit Reimbursement</button>
    </div>
</form>

