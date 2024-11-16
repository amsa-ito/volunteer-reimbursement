<?php
$current_user = wp_get_current_user();
?>

<form id="reimbursement-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="submit_request_form">
    <input type="hidden" name="form_type" value="reimbursement">

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

    <div class="rv-form-group">
        <label for="payee_bank_name">Bank name<span class="required">*</span></label>
        <input type="text" name="payee_bank_name" id="payee_bank_name" required>
    </div>

    <div class="rv-form-group">
        <label for="payee_bsb">BSB<span class="required">*</span></label>
        <input type="text" name="payee_bsb" id="payee_bsb" required pattern="\d{6}" data-helper="Just the numerals, no hyphen.">
    </div>

    <div class="rv-form-group">
        <label for="payee_account_number">Bank account Number<span class="required">*</span></label>
        <input type="text" name="payee_account_number" id="payee_account_number" required pattern="\d{5,9}">
    </div>

    <div class="rv-form-group">
        <label for="budget_reference">Budget reference</label>
        <input type="text" name="budget_reference" id="budget_reference" data-helper="Please ask your Treasurer to tell you where this transaction has been accounted for.">
    </div>

    <div class="rv-form-group">
        <label for="additional_email">Optional additional email for receipt</label>
        <input type="email" name="additional_email" id="additional_email" data-helper="A copy of the reimbursement will be CC'ed to this email address once processed">
    </div>


    <h3>Reimbursement details</h3>

    <div class="rv-form-group">
        <label for="purpose">Activity/event/project<span class="required">*</span></label>
        <input type="text" name="purpose" id="purpose" required data-helper="In 10 words or less; this will be used in the email subject.">
    </div>

    <div class="rv-form-group">
        <label for="transaction_details">Transaction details<span class="required">*</span></label>
        <textarea name="transaction_details" id="transaction_details" required data-helper="Please describe each transaction that makes up this request. For each transaction this should include:\n- what you bought\n- who you bought it from\n- how much it cost"></textarea>
    </div>


    <div class="amount-claimed-container rv-form-group">
        <label for="dollars">Total amount claimed <span class="required">*</span></label>
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
    
    <h3>Attachments</h3>

    <div class="rv-form-group">
        <label for="rv-multiple-file-input">Please attach legible scans or photos of each original invoice and receipt.<span class="required">*</span></label>

        <input type="file" id="rv-multiple-file-input" name="attachments[]" accept="image/*,.pdf" multiple>
        <ul id="rv-file-list"></ul>
    </div>

    <button type="submit">Submit Reimbursement</button>
</form>

