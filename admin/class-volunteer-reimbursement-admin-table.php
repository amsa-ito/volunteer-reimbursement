<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


if (!class_exists('VR_Reimbursements_Table')) {
    class VR_Reimbursements_Table extends WP_List_Table {

        private $data;

        public function __construct($data) {
            parent::__construct([
                'singular' => 'reimbursement',
                'plural'   => 'reimbursements',
                'ajax'     => false,
            ]);

            $this->data = $data;
        }

        // Define table columns
        public function get_columns() {
            return [
                'cb'         => '<input type="checkbox" />',
                'id'         => 'Form ID',
                'submit_date'=> 'Submit Date',
                'user'       => 'User',
                'status'     => 'Status',
                'form_type'  => 'Form Type',
            ];
        }

        // Default column renderer
        function column_default($item, $column_name) {
            return $item->$column_name;
        }

        // Checkbox column
        protected function column_cb($item) {
            return sprintf('<input type="checkbox" name="reimbursement_ids[]" value="%s" />', $item->id);
        }

        protected function column_form_type($item){
            switch($item->form_type){
                case "payment_request":
                    return "Payment Request";
                case "reimbursement":
                    return "Reimbursement";
                default:
                    return $item->form_type;
            }
        }

        protected function column_id($item){
            $form_detail_url = add_query_arg(['page' => 'vr_reimbursement_detail', 'form_id' => $item->id], admin_url('admin.php'));

            return esc_html($item->id). '&emsp; <a href="' . esc_url($form_detail_url) . '">edit</a>';
        }

        protected function column_status($item){
            switch($item->status){
                case "pending":
                    return '<span style="color: orange; font-weight: bold;">Pending</span>';
                case "approved":
                    return '<span style="color: blue; font-weight: bold;">Approved</span>';
                case "paid":
                    return '<span style="color: green; font-weight: bold;">Paid</span>';
                default:
                    return $item->status;
            }
        }

        // User column with link to profile
        protected function column_user($item) {
            $user_name = json_decode($item->meta, true)['payee_name'];

            if($item->user_id){
                $user_profile_url = get_edit_user_link($item->user_id);
                return sprintf('<a href="%s" target="_blank">%s</a>', esc_url($user_profile_url), esc_html($user_name));
            }else{
                return $user_name;
            }

           
        }

        // Prepare table items and pagination
        public function prepare_items() {
            $per_page = 10;
            $current_page = $this->get_pagenum();
            $total_items = count($this->data);

            // Filter the data based on the selected form type
            // error_log(print_r($_GET['form_type'], true));
            // error_log(print_r($this->data, true));
            if (!empty($_GET['form_type'])) {
                $this->data = array_filter($this->data, function($item) {
                    return $item->form_type === $_GET['form_type'];
                });
            }
            // error_log(print_r($this->data, true));

            $columns = $this->get_columns();
            $hidden = array();
            $sortable = array();
            $primary  = 'submit_date';

            $this->_column_headers = array($columns, $hidden, $sortable, $primary);

            // Set pagination
            $this->items = array_slice($this->data, (($current_page - 1) * $per_page), $per_page);

            // error_log(print_r($this->items, true));
            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page),
            ]);
        }

        // Bulk actions
        protected function get_bulk_actions() {
            return [
                'delete'           => 'Delete',
                'status_pending'   => 'Change Status to Pending',
                'status_approved'  => 'Change Status to Approved',
                'status_paid'      => 'Change Status to Paid',
                // 'export_aba'       => 'Export ABA',
                // 'export_xero'      => 'Export Xero'
            ];
        }

        protected function extra_tablenav( $which ) {
            if($which=='top'){
                $page = $_REQUEST['page'];
                ?>
                    <div class="alignleft actions bulkactions">
                        <button class="button action" id="export_aba">Export ABA</button>
                        <button class="button action" id="export_xero">Export Xero</button>
                    </div>
                <?php

            }
           
        }



    }
}
