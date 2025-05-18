<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

    class Warehouse_model extends CI_Model {

        private $table = 'warehouse';
    
        // Get all warehouses
        public function get_all() {
            return $this->db->order_by('id', 'DESC')->get($this->table)->result();
        }
    
        // Get a single warehouse by ID
        public function get_by_id($id) {
            return $this->db->where('id', $id)->get($this->table)->row();
        }
    
        // Insert new warehouse
        public function insert($data) {
            return $this->db->insert($this->table, $data);
        }
    
        // Update existing warehouse
        public function update($id, $data) {
            return $this->db->where('id', $id)->update($this->table, $data);
        }
    
        // Delete a warehouse
        public function delete($id) {
            return $this->db->where('id', $id)->delete($this->table);
        }

        public function check_duplicate_code($code) {
            return $this->db->where('warehouse_code', $code)
                            ->get($this->table)
                            ->num_rows() > 0;
        }
        
        public function check_duplicate_name($name) {
            return $this->db->where('name', $name)
                            ->get($this->table)
                            ->num_rows() > 0;
        }

        public function get_datatables($postData)
        {
            $this->db->select('
                w.id AS id, 
                w.warehouse_code, 
                w.name, 
                w.city, 
                w.phone, 
                w.email,
                w.location,
                w.status,
                e.first_name AS contact_first_name,
                e.last_name AS contact_last_name,
                e.designation
            ');
            
            $this->_get_datatables_query($postData);

            if ($postData['length'] != -1) {
                $this->db->limit($postData['length'], $postData['start']);
            }

            $query = $this->db->get();
            log_message('debug', $this->db->last_query());
            return $query->result();
        }

        private function _get_datatables_query($postData)
        {
            // Define the searchable columns
            $searchableColumns = [
                'w.warehouse_code',
                'w.name', 
                'w.city', 
                'w.phone', 
                'w.email',
                'w.location',
                'e.first_name',
                'e.last_name',
                'e.designation'
            ];

            // Main table and join
            $this->db->from('warehouse w');
            $this->db->join('employee_history e', 'e.id = w.contact_person', 'left');

            // Apply search filter
            if (!empty($postData['search']['value'])) {
                $this->db->group_start();
                foreach ($searchableColumns as $column) {
                    $this->db->or_like($column, $postData['search']['value']);
                }
                $this->db->group_end();
            }

            // Alias mapping for DataTables columns
            $columnMapping = [
                'warehouse_code' => 'w.warehouse_code',
                'name' => 'w.name',
                'city' => 'w.city',
                'phone' => 'w.phone',
                'email' => 'w.email',
                'location' => 'w.location',
                'contact_person_name' => 'e.first_name',
                'designation' => 'e.designation',
                'status' => 'w.status'
            ];

            // Apply sorting
            if (!empty($postData['order'])) {
                $columnName = $postData['columns'][$postData['order'][0]['column']]['data'];
                
                if (array_key_exists($columnName, $columnMapping)) {
                    $this->db->order_by(
                        $columnMapping[$columnName], 
                        $postData['order'][0]['dir']
                    );
                }
            } else {
                $this->db->order_by('w.warehouse_code', 'ASC');
            }
        }

        public function count_all()
        {
            return $this->db->count_all('warehouse');
        }

        public function count_filtered($postData)
        {
            $this->db->select('w.id');
            $this->_get_datatables_query($postData);
            $query = $this->db->get();
            return $query->num_rows();
        }

        public function get_batch_datatables($postData)
        {
            $this->db->select('
                bm.id AS id,
                bm.batch_id AS batch_id,
                bm.product_id AS product_id,
                p.product_name AS product_name,
                bm.warehouse_id AS warehouse_id,
                w.name AS warehouse_name,
                bm.expiry_date AS expiry_date,
                bm.total_quantity AS total_quantity,
                bm.available_quantity AS available_quantity
            ');
            $this->db->from('batch_master bm');
            $this->db->join('product_information p', 'p.product_id = bm.product_id', 'left');
            $this->db->join('warehouse w', 'w.id = bm.warehouse_id', 'left');

            // Search filter
            if (!empty($postData['search']['value'])) {
                $this->db->group_start()
                    ->like('bm.batch_id', $postData['search']['value'])
                    ->or_like('p.product_name', $postData['search']['value'])
                    ->or_like('w.name', $postData['search']['value'])
                ->group_end();
            }

            // Fix: Remove reference to "sl" column
            if (isset($postData['order'])) {
                $columnIndex = $postData['order'][0]['column'];
                $columnName = $postData['columns'][$columnIndex]['data'];

                // Do not sort by `sl`, it's not a database column
                if ($columnName !== 'sl') {
                    $columnSortOrder = $postData['order'][0]['dir'];
                    $this->db->order_by($columnName, $columnSortOrder);
                }
            } else {
                $this->db->order_by('bm.id', 'asc');
            }

            // Limit
            if ($postData['length'] != -1) {
                $this->db->limit($postData['length'], $postData['start']);
            }

            // Execute query
            $query = $this->db->get();
            log_message('debug', $this->db->last_query());

            return $query->result_array();
        }

        public function _get_batch_query($postData)
        {
            $searchValue = $postData['search']['value']; // Search value

            // Select statement
            $this->db->select(" 
                bm.id AS id,
                bm.batch_id AS batch_id,
                p.product_name AS product_name,
                w.name AS warehouse_name,
                bm.expiry_date AS expiry_date,
                bm.total_quantity AS total_quantity,
                bm.available_quantity AS available_quantity
            ");
            $this->db->from('batch_master bm');
            $this->db->join('product_information p', 'p.product_id = bm.product_id', 'left');
            $this->db->join('warehouse w', 'w.id = bm.warehouse_id', 'left');

            // Search filter
            if (!empty($searchValue)) {
                $this->db->group_start()
                    ->like('bm.batch_id', $searchValue)
                    ->or_like('p.product_name', $searchValue)
                    ->or_like('w.name', $searchValue)  // Changed to `w.name`
                ->group_end();
            }
        }

        public function count_all_batches()
        {
            return $this->db->count_all('batch_master');
        }

        public function count_filtered_batches($postData)
        {
            $this->_get_batch_query($postData);
            return $this->db->get()->num_rows();
        }

        public function get_batch_by_id($id)
        {
            $this->db->select('bm.*, w.name as warehouse_name');
            $this->db->from('batch_master bm');
            $this->db->join('warehouse w', 'w.id = bm.warehouse_id', 'left');
            $this->db->where('bm.id', $id);

            $query = $this->db->get();
            return $query->row();
        }   

        public function get_all_warehouses()
        {
            $this->db->select('id, name');
            $this->db->from('warehouse');
            $query = $this->db->get();
            return $query->result();
        }


        public function update_batch($id, $data)
        {
            $this->db->where('id', $id);
            return $this->db->update('batch_master', $data);
        }

        // ---------

        // Add these methods to your Warehouse_model class

        public function get_batch_details($batch_id) {
            return $this->db->select('bm.*, w.name as warehouse_name, p.product_name, p.product_id')
                           ->from('batch_master bm')
                           ->join('warehouse w', 'w.id = bm.warehouse_id', 'left') // Ensure left join
                           ->join('product_information p', 'p.product_id = bm.product_id', 'left')
                           ->where('bm.batch_id', $batch_id)
                           ->get()
                           ->row();
        }

        public function get_movement_types_by_category($category) {
            return $this->db->where('category', $category)
                        ->get('movement_type')
                        ->result();
        }

        public function process_stock_movement($data) {
            // Start transaction
            $this->db->trans_start();
            
            // 1. Insert into stock_movement table
            $this->db->insert('stock_movement', $data['movement_data']);
            
            // 2. Update batch_master (reduce from source)
            if ($data['movement_data']['movement_type'] != 'IN') {
                $this->db->set('available_quantity', 'available_quantity - ' . $data['movement_data']['quantity'], FALSE)
                        ->where('batch_id', $data['movement_data']['batch_id'])
                        ->update('batch_master');
            }
            
            // 3. If transfer, update destination batch (or create new)
            if ($data['movement_data']['movement_type'] == 'TRANSFER') {
                $this->db->set('available_quantity', 'available_quantity + ' . $data['movement_data']['quantity'], FALSE)
                        ->where('batch_id', $data['movement_data']['batch_id'])
                        ->where('warehouse_id', $data['movement_data']['destination_warehouse_id'])
                        ->update('batch_master');
                        
                // If no rows affected, create new batch record
                if ($this->db->affected_rows() == 0) {
                    $source_batch = $this->get_batch_details($data['movement_data']['batch_id']);
                    $new_batch = [
                        'batch_id' => $source_batch->batch_id,
                        'product_id' => $source_batch->product_id,
                        'warehouse_id' => $data['movement_data']['destination_warehouse_id'],
                        'total_quantity' => $data['movement_data']['quantity'],
                        'available_quantity' => $data['movement_data']['quantity'],
                        'manufacture_date' => $source_batch->manufacture_date,
                        'expiry_date' => $source_batch->expiry_date
                    ];
                    $this->db->insert('batch_master', $new_batch);
                }
            }
            
            // Complete transaction
            $this->db->trans_complete();
            
            return $this->db->trans_status();
        }

        public function get_all_batches()
        {
            $this->db->select('bm.id, bm.batch_id, p.product_name, w.name as warehouse_name');
            $this->db->from('batch_master bm');
            $this->db->join('product_information p', 'p.product_id = bm.product_id', 'left');
            $this->db->join('warehouse w', 'w.id = bm.warehouse_id', 'left');
            $query = $this->db->get();
            return $query->result();
        }

        public function get_all_warehouses_by_status() {
            return $this->db->select('id, name')
                        ->from('warehouse')
                        ->where('status', 1)
                        ->get()
                        ->result();
        }

        // warehouse history

       
 
        
        public function get_stock_movement_datatables($postData) {
            $this->db->select('
                sm.id,
                sm.batch_id,
                p.product_name,
                sm.movement_type,
                sm.quantity,
                source_w.name as source_warehouse_name,
                dest_w.name as destination_warehouse_name,
                sm.reference_no,
                CONCAT(u.first_name, " ", u.last_name) as created_by_name,
                sm.movement_date,
                sm.remarks
            ');
            $this->db->from('stock_movement sm');
            $this->db->join('product_information p', 'p.product_id = sm.product_id', 'left');
            $this->db->join('warehouse source_w', 'source_w.id = sm.source_warehouse_id', 'left');
            $this->db->join('warehouse dest_w', 'dest_w.id = sm.destination_warehouse_id', 'left');
            $this->db->join('users u', 'u.id = sm.created_by', 'left');
            
            $this->_get_stock_movement_query($postData);
        
            if ($postData['length'] != -1) {
                $this->db->limit($postData['length'], $postData['start']);
            }
        
            $query = $this->db->get();
            return $query->result();
        }
        
        private function _get_stock_movement_query($postData) {
            $searchableColumns = [
                'sm.batch_id',
                'p.product_name',
                'sm.movement_type',
                'source_w.name',
                'dest_w.name',
                'sm.reference_no',
                'CONCAT(u.first_name, " ", u.last_name)',
                'sm.remarks'
            ];
        
            // Apply search filter
            if (!empty($postData['search']['value'])) {
                $this->db->group_start();
                foreach ($searchableColumns as $column) {
                    $this->db->or_like($column, $postData['search']['value']);
                }
                $this->db->group_end();
            }
        
            // Column mapping for sorting
            $columnMapping = [
                'batch_id' => 'sm.batch_id',
                'product_name' => 'p.product_name',
                'movement_type' => 'sm.movement_type',
                'quantity' => 'sm.quantity',
                'source_warehouse' => 'source_w.name',
                'destination_warehouse' => 'dest_w.name',
                'reference_no' => 'sm.reference_no',
                'created_by' => 'u.first_name',
                'movement_date' => 'sm.movement_date'
            ];
        
            // Apply sorting
            if (!empty($postData['order'])) {
                $columnIndex = $postData['order'][0]['column'];
                $columnName = $postData['columns'][$columnIndex]['data'];
                
                if (array_key_exists($columnName, $columnMapping)) {
                    $this->db->order_by($columnMapping[$columnName], $postData['order'][0]['dir']);
                }
            } else {
                $this->db->order_by('sm.movement_date', 'DESC');
            }
        }
        
        public function count_all_stock_movement() {
            return $this->db->count_all('stock_movement');
        }
        
        public function count_filtered_stock_movement($postData) {
            // Add the FROM and JOIN clauses before applying filters
            $this->db->select('
                sm.id,
                sm.batch_id,
                p.product_name,
                sm.movement_type,
                sm.quantity,
                source_w.name as source_warehouse_name,
                dest_w.name as destination_warehouse_name,
                sm.reference_no,
                CONCAT(u.first_name, " ", u.last_name) as created_by_name,
                sm.movement_date,
                sm.remarks
            ');
            $this->db->from('stock_movement sm');
            $this->db->join('product_information p', 'p.product_id = sm.product_id', 'left');
            $this->db->join('warehouse source_w', 'source_w.id = sm.source_warehouse_id', 'left');
            $this->db->join('warehouse dest_w', 'dest_w.id = sm.destination_warehouse_id', 'left');
            $this->db->join('users u', 'u.id = sm.created_by', 'left');
        
            $this->_get_stock_movement_query($postData);
        
            // Perform the query and return the number of rows
            $query = $this->db->get();
            log_message('debug', 'Filtered Stock Movement Query: ' . $this->db->last_query());
            return $query->num_rows();
        }

        public function batch_id_exists($batch_id) {
            log_message('debug', 'Checking batch existence for ID: ' . $batch_id);
        
            $this->db->select('id');
            $this->db->from('batch_master');
            $this->db->where('batch_id', $batch_id);
            $query = $this->db->get();
        
            $exists = $query->num_rows() > 0;
        
            log_message('debug', 'Batch existence: ' . ($exists ? 'Found' : 'Not Found'));
            return $exists;
        }

    }