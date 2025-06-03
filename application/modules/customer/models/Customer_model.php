<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Customer_model extends CI_Model {

     
    public function create($data = array())
{
    // Extract commission-related fields (if present)
    $commission_data = [];
    if (isset($data['comission_type']) || isset($data['commision_value']) || isset($data['comission_note'])) {
        $commission_data = [
            'comission_type'  => $data['comission_type'],
            'commision_value' => $data['commision_value'],
            'notes'           => $data['comission_note'],
            'create_by'       => $this->session->userdata('id'),
            'status'          => 1,
            'create_date'     => date('Y-m-d H:i:s'),
            'update_date'     => date('Y-m-d H:i:s'),
        ];
        unset($data['comission_type'], $data['commision_value'], $data['comission_note']);
    }

    $data['create_by'] = $this->session->userdata('id');

    // Insert into customer_information
    $this->db->insert('customer_information', $data);
    $customer_id = $this->db->insert_id();

    if ($customer_id) {
        // Create COA entry
        $coa = $this->headcode();
        $headcode = ($coa->HeadCode != NULL) ? $coa->HeadCode + 1 : "113100000001";

        $c_acc = $customer_id . '-' . $data['customer_name'];
        $createdate = date('Y-m-d H:i:s');

        $customer_coa = [
            'HeadCode'         => $headcode,
            'HeadName'         => $c_acc,
            'PHeadName'        => 'Merchants',
            'HeadLevel'        => '4',
            'IsActive'         => '1',
            'IsTransaction'    => '1',
            'IsGL'             => '0',
            'HeadType'         => 'A',
            'IsBudget'         => '0',
            'IsDepreciation'   => '0',
            'DepreciationRate' => '0',
            'customer_id'      => $customer_id,
            'CreateBy'         => $this->session->userdata('id'),
            'CreateDate'       => $createdate,
        ];

        $this->db->insert('acc_subcode', [
            'subTypeId'    => 3,
            'name'         => $data['customer_name'],
            'referenceNo'  => $customer_id,
            'status'       => 1,
            'created_date' => date("Y-m-d"),
        ]);

        // Insert into customer_comission
        if (!empty($commission_data)) {
            $commission_data['customer_id'] = $customer_id;
            $this->db->insert('customer_comission', $commission_data);
        }
    }

    return true;
}

	public function customer_dropdown()
	{
		$data =  $this->db->select("*")
			->from('customer_information')
			->order_by('customer_name', 'asc')
			->get()
			->result();

      $list[''] = display('select_option');
    if (!empty($data)) {
      foreach($data as $value)
        $list[$value->customer_id] = $value->customer_name;
      return $list;
    } else {
      return false; 
    }
	}

  //credit customer dropdown
    public function paysenz_credit_customer_dropdown()
  {
    $data =  $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('customer_information a')
      ->join('acc_coa b','a.customer_id = b.customer_id','left')
      ->having('balance > 0')
      ->group_by('a.customer_id')
      ->order_by('a.customer_name', 'asc')
      ->get()
      ->result();

      $list[''] = display('select_option');
    if (!empty($data)) {
      foreach($data as $value)
        $list[$value->customer_id] = $value->customer_name;
      return $list;
    } else {
      return false; 
    }
  }


  // paid customer dropdown
   public function paysenz_paid_customer_dropdown()
  {
    $data =  $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('customer_information a')
      ->join('acc_coa b','a.customer_id = b.customer_id','left')
      ->having('balance <= 0')
      ->group_by('a.customer_id')
      ->order_by('a.customer_name', 'asc')
      ->get()
      ->result();

      $list[''] = display('select_option');
    if (!empty($data)) {
      foreach($data as $value)
        $list[$value->customer_id] = $value->customer_name;
      return $list;
    } else {
      return false; 
    }
  }

	public function customer_list($offset=null, $limit=null)
    {
  

        return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
			->from('customer_information a')
			->join('acc_coa b','a.customer_id = b.customer_id','left')
			->group_by('a.customer_id')
			->order_by('a.customer_name', 'asc')
			->limit($offset, $limit)
			->get()
			->result();

         
    }


    public function getCustomerList($postData = null)
{
    log_message('error', '========= getCustomerList() START =========');

    $response = array();
    log_message('error', 'POST Data: ' . json_encode($postData));

    $customer_id = $this->input->post('customer_id');
    $custom_data = $this->input->post('customfiled');

    log_message('error', 'Received customer_id: ' . json_encode($customer_id));
    log_message('error', 'Received customfiled: ' . json_encode($custom_data));

    if (!empty($custom_data)) {
        $cus_data = [''];
        foreach ($custom_data as $cusd) {
            $cus_data[] = $cusd;
        }
        log_message('error', 'Processed custom_data: ' . json_encode($cus_data));
    }

    $draw = (int) $postData['draw'];
    $start = (int) $postData['start'];
    $rowperpage = (int) $postData['length'];
    $columnIndex = (int) $postData['order'][0]['column'];
    $columnName = $postData['columns'][$columnIndex]['data'];
    $columnSortOrder = $postData['order'][0]['dir'];
    $searchValue = $postData['search']['value'];

    log_message('error', "Pagination info: start=$start, length=$rowperpage");
    log_message('error', "Ordering: column=$columnName, direction=$columnSortOrder");
    log_message('error', "Search value: $searchValue");

    $this->db->select('count(*) as allcount');
    $this->db->from('customer_information a');
    $this->db->join('acc_coa b', 'a.customer_id = b.customer_id', 'left');
    $this->db->group_by('a.customer_id');
    $totalRecords = $this->db->get()->num_rows();
    log_message('error', 'Total unfiltered records: ' . $totalRecords);

    $this->db->select("
        a.customer_id, 
        a.customer_name, 
        a.customer_address, 
        a.customer_mobile, 
        a.customer_email, 
        a.email_address AS vat_no, 
        a.sales_permit_number, 
        a.sales_permit, 
        a.zip, 
        a.country, 
        a.status, 
        (
            COALESCE((SELECT SUM(Debit - Credit) FROM acc_transaction WHERE subCode = s.id AND subType = 3 AND IsAppove = 1), 0) 
            + 
            COALESCE((SELECT SUM(due_amount + prevous_due) FROM invoice WHERE customer_id = a.customer_id), 0)
        ) AS balance
    ");
    $this->db->from('customer_information a');
    $this->db->join('acc_coa b', 'a.customer_id = b.customer_id', 'left');
    $this->db->join('acc_subcode s', 'a.customer_id = s.referenceNo AND s.subTypeId = 3', 'left');

    if (!empty($searchValue)) {
        $this->db->group_start();
        $this->db->like('a.customer_name', $searchValue);
        $this->db->or_like('a.customer_mobile', $searchValue);
        $this->db->or_like('a.customer_email', $searchValue);
        $this->db->or_like('a.zip', $searchValue);
        $this->db->or_like('a.country', $searchValue);
        $this->db->or_like('a.sales_permit_number', $searchValue); // ✅ SP No search enabled
        $this->db->group_end();
    }

    $this->db->group_by('a.customer_id');
    $this->db->order_by($columnName, $columnSortOrder);

    if ($rowperpage !== -1) {
        log_message('error', "Applying LIMIT: $rowperpage OFFSET: $start");
        $this->db->limit($rowperpage, $start);
    }

    $records = $this->db->get()->result();
    log_message('error', 'Records fetched from DB: ' . count($records));

    $data = [];
    $sl = 1;
    foreach ($records as $record) {
        $button = '';
        $base_url = base_url();

        if ($this->permission1->method('manage_customer', 'update')->access()) {
            $button .= '<a href="' . $base_url . 'edit_customer/' . $record->customer_id . '" class="btn btn-info btn-xs m-b-5 custom_btn" title="Update"><i class="pe-7s-note"></i></a>';
        }
        if ($this->permission1->method('manage_customer', 'delete')->access()) {
            $button .= '<a onclick="customerdelete(' . $record->customer_id . ')" href="javascript:void(0)" class="btn btn-danger btn-xs m-b-5 custom_btn" title="Delete"><i class="pe-7s-trash"></i></a>';
        }

        $data[] = [
            'sl'                  => $sl++,
            'customer_name'       => $record->customer_name,
            'address'             => $record->customer_address,
            'mobile'              => $record->customer_mobile,
            'email'               => $record->customer_email,
            'vat_no'              => $record->vat_no,
            'sales_permit_number' => $record->sales_permit_number,
            'sales_permit'        => !empty($record->sales_permit) ? '<a href="' . base_url('uploads/sales_permits/' . $record->sales_permit) . '" target="_blank">View File</a>' : 'N/A',
            'zip'                 => $record->zip,
            'country'             => $record->country,
            'balance'             => (!empty($record->balance) ? $record->balance : 0),
            'status'              => $record->status,
            'button'              => $button,
        ];
    }

    $response = [
        "draw" => $draw,
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => count($records),
        "aaData" => $data
    ];

    log_message('error', 'JSON Response: ' . json_encode($response));
    log_message('error', '========= getCustomerList() END =========');

    return $response;
}



public function getCreditCustomerList($postData=null){

  $response = array();
  $customer_id =  $this->input->post('customer_id');
  $custom_data = $this->input->post('customfiled');
  if(!empty($custom_data)){
      $cus_data = [''];
      foreach ($custom_data as $cusd) {
          $cus_data[] = $cusd;
      }
  }

  ## Read value
  $draw = $postData['draw'];
  $start = $postData['start'];
  $rowperpage = $postData['length'];
  $columnIndex = $postData['order'][0]['column'];
  $columnName = $postData['columns'][$columnIndex]['data'];
  $columnSortOrder = $postData['order'][0]['dir'];
  $searchValue = $postData['search']['value'];

  ## Search Query
  $searchQuery = "";
  if($searchValue != ''){
      $searchQuery = " (a.customer_name like '%".$searchValue."%' 
          OR a.customer_mobile like '%".$searchValue."%' 
          OR a.customer_email like '%".$searchValue."%' 
          OR a.phone like '%".$searchValue."%' 
          OR a.customer_address like '%".$searchValue."%' 
          OR a.country like '%".$searchValue."%' 
          OR a.state like '%".$searchValue."%' 
          OR a.zip like '%".$searchValue."%' 
          OR a.city like '%".$searchValue."%') ";
  }

  ## Fetching Customer Balances
  $this->db->select("a.*, 
      ( 
          COALESCE(SUM(t.Debit - t.Credit), 0) 
          + COALESCE(SUM(i.due_amount + i.previous_due), 0)
      ) AS balance");
  $this->db->from('customer_information a');
  $this->db->join('acc_transaction t', 'a.customer_id = t.referenceNo AND t.IsAppove = 1', 'left');
  $this->db->join('invoice i', 'a.customer_id = i.customer_id', 'left');
  $this->db->group_by('a.customer_id');

  if(!empty($customer_id)){
      $this->db->where('a.customer_id', $customer_id);
  }
  if(!empty($custom_data)){
      $this->db->where_in('a.customer_id', $cus_data);
  }
  if($searchValue != '') {
      $this->db->where($searchQuery);
  }
  $this->db->having('balance > 0');
  $this->db->order_by($columnName, $columnSortOrder);
  $this->db->limit($rowperpage, $start);
  $records = $this->db->get()->result();

  ## Data Formatting
  $data = array();
  $sl = 1;
  foreach($records as $record ){
      $button = '';
      $base_url = base_url();

      if($this->permission1->method('credit_customer','update')->access()){
          $button .=' <a href="'.$base_url.'edit_customer/'.$record->customer_id.'" class="btn btn-info btn-xs m-b-5 custom_btn" data-toggle="tooltip" title="Update"><i class="pe-7s-note"></i></a>';
      }
      if($this->permission1->method('credit_customer','delete')->access()){
          $button .=' <a onclick="customerdelete('.$record->customer_id.')" href="javascript:void(0)" class="btn btn-danger btn-xs m-b-5 custom_btn" data-toggle="tooltip" title="Delete"><i class="pe-7s-trash"></i></a>';
      }

      $data[] = array( 
          'sl'               =>$sl,
          'customer_name'    =>$record->customer_name,
          'address'          =>$record->customer_address,
          'mobile'           =>$record->customer_mobile,
          'email'            =>$record->customer_email,
          'city'             =>$record->city,
          'state'            =>$record->state,
          'zip'              =>$record->zip,
          'country'          =>$record->country,
          'balance'          =>(!empty($record->balance)?$record->balance:0),
          'button'           =>$button,
      ); 
      $sl++;
  }

  ## Response
  $response = array(
      "draw" => intval($draw),
      "iTotalRecords" => count($records),
      "iTotalDisplayRecords" => count($records),
      "aaData" => $data
  );

  return $response; 
}

    //paid customer list
     public function paysenz_getPaidCustomerList($postData=null){

         $response = array();
         $customer_id =  $this->input->post('customer_id');
         $custom_data = $this->input->post('customfiled');
         if(!empty($custom_data)){
         $cus_data = [''];
         foreach ($custom_data as $cusd) {
           $cus_data[] = $cusd;
         }
       }
    
         ## Read value
         $draw = $postData['draw'];
         $start = $postData['start'];
         $rowperpage = $postData['length']; // Rows display per page
         $columnIndex = $postData['order'][0]['column']; // Column index
         $columnName = $postData['columns'][$columnIndex]['data']; // Column name
         $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
         $searchValue = $postData['search']['value']; // Search value

         ## Search 
         $searchQuery = "";
         if($searchValue != ''){
            $searchQuery = " (a.customer_name like '%".$searchValue."%' or a.customer_mobile like '%".$searchValue."%' or a.customer_email like '%".$searchValue."%'or a.phone like '%".$searchValue."%' or a.customer_address like '%".$searchValue."%' or a.country like '%".$searchValue."%' or a.state like '%".$searchValue."%' or a.zip like '%".$searchValue."%' or a.city like '%".$searchValue."%') ";
         }

         ## Total number of records without filtering
         $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)) as balance");
         $this->db->from('customer_information a');
         $this->db->join('acc_coa b','a.customer_id = b.customer_id','left');
         
         if(!empty($customer_id)){
             $this->db->where('a.customer_id',$customer_id);
         }
         if(!empty($custom_data)){
             $this->db->where_in('a.customer_id',$cus_data);
         }
          if($searchValue != '')
         $this->db->where($searchQuery);
         $this->db->having('balance <= 0'); 
         $this->db->group_by('a.customer_id');
         $totalRecords =$this->db->get()->num_rows();

         ## Total number of record with filtering
         $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)) as balance");
         $this->db->from('customer_information a');
         $this->db->join('acc_coa b','a.customer_id = b.customer_id','left');
         if(!empty($customer_id)){
             $this->db->where('a.customer_id',$customer_id);
         }
          if(!empty($custom_data)){
             $this->db->where_in('a.customer_id',$cus_data);
         }
         if($searchValue != '')
            $this->db->where($searchQuery);
           $this->db->having('balance <= 0');
           $this->db->group_by('a.customer_id');
         $totalRecordwithFilter = $this->db->get()->num_rows();

         ## Fetch records
         $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode` AND IsAppove = 1)) as balance");
         $this->db->from('customer_information a');
         $this->db->join('acc_coa b','a.customer_id = b.customer_id','left');
         $this->db->group_by('a.customer_id');
          if(!empty($customer_id)){
             $this->db->where('a.customer_id',$customer_id);
         }
          if(!empty($custom_data)){
             $this->db->where_in('a.customer_id',$cus_data);
         }
         if($searchValue != '')
         $this->db->where($searchQuery);
         $this->db->having('balance <= 0');
         $this->db->order_by($columnName, $columnSortOrder);
         $this->db->limit($rowperpage, $start);
         $this->db->group_by('a.customer_id');
         $records = $this->db->get()->result();
         $data = array();
         $sl =1;
  
         foreach($records as $record ){
          $button = '';
          $base_url = base_url();
 
          if($this->permission1->method('paid_customer','update')->access()){
            $button .=' <a href="'.$base_url.'edit_customer/'.$record->customer_id.'" class="btn btn-info btn-xs m-b-5 custom_btn" data-toggle="tooltip" data-placement="left" title="Update"><i class="pe-7s-note" aria-hidden="true"></i></a>';
          }
          if($this->permission1->method('paid_customer','delete')->access()){
            $button .=' <a onclick="customerdelete('.$record->customer_id.')" href="javascript:void(0)"  class="btn btn-danger btn-xs m-b-5 custom_btn" data-toggle="tooltip" data-placement="right" title="Delete "><i class="pe-7s-trash" aria-hidden="true"></i></a>';
          }


        
               
            $data[] = array( 
                'sl'               =>$sl,
                'customer_name'    =>$record->customer_name,
                'address'          =>$record->customer_address,
                'address2'         =>$record->address2,
                'mobile'           =>$record->customer_mobile,
                'phone'            =>$record->phone,
                'email'            =>$record->customer_email,
                'email_address'    =>$record->email_address,
                'contact'          =>$record->contact,
                'fax'              =>$record->fax,
                'city'             =>$record->city,
                'state'            =>$record->state,
                'zip'              =>$record->zip,
                'country'          =>$record->country,
                'balance'          =>(!empty($record->balance)?$record->balance:0),
                'button'           =>$button,
                
            ); 
            $sl++;
         }

         ## Response
         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecords,
            "aaData" => $data
         );

         return $response; 
    }

    public function individual_info($id){
      return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('customer_information a')
      ->join('acc_coa b','a.customer_id = b.customer_id','left')
      ->where('a.customer_id',$id)
      ->group_by('a.customer_id')
      ->order_by('a.customer_name', 'asc')
      ->get()
      ->result();
    }

    public function credit_customer($offset=null, $limit=null)
    {
  

        return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
			->from('customer_information a')
			->join('acc_coa b','a.customer_id = b.customer_id','left')
			->having('balance > 0') 
			->group_by('a.customer_id')
			->order_by('a.customer_name', 'asc')
			->limit($offset, $limit)
			->get()
			->result();

         
    }


     public function count_credit_customer()
    {
        return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
			->from('customer_information a')
			->join('acc_coa b','a.customer_id = b.customer_id','left')
			->having('balance > 0') 
			->group_by('a.customer_id')
			->order_by('a.customer_name', 'asc')
			->get()
			->num_rows();

         
    }

	public function singledata($id = null)
	{
		return $this->db->select('*')
			->from('customer_information')
			->where('customer_id', $id)
			->get()
			->row();
	}

  public function allcustomer()
  {
    return $this->db->select('*')
      ->from('customer_information')
      ->get()
      ->result();
  }

  public function paysenz_all_credit_customer(){

   return $data =  $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('customer_information a')
      ->join('acc_coa b','a.customer_id = b.customer_id','left')
      ->having('balance > 0')
      ->group_by('a.customer_id')
      ->order_by('a.customer_name', 'asc')
      ->get()
      ->result();
  }

    public function paysenz_all_paid_customer(){

   return $data =  $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('customer_information a')
      ->join('acc_coa b','a.customer_id = b.customer_id','left')
      ->having('balance <= 0')
      ->group_by('a.customer_id')
      ->order_by('a.customer_name', 'asc')
      ->get()
      ->result();
  }

    public function update($data = array())
    {
        log_message('debug', '[CustomerUpdate] Starting customer update process');

        if (!array_key_exists('status', $data)) {
            $data['status'] = $this->input->post('status', TRUE);
        }

        $customer_id = $data["customer_id"];
        log_message('debug', "[CustomerUpdate] Updating customer_id={$customer_id}");

        $existing_customer = $this->db->get_where('customer_information', ['customer_id' => $customer_id])->row();
        $existing_status = $existing_customer->status;
        $existing_email  = $existing_customer->customer_email;
        $existing_name   = $existing_customer->customer_name;

        log_message('debug', "[CustomerUpdate] Existing status={$existing_status}, name={$existing_name}, email={$existing_email}");

        $this->db->select('sales_permit');
        $this->db->from('customer_information');
        $this->db->where('customer_id', $customer_id);
        $existing = $this->db->get()->row();

        if (!empty($_FILES['sales_permit']['name'])) {
            log_message('debug', '[CustomerUpdate] New sales permit file uploaded');

            $config['upload_path']   = './uploads/sales_permits/';
            $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
            $config['max_size']      = 2048;
            $config['file_name']     = time() . '_' . $_FILES['sales_permit']['name'];

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('sales_permit')) {
                $upload_data = $this->upload->data();
                $data['sales_permit'] = $upload_data['file_name'];
                log_message('debug', '[CustomerUpdate] File uploaded successfully: ' . $data['sales_permit']);

                if (!empty($existing->sales_permit)) {
                    $old_file = './uploads/sales_permits/' . $existing->sales_permit;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                        log_message('debug', '[CustomerUpdate] Old sales permit file removed: ' . $existing->sales_permit);
                    }
                }
            } else {
                log_message('error', '[CustomerUpdate] File upload error: ' . $this->upload->display_errors());
                $this->session->set_flashdata('exception', $this->upload->display_errors());
                redirect($_SERVER['HTTP_REFERER']);
                return false;
            }
        } else {
            $data['sales_permit'] = $existing->sales_permit;
            log_message('debug', '[CustomerUpdate] No new file uploaded. Using existing sales permit.');
        }

        $this->db->where('customer_id', $customer_id)->update("customer_information", $data);
        log_message('debug', '[CustomerUpdate] customer_information updated');

        $this->db->where('referenceNo', $customer_id)->where('subTypeId', 3)->update('acc_subcode', ['name' => $data['customer_name']]);
        log_message('debug', '[CustomerUpdate] acc_subcode updated with name=' . $data['customer_name']);

        if (!empty($this->input->post('comission_type')) || !empty($this->input->post('comission_value')) || !empty($this->input->post('comission_note'))) {
            $this->db->where('customer_id', $customer_id)->where('status', 1)->update('customer_comission', ['status' => 0]);
            $commission_data = [
                'customer_id'     => $customer_id,
                'comission_type'  => $this->input->post('comission_type', true),
                'commision_value' => $this->input->post('comission_value', true),
                'notes'           => $this->input->post('comission_note', true),
                'create_by'       => $this->session->userdata('id'),
                'status'          => 1,
                'create_date'     => date('Y-m-d H:i:s'),
                'update_date'     => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('customer_comission', $commission_data);
            log_message('info', "[CustomerUpdate] Inserted new commission and archived old ones for customer_id={$customer_id}");
        }

        if ($existing_status != $data['status']) {
            $status_text = match ((int)$data['status']) {
                0 => 'Inactive',
                1 => 'Active',
                2 => 'Deleted',
                default => 'Unknown',
            };

            log_message('debug', "[CustomerUpdate] Status change detected: {$existing_status} → {$data['status']}");

            $this->load->library('sendmail_lib');

            // Admin Email
            $admin_subject = "Customer Status Updated";
            $admin_message = "
                <h3>Status Change Notification</h3>
                <p>The status for customer <strong>{$existing_name}</strong> (ID: {$customer_id}) has been updated.</p>
                <p><strong>New Status:</strong> {$status_text}</p>
            ";
            $this->sendmail_lib->send(
                'faizshiraji@gmail.com',
                $admin_subject,
                $admin_message,
                'noreply@hostelevate.com',
                'DeshiShad Alert System'
            );
            log_message('debug', "[CustomerUpdate] Admin email sent for customer_id={$customer_id}");

            switch ((int)$data['status']) {
                case 0:
                    $customer_subject = "Your DeshiShad Account is Inactive";
                    $customer_message = "<h3>Dear {$existing_name},</h3><p>Your DeshiShad account has been marked as <strong>Inactive</strong> by the admin team.</p><p>Please contact support for details.</p>";
                    $notification_body = "Your account has been set to Inactive.";
                    break;
                case 1:
                    $customer_subject = "Your DeshiShad Account is Activated";
                    $customer_message = "<h3>Dear {$existing_name},</h3><p>Your DeshiShad Merchant Account has been <strong>activated</strong>. You can now log in.</p>";
                    $notification_body = "Welcome! Your account has been activated.";
                    break;
                case 2:
                    $customer_subject = "Your DeshiShad Account is Deleted";
                    $customer_message = "<h3>Dear {$existing_name},</h3><p>Your DeshiShad account has been <strong>deleted</strong> by the admin.</p>";
                    $notification_body = "Your account has been deleted.";
                    break;
                default:
                    $customer_subject = "Your Account Status Updated";
                    $customer_message = "<h3>Dear {$existing_name},</h3><p>Your account status is now: <strong>{$status_text}</strong>.</p>";
                    $notification_body = "Your account status has changed.";
            }

            $fcmToken = $existing_customer->fcm_token ?? null;
            if (!empty($fcmToken)) {
                $this->load->helper('firebase');
                log_message('debug', "[CustomerUpdate] Sending FCM to token={$fcmToken}");

                $fcm_response = send_firebase_notification($fcmToken, $customer_subject, $notification_body);
                log_message('info', "[FCM] Notification sent to {$existing_email}, response: " . json_encode($fcm_response));
            } else {
                log_message('info', "[FCM] No FCM token found for customer_id={$customer_id}");
            }

            $this->sendmail_lib->send(
                $existing_email,
                $customer_subject,
                $customer_message,
                'noreply@hostelevate.com',
                'DeshiShad'
            );

            log_message('info', "[CustomerUpdate] Customer email sent for customer_id={$customer_id}");
        }

        log_message('debug', "[CustomerUpdate] Update process complete for customer_id={$customer_id}");
        return true;
    }

	public function delete($id = null)
	{
    $this->db->where('referenceNo', $id)
                 ->where('subTypeId', 3)
                 ->delete('acc_subcode');

		return $this->db->where('customer_id', $id)
			->delete("customer_information");
	}


	   public function headcode(){
        $query=$this->db->query("SELECT MAX(HeadCode) as HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '113100%'");
        return $query->row();

    }


    public function previous_balance_add($balance, $customer_id) {
    $cusifo = $this->db->select('*')->from('customer_information')->where('customer_id',$customer_id)->get()->row();
    $headn = $customer_id.'-'.$cusifo->customer_name;
    $coainfo = $this->db->select('*')->from('acc_coa')->where('HeadName',$headn)->get()->row();
    $customer_headcode = $coainfo->HeadCode;
        $transaction_id = $this->generator(10);
       

// Customer debit for previous balance
      $cosdr = array(
      'VNo'            =>  $transaction_id,
      'Vtype'          =>  'PR Balance',
      'VDate'          =>  date("Y-m-d"),
      'COAID'          =>  $customer_headcode,
      'Narration'      =>  'Merchant debit For '.$cusifo->customer_name,
      'Debit'          =>  $balance,
      'Credit'         =>  0,
      'IsPosted'       => 1,
      'CreateBy'       => $this->session->userdata('id'),
      'CreateDate'     => date('Y-m-d H:i:s'),
      'IsAppove'       => 1
    );
       $inventory = array(
      'VNo'            =>  $transaction_id,
      'Vtype'          =>  'PR Balance',
      'VDate'          =>  date("Y-m-d"),
      'COAID'          =>  1141,
      'Narration'      =>  'Inventory credit For Old sale For'.$cusifo->customer_name,
      'Debit'          =>  0,
      'Credit'         =>  $balance,//purchase price asbe
      'IsPosted'       => 1,
      'CreateBy'       => $this->session->userdata('id'),
      'CreateDate'     => date('Y-m-d H:i:s'),
      'IsAppove'       => 1
    ); 

       
        if(!empty($balance)){
           $this->db->insert('acc_transaction', $cosdr); 
           $this->db->insert('acc_transaction', $inventory); 
        }
    }



public function generator($lenth)
    {
        $number=array("A","B","C","D","E","F","G","H","I","J","K","L","N","M","O","P","Q","R","S","U","V","T","W","X","Y","Z","1","2","3","4","5","6","7","8","9","0");
    
        for($i=0; $i<$lenth; $i++)
        {
            $rand_value=rand(0,34);
            $rand_number=$number["$rand_value"];
        
            if(empty($con))
            { 
            $con=$rand_number;
            }
            else
            {
            $con="$con"."$rand_number";}
        }
        return $con;
    }


          public function customer_ledgerdata($per_page, $page) {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where('b.PHeadName','Merchant Receivable');
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $this->db->limit($per_page, $page);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        
        public function count_customer_ledger() {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where('b.PHeadName','Merchant Receivable');
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return false;
    }
  

      public function customer_list_ledger() {
        $this->db->select('*');
        $this->db->from('customer_information');
        $this->db->order_by('customer_name', 'asc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function customer_personal_data($customer_id) {
        $this->db->select('*');
        $this->db->from('customer_information');
        $this->db->where('customer_id', $customer_id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

           public function customerledger_searchdata($customer_id, $start, $end) {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where(array('b.customer_id' => $customer_id, 'a.VDate >=' => $start, 'a.VDate <=' => $end));
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function customer_list_advance(){
        $this->db->select('*');
        $this->db->from('customer_information');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function advance_details($transaction_id,$customer_id){

        $headcode = $this->db->select('HeadCode')->from('acc_coa')->where('customer_id',$customer_id)->get()->row();
        return $data  = $this->db->select('*')
                        ->from('acc_transaction')
                        ->where('VNo',$transaction_id)
                        ->where('COAID',$headcode->HeadCode)
                        ->get()
                        ->result_array();

    }

}

