<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Customer extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
  
        $this->load->model(array(
            'customer_model')); 
        if (! $this->session->userdata('isLogIn'))
            redirect('login');
          
    }
    
    function index() {
        $data['title']             = display('customer_list');
        $data['module']            = "customer";
        $data['page']              = "customer_list"; 
        $data["customer_dropdown"] = $this->customer_model->customer_dropdown();
        $data['all_customer']      = $this->customer_model->allcustomer(); 
        
        echo modules::run('template/layout', $data);
    }


    public function paysenz_CheckCustomerList(){
        $postData = $this->input->post();
        $data     = $this->customer_model->getCustomerList($postData);
        
        // 🔴 Debugging: Print JSON output to check if balance is included
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

     public function paysenz_credit_customer() {
        $data['title']             = display('credit_customer');
        $data['module']            = "customer";
        $data['page']              = "credit_customer"; 
        $data["customer_dropdown"] = $this->customer_model->paysenz_credit_customer_dropdown();
        $data['all_customer']      = $this->customer_model->paysenz_all_credit_customer(); 
        echo modules::run('template/layout', $data);
    }

     public function paysenz_CheckCreditCustomerList(){

        $postData = $this->input->post();
        $data = $this->customer_model->getCreditCustomerList($postData);
        echo json_encode($data);
    }

    //Paid Customer list. The customer who will pay 100%.
    public function paysenz_paid_customer() {
        $data['title']             = display('paid_customer');
        $data['module']            = "customer";
        $data['page']              = "paid_customer"; 
        $data["customer_dropdown"] = $this->customer_model->paysenz_paid_customer_dropdown();
        $data['all_customer']      = $this->customer_model->paysenz_all_paid_customer(); 
        echo modules::run('template/layout', $data);
    }
    
     public function paysenz_CheckPaidCustomerList(){
        // GET data
        $postData = $this->input->post();
        $data = $this->customer_model->paysenz_getPaidCustomerList($postData);
        echo json_encode($data);
    } 


//     public function paysenz_form($id = null)
// {
//     $data['title'] = display('add_customer');

//     #-------------------------------#
//     $this->form_validation->set_rules('customer_name', display('customer_name'), 'required|max_length[200]');
//     $this->form_validation->set_rules('customer_mobile', display('customer_mobile'), 'max_length[20]');
//     if (empty($id)) {
//         $this->form_validation->set_rules('customer_email', display('email'), 'max_length[100]|valid_email|is_unique[customer_information.customer_email]');
//     } else {
//         $this->form_validation->set_rules('customer_email', display('email'), 'max_length[100]|valid_email');
//     }
//     $this->form_validation->set_rules('contact', display('contact'), 'max_length[200]');
//     $this->form_validation->set_rules('phone', display('phone'), 'max_length[20]');
//     $this->form_validation->set_rules('city', display('city'), 'max_length[100]');
//     $this->form_validation->set_rules('state', display('state'), 'max_length[100]');
//     $this->form_validation->set_rules('zip', display('zip'), 'max_length[30]');
//     $this->form_validation->set_rules('country', display('country'), 'max_length[100]');
//     $this->form_validation->set_rules('customer_address', display('customer_address'), 'max_length[255]');
//     $this->form_validation->set_rules('address2', display('address2'), 'max_length[255]');
//     $this->form_validation->set_rules('sales_permit_number', display('sales_permit_number'), 'max_length[50]');

//     #-------------------------------#

//     // Handle file upload
//     $sales_permit = "";
//     if (!empty($_FILES['sales_permit']['name'])) {
//         $config['upload_path']   = './uploads/sales_permits/';
//         $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
//         $config['max_size']      = 2048;
//         $config['file_name']     = time() . '_' . $_FILES['sales_permit']['name'];

//         $this->load->library('upload', $config);

//         if ($this->upload->do_upload('sales_permit')) {
//             $upload_data = $this->upload->data();
//             $sales_permit = $upload_data['file_name'];
//         } else {
//             log_message('error', 'File Upload Error: ' . $this->upload->display_errors());
//             $this->session->set_flashdata('exception', 'File upload failed: ' . $this->upload->display_errors());
//             redirect($_SERVER['HTTP_REFERER']);
//             return;
//         }
//     }

//     // Customer Data
//     $data['customer'] = (object)$postData = [
//         'customer_id'        => $this->input->post('customer_id', true),
//         'customer_name'      => $this->input->post('customer_name', true),
//         'customer_mobile'    => $this->input->post('customer_mobile', true),
//         'customer_email'     => $this->input->post('customer_email', true),
//         'email_address'      => $this->input->post('email_address', true),
//         'contact'            => $this->input->post('contact', true),
//         'phone'              => $this->input->post('phone', true),
//         'fax'                => $this->input->post('fax', true),
//         'city'               => $this->input->post('city', true),
//         'state'              => $this->input->post('state', true),
//         'zip'                => $this->input->post('zip', true),
//         'country'            => $this->input->post('country', true),
//         'customer_address'   => $this->input->post('customer_address', true),
//         'address2'           => !empty($this->input->post('address2', true)) ? $this->input->post('address2', true) : NULL,
//         'sales_permit_number'=> $this->input->post('sales_permit_number', true),
//         'status'             => $this->input->post('status', true),
//         'create_by'          => $this->session->userdata('id')
//     ];

//     if (!empty($sales_permit)) {
//         $postData['sales_permit'] = $sales_permit;
//     }

//     #-------------------------------#
//     if ($this->form_validation->run() === true) {
//         if (empty($postData['customer_id'])) {
//             if ($this->customer_model->create($postData)) {
//                 $customer_id = $this->db->insert_id();

//                 // ✅ Insert commission manually during create, because insert_id is needed
//                 $commission_data = [
//                     'customer_id'     => $customer_id,
//                     'comission_type'  => $this->input->post('comission_type', true),
//                     'commision_value' => $this->input->post('comission_value', true),
//                     'notes'           => $this->input->post('comission_note', true),
//                     'create_by'       => $this->session->userdata('id'),
//                     'status'          => 1,
//                     'create_date'     => date('Y-m-d H:i:s'),
//                     'update_date'     => date('Y-m-d H:i:s')
//                 ];

//                 $this->db->insert('customer_comission', $commission_data);

//                 $info['msg'] = display('save_successfully');
//                 $info['status'] = 1;
//             } else {
//                 $info['msg'] = display('please_try_again');
//                 $info['status'] = 0;
//             }
//         } else {
//             // ✅ Don't manually handle commission on update — it's handled in the model
//             if ($this->customer_model->update($postData)) {
//                 $info['msg'] = display('update_successfully');
//                 $info['status'] = 1;
//             } else {
//                 $info['msg'] = display('please_try_again');
//                 $info['status'] = 0;
//             }
//         }

//         if ($this->input->is_ajax_request()) {
//             echo json_encode($info);
//         } else {
//             if ($info['status'] == 1) {
//                 $this->session->set_flashdata('message', $info['msg']);
//                 redirect('customer_list');
//             } else {
//                 $this->session->set_flashdata('exception', $info['msg']);
//                 redirect($_SERVER['HTTP_REFERER']);
//             }
//         }
//     } else {
//         if ($this->input->is_ajax_request()) {
//             $info['msg'] = validation_errors();
//             $info['status'] = 0;
//             echo json_encode($info);
//         } else {
//             if (!empty($id)) {
//                 $data['title'] = display('edit_customer');
//                 $data['customer'] = $this->customer_model->singledata($id);

//                 // load commission data into form
//                 $commission = $this->db->get_where('customer_comission', ['customer_id' => $id, 'status' => 1])->row();
//                 if ($commission) {
//                     $data['customer']->comission_value = $commission->commision_value;
//                     $data['customer']->comission_type = $commission->comission_type;
//                     $data['customer']->comission_note = $commission->notes;
//                 }
//             }

//             $data['module'] = "customer";
//             $data['page']   = "form";
//             echo Modules::run('template/layout', $data);
//         }
//     }
// }
    public function paysenz_form($id = null)
    {
        $data['title'] = display('add_customer');

        $this->form_validation->set_rules('customer_name', display('customer_name'), 'required|max_length[200]');
        $this->form_validation->set_rules('customer_mobile', display('customer_mobile'), 'max_length[20]');
        if (empty($id)) {
            $this->form_validation->set_rules('customer_email', display('email'), 'max_length[100]|valid_email|is_unique[customer_information.customer_email]');
        } else {
            $this->form_validation->set_rules('customer_email', display('email'), 'max_length[100]|valid_email');
        }
        $this->form_validation->set_rules('email_address', display('email_address'), 'max_length[100]');
        $this->form_validation->set_rules('contact', display('contact'), 'max_length[200]');
        $this->form_validation->set_rules('phone', display('phone'), 'max_length[20]');
        $this->form_validation->set_rules('fax', display('fax'), 'max_length[20]');
        $this->form_validation->set_rules('city', display('city'), 'max_length[100]');
        $this->form_validation->set_rules('state', display('state'), 'max_length[100]');
        $this->form_validation->set_rules('zip', display('zip'), 'max_length[30]');
        $this->form_validation->set_rules('country', display('country'), 'max_length[100]');
        $this->form_validation->set_rules('customer_address', display('customer_address'), 'max_length[255]');
        $this->form_validation->set_rules('address2', display('address2'), 'max_length[255]');
        $this->form_validation->set_rules('sales_permit_number', display('sales_permit_number'), 'max_length[50]');
        $this->form_validation->set_rules('comission_value', display('comission_value'), 'max_length[100]');
        $this->form_validation->set_rules('comission_type', display('comission_type'), 'max_length[100]');
        $this->form_validation->set_rules('comission_note', display('comission_note'), 'max_length[255]');

        if ($this->input->post('password_option') && $this->input->post('password')) {
            $this->form_validation->set_rules('password', 'Password', 'min_length[6]|max_length[255]');
        }

        $sales_permit = "";
        if (!empty($_FILES['sales_permit']['name'])) {
            $config['upload_path']   = './uploads/sales_permits/';
            $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
            $config['max_size']      = 2048;
            $config['file_name']     = time() . '_' . $_FILES['sales_permit']['name'];

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('sales_permit')) {
                $upload_data = $this->upload->data();
                $sales_permit = $upload_data['file_name'];
            } else {
                log_message('error', 'File Upload Error: ' . $this->upload->display_errors());
                $this->session->set_flashdata('exception', 'File upload failed: ' . $this->upload->display_errors());
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }
        }

        $data['customer'] = (object)$postData = [
            'customer_id'         => $this->input->post('customer_id', true),
            'customer_name'       => $this->input->post('customer_name', true),
            'customer_mobile'     => $this->input->post('customer_mobile', true),
            'customer_email'      => $this->input->post('customer_email', true),
            'email_address'       => $this->input->post('email_address', true),
            'contact'             => $this->input->post('contact', true),
            'phone'               => $this->input->post('phone', true),
            'fax'                 => $this->input->post('fax', true),
            'city'                => $this->input->post('city', true),
            'state'               => $this->input->post('state', true),
            'zip'                 => $this->input->post('zip', true),
            'country'             => $this->input->post('country', true),
            'customer_address'    => $this->input->post('customer_address', true),
            'address2'            => !empty($this->input->post('address2', true)) ? $this->input->post('address2', true) : NULL,
            'sales_permit_number' => $this->input->post('sales_permit_number', true),
            'status'              => $this->input->post('status', true),
            'create_by'           => $this->session->userdata('id')
        ];

        if (!empty($sales_permit)) {
            $postData['sales_permit'] = $sales_permit;
        }

            if ($this->form_validation->run() === true) {
                if (empty($postData['customer_id'])) {
                    if ($this->customer_model->create($postData)) {
                        $customer_id = $this->db->insert_id();

                        $commission_data = [
                            'customer_id'     => $customer_id,
                            'comission_type'  => $this->input->post('comission_type', true),
                            'commision_value' => $this->input->post('comission_value', true), // ⚠️ double "m"
                            'notes'           => $this->input->post('comission_note', true),
                            'create_by'       => $this->session->userdata('id'),
                            'status'          => 1,
                            'create_date'     => date('Y-m-d H:i:s'),
                            'update_date'     => date('Y-m-d H:i:s')
                        ];
                        $this->db->insert('customer_comission', $commission_data);

                        $email = $postData['customer_email'];
                        $password = $this->random_password(8);

                        $auth_exists = $this->db->where('username', $email)->get('customer_auth')->row();
                        if (!$auth_exists) {
                            $this->db->insert('customer_auth', [
                                'customer_id' => $customer_id,
                                'username'    => $email,
                                'password'    => password_hash($password, PASSWORD_BCRYPT),
                                'status'      => 1
                            ]);
                        }

                        $query = http_build_query([
                            'name'     => $postData['customer_name'],
                            'email'    => $email,
                            'phone'    => $postData['customer_mobile'],
                            'password' => $password
                        ]);
                        $api_url = "http://demob2b.paysenzhost.xyz/api/erpSignUp?$query";
                        $api_response = file_get_contents($api_url);
                        log_message('debug', "📤 ERP API Response: $api_response");

                        $this->load->library('sendmail_lib');
                        $this->sendmail_lib->send(
                            $email,
                            'Welcome to DeshiShad',
                            "<p>Dear {$postData['customer_name']},</p><p>Your account has been created.</p><p><strong>Email:</strong> {$email}</p><p><strong>Password:</strong> {$password}</p><p>Please login and change your password.</p><br><p>Thanks,<br>DeshiShad Tech Team(PSB)</p>"
                        );

                        $info['msg'] = display('save_successfully');
                        $info['status'] = 1;
                    } else {
                        $info['msg'] = display('please_try_again');
                        $info['status'] = 0;
                    }
                } else {
                    if ($this->customer_model->update($postData)) {
                        $password_option = $this->input->post('password_option', true);
                        $password_value  = $this->input->post('password', true);

                        if (in_array($password_option, ['set', 'reset']) && !empty($password_value)) {
                            $hashed_password = password_hash($password_value, PASSWORD_BCRYPT);
                            $auth_row = $this->db->get_where('customer_auth', ['customer_id' => $postData['customer_id']])->row();

                            if ($auth_row) {
                                $this->db->where('customer_id', $postData['customer_id'])->update('customer_auth', [
                                    'password'   => $hashed_password,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                            } else {
                                $this->db->insert('customer_auth', [
                                    'customer_id' => $postData['customer_id'],
                                    'username'    => $postData['customer_email'],
                                    'password'    => $hashed_password,
                                    'status'      => 1
                                ]);
                            }

                            $this->load->library('sendmail_lib');
                            $this->sendmail_lib->send(
                                $postData['customer_email'],
                                'Password Updated',
                                "<p>Dear {$postData['customer_name']},</p><p>Your password has been updated.</p><p><strong>Password:</strong> {$password_value}</p><br><p>Thanks,<br>DeshiShad Tech Team(PSB)</p>"
                            );

                            $admin_users = $this->db->where('status', 1)->get('users')->result();
                            foreach ($admin_users as $admin) {
                                $this->sendmail_lib->send(
                                    $admin->email,
                                    'Customer Password Changed',
                                    "<p>Customer <strong>{$postData['customer_name']}</strong> has changed password.</p><p>Email: {$postData['customer_email']}</p>"
                                );
                            }

                            log_message('debug', "[CustomerUpdate] Password {$password_option} for customer_id={$postData['customer_id']}");
                        }

                        $info['msg'] = display('update_successfully');
                        $info['status'] = 1;
                    } else {
                        $info['msg'] = display('please_try_again');
                        $info['status'] = 0;
                    }
                }

                if ($this->input->is_ajax_request()) {
                    echo json_encode($info);
                } else {
                    if ($info['status'] == 1) {
                        $this->session->set_flashdata('message', $info['msg']);
                        redirect('customer_list');
                    } else {
                        $this->session->set_flashdata('exception', $info['msg']);
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }
            } else {
                if ($this->input->is_ajax_request()) {
                    $info['msg'] = validation_errors();
                    $info['status'] = 0;
                    echo json_encode($info);
                } else {
                    if (!empty($id)) {
                        $data['title'] = display('edit_customer');
                        $data['customer'] = $this->customer_model->singledata($id);

                        $commission = $this->db->get_where('customer_comission', ['customer_id' => $id, 'status' => 1])->row();
                        if ($commission) {
                            $data['customer']->comission_value = $commission->commision_value;
                            $data['customer']->comission_type = $commission->comission_type;
                            $data['customer']->comission_note = $commission->notes;
                        }
                    }

                    $data['module'] = "customer";
                    $data['page']   = "form";
                    echo Modules::run('template/layout', $data);
                }
            }
        }

        private function random_password($length = 8) {
            return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!'), 0, $length);
        }



    public function paysenz_delete($id) {
        if ($this->customer_model->delete($id)) {
            echo display('delete_successfully');
        } else {
            display('please_try_again');
        }
    }

    public function customer_search($id){
       $data["customers"] = $this->customer_model->individual_info($id);
        $this->load->view('customer_search', $data);
    }

    public function paysenz_customer_ledger() {
    $data['title']             = display('customer_ledger'); 
    $config["base_url"]        = base_url('customer_ledger');
    $config["total_rows"]      = $this->customer_model->count_customer_ledger();
    $config["per_page"]        = 10;
    $config["uri_segment"]     = 2;
    $config["last_link"]       = "Last"; 
    $config["first_link"]      = "First"; 
    $config['next_link']       = 'Next';
    $config['prev_link']       = 'Prev';  
    $config['full_tag_open']   = "<ul class='pagination col-xs pull-right'>";
    $config['full_tag_close']  = "</ul>";
    $config['num_tag_open']    = '<li>';
    $config['num_tag_close']   = '</li>';
    $config['cur_tag_open']    = "<li class='disabled'><li class='active'><a href='#'>";
    $config['cur_tag_close']   = "<span class='sr-only'></span></a></li>";
    $config['next_tag_open']   = "<li>";
    $config['next_tag_close']  = "</li>";
    $config['prev_tag_open']   = "<li>";
    $config['prev_tagl_close'] = "</li>";
    $config['first_tag_open']  = "<li>";
    $config['first_tagl_close']= "</li>";
    $config['last_tag_open']   = "<li>";
    $config['last_tagl_close'] = "</li>";
    $this->pagination->initialize($config);
    $page                      = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
    $data["ledgers"]           = $this->customer_model->customer_ledgerdata($config["per_page"], $page);
    $data["links"]             = $this->pagination->create_links();
    $data['customer']          = $this->customer_model->customer_list_ledger();
    $data['customer_name']     = '';
    $data['customer_id']       = '';
    $data['address']           ='';
    $data['module']            = "customer";
    $data['page']              = "customer_ledger";   
    echo Modules::run('template/layout', $data); 
    }

    public function paysenz_customer_ledgerData() {
    $start                 = $this->input->post('from_date',true);
    $end                   = $this->input->post('to_date',true);
    $customer_id           = $this->input->post('customer_id',true);
    $customer_detail       = $this->customer_model->customer_personal_data($customer_id);
    $data['title']         = display('customer_ledger');
    $data['customer']      = $this->customer_model->customer_list_ledger();
    $data["ledgers"]       = $this->customer_model->customerledger_searchdata($customer_id, $start, $end);
    $data['customer_name'] = $customer_detail[0]['customer_name'];
    $data['customer_id']   = $customer_id;
    $data['address']       = $customer_detail[0]['customer_address'];
    $data['module']        = "customer";
    $data["links"]         = '';
    $data['page']          = "customer_ledger";   
    echo Modules::run('template/layout', $data); 
    }


    public function paysenz_customer_advance() {
    $data['title']        = display('customer_advance');    
    $data['customer_list']= $this->customer_model->customer_list_advance();
    $data['module']       = "customer";
    $data['page']         = "customer_advance";   
    echo Modules::run('template/layout', $data); 
    }

      public function insert_customer_advance(){
        $advance_type = $this->input->post('type',TRUE);
        if($advance_type ==1){
            $dr = $this->input->post('amount',TRUE);
            $tp = 'd';
        }else{
            $cr = $this->input->post('amount',TRUE);
            $tp = 'c';
        }
            $createby      = $this->session->userdata('id');
            $createdate    = date('Y-m-d H:i:s');
            $transaction_id= $this->customer_model->generator(10);
            $customer_id   = $this->input->post('customer_id',TRUE);
            $cusifo        = $this->db->select('*')->from('customer_information')->where('customer_id',$customer_id)->get()->row();
            $headn         = $customer_id.'-'.$cusifo->customer_name;
            $coainfo       = $this->db->select('*')->from('acc_coa')->where('customer_id',$customer_id)->get()->row();
    $customer_headcode = $coainfo->HeadCode;
              
                   $customer_accledger = array(
      'VNo'            =>  $transaction_id,
      'Vtype'          =>  'Advance',
      'VDate'          =>  date("Y-m-d"),
      'COAID'          =>  $customer_headcode,
      'Narration'      =>  'Merchant Advance For  '.$cusifo->customer_name,
      'Debit'          =>  (!empty($dr)?$dr:0),
      'Credit'         =>  (!empty($cr)?$cr:0),
      'IsPosted'       => 1,
      'CreateBy'       => $this->session->userdata('id'),
      'CreateDate'     => date('Y-m-d H:i:s'),
      'IsAppove'       => 1
    );
                         $cc = array(
      'VNo'            =>  $transaction_id,
      'Vtype'          =>  'Advance',
      'VDate'          =>  date("Y-m-d"),
      'COAID'          =>  111000001,
      'Narration'      =>  'Cash in Hand  For '.$cusifo->customer_name.' Advance',
      'Debit'          =>  (!empty($dr)?$dr:0),
      'Credit'         =>  (!empty($cr)?$cr:0),
      'IsPosted'       =>  1,
      'CreateBy'       =>  $this->session->userdata('id'),
      'CreateDate'     =>  date('Y-m-d H:i:s'),
      'IsAppove'       =>  1
    ); 
                  
       $this->db->insert('acc_transaction',$customer_accledger);
       $this->db->insert('acc_transaction',$cc);
       redirect(base_url('advance_receipt/'.$transaction_id.'/'.$customer_id));

  }

  //customer_advance_receipt
   public function customer_advancercpt($receiptid=null,$customer_id=null) {
    $data['title']         = display('advance_receipt'); 
    $customer_id           = $this->uri->segment(3);
    $receiptdata           = $this->customer_model->advance_details($receiptid,$customer_id);
    $customer_details      = $this->customer_model->customer_personal_data($customer_id);
    $data['details']       = $receiptdata;
    $data['customer_name'] = $customer_details[0]['customer_name'];
    $data['receipt_no']    = $receiptdata[0]['VNo'];
    $data['address']       = $customer_details[0]['customer_address'];
    $data['mobile']        = $customer_details[0]['customer_mobile'];
    $data['module']        = "customer";
    $data['page']          = "customer_advance_receipt";   
    echo Modules::run('template/layout', $data); 
    }

}

