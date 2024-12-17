<?php
/**
 * Payment Request Partial
 *
 * This partial provides the form and instructions for submitting a payment request. 
 * Users can request AMSA to pay an external supplier directly, with additional details 
 * for specific scenarios such as prizes, suppliers without ABNs, or donations.
 *
 * Usage:
 * - Included dynamically based on user selection in the `claim-type-selection` partial.
 *
 * @since 1.0.0
 */
$current_user = wp_get_current_user();

?>

<form id="reimbursement-form" enctype="multipart/form-data"  class="vr-claim-form">
    <input type="hidden" name="action" value="submit_claim_form">
    <input type="hidden" name="form_type" value="payment_request">

    <h2>Payment Request Form</h2>
    <p>Please complete the form below to submit your payment request.</p>

    <fieldset>
        <legend>Your Details</legend>

        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="payee_name">Your Name<span class="required">*</span></label>
                <input type="text" name="payee_name" id="payee_name" required <?php if($current_user){echo 'value="' . esc_attr( $current_user->display_name ) . '"'; }?>>

            </div>

            <div class="vr-form-group">
                <label for="payee_email">Your Email<span class="required">*</span></label>
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
                    <!-- <option value="AMSA Reps">AMSA Reps</option>
                    <option value="AMSA Global Health Committee">AMSA Global Health Committee</option>
                    <option value="AMSA Rural Health Committee">AMSA Rural Health Committee</option>
                    <option value="Board of Directors">Board of Directors</option>
                    <option value="Convention 2022">Convention 2022</option>
                    <option value="Convention 2023">Convention 2023</option>
                    <option value="Executive">Executive</option>
                    <option value="Careers Conference 23">Careers Conference 23</option>
                    <option value="AMSA Indigenous Health">AMSA Indigenous Health</option>
                    <option value="Med Ed">Med Ed</option>
                    <option value="AMSA ISN">AMSA ISN</option>
                    <option value="AMSA Projects">AMSA Projects</option>
                    <option value="NLDS 2022">NLDS 2022</option>
                    <option value="RHS 2022">RHS 2022</option>
                    <option value="AMSA Queer">AMSA Queer</option>
                    <option value="Vampire Cup">Vampire Cup</option>
                    <option value="Mental Health">Mental Health</option>
                    <option value="Gender Equity">Gender Equity</option>
                    <option value="National Council">National Council</option>
                    <option value="Other">Other</option> -->
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


        <!-- Supplier Details -->
    <fieldset>
        <legend>Supplier Details</legend>
        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="business_name">Business name<span class="required">*</span></label>
                <input type="text" name="business_name" id="business_name" required>
            </div>

            <div class="vr-form-group">
                <label for="contact_name">Contact name</label>
                <input type="text" name="contact_name" id="contact_name">
            </div>
        </div>

        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="supplier_email">Supplier Email<span class="required">*</span></label>
                <input type="email" name="supplier_email" id="supplier_email" required>
            </div>

            <div class="vr-form-group">
                <label for="supplier_phone">Supplier Phone</label>
                <input type="text" name="supplier_phone" id="supplier_phone">
            </div>
        </div>

        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="supplier_bank_name">Bank account name<span class="required">*</span></label>
                <input type="text" name="supplier_bank_name" id="supplier_bank_name" required>
            </div>

            <div class="vr-form-group">
                <label for="supplier_bsb">BSB number<span class="required">*</span></label>
                <input type="text" name="supplier_bsb" id="supplier_bsb" pattern="\d{5,6}" data-helper="Just the numerals, no hyphen." required>
            </div>

            <div class="vr-form-group">
                <label for="supplier_account_number">Bank account Number<span class="required">*</span></label>
                <input type="text" name="supplier_account_number" id="supplier_account_number" pattern="\d{5,9}" required>
            </div>
        </div>

        <!-- <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="supplier_bpay">BPAY biller code</label>
                <input type="text" name="supplier_bpay" id="supplier_bpay" pattern="\d{4,6}" data-helper="Just the numerals, no hyphen.">
            </div>

            <div class="vr-form-group">
                <label for="supplier_bpay_reference">BPAY Account or reference number</label>
                <input type="text" name="supplier_bpay_reference" id="supplier_bpay_reference">
            </div>
        </div> -->
    </fieldset>
    

    <fieldset>
        <legend>Transaction Details</legend>
        <div class="vr-form-row">
            <div class="vr-form-group">
                <label for="purpose">Activity/event/project<span class="required">*</span></label>
                <input type="text" name="purpose" id="purpose" required data-helper="In 10 words or less; this will be used in the email subject.">
            </div>

            <div class="vr-form-group">
                <label for="due_date">Due date for this payment<span class="required">*</span></label>
                <input type="date" name="due_date" id="due_date" required />
            </div>
        </div>

        <div class="vr-form-group">
            <label for="transaction_details">Payment description and/or notes<span class="required">*</span></label>
            <textarea name="transaction_details" id="transaction_details" data-helper="If this is captured in the previous field or on the invoice itself, just leave this blank. However if neither of these adequately describe what you are paying for, include a longer description here."></textarea>
        </div>

        <div class="vr-form-row">
            <div class="amount-claimed-container vr-form-group">
                <label for="dollars">Amount to be paid <span class="required">*</span></label>
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

            <div class="vr-form-group">
                <label for="currency">Currency</label>
                <input type="text" name="currency" id="currency" class="currency-input" placeholder="AUD" value="AUD">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Attachments</legend>
        <div class="vr-form-group">
            <label for="vr-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.</label>

            <input type="file" id="vr-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
            <ul id="vr-file-list"></ul>
        </div>
    </fieldset>

    <div class="vr-form-group">
        <button type="submit">Submit Payment Request</button>
    </div>
</form>

