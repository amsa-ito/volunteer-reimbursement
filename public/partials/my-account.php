<h2>Your Reimbursement Claims</h2>
<?php

if ($claims) {

    ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
    <thead>
    <tr>
    <th>Claim ID</th>
    <th>Submit Date</th>
    <th>Status</th>
    <th>Claim Type</th>
    <th>Purpose</th>
    <th>Transaction Details</th>
    </tr>
    </thead>
    <tbody>

    <?php
    foreach ($claims as $claim) {
        $claim_meta = json_decode($claim->meta, true); // Decode the meta JSON
        $purpose = isset($claim_meta['purpose']) ? esc_html($claim_meta['purpose']) : 'N/A';
        $transaction_details = isset($claim_meta['transaction_details']) ? esc_html($claim_meta['transaction_details']) : 'N/A';
        ?>
        <tr>
        <td><?php echo esc_html($claim->id) ?></td>
        <td><?php echo esc_html(date('Y-m-d', strtotime($claim->submit_date))) ?></td>
        <td><?php echo MetaDataFormatter::format_status_colored($claim->status) ?></td>
        <td><?php echo  MetaDataFormatter::format_form_type($claim->form_type) ?></td>
        <td><?php echo  $purpose ?></td>
        <td><?php echo  $transaction_details ?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
    </table>
    <?php
} else {
    ?>
    <p>You have no reimbursement claims.</p>
    <?php
}