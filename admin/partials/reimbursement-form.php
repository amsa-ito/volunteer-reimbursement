<?php
$form_data = json_decode($claim->meta, true) ?: [];


?>

<!-- Payee Details -->
<fieldset>
    <legend>Payee Details</legend>
    <div class="vr-form-row">
        <div class="vr-form-group">
            <label for="payee_name">Payee Name</label>
            <input type="text" name="payee_name" id="payee_name" value="<?php echo esc_attr($form_data['payee_name'] ?? ''); ?>">
        </div>
        <div class="vr-form-group">
            <label for="payee_email">Email</label>
            <input type="email" name="payee_email" id="payee_email" value="<?php echo esc_attr($form_data['payee_email'] ?? ''); ?>">
        </div>

    </div>

    <div class="vr-form-row">

        <div class="vr-form-group">
            <label for="payee_phone_number">Phone Number</label>
            <input type="text" name="payee_phone_number" id="payee_phone_number" value="<?php echo esc_attr($form_data['payee_phone_number'] ?? ''); ?>">
        </div>
        <div class="vr-form-group">
            <?php 
                $payee_committee = $form_data['payee_committee']; 
                require_once(VR_PLUGIN_PATH . 'admin/partials/payee-committee-field.php');

             ?>
        </div>

    </div>
</fieldset>

<!-- Bank Details -->
<fieldset>
    <legend>Bank Details</legend>
    <div class="vr-form-row">
        <div class="vr-form-group">
            <label for="payee_bank_name">Bank Name</label>
            <input type="text" name="payee_bank_name" id="payee_bank_name" value="<?php echo esc_attr($form_data['payee_bank_name'] ?? ''); ?>">
        </div>
        <div class="vr-form-group">
            <label for="payee_bsb">BSB</label>
            <input type="text" name="payee_bsb" id="payee_bsb" value="<?php echo esc_attr($form_data['payee_bsb'] ?? ''); ?>">
        </div>
    </div>

    <div class="vr-form-row">
        <div class="vr-form-group">
            <label for="payee_account_number">Bank Account Number</label>
            <input type="text" name="payee_account_number" id="payee_account_number" value="<?php echo esc_attr($form_data['payee_account_number'] ?? ''); ?>">
        </div>

        <div class="vr-form-group">
                <label for="budget_reference">Budget reference</label>
                <input type="text" name="budget_reference" id="budget_reference" value="<?php echo esc_attr($form_data['budget_reference'] ?? ''); ?>" data-helper="Please ask your Treasurer to tell you where this transaction has been accounted for.">
        </div>

        <div class="vr-form-group">
            <label for="additional_email">Optional additional email for receipt</label>
            <input type="email" name="additional_email" id="additional_email" value="<?php echo esc_attr($form_data['additional_email'] ?? ''); ?>" data-helper="A copy of the claim will be CC'ed to this email address once processed">
        </div>
    </div>
</fieldset>

<!-- Transaction Details -->
<fieldset>
    <legend>Reimbursement Details</legend>
    <div class="vr-form-row">
        <div class="vr-form-group">
            <label for="purpose">Activity/event/project</label>
            <input type="text" name="purpose" id="purpose" value="<?php echo esc_attr($form_data['purpose'] ?? ''); ?>">
        </div>

    </div>

    <div class="vr-form-group">
            <label for="transaction_details">Transaction Details</label>
            <textarea name="transaction_details" id="transaction_details"><?php echo esc_textarea($form_data['transaction_details'] ?? ''); ?></textarea>
    </div>

    <div class="amount-claimed-container vr-form-group">
            <label for="dollars">Total amount claimed <span class="required">*</span></label>
            <div class="amount-inputs">
                <span>$</span>
                <input type="number" name="dollars" id="dollars" class="amount-input" placeholder="0" min="0" required value="<?php echo esc_attr($form_data['amount']['dollars'] ?? '0'); ?>">
                <span class="separator">.</span>
                <input type="number" name="cents" id="cents" class="amount-input" placeholder="00" min="0" max="99" required value="<?php echo esc_attr($form_data['amount']['cents'] ?? '0'); ?>" maxlength="2">
            </div>
            <div class="amount-labels">
                <span>Dollars</span>
                <span>Cents</span>
            </div>
        </div>

</fieldset>

<!-- Attachments -->
<fieldset>
    <legend>Attachments</legend>
    <div class="vr-form-group">
        <?php 
            $attachments = $form_data['attachments']; 
            require_once(VR_PLUGIN_PATH . 'admin/partials/attachments-field.php');
         ?>
    </div>

</fieldset>




