<?php
 $form_data = json_decode($claim->meta, true) ?: [];
?>

<fieldset>
    <legend>Volunteer Details</legend>

    <div class="form-row">

        <div class="rv-form-group">
            <label for="payee_name">Name</label>
            <input type="text" name="payee_name" id="payee_name" value="<?php echo esc_attr($form_data['payee_name'] ?? ''); ?>" required>
        </div>

        <div class="rv-form-group">
            <label for="payee_email">Email</label>
            <input type="email" name="payee_email" id="payee_email" value="<?php echo esc_attr($form_data['payee_email'] ?? ''); ?>" required>
        </div>
    </div>
    
    <div class="form-row">
        <div class="rv-form-group">
            <label for="payee_phone_number">Phone Number</label>
            <input type="text" name="payee_phone_number" id="payee_phone_number" value="<?php echo esc_attr($form_data['payee_phone_number'] ?? ''); ?>" required>
        </div>

        <div class="rv-form-group">
            <?php 
                $payee_committee = $form_data['payee_committee']; 
                require_once(VR_PLUGIN_PATH . 'admin/partials/payee-committee-field.php');

             ?>
        </div>

    </div>

</fieldset>

<fieldset>
    <legend>Supplier Details</legend>
    <div class="form-row">

        <div class="rv-form-group">
            <label for="business_name">Business Name</label>
            <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($form_data['business_name'] ?? ''); ?>" required>
        </div>

        <div class="rv-form-group">
            <label for="contact_name">Contact Name</label>
            <input type="text" name="contact_name" id="contact_name" value="<?php echo esc_attr($form_data['contact_name'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="rv-form-group">
            <label for="supplier_email">Supplier Email</label>
            <input type="email" name="supplier_email" id="supplier_email" value="<?php echo esc_attr($form_data['supplier_email'] ?? ''); ?>" required>
        </div>

        <div class="rv-form-group">
            <label for="supplier_phone">Supplier Phone</label>
            <input type="text" name="supplier_phone" id="supplier_phone" value="<?php echo esc_attr($form_data['supplier_phone'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="rv-form-group">
            <label for="supplier_bank_name">Bank Account Name</label>
            <input type="text" name="supplier_bank_name" id="supplier_bank_name" value="<?php echo esc_attr($form_data['supplier_bank_name'] ?? ''); ?>">
        </div>

        <div class="rv-form-group">
            <label for="supplier_bsb">BSB Number</label>
            <input type="text" name="supplier_bsb" id="supplier_bsb" value="<?php echo esc_attr($form_data['supplier_bsb'] ?? ''); ?>">
        </div>

        <div class="rv-form-group">
            <label for="supplier_account_number">Bank Account Number</label>
            <input type="text" name="supplier_account_number" id="supplier_account_number" value="<?php echo esc_attr($form_data['supplier_account_number'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="rv-form-group">
            <label for="supplier_bpay">BPAY Biller Code</label>
            <input type="text" name="supplier_bpay" id="supplier_bpay" value="<?php echo esc_attr($form_data['supplier_bpay'] ?? ''); ?>">
        </div>

        <div class="rv-form-group">
            <label for="supplier_bpay_reference">BPAY Reference Number</label>
            <input type="text" name="supplier_bpay_reference" id="supplier_bpay_reference" value="<?php echo esc_attr($form_data['supplier_bpay_reference'] ?? ''); ?>">
        </div>
    </div>
    
</fieldset>

<fieldset>
    <legend>Transaction Details</legend>
    <div class="form-row">
        <div class="rv-form-group">
            <label for="purpose">Activity/event/project</label>
            <input type="text" name="purpose" id="purpose" value="<?php echo esc_attr($form_data['purpose'] ?? ''); ?>" required>
        </div>

        <div class="rv-form-group">
            <label for="due_date">Due Date for this payment</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo esc_attr($form_data['due_date'] ?? ''); ?>" required>
        </div>
    </div>

    <div class="rv-form-group">
        <label for="transaction_details">Transaction Details</label>
        <textarea name="transaction_details" id="transaction_details"><?php echo esc_textarea($form_data['transaction_details'] ?? ''); ?></textarea>
    </div>

    <div class="form-row">
        <div class="amount-claimed-container rv-form-group">
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

        <div class="rv-form-group">
            <label for="currency">Currency</label>
            <input type="text" name="currency" id="currency" value="<?php echo esc_attr($form_data['currency'] ?? 'AUD'); ?>">
        </div>
    </div>

</fieldset>

<fieldset>
    <legend>Attachments</legend>
    <div class="rv-form-group">
        <?php 
                $attachments = $form_data['payee_committee']; 
                require_once(VR_PLUGIN_PATH . 'admin/partials/attachments-field.php');
         ?>
    </div>

</fieldset>



