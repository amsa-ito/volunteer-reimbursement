<?php
$current_user = wp_get_current_user();

?>

<form id="reimbursement-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="submit_claim_form">
    <input type="hidden" name="form_type" value="payment_request">

    <h3>Your details</h3>

    <div class="rv-form-group">
        <label for="payee_name">Payee Name<span class="required">*</span></label>
        <input type="text" name="payee_name" id="payee_name" required <?php if($current_user){echo 'value="' . esc_attr( $current_user->display_name ) . '"'; }?>>

    </div>

    <div class="rv-form-group">
    <label for="payee_committee">Select Committee<span class="required">*</span></label>
    <select id="payee_committee" name="payee_committee" required>
        <option value="AMSA Reps">AMSA Reps</option>
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
        <option value="Other">Other</option>
    </select>

    <!-- Text input for specifying "Other" committee, hidden initially -->
    <input type="text" id="payee-other-committee" name="payee_other_committee" placeholder="Please specify the committee" style="display: none; margin-top: 10px;">
    </div>

    <div class="rv-form-group">
        <label for="payee_email">Email<span class="required">*</span></label>
        <input type="email" name="payee_email" id="payee_email" required <?php if($current_user){echo 'value="' . esc_attr( $current_user->user_email ) . '"'; }?>>

    </div>

    <div class="rv-form-group">
        <label for="payee_phone_number">Phone number<span class="required">*</span></label>
        <input type="text" name="payee_phone_number" id="payee_phone_number" required>
    </div>
<!-- 
    <div class="rv-form-group">
        <label for="budget_reference">Budget reference</label>
        <input type="text" name="budget_reference" data-helper="Please ask your Treasurer to tell you where this transaction has been accounted for.">
    </div> -->

    <!-- <div class="rv-form-group">
        <label for="additional_email">Optional additional email for receipt</label>
        <input type="text" name="additional_email" data-helper="A copy of the reimbursement will be CC'ed to this email address once processed">
    </div> -->


    <h3>Supplier details</h3>
    <div class="rv-form-group">
        <label for="business_name">Business name<span class="required">*</span></label>
        <input type="text" name="business_name" id="business_name" required>
    </div>

    <div class="rv-form-group">
        <label for="contact_name">Contact name</label>
        <input type="text" name="contact_name" id="contact_name">
    </div>

    <div class="rv-form-group">
        <label for="supplier_email">Email<span class="required">*</span></label>
        <input type="email" name="supplier_email" id="supplier_email" required>
    </div>

    <div class="rv-form-group">
        <label for="supplier_phone">Phone</label>
        <input type="text" name="supplier_phone" id="supplier_phone">
    </div>

    <div class="rv-form-group">
        <label for="supplier_bank_name">Bank account name</label>
        <input type="text" name="supplier_bank_name" id="supplier_bank_name">
    </div>

    <div class="rv-form-group">
        <label for="supplier_bsb">BSB number</label>
        <input type="text" name="supplier_bsb" id="supplier_bsb" pattern="\d{5,6}" data-helper="Just the numerals, no hyphen.">
    </div>

    <div class="rv-form-group">
        <label for="supplier_account_number">Bank account Number</label>
        <input type="text" name="supplier_account_number" id="supplier_account_number" pattern="\d{5,9}">
    </div>

    <div class="rv-form-group">
        <label for="supplier_bpay">BPAY biller code</label>
        <input type="text" name="supplier_bpay" id="supplier_bpay" pattern="\d{4,6}" data-helper="Just the numerals, no hyphen.">
    </div>

    <div class="rv-form-group">
        <label for="supplier_bpay_reference">BPAY Account or reference number</label>
        <input type="text" name="supplier_bpay_reference" id="supplier_bpay_reference">
    </div>

    <h3>Transaction details</h3>

    <div class="rv-form-group">
        <label for="purpose">Activity/event/project<span class="required">*</span></label>
        <input type="text" name="purpose" id="purpose" required data-helper="In 10 words or less; this will be used in the email subject.">
    </div>

    <div class="rv-form-group">
        <label for="transaction_details">Payment description and/or notes<span class="required">*</span></label>
        <textarea name="transaction_details" id="transaction_details" data-helper="If this is captured in the previous field or on the invoice itself, just leave this blank. However if neither of these adequately describe what you are paying for, include a longer description here."></textarea>
    </div>
    
    <div class="rv-form-group">
        <label for="due_date">Due date for this payment<span class="required">*</span></label>
        <input type="date" name="due_date" id="due_date" required />
    </div>

    <div class="amount-claimed-container rv-form-group">
        <label for="dollars">Amount to be paid <span class="required">*</span></label>
        <div class="amount-inputs">
            <span>$</span>
            <input type="number" name="dollars" id="dollars" class="amount-input" placeholder="0" min="0" required value="0">
            <span class="separator">.</span>
            <input type="number" name="cents" id="cents" class="amount-input" placeholder="00" min="0" max="99" required value="00">
        </div>
        <div class="amount-labels">
            <span>Dollars</span>
            <span>Cents</span>
        </div>
    </div>

    <div class="rv-form-group">
        <label for="currency">Currency</label>
        <input type="text" name="currency" id="currency" class="currency-input" placeholder="AUD" value="AUD">
    </div>

    <h3>Attachments</h3>
    <div class="rv-form-group">
        <label for="rv-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.</label>

        <input type="file" id="rv-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
        <ul id="rv-file-list"></ul>
    </div>

    <button type="submit">Submit Reimbursement</button>
</form>

