<?php 
if($reimbursement->user_id>0){
    $user_name = get_userdata($reimbursement->user_id)->display_name;
    $user_profile_url = get_edit_user_link($reimbursement->user_id);
    $user_name_display = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($user_profile_url), esc_html($user_name));
}else{
    $user_name_display = json_decode($item->meta, true)['payee_name'];
}

?>

<div class="wrap">
<h1>Reimbursement Form Details</h1>
<form method="post" id="vr-reimbursement-form" class="rv-claim-form">
<input type="hidden" name="action" value="save_admin_claim_form">
<input type="hidden" name="form_id" value=" <?php echo $form_id ?>">

<div class="form-layout">
    <div class="form-panel-left">
    <?php
    $content = '';
    $content = apply_filters('vr_display_admin_'.$reimbursement->form_type, $content, $reimbursement);
    echo $content;
    ?>
    </div>
    <div class="form-panel-right">
        <fieldset>
        <legend>Meta Information</legend>
        <table class='meta-table'><thead>
        <tr>
            <td>User</td>
            <td><?php echo $user_name_display  ?></td>
        </tr></thead>
        <tbody>
        <tr>
            <td>Submit Date</td>
            <td><?php echo esc_html(date('Y-m-d', strtotime($reimbursement->submit_date))) ?></td>
        </tr>
        </tbody>
        </table>

        <div class="rv-form-group">
            <label for="status">Status</label>
            <div>
            <select name="status">
            <option value="pending"<?php echo selected($reimbursement->status, 'pending', false) ?>>Pending</option>
            <option value="approved"<?php echo selected($reimbursement->status, 'approved', false) ?>>Approved</option>
            <option value="paid"<?php echo selected($reimbursement->status, 'paid', false) ?>>Paid</option>
            </select>
            </div>
        </div>
        </fieldset>
        <!-- Comments -->
        <fieldset>
            <legend>Admin Comments</legend>
            <div class="rv-form-group">
                <label for="comments">Comments</label>
                <textarea name="comments" id="comments"><?php echo esc_textarea(json_decode($reimbursement->meta, true)['comments'] ?? ''); ?></textarea>
            </div>
        </fieldset>

        <p class="submit"><input type="submit" class="button-primary" value="Save Changes"></p>
        <div id="form-response"></div>
    </div>
</div>


</form>


