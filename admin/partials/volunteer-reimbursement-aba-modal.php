<div id="export-aba-modal">
    <h2>Export ABA Details</h2>
    <div class="vr-form-row">
        <div class="form-group">
            <label for="bsb">BSB:</label>
            <input type="text" id="bsb" name="description[bsb]" required>
        </div>
        <div class="form-group">
            <label for="account_number">Account Number:</label>
            <input type="text" id="account_number" name="description[account_number]" required>
        </div>
    </div>
    <div class="vr-form-row">
        <div class="form-group">
            <label for="bank_name">Bank Name:</label>
            <input type="text" id="bank_name" name="description[bank_name]" required value=<?php echo esc_attr(get_option('vr_default_bank_name', ''));?>>
        </div>
        <div class="form-group">
            <label for="user_name">User Name:</label>
            <input type="text" id="user_name" name="description[user_name]" required value=<?php echo esc_attr(wp_get_current_user()->display_name);?>>
        </div>
    </div>
    <div class="vr-form-row">
        <div class="form-group">
            <label for="remitter">Remitter:</label>
            <input type="text" id="remitter" name="description[remitter]" required>
        </div>
        <div class="form-group">
            <label for="entry_id">Entry ID:</label>
            <input type="text" id="entry_id" name="description[entry_id]" required>
        </div>
    </div>
    <div class="vr-form-row">
        <div class="form-group">
            <label for="description">Description:</label>
            <input type="text" id="description" name="description[description]" required>
        </div>
    </div>
    <div class="form-actions">
        <button class="button action" id="submit_aba_export">Export to ABA</button>
    </div>
    <div id="form-response"></div>
</div>