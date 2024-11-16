<div class="wrap">
<h1>Volunteer Reimbursements</h1>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th scope="col">Form ID</th>
<th scope="col">Submit Date</th>
<th scope="col">User</th>
<th scope="col">Status</th>
<th scope="col">Form Type</th>
</tr>
</thead>
<tbody>

<?php if (!empty($reimbursements)) : ?>
    <?php foreach ($reimbursements as $reimbursement) : ?>
        <?php
            // Get user information
            $user_info = get_userdata($reimbursement->user_id);
            $user_name = $user_info ? $user_info->display_name : 'Unknown User';
            $user_profile_url = $user_info ? get_edit_user_link($reimbursement->user_id) : '#';
        ?>
        <tr>
            <td><?php echo esc_html($reimbursement->id); ?></td>
            <td><?php echo esc_html(date('Y-m-d', strtotime($reimbursement->submit_date))); ?></td>
            <td><a href="<?php echo esc_url($user_profile_url); ?>" target="_blank"><?php echo esc_html($user_name); ?></a></td>
            <td><?php echo esc_html($reimbursement->status); ?></td>
            <td><?php echo esc_html($reimbursement->form_type); ?></td>
        </tr>
    <?php endforeach; ?>
<?php else : ?>
    <tr><td colspan="5">No reimbursement claims found.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>