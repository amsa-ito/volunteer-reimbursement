<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

require_once VR_PLUGIN_PATH . "public/class-volunteer-reimbursements-my-account.php";


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
                'id'         => 'Claim ID',
                'submit_date'=> 'Submit Date',
                'user'       => 'User',
                'status'     => 'Status',
                'form_type'  => 'Claim Type',
                'approve_date' => 'Approve Date',
                'paid_date'    => 'Paid Date',
                'meta_purpose' => 'Purpose',
            ];
        }

        // Specify sortable columns
        protected function get_sortable_columns() {
            return [
                'id'          => ['id', true], // Sort by ID, default ascending
                'submit_date' => ['submit_date', false], // Sort by submit date
            ];
        }

            // Column rendering for new columns
        protected function column_approve_date($item) {
            return esc_html($item->approve_date && $item->approve_date !== '0000-00-00 00:00:00' ? $item->approve_date : 'N/A');
        }

        protected function column_paid_date($item) {
            return esc_html($item->paid_date && $item->paid_date !== '0000-00-00 00:00:00' ? $item->paid_date : 'N/A');
        }

        protected function column_meta_purpose($item) {
            $meta = json_decode($item->meta, true);
            return esc_html($meta['purpose'] ?? 'N/A');
        }


        // Default column renderer
        function column_default($item, $column_name) {
            return  isset($item->$column_name) ? $item->$column_name : 'N/A';
        }

        // Checkbox column
        protected function column_cb($item) {
            return sprintf('<input type="checkbox" name="reimbursement_ids[]" value="%s" />', $item->id);
        }

        protected function column_form_type($item){
            return MetaDataFormatter::format_form_type($item->form_type);
        }

        protected function column_id($item){
            $form_detail_url = add_query_arg(['page' => 'vr_reimbursement_detail', 'form_id' => $item->id], admin_url('admin.php'));

            $delete_url = add_query_arg([
                'page' => $_GET['page'], // Keep on the current admin page
                'action' => 'delete',
                'reimbursement_id' => $item->id, // Pass the ID of the claim to delete
                '_wpnonce' => wp_create_nonce('delete_reimbursement_' . $item->id), // Security nonce
            ], admin_url('admin.php'));

            return sprintf(
                '%s &emsp; <a href="%s">view</a> &emsp; <a href="%s" class="delete-link" onclick="return confirm(\'Are you sure you want to delete this claim?\')">delete</a>',
                esc_html($item->id),
                esc_url($form_detail_url),
                esc_url($delete_url)
            );
        }

        protected function column_status($item){
            return MetaDataFormatter::format_status_colored($item->status);
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

            $sortable_columns = $this->get_sortable_columns();
            $orderby = !empty($_GET['orderby']) ? $_GET['orderby'] : 'id';
            $order = !empty($_GET['order']) ? $_GET['order'] : 'asc';

            usort($this->data, function ($a, $b) use ($orderby, $order) {
                $result = strcmp($a->$orderby, $b->$orderby);
                return $order === 'asc' ? $result : -$result;
            });
    

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
        
            $primary  = 'submit_date';

            $this->_column_headers = array($columns, $hidden, $sortable_columns, $primary);

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
            ];
        }

        public function get_hidden_columns() {
            // Retrieve hidden columns from user settings
            $hidden = get_user_option('manage_' . $this->screen->id . '_columnshidden');
            return $hidden ? $hidden : [];
        }

    
        public function save_hidden_columns() {
            if (isset($_POST['hidden'])) {
                update_user_option(get_current_user_id(), 'manage_' . $this->screen->id . '_columnshidden', $_POST['hidden']);
            }
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

        public function render_screen_options() {
            add_filter('screen_settings', function ($settings, $screen) {
                if ($screen->id !== $this->screen->id) {
                    return $settings;
                }
    
                ob_start();
                $columns = $this->get_columns();
                $hidden = $this->get_hidden_columns();
                foreach ($columns as $column => $title) {
                    echo '<label>';
                    echo '<input type="checkbox" name="hidden[]" value="' . esc_attr($column) . '" ' . checked(!in_array($column, $hidden), true, false) . ' />';
                    echo esc_html($title);
                    echo '</label><br />';
                }
                $settings .= ob_get_clean();
                return $settings;
            }, 10, 2);
        }

    }
}
