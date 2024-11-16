<?php

/**
 * Class MetaDataFormatter
 *
 * Handles the formatting of custom post meta data, such as status and form types.
 */
class MetaDataFormatter {

    /**
     * Format the status meta data.
     *
     * @param string $status The status string to format.
     * @return string The formatted status.
     */
    static public function format_status( $status ) {
        // Define a mapping of raw statuses to formatted statuses.
        $status_mapping = [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'paid'      => 'Paid',
        ];

        // Return the formatted status or the original status if not found in the mapping.
        return isset( $status_mapping[ $status ] ) ? $status_mapping[ $status ] : ucfirst( $status );
    }

    static public function format_status_colored($status){
        return '<span class="vr_form_status_'.$status.'">'.MetaDataFormatter::format_status($status).'</span>';
    }

    /**
     * Format the form type meta data.
     *
     * @param string $form_type The form type string to format.
     * @return string The formatted form type.
     */
    static public function format_form_type( $form_type ) {
        // Define a mapping of raw form types to formatted form types.
        $form_type_mapping = [
            'reimbursement'    => 'Reimbursement',
            'payment_request'  => 'Payment Request',
        ];

        // Return the formatted form type or the original form type if not found in the mapping.
        return isset( $form_type_mapping[ $form_type ] ) ? $form_type_mapping[ $form_type ] : ucwords( str_replace('_', ' ', $form_type) );
    }
}