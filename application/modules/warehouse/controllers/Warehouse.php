<?php
defined('BASEPATH') OR exit('No direct script access allowed');

 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

    class Warehouse extends MX_Controller {

        public function __construct() {
            parent::__construct();
        
            $timezone = $this->db->select('timezone')->from('web_setting')->get()->row();
            date_default_timezone_set($timezone->timezone);
        
            // Load models
            $this->load->model(array(
                'warehouse_model',
                'invoice/Invoice_model',
                'purchase/Purchase_model',
                'product/Product_model'
            ));
        
            // ✅ Load the template library
            // $this->load->library('template');
        
            // Check login
            if (!$this->session->userdata('isLogIn')) {
                redirect('login');
            }
        }
    
        public function index() {
            $data['title'] = display('manage_warehouse');
            $data['warehouses'] = $this->warehouse_model->get_all();
        
            $data['module'] = "warehouse";
            $data['page']   = "manage_warehouse"; // Corresponds to: application/modules/warehouse/views/manage_warehouse.php
        
            echo modules::run('template/layout', $data);
        }
    
        public function add() {
            $data['title'] = display('add_warehouse');
            $data['warehouse'] = [];
            $data['employees'] = $this->db->select('id, first_name, last_name, designation')
                                          ->from('employee_history')
                                          ->get()
                                          ->result();
        
            $data['module'] = "warehouse";
            $data['page'] = "add_warehouse_form";
        
            echo modules::run('template/layout', $data);
        }
    
        // Manage Batch - Table listing
        public function manage_batch() {
            $data['title'] = display('manage_batch');
            $data['module'] = "warehouse";
            $data['page'] = "manage_batch"; // View file
            echo modules::run('template/layout', $data);
        }

        // Stock Movement - Form input
        // public function stock_movement() {
        //     $data['title'] = display('stock_movement');
        //     $data['module'] = "warehouse";
        //     $data['page'] = "stock_movement"; // View file
        //     echo modules::run('template/layout', $data);
        // }

        public function stock_movement() {
            $data['title'] = display('stock_movement');
            $data['batches'] = $this->warehouse_model->get_all_batches();
            $data['warehouses'] = $this->warehouse_model->get_all_warehouses_by_status();
            $data['module'] = "warehouse";
            $data['page'] = "stock_movement";
            
            echo modules::run('template/layout', $data);
        }

        public function check_duplicate_code() {
            $code = $this->input->post('warehouse_code');
            $exists = $this->warehouse_model->check_duplicate_code($code);
        
            echo $exists ? 'exists' : 'ok';
        }
        
        public function check_duplicate_name() {
            $name = $this->input->post('name');
            $exists = $this->warehouse_model->check_duplicate_name($name);
        
            echo $exists ? 'exists' : 'ok';
        }

        public function insert() {
            $this->load->library('form_validation');
        
            $this->form_validation->set_rules('warehouse_code', 'Warehouse Code', 'required|trim');
            $this->form_validation->set_rules('name', 'Warehouse Name', 'required|trim');
        
            if ($this->form_validation->run() === FALSE) {
                $this->session->set_flashdata('exception', validation_errors());
                redirect('warehouse/warehouse/add');
            }
        
            $warehouse_code = $this->input->post('warehouse_code', TRUE);
            $name = $this->input->post('name', TRUE);
        
            // Check for duplicate warehouse_code or name
            $exists = $this->db->where('warehouse_code', $warehouse_code)
                               ->or_where('name', $name)
                               ->get('warehouse')
                               ->row();
        
            if ($exists) {
                $this->session->set_flashdata('exception', 'Duplicate warehouse code or name detected.');
                redirect('warehouse/warehouse/add');
            }
        
            $data = array(
                'warehouse_code'   => $warehouse_code,
                'name'             => $name,
                'contact_person'   => $this->input->post('contact_person', TRUE),
                'phone'            => $this->input->post('phone', TRUE),
                'email'            => $this->input->post('email', TRUE),
                'address_line1'    => $this->input->post('address_line1', TRUE),
                'city'             => $this->input->post('city', TRUE),
                'country'          => $this->input->post('country', TRUE),
                'location'         => $this->input->post('location', TRUE),
                'description'      => $this->input->post('description', TRUE),
                'status'           => $this->input->post('status', TRUE),
            );
        
            $this->warehouse_model->insert($data);
            $this->session->set_flashdata('message', 'Warehouse added successfully.');
            redirect('warehouse/warehouse');
        }

        public function CheckWarehouseList()
        {
            $this->load->model('warehouse_model');
            $postData = $this->input->post();
            $warehouses = $this->warehouse_model->get_datatables($postData);

            $data = [];
            $sl = $postData['start'] + 1;

            foreach ($warehouses as $w) {
                $row = [];
                $row['sl'] = $sl++;
                $row['warehouse_code'] = html_escape($w->warehouse_code);
                $row['name'] = html_escape($w->name);

                // 📝 Handle the contact person gracefully
                $contactName = '';
                if (!empty($w->contact_first_name) && !empty($w->contact_last_name)) {
                    $contactName = html_escape($w->contact_first_name . ' ' . $w->contact_last_name);
                    if (!empty($w->designation)) {
                        $contactName .= ' (' . html_escape($w->designation) . ')';
                    }
                } else {
                    $contactName = '<span class="text-muted">N/A</span>';
                }
                $row['contact_person_name'] = $contactName;

                // Other fields
                $row['city'] = html_escape($w->city);
                $row['phone'] = html_escape($w->phone);
                $row['email'] = html_escape($w->email);
                $row['location'] = html_escape($w->location);
                $row['status'] = $w->status ? 'Active' : 'Inactive';

                // Action buttons
                $buttons = '';
                if ($this->permission1->method('warehouse', 'update')->access()) {
                    $buttons .= '<a href="' . base_url("warehouse/warehouse/edit/" . $w->id) . '" class="btn btn-sm btn-primary"><i class="ti-pencil"></i></a> ';
                }
                if ($this->permission1->method('warehouse', 'delete')->access()) {
                    $buttons .= '<a href="' . base_url("warehouse/warehouse/delete/" . $w->id) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="ti-trash"></i></a>';
                }
                $row['action'] = $buttons;

                $data[] = $row;
            }

            // Count the total number of records
            $totalRecords = $this->warehouse_model->count_all();
            $totalFiltered = $this->warehouse_model->count_filtered($postData);

            // Return the response
            echo json_encode([
                "draw" => intval($postData['draw']),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ]);
        }

        public function CheckBatchList()
        {
            $this->load->model('warehouse_model');
            $postData = $this->input->post();
            $batches = $this->warehouse_model->get_batch_datatables($postData);

            $data = [];
            $sl = $postData['start'] + 1;

            foreach ($batches as $b) {
                $row = [];
                $row['sl'] = $sl++;
                $row['batch_name'] = html_escape($b['batch_id'] ?? 'N/A');
                $row['product_name'] = html_escape($b['product_name'] ?? 'N/A');
                $row['warehouse_name'] = html_escape($b['warehouse_name'] ?? 'N/A');
                $row['expiry_date'] = !empty($b['expiry_date']) ? html_escape($b['expiry_date']) : '-';
                
                // Added total_quantity and available_quantity with null checks
                $row['total_quantity'] = !empty($b['total_quantity']) ? number_format($b['total_quantity'], 2) : '0.00';
                $row['available_quantity'] = !empty($b['available_quantity']) ? number_format($b['available_quantity'], 2) : '0.00';

                // Set status based on available quantity
                $row['status'] = (float)$b['available_quantity'] > 0 ? 'Active' : 'Inactive';

                // Action button
                $row['action'] = '<a href="' . base_url("warehouse/warehouse/edit_batch/" . $b['id']) . '" class="btn btn-sm btn-primary"><i class="ti-pencil"></i></a>';

                $data[] = $row;
            }

            $totalRecords = $this->warehouse_model->count_all_batches();
            $totalFiltered = $this->warehouse_model->count_filtered_batches($postData);

            echo json_encode([
                "draw" => intval($postData['draw']),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ]);
        }

        

        public function delete($id) {
            if ($this->permission1->method('warehouse', 'delete')->access()) {
                $this->warehouse_model->delete($id);
                $this->session->set_flashdata('message', display('delete_successfully'));
            } else {
                $this->session->set_flashdata('exception', 403);
            }
            redirect('warehouse/warehouse');
        }

        public function edit_batch($id = null)
        {
            if (!$id) {
                show_404();
            }

            $this->load->model('warehouse_model');

            // Fetch batch details
            $batch = $this->warehouse_model->get_batch_by_id($id);

            if (empty($batch)) {
                show_404();
            }

            // Fetch warehouse list
            $data['warehouses'] = $this->warehouse_model->get_all_warehouses_by_status();
            $data['batch'] = $batch;

            // Template setup
            $data['title'] = display('edit_batch');
            $data['module'] = "warehouse";
            $data['page'] = "edit_batch_form";

            echo modules::run('template/layout', $data);
        }

        public function update_batch()
        {
            // Retrieve the data from the form
            $id = $this->input->post('id');
            $warehouse_id = $this->input->post('warehouse_id');
            $expiry_date = $this->input->post('expiry_date');

            // Validate input (expiry_date is now optional)
            if (empty($id) || empty($warehouse_id)) {
                $this->session->set_flashdata('error', 'Warehouse and ID are required.');
                redirect('warehouse/warehouse/edit_batch/' . $id);
                return;
            }

            // Prepare data for update
            $data = [
                'warehouse_id' => $warehouse_id
            ];

            // Only include expiry_date if it's provided
            if (!empty($expiry_date)) {
                $data['expiry_date'] = $expiry_date;
            }

            // Load the model
            $this->load->model('warehouse_model');

            // Execute the update
            try {
                $updated = $this->warehouse_model->update_batch($id, $data);

                if ($updated) {
                    $this->session->set_flashdata('success', 'Batch updated successfully.');
                } else {
                    $this->session->set_flashdata('error', 'Failed to update batch.');
                }
            } catch (Exception $e) {
                log_message('error', 'Batch update failed: ' . $e->getMessage());
                $this->session->set_flashdata('error', 'An unexpected error occurred. Please try again.');
            }

            // Redirect back to the manage batch page
            redirect('warehouse/warehouse/manage_batch');
        }

        // -------------------

        public function stock_movement_history() {
            $data['title'] = display('stock_movement_history');
            $data['module'] = "warehouse";
            $data['page'] = "stock_movement_history";
            
            echo modules::run('template/layout', $data);
        }

        // Add these methods to your Warehouse controller

        public function get_batch_details()
        {
            $id = $this->input->post('id');

            $this->load->model('warehouse_model');
            $batch = $this->warehouse_model->get_batch_by_id($id);

            if ($batch) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'product_name'       => $batch->product_id,
                        'warehouse_name'     => $batch->warehouse_name,
                        'warehouse_id'       => $batch->warehouse_id,
                        'available_quantity' => $batch->available_quantity,
                        'total_quantity'     => $batch->total_quantity
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch not found.'
                ]);
            }
        }

        public function get_movement_types()
        {
            $category = $this->input->post('category');
            
            if ($category) {
                $this->load->model('warehouse_model');
                $types = $this->warehouse_model->get_movement_types_by_category($category);

                if ($types) {
                    echo json_encode([
                        'success' => true,
                        'data' => $types
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No types found for the selected category.'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid category.'
                ]);
            }
        }

        public function process_stock_movement()
        {
            // 🚀 Enable Logging for Debugging
            log_message('debug', 'Stock Movement Request Received');
            log_message('debug', 'Incoming POST Data: ' . json_encode($this->input->post()));

            // 📝 Form Validation
            $this->form_validation->set_rules('batch_id', 'Batch ID', 'required');
            $this->form_validation->set_rules('movement_category', 'Movement Category', 'required');
            $this->form_validation->set_rules('movement_type', 'Movement Type', 'required');
            $this->form_validation->set_rules('quantity', 'Quantity', 'required|numeric|greater_than[0]');

            if ($this->form_validation->run() === FALSE) {
                log_message('error', 'Validation Failed: ' . validation_errors());
                $this->session->set_flashdata('exception', validation_errors());
                redirect('warehouse/warehouse/stock_movement');
            }

            // 🗃️ **Fetch Posted Data**
            $batch_primary_id = $this->input->post('batch_id');
            $quantity = $this->input->post('quantity');
            $destination_warehouse_id = $this->input->post('destination_warehouse_id');
            $reference_no = $this->input->post('reference_no');
            $remarks = $this->input->post('remarks');

            log_message('debug', "Batch Primary ID: $batch_primary_id, Quantity: $quantity, Destination Warehouse: $destination_warehouse_id");

            // 🗃️ **Fetch Original Batch Details**
            $batch = $this->warehouse_model->get_batch_by_id($batch_primary_id);

            // 🔍 **Check if batch exists**
            if (!$batch) {
                log_message('error', "Batch not found for ID: $batch_primary_id");
                $this->session->set_flashdata('exception', 'Batch not found');
                redirect('warehouse/warehouse/stock_movement');
            }
            
            // ✅ Extract the actual `batch_id` from the details
            $batch_id = $batch->batch_id;

            log_message('debug', "Original Batch ID fetched: $batch_id");
            log_message('debug', "Batch Details: " . json_encode($batch));

            // 🛡️ **Validation: Check if sufficient quantity is available**
            if ($batch->available_quantity < $quantity) {
                log_message('error', "Insufficient available quantity for batch $batch_id. Available: $batch->available_quantity, Requested: $quantity");
                $this->session->set_flashdata('exception', 'Insufficient available quantity');
                redirect('warehouse/warehouse/stock_movement');
            }

            // 📝 **Prepare movement data**
            $movement_data = [
                'batch_id' => $batch_id,
                'product_id' => $batch->product_id,
                'movement_type' => $this->input->post('movement_type'),
                'quantity' => $quantity,
                'source_warehouse_id' => $batch->warehouse_id,
                'destination_warehouse_id' => $destination_warehouse_id,
                'reference_no' => $reference_no,
                'remarks' => $remarks,
                'created_by' => $this->session->userdata('id'),
                'movement_date' => date('Y-m-d H:i:s')
            ];

            log_message('debug', 'Movement Data Prepared: ' . json_encode($movement_data));

            // 🚀 **Process the Stock Movement**
            $result = $this->warehouse_model->process_stock_movement(['movement_data' => $movement_data]);

            if ($result) {
                if ($batch->available_quantity == $quantity) {
                    // ✅ **Full Transfer**: Update the warehouse_id in the same batch
                    $this->db->where('id', $batch_primary_id);
                    $this->db->update('batch_master', [
                        'warehouse_id' => $destination_warehouse_id
                    ]);

                    log_message('info', "Full Transfer: Warehouse updated for batch $batch_id to Warehouse $destination_warehouse_id");
                } else {
                    // ✅ **Partial Transfer**:
                    // 📝 1️⃣ Update the original warehouse quantity
                    $remaining_quantity = $batch->available_quantity - $quantity;

                    $this->db->where('id', $batch_primary_id);
                    $this->db->update('batch_master', [
                        'total_quantity'     => $remaining_quantity,
                        'available_quantity' => $remaining_quantity
                    ]);
                    log_message('info', "Partial Transfer: Updated batch $batch_id at Warehouse $batch->warehouse_id with remaining quantity: $remaining_quantity");

                    // 📝 2️⃣ Create a new batch entry for the new warehouse
                    $new_batch_data = array(
                        'batch_id'           => $batch_id,
                        'product_id'         => $batch->product_id,
                        'warehouse_id'       => $destination_warehouse_id,
                        'manufacture_date'   => $batch->manufacture_date,
                        'expiry_date'        => $batch->expiry_date,
                        'total_quantity'     => $quantity,
                        'available_quantity' => $quantity,
                        'created_at'         => date('Y-m-d H:i:s')
                    );

                    // 🔄 **Insert the new batch**
                    $this->db->insert('batch_master', $new_batch_data);
                    $new_id = $this->db->insert_id();
                    log_message('info', "Partial Transfer: New batch created with ID $new_id in warehouse $destination_warehouse_id with quantity $quantity");
                }

                // ✅ **Set Success Message**
                $this->session->set_flashdata('message', display('save_successfully'));
            } else {
                log_message('error', "Failed to process stock movement for batch $batch_id");
                $this->session->set_flashdata('exception', display('please_try_again'));
            }

            // ✅ **Redirect Back**
            redirect('warehouse/warehouse/stock_movement');
        }

        // Stock Movement History

        public function CheckStockMovementList() {
            $this->load->model('warehouse_model');
            log_message('debug', 'Warehouse model loaded successfully.');
        
            // Collecting POST data
            $postData = $this->input->post();
            log_message('debug', 'Post Data received: ' . json_encode($postData));
        
            // Step 1: Counting total records
            try {
                $totalRecords = $this->warehouse_model->count_all_stock_movement();
                log_message('debug', "Total records found: $totalRecords");
            } catch (Exception $e) {
                log_message('error', 'Error in counting all stock movement: ' . $e->getMessage());
                $totalRecords = 0;
            }
        
            // Step 2: Counting filtered records
            try {
                $totalFiltered = $this->warehouse_model->count_filtered_stock_movement($postData);
                log_message('debug', "Total filtered records found: $totalFiltered");
            } catch (Exception $e) {
                log_message('error', 'Error in counting filtered stock movement: ' . $e->getMessage());
                $totalFiltered = 0;
            }
        
            // Step 3: Fetching the data for DataTable
            try {
                $data = $this->warehouse_model->get_stock_movement_datatables($postData);
                log_message('debug', 'Data fetched from database: ' . json_encode($data));
            } catch (Exception $e) {
                log_message('error', 'Error in fetching stock movement datatables: ' . $e->getMessage());
                $data = [];
            }
        
            // Step 4: Preparing the response
            $response = [
                "draw" => intval($postData['draw']),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ];
            log_message('debug', 'Response prepared: ' . json_encode($response));
        
            // Step 5: Sending the response as JSON
            try {
                echo json_encode($response);
                log_message('debug', 'Response sent successfully.');
            } catch (Exception $e) {
                log_message('error', 'Failed to send JSON response: ' . $e->getMessage());
            }
        }

        public function check_batch_id() {
            log_message('debug', 'check_batch_id called');
            
            $batch_id = $this->input->post('batch_id');
            log_message('debug', 'Batch ID received: ' . $batch_id);
        
            if (empty($batch_id)) {
                log_message('error', 'Batch ID is empty');
                echo "false";
                return;
            }
        
            // Load the model
            $this->load->model('warehouse_model');
            $exists = $this->warehouse_model->batch_id_exists($batch_id);
        
            log_message('debug', 'Batch ID existence: ' . ($exists ? 'true' : 'false'));
            if ($exists) {
                echo "true";
            } else {
                echo "false";
            }
        }

        public function get_batch_warehouse()
        {
            $id = $this->input->post('id');
            $this->load->model('warehouse_model');
            
            $batch = $this->warehouse_model->get_batch_by_id($id);

            if ($batch) {
                echo json_encode([
                    'status' => 'success',
                    'warehouse_id' => $batch->warehouse_id,
                    'total_quantity' => $batch->total_quantity,
                    'available_quantity' => $batch->available_quantity
                ]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }

        public function get_all_batches()
    {
        $this->load->model('warehouse_model');
        $batches = $this->warehouse_model->get_all_batches();
        
        if ($batches) {
            echo json_encode([
                'success' => true,
                'data' => $batches
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No batches found.'
            ]);
        }
    }

    public function get_all_warehouses()
    {
        log_message('debug', 'Fetching all active warehouses...');
        
        // Fetch warehouses using the model method
        $warehouses = $this->warehouse_model->get_all_warehouses_by_status();

        if ($warehouses) {
            log_message('debug', 'Warehouses fetched: ' . json_encode($warehouses));
            echo json_encode(['success' => true, 'data' => $warehouses]);
        } else {
            log_message('error', 'No active warehouses found.');
            echo json_encode(['success' => false, 'message' => 'No active warehouses found.']);
        }
    }
}