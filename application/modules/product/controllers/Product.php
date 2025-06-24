<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Product extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
  
        $this->load->model(array(
            'product_model','supplier/supplier_model')); 
        $this->load->library('ciqrcode');
        if (! $this->session->userdata('isLogIn'))
            redirect('login');
          
    }
   
   // category part
    function paysenz_category_list() {
        $data['title']      = display('manage_category');
        $data['module']     = "product";
        $data['page']       = "category_list"; 
        $data["category_list"] = $this->product_model->category_list();
        echo modules::run('template/layout', $data);
    }


    //   public function paysenz_category_form($id = null)
    // {
    //     $data['title'] = display('add_category');
    //     #-------------------------------#
    //     $this->form_validation->set_rules('category_name',display('category_name'),'required|max_length[200]');
    //     $this->form_validation->set_rules('status', display('status') ,'max_length[2]');
    //     #-------------------------------#
    //     $data['category'] = (object)$postData = [
    //         'category_id'      => $id,
    //         'category_name'    => $this->input->post('category_name',true),
    //         'status'           => $this->input->post('status',true),
    //     ]; 
    //     #-------------------------------#
    //     if ($this->form_validation->run() === true) {

    //         #if empty $id then insert data
    //         if (empty($id)) {
    //             if ($this->product_model->create_category($postData)) {
    //                 #set success message
    //                $this->session->set_flashdata('message', display('save_successfully'));
    //             } else {
    //              $this->session->set_flashdata('exception', display('please_try_again'));
    //             }
    //             if (isset($_POST['add-another'])) {
    //             redirect(base_url('category_form'));
    //             exit;
    //         }else{
    //             redirect("category_list");
    //         }
    //         } else {
    //             if ($this->product_model->update_category($postData)) {
    //                $this->session->set_flashdata('message', display('update_successfully'));
    //             } else {
    //               $this->session->set_flashdata('exception', display('please_try_again'));
    //             } 
    //             if (isset($_POST['add-another'])) {
    //             redirect(base_url('category_form'));
    //             exit;
    //         }else{
    //             redirect("category_list");
    //         }
    //         }
    //         } else { 
    //             if(!empty($id)){
    //             $data['title']    = display('edit_category');
    //             $data['category'] = $this->product_model->single_category_data($id);  
    //         }
    //         $data['module']   = "product";  
    //         $data['page']     = "category_form";  
    //         echo Modules::run('template/layout', $data); 
           
    //         } 
    // }


    public function paysenz_category_form($id = null) {
        $data['title'] = empty($id) ? display('add_category') : display('edit_category');
    
        $this->form_validation->set_rules('category_name', display('category_name'), 'required|max_length[200]');
        $this->form_validation->set_rules('status', display('status'), 'max_length[2]');
    
        $parent_id = $this->input->post('parent_id', true);
        $sub_category_name = trim($this->input->post('category_name', true));
    
        $parent_category_name = $this->product_model->get_category_name($parent_id);
        $final_category_name = (!empty($parent_category_name) && $parent_category_name !== $sub_category_name)
            ? $parent_category_name . '->' . $sub_category_name
            : $sub_category_name;
    
        // ✅ Check for duplicate only when inserting new category
        if (empty($id) && $this->product_model->is_duplicate_category($final_category_name)) {
            $this->session->set_flashdata('exception', 'Error: This category already exists!');
            redirect($_SERVER['HTTP_REFERER']); 
            exit;
        }
    
        $data['category'] = (object)$postData = [
            'category_id'   => $id,
            'category_name' => $final_category_name, 
            'parent_id'     => ($parent_id == 0) ? NULL : $parent_id,
            'status'        => $this->input->post('status', true),
        ];
    
        if ($this->form_validation->run() === true) {
            if (empty($id)) {
                if ($this->product_model->create_category($postData)) {
                    $this->session->set_flashdata('message', 'Category saved successfully!');
                } else {
                    $this->session->set_flashdata('exception', 'Error: Could not save the category!');
                }
            } else {
                if ($this->product_model->update_category($postData)) {
                    $this->session->set_flashdata('message', 'Category updated successfully!');
                } else {
                    $this->session->set_flashdata('exception', 'Error: Could not update the category!');
                }
            }
    
            // ✅ Use submit_action to determine redirection
            $submit_action = $this->input->post('submit_action');
            if ($submit_action == 'add_another') {
                redirect('product/paysenz_category_form');
            } else {
                redirect('category_list');
            }
        } else {
            // ✅ Load form with correct category name for editing
            if (!empty($id)) {
                $data['title'] = display('edit_category');
                $category_data = $this->product_model->single_category_data($id);
    
                // ✅ Extract only the last part of category_name
                $name_parts = explode('->', $category_data->category_name);
                $category_data->category_name = trim(end($name_parts));
    
                $data['category'] = $category_data;
            }
    
            $data['categories'] = $this->product_model->get_all_categories();
            $data['module'] = "product";
            $data['page'] = "category_form";
    
            echo Modules::run('template/layout', $data);
        }
    }


    public function paysenz_deletecategory($id = null) {
        if ($this->product_model->delete_category($id)) {
      $this->session->set_flashdata('message', display('delete_successfully'));
        } else {
       $this->session->set_flashdata('exception', display('please_try_again'));
        }

        redirect("category_list");
    }

    // unit part
        function paysenz_unit_list() {
        $data['title']      = display('manage_unit');
        $data['module']     = "product";
        $data['page']       = "unit_list"; 
        $data["unit_list"] = $this->product_model->unit_list();
        echo modules::run('template/layout', $data);
    }


      public function paysenz_unit_form($id = null)
    {
        $data['title'] = display('add_unit');
        #-------------------------------#
        $this->form_validation->set_rules('unit_name',display('unit_name'),'required|max_length[200]');
        $this->form_validation->set_rules('status', display('status') ,'max_length[2]');
        #-------------------------------#
        $data['unit'] = (object)$postData = [
            'unit_id'      => $id,
            'unit_name'    => $this->input->post('unit_name',true),
            'status'       => $this->input->post('status',true),
        ]; 
        #-------------------------------#
        if ($this->form_validation->run() === true) {

            #if empty $id then insert data
            if (empty($id)) {
                if ($this->product_model->create_unit($postData)) {
                    #set success message
                   $this->session->set_flashdata('message', display('save_successfully'));
                } else {
                 $this->session->set_flashdata('exception', display('please_try_again'));
                }
                    if (isset($_POST['add-another'])) {
                redirect(base_url('unit_form'));
                exit;
            }else{
                redirect("unit_list");
            }
               
            } else {
                if ($this->product_model->update_unit($postData)) {
                   $this->session->set_flashdata('message', display('update_successfully'));
                } else {
                  $this->session->set_flashdata('exception', display('please_try_again'));
                } 
                          if (isset($_POST['add-another'])) {
                redirect(base_url('unit_form'));
                exit;
            }else{
                redirect("unit_list");
            }
            }
            } else { 
                if(!empty($id)){
                $data['title']    = display('edit_unit');
                $data['unit'] = $this->product_model->single_unit_data($id);  
                }
                $data['module']   = "product";  
                $data['page']     = "unit_form";  
                echo Modules::run('template/layout', $data); 
           
            } 
    }



    public function paysenz_deleteunit($id = null) {
        if ($this->product_model->delete_unit($id)) {
      $this->session->set_flashdata('message', display('delete_successfully'));
        } else {
       $this->session->set_flashdata('exception', display('please_try_again'));
        }
        redirect("unit_list");
    }

    public function get_subcategories() {
        $parent_id = $this->input->post('parent_id');
        $subcategories = $this->product_model->get_sub_categories($parent_id);
        echo json_encode($subcategories);
    }

    public function get_childcategories() {
        $sub_id = $this->input->post('sub_id');
        
        // Validate input
        if (empty($sub_id)) {
            echo json_encode([]);
            return;
        }
    
        // Fetch child categories
        $child_categories = $this->product_model->get_child_categories($sub_id);
    
        // Debugging: Check if data is retrieved
        if (empty($child_categories)) {
            log_message('error', "No child categories found for sub_category_id: " . $sub_id);
        }
    
        echo json_encode($child_categories);
    }

    // product part
    public function paysenz_product_form($id = null)
    {
        $data['title'] = display('add_product');

        #-------------------------------#
        $this->form_validation->set_rules('product_name', display('product_name'), 'required|max_length[200]');
        $this->form_validation->set_rules('model', display('model'), 'max_length[200]');
        $this->form_validation->set_rules('category_id', display('category'), 'required|max_length[20]');
        $this->form_validation->set_rules('price', display('price'), 'max_length[12]');

        $product_id = (!empty($this->input->post('product_id', TRUE)) ? $this->input->post('product_id', TRUE) : $this->generator(8));
        $sup_price = $this->input->post('supplier_price', TRUE);
        $s_id = $this->input->post('supplier_id', TRUE);
        $product_model = $this->input->post('model', TRUE);

        # Fetch tax settings
        $taxfield = $this->db->select('tax_name,default_value')
                            ->from('tax_settings')
                            ->get()
                            ->result_array();

        #-------------------------------#
        $data['product'] = (object)$postData = [
            'product_id'     => (!empty($id) ? $id : $product_id),
            'product_name'   => $this->input->post('product_name', TRUE),
            'category_id'    => $this->input->post('category_id', TRUE),
            'unit'           => $this->input->post('unit', TRUE),
            'tax'            => 0,
            'serial_no'      => $this->input->post('serial_no', TRUE),
            'price'          => $this->input->post('price', TRUE),
            'product_model'  => $this->input->post('model', TRUE),
            'product_details'=> $this->input->post('description', TRUE),
            'product_vat'    => $this->input->post('product_vat', TRUE),
            'status'         => 1,
        ];

        # Handle dynamic tax columns
        $tablecolumn = $this->db->list_fields('tax_collection');
        $num_column = count($tablecolumn) - 4;
        if ($num_column > 0) {
            $txf = [];
            for ($i = 0; $i < $num_column; $i++) {
                $txf[$i] = 'tax' . $i;
            }
            foreach ($txf as $key => $value) {
                $postData[$value] = (!empty($this->input->post($value)) ? $this->input->post($value) : 0) / 100;
            }
        }

        #-------------------------------#
        if ($this->form_validation->run() === true) {

            // ✅ Image validation only during POST/form submission
            $image = $this->process_product_image('image', './my-assets/image/product/');
            if (!$image && empty($this->input->post('old_image', TRUE))) {
                $this->session->set_flashdata('exception', 'Invalid image. Please upload JPG, PNG, or SVG.');
                redirect($_SERVER['HTTP_REFERER']);
            }

            $image = (!empty($image) ? $image : $this->input->post('old_image', TRUE));
            $postData['image'] = (!empty($image) ? $image : 'my-assets/image/product.png');

            if (empty($id)) {  # Insert new product
                if ($this->product_model->create_product($postData)) {
                    for ($i = 0, $n = count($s_id); $i < $n; $i++) {
                        $supp_id = $s_id[$i];
                        $supp_prd = [
                            'product_id'     => $product_id,
                            'supplier_id'    => $supp_id,
                            'supplier_price' => $sup_price,
                            'products_model' => $product_model,
                        ];
                        $this->db->insert('supplier_product', $supp_prd);
                    }
                    $this->session->set_flashdata('message', display('save_successfully'));
                } else {
                    $this->session->set_flashdata('exception', display('please_try_again'));
                }
                redirect("product_list");
            } else {  # Update existing product
                if ($this->product_model->update_product($postData)) {
                    $this->db->where('product_id', $id)->delete("supplier_product");
                    for ($i = 0, $n = count($s_id); $i < $n; $i++) {
                        $supp_id = $s_id[$i];
                        $supp_prd = [
                            'product_id'     => $id,
                            'supplier_id'    => $supp_id,
                            'supplier_price' => $sup_price,
                            'products_model' => $product_model,
                        ];
                        $this->db->insert('supplier_product', $supp_prd);
                    }
                    $this->session->set_flashdata('message', display('update_successfully'));
                } else {
                    $this->session->set_flashdata('exception', display('please_try_again'));
                }
                redirect("product_list");
            }

        } else {
            if (!empty($id)) {
                $data['title'] = display('edit_product');
                $data['product'] = $this->product_model->single_product_data($id);

                // Fetch parent/sub/child category ids
                $category_ids = $this->product_model->get_category_hierarchy($data['product']->category_id);
                $data = array_merge($data, $category_ids);
            }

            # ✅ Fetch Parent & Child Categories
            $data['parent_categories'] = $this->product_model->get_parent_categories();
            $data['sub_categories'] = $this->product_model->get_sub_categories();
            $data['child_categories'] = $this->product_model->get_child_categories();

            $data['supplier'] = $this->product_model->supplier_list();
            $data['vattaxinfo'] = $this->product_model->vat_tax_setting();
            $data['id'] = $id;
            $data['unit_list'] = $this->product_model->active_unit();
            $data['supplier_pr'] = $this->product_model->supplier_product_list($id);
            $data['taxfield'] = $taxfield;
            $data['module'] = "product";
            $data['page'] = "product_form";
            echo Modules::run('template/layout', $data);
        }
    }


    private function process_product_image($field_name, $upload_path)
    {
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed_ext = ['jpg', 'jpeg', 'png', 'svg'];
        $file_info = pathinfo($_FILES[$field_name]['name']);
        $extension = strtolower($file_info['extension']);

        if (!in_array($extension, $allowed_ext)) {
            return null;
        }

        $file_tmp = $_FILES[$field_name]['tmp_name'];
        $file_name = uniqid() . '.' . $extension;
        $full_path = $upload_path . $file_name;

        // Create upload directory if not exists
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        if ($extension === 'svg') {
            // SVG: move without compression
            move_uploaded_file($file_tmp, $full_path);
        } else {
            // JPG/PNG: compress and save
            $this->compress_image($file_tmp, $full_path, 75); // 75% quality
        }

        return str_replace('./', '', $full_path);
    }

    private function compress_image($source, $destination, $quality)
    {
        $info = getimagesize($source);
        if (!$info) return false;

        $mime = $info['mime'];

        // Load original image
        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($source);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        } else {
            return false; // Unsupported
        }

        $orig_width = $info[0];
        $orig_height = $info[1];

        // Resize to fixed height 500px, width = ratio
        $new_height = 500;
        $ratio = $new_height / $orig_height;
        $new_width = (int)($orig_width * $ratio);

        $resized_image = imagecreatetruecolor($new_width, $new_height);

        // Handle transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
        }

        // Resize the image
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

        // Save to destination
        if ($mime === 'image/jpeg') {
            imagejpeg($resized_image, $destination, $quality);
        } elseif ($mime === 'image/png') {
            imagepng($resized_image, $destination, 6); // Compression level 6 (0-9)
        }

        // Free memory
        imagedestroy($image);
        imagedestroy($resized_image);

        return true;
    }

    public function paysenz_product_list(){
        $data['title']         = display('manage_product');
        $data['total_product'] = $this->db->count_all("product_information");
        $data['module']        = "product";
        $data['page']          = "product_list"; 
        echo modules::run('template/layout', $data);
    }

    public function CheckProductList(){
        $postData = $this->input->post();
        $data = $this->product_model->getProductList($postData);

        // ✅ Debugging in controller
    log_message('error', 'Controller Product Name: ' . print_r($data, true));

        echo json_encode($data);
    } 

        public function paysenz_deleteproduct($id = null) {
            $check_calculation = $this->product_model->check_product($id);
            if($check_calculation > 0){
                $this->session->set_flashdata('exception', 'You can not Delete this product because it already engaged in calculation');
                redirect("product_list");
            }
        if ($this->product_model->delete_product($id)) {
      $this->session->set_flashdata('message', display('delete_successfully'));
        } else {
       $this->session->set_flashdata('exception', display('please_try_again'));
        }

        redirect("product_list");
    }

    public function paysenz_csv_product(){
        $data['title']         = display('add_product_csv');
        $data['module']        = "product";
        $data['page']          = "add_product_csv"; 
        echo modules::run('template/layout', $data);
    }


    function uploadCsv()
    {
        $filename = $_FILES['upload_csv_file']['name'];  
        $basenameAndExtension = explode('.', $filename);
        $ext = end($basenameAndExtension);
        if($ext == 'csv'){
        $count=0;
        $fp = fopen($_FILES['upload_csv_file']['tmp_name'],'r') or die("can't open file");

        if (($handle = fopen($_FILES['upload_csv_file']['tmp_name'], 'r')) !== FALSE)
        {
  
         while($csv_line = fgetcsv($fp,1024)){
                //keep this if condition if you want to remove the first row
                for($i = 0, $j = count($csv_line); $i < $j; $i++)
                {                  
                  $product_id = $this->generator(10);                  
                  $insert_csv = array();
                  $insert_csv['supplier_id']    = (!empty($csv_line[1])?$csv_line[1]:null);
                  $insert_csv['product_name']   = (!empty($csv_line[2])?$csv_line[2]:null);
                  $insert_csv['product_model']  = (!empty($csv_line[3])?$csv_line[3]:null);
                  $insert_csv['category_id']    = (!empty($csv_line[4])?$csv_line[4]:null);
                  $insert_csv['price']          = (!empty($csv_line[5])?$csv_line[5]:null);
                  $insert_csv['supplier_price'] = (!empty($csv_line[6])?$csv_line[6]:null);
                }
                 $check_supplier = $this->db->select('*')->from('supplier_information')->where('supplier_name',$insert_csv['supplier_id'])->get()->row();
                if(!empty($check_supplier)){
                    $supplier_id = $check_supplier->supplier_id;
                }else{
                     $supplierinfo=array(
            'supplier_name' => $insert_csv['supplier_id'],
            'address'           => '',
            'mobile'            => '',
            'details'           => '',
            'status'            => 1
            );
                     if ($count > 0) {
            $this->db->insert('supplier_information',$supplierinfo);
        }
            $supplier_id = $this->db->insert_id();
            $coa = $this->supplier_model->headcode();
        if($coa->HeadCode!=NULL){
            $headcode=$coa->HeadCode+1;
        }
        else{
            $headcode="21110000001";
        }
        $c_acc=$supplier_id.'-'.$insert_csv['supplier_id'];
        $createby=$this->session->userdata('id');
        $createdate=date('Y-m-d H:i:s');

       
         $supplier_coa = [
            'HeadCode'         => $headcode,
            'HeadName'         => $c_acc,
            'PHeadName'        => 'Suppliers',
            'HeadLevel'        => '3',
            'IsActive'         => '1',
            'IsTransaction'    => '1',
            'IsGL'             => '0',
            'HeadType'         => 'L',
            'IsBudget'         => '0',
            'IsDepreciation'   => '0',
            'supplier_id'      => $supplier_id,
            'DepreciationRate' => '0',
            'CreateBy'         => $createby,
            'CreateDate'       => $createdate,
        ];

        if ($count > 0) {
        $this->db->insert('acc_coa',$supplier_coa);
    }
                }

        $check_category = $this->db->select('*')->from('product_category')->where('category_name',$insert_csv['category_id'])->get()->row();
        if(!empty($check_category)){
            $category_id = $check_category->category_id;
        }else{
            $categorydata=array(
            'category_name'         => $insert_csv['category_id'],
            'status'                => 1
            );
            if ($count > 0) {
        $this->db->insert('product_category',$categorydata);
        $category_id = $this->db->insert_id();
}

        }
         $data = array(
                    'product_id'    => $product_id,
                    'category_id'   => $category_id,
                    'product_name'  => $insert_csv['product_name'],
                    'product_model' => $insert_csv['product_model'],
                    'price'         => $insert_csv['price'],
                    'unit'          => '',
                    'tax'           => '',
                    'product_details'=>'Csv Product',
                    'image'         => 'my-assets/image/product.png',
                    'status'        => 1
                );

                if ($count > 0) {

                                 $result = $this->db->select('*')
                                        ->from('product_information')
                                        ->where('product_name',$data['product_name'])
                                        ->where('product_model',$data['product_model'])
                                        ->where('category_id',$category_id)
                                        ->get()
                                        ->row();
                    if (empty($result)){
                        $this->db->insert('product_information',$data);
                        $product_id = $product_id;
                         }else {
                    $product_id = $result->product_id;      
                      $udata = array(
                        'product_id'     => $result->product_id,
                        'category_id'    => $category_id,
                        'product_name'   => $result->product_name,
                        'product_model'  => $insert_csv['product_model'],
                        'price'          => $insert_csv['price'],
                        'unit'           => '',
                        'tax'            => '',
                        'product_details'=> 'Csv Uploaded Product',
                        'image'         => 'my-assets/image/product.png',
                        'status'        => 1
                     );
                   $this->db->where('product_id',$result->product_id);
                   $this->db->update('product_information',$udata);
                        
                    }

                     $supp_prd = array(
                    'product_id'     => $product_id,
                    'supplier_id'    => $supplier_id,
                    'supplier_price' => $insert_csv['supplier_price'],
                    'products_model' => $insert_csv['product_model'],
                );

                       $splprd = $this->db->select('*')
                            ->from('supplier_product')
                            ->where('supplier_id', $supplier_id)
                            ->where('product_id', $product_id)
                            ->get()
                            ->num_rows();

                    if ($splprd == 0) {
                        $this->db->insert('supplier_product', $supp_prd);
                    } else {
                        $supp_prd = array(
                            'supplier_id'    => $supplier_id,
                            'supplier_price' => $insert_csv['supplier_price'],
                            'products_model' => $insert_csv['product_model']
                        );
                        $this->db->where('product_id', $product_id);
                        $this->db->where('supplier_id', $supplier_id);
                        $this->db->update('supplier_product', $supp_prd);
                    }
    
                }  
                $count++; 
            }
            
        }
        
        $this->session->set_flashdata(array('message'=>display('successfully_added')));
        redirect(base_url('product_list'));
    }else{
        $this->session->set_flashdata(array('error_message'=>'Please Import Only Csv File'));
        redirect(base_url('bulk_products'));
    }
    
    }



        public function qrgenerator($product_id) {
        $config['cacheable'] = true; //boolean, the default is true
        $config['cachedir'] = ''; //string, the default is application/cache/
        $config['errorlog'] = ''; //string, the default is application/logs/
        $config['quality'] = true; //boolean, the default is true
        $config['size'] = '1024'; //interger, the default is 1024
        $config['black'] = array(224, 255, 255); // array, default is 
        $config['white'] = array(70, 130, 180); // array, default is array(0,0,0)
        $this->ciqrcode->initialize($config);
        $params['data'] = $product_id;
        $params['level'] = 'H';
        $params['size'] = 10;
        $image_name = $product_id . '.png';
        $params['savename'] = FCPATH . 'my-assets/image/qr/' . $image_name;
        $this->ciqrcode->generate($params);
        $product_info = $this->product_model->paysenz_barcode_productdata($product_id);
        $data = array(
            'title'           => display('qr_code'),
            'product_name'    => $product_info[0]['product_name'],
            'product_model'   => $product_info[0]['product_model'],
            'price'           => $product_info[0]['price'],
            'product_details' => $product_info[0]['product_details'],
            'qr_image'        => $image_name,
        );
        $data['module']        = "product";
        $data['page']          = "barcode_print_page"; 
        echo modules::run('template/layout', $data);
    }


    // bar code part
    public function barcode_print($product_id){
        $product_info = $this->product_model->paysenz_barcode_productdata($product_id);

        $data = array(
                'title'           => display('barcode'),
                'product_id'      => $product_id,
                'product_name'    => $product_info[0]['product_name'],
                'product_model'   => $product_info[0]['product_model'],
                'price'           => $product_info[0]['price'],
                'product_details' => $product_info[0]['product_details'],
                
            );

        $data['module']        = "product";
        $data['page']          = "barcode_print_page"; 
        echo modules::run('template/layout', $data);
    }

   
public function paysenz_product_details($product_id = null){
        $details_info = $this->product_model->paysenz_barcode_productdata($product_id);
        $purchaseData = $this->product_model->product_purchase_info($product_id);
        $totalPurchase = 0;
        $totalPrcsAmnt = 0;

        if (!empty($purchaseData)) {
            foreach ($purchaseData as $k => $v) {
                $purchaseData[$k]['final_date'] = $purchaseData[$k]['purchase_date'];
                $totalPrcsAmnt = ($totalPrcsAmnt + $purchaseData[$k]['total_amount']);
                $totalPurchase = ($totalPurchase + $purchaseData[$k]['quantity']);
            }
        }

        $salesData = $this->product_model->invoice_data($product_id);

        $totalSales = 0;
        $totaSalesAmt = 0;
        if (!empty($salesData)) {
            foreach ($salesData as $k => $v) {
                $salesData[$k]['final_date'] = $salesData[$k]['date'];
                $totalSales = ($totalSales + $salesData[$k]['quantity']);
                $totaSalesAmt = ($totaSalesAmt + $salesData[$k]['total_amount']);
            }
        }

        $stock = ($totalPurchase - $totalSales);
       $data = array(
            'title'               => display('product_details'),
            'product_name'        => $details_info[0]['product_name'],
            'product_model'       => $details_info[0]['product_model'],
            'price'               => $details_info[0]['price'],
            'purchaseTotalAmount' => number_format($totalPrcsAmnt, 2, '.', ','),
            'salesTotalAmount'    => number_format($totaSalesAmt, 2, '.', ','),
            'img'                 => $details_info[0]['image'],
            'total_purchase'      => $totalPurchase,
            'total_sales'         => $totalSales,
            'purchaseData'        => $purchaseData,
            'salesData'           => $salesData,
            'stock'               => $stock,
        );

        $data['module']        = "product";
        $data['page']          = "product_details"; 
        echo modules::run('template/layout', $data);
}


 public function generator($lenth) {
        $number = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");

        for ($i = 0; $i < $lenth; $i++) {
            $rand_value = rand(0, 9);
            $rand_number = $number["$rand_value"];

            if (empty($con)) {
                $con = $rand_number;
            } else {
                $con = "$con" . "$rand_number";
            }
        }
        return $con;
    }
}

