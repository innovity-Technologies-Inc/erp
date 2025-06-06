<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Supplier_model extends CI_Model {

     
   public function create($data = array())
	{
		$add_supplier =  $this->db->insert('supplier_information', $data);

		 $supplier_id = $this->db->insert_id();
        $coa = $this->headcode();
           if($coa->HeadCode!=NULL){
                $headcode=$coa->HeadCode+1;
           }else{
                $headcode="21110000001";
            }
    $c_acc=$supplier_id.'-'.$data['supplier_name'];
    $createby=$this->session->userdata('id');
    $createdate=date('Y-m-d H:i:s');
       

    $supplier_coa = [
             'HeadCode'        => $headcode,
            'HeadName'         => $c_acc,
            'PHeadName'        => 'Suppliers',
            'HeadLevel'        => '4',
            'IsActive'         => '1',
            'IsTransaction'    => '1',
            'IsGL'             => '0',
            'HeadType'         => 'L',
            'IsBudget'         => '0',
            'supplier_id'      => $supplier_id,
            'IsDepreciation'   => '0',
            'DepreciationRate' => '0',
            'CreateBy'         => $createby,
            'CreateDate'       => $createdate,
        ];

        $sub_acc = [
            'subTypeId'   => 4,
            'name'        => $data['supplier_name'],
            'referenceNo' => $supplier_id,
            'status'      => 1,
            'created_date'=> date("Y-m-d"),
            
       ];

        if($add_supplier){
           
            $this->db->insert('acc_subcode',$sub_acc);
        }
        if(!empty($this->input->post('previous_balance'))){
        
          }
        return true;
	}

	public function supplier_dropdown()
	{
		$data =  $this->db->select("*")
			->from('supplier_information')
			->order_by('supplier_name', 'asc')
			->get()
			->result();

      $list[''] = display('select_option');
    if (!empty($data)) {
      foreach($data as $value)
        $list[$value->supplier_id] = $value->supplier_name;
      return $list;
    } else {
      return false; 
    }
	}





	public function supplier_list($offset=null, $limit=null)
    {
  

        return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
			->from('supplier_information a')
			->join('acc_coa b','a.supplier_id = b.supplier_id','left')
			->group_by('a.supplier_id')
			->order_by('a.supplier_name', 'asc')
			->limit($offset, $limit)
			->get()
			->result();

         
    }


    public function getsupplierList($postData=null){

        $response = array();
        $supplier_id =  $this->input->post('supplier_id');
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
            $searchQuery = " (a.supplier_name like '%".$searchValue."%' 
                            or a.mobile like '%".$searchValue."%' 
                            or a.emailnumber like '%".$searchValue."%'
                            or a.phone like '%".$searchValue."%' 
                            or a.address like '%".$searchValue."%' 
                            or a.country like '%".$searchValue."%' 
                            or a.state like '%".$searchValue."%' 
                            or a.zip like '%".$searchValue."%' 
                            or a.city like '%".$searchValue."%') ";
        }
    
        ## Total number of records without filtering
        $this->db->select('count(*) as allcount');
        $this->db->from('supplier_information a');
        $this->db->join('acc_coa b','a.supplier_id = b.supplier_id','left');
    
        if(!empty($supplier_id)){
            $this->db->where('a.supplier_id',$supplier_id);
        }
        if(!empty($custom_data)){
            $this->db->where_in('a.supplier_id',$cus_data);
        }
        if($searchValue != '')
            $this->db->where($searchQuery);
        $this->db->group_by('a.supplier_id');
        $totalRecords =$this->db->get()->num_rows();
    
        ## Total number of record with filtering
        $this->db->select('count(*) as allcount');
        $this->db->from('supplier_information a');
        $this->db->join('acc_coa b','a.supplier_id = b.supplier_id','left');
        if(!empty($supplier_id)){
            $this->db->where('a.supplier_id',$supplier_id);
        }
        if(!empty($custom_data)){
            $this->db->where_in('a.supplier_id',$cus_data);
        }
        if($searchValue != '')
            $this->db->where($searchQuery);
        $this->db->group_by('a.supplier_id');
        $totalRecordwithFilter = $this->db->get()->num_rows();
    
        ## Fetch records
        $this->db->select("
            a.supplier_id, 
            a.supplier_name, 
            a.address, 
            a.mobile, 
            a.emailnumber AS email, 
            a.city, 
            a.state, 
            a.zip, 
            a.country, 
            (
                COALESCE((
                    SELECT SUM(t.Debit) - SUM(t.Credit) + SUM(pp.due_amount) 
                    FROM acc_transaction t
                    JOIN product_purchase pp ON pp.purchase_id = t.referenceNo
                    WHERE pp.supplier_id = a.supplier_id
                    AND t.IsAppove = 1
                ), 0)
            ) AS balance
        ");
        $this->db->from('supplier_information a');
        $this->db->group_by('a.supplier_id');
    
        if(!empty($supplier_id)){
            $this->db->where('a.supplier_id',$supplier_id);
        }
        if(!empty($custom_data)){
            $this->db->where_in('a.supplier_id',$cus_data);
        }
        if($searchValue != '')
            $this->db->where($searchQuery);
        $this->db->order_by($columnName, $columnSortOrder);
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        
        $data = array();
        $sl =1;
    
        foreach($records as $record ){
            $button = '';
            $base_url = base_url();
    
            if($this->permission1->method('manage_supplier','update')->access()){
                $button .=' <a href="'.$base_url.'edit_supplier/'.$record->supplier_id.'" class="btn btn-info btn-xs m-b-5 custom_btn" data-toggle="tooltip" data-placement="left" title="Update"><i class="pe-7s-note" aria-hidden="true"></i></a>';
            }
            if($this->permission1->method('manage_supplier','delete')->access()){
                $button .=' <a onclick="supplierdelete('.$record->supplier_id.')" href="javascript:void(0)"  class="btn btn-danger btn-xs m-b-5 custom_btn" data-toggle="tooltip" data-placement="right" title="Delete "><i class="pe-7s-trash" aria-hidden="true"></i></a>';
            }
    
            $data[] = array( 
                'sl'               =>$sl,
                'supplier_name'    =>$record->supplier_name,
                'address'          =>$record->address,
                'address2'         =>$record->address2,
                'mobile'           =>$record->mobile,
                'phone'            =>$record->phone,
                'email'            =>$record->emailnumber,
                'email_address'    =>$record->email_address,
                'contact'          =>$record->contact,
                'fax'              =>$record->fax,
                'city'             =>$record->city,
                'state'            =>$record->state,
                'zip'              =>$record->zip,
                'country'          =>$record->country,
                'balance'          =>(!empty($record->balance) ? $record->balance : 0),
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



    // Get all suppliers
    public function get_all_suppliers() {
        $this->db->select('supplier_id, supplier_name');
        $this->db->from('suppliers'); // Make sure your supplier table name is correct
        $query = $this->db->get();
        return $query->result_array();
    }    
    
    public function individual_info($id){
      return $result = $this->db->select("a.*,b.HeadCode,((select ifnull(sum(Debit),0) from acc_transaction where COAID= `b`.`HeadCode`)-(select ifnull(sum(Credit),0) from acc_transaction where COAID= `b`.`HeadCode`)) as balance")
      ->from('supplier_information a')
      ->join('acc_coa b','a.supplier_id = b.supplier_id','left')
      ->where('a.supplier_id',$id)
      ->group_by('a.supplier_id')
      ->order_by('a.supplier_name', 'asc')
      ->get()
      ->result();
    }






	public function singledata($id = null)
	{
		return $this->db->select('*')
			->from('supplier_information')
			->where('supplier_id', $id)
			->get()
			->row();
	}

  public function allsupplier()
  {
    return $this->db->select('*')
      ->from('supplier_information')
      ->get()
      ->result();
  }




	public function update($data = array())
	{
		$updatesupplier =  $this->db->where('supplier_id', $data["supplier_id"])
			->update("supplier_information", $data);

		$supplier_id = $data["supplier_id"];
        $old_headnam = $supplier_id.'-'.$this->input->post("old_name");
        $c_acc=$supplier_id.'-'.$data["supplier_name"];
         $supplier_coa = [
             'HeadName'         => $c_acc
        ];
 

        $sub_acc = [
            'name'        => $data['supplier_name'],
          ];

        $this->db->where('referenceNo', $supplier_id)
                 ->where('subTypeId', 4)
                 ->update('acc_subcode',$sub_acc);
    
    return true;
	}

	public function delete($id = null)
	{

        $this->db->where('referenceNo', $id)
                 ->where('subTypeId', 4)
                 ->delete('acc_subcode');

		return $this->db->where('supplier_id', $id)
			->delete("supplier_information");
	}


	   public function headcode(){
         $query=$this->db->query("SELECT MAX(HeadCode) as HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '21110%'");
        return $query->row();

    }


      public function previous_balance_add($balance, $supplier_id) {
    $cusifo = $this->db->select('*')->from('supplier_information')->where('supplier_id',$supplier_id)->get()->row();
    $headn = $supplier_id.'-'.$cusifo->supplier_name;
    $coainfo = $this->db->select('*')->from('acc_coa')->where('HeadName',$headn)->get()->row();
    $supplier_headcode = $coainfo->HeadCode;
        $transaction_id = $this->generator(10);
       

// supplier debit for previous balance
      $cosdr = array(
      'VNo'            =>  $transaction_id,
      'Vtype'          =>  'PR Balance',
      'VDate'          =>  date("Y-m-d"),
      'COAID'          =>  $supplier_headcode,
      'Narration'      =>  'supplier debit For '.$cusifo->supplier_name,
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
      'Narration'      =>  'Inventory credit For Old sale For'.$cusifo->supplier_name,
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


          public function supplier_ledgerdata($per_page, $page) {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where('b.PHeadName','Suppliers');
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $this->db->limit($per_page, $page);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
         
            return $query->result_array();
        }
        return false;
    }

        
        public function count_supplier_ledger() {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where('b.PHeadName','Suppliers');
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return false;
    }
  

      public function supplier_list_ledger() {
        $this->db->select('*');
        $this->db->from('supplier_information');
        $this->db->order_by('supplier_name', 'asc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function supplier_personal_data($supplier_id) {
        $this->db->select('*');
        $this->db->from('supplier_information');
        $this->db->where('supplier_id', $supplier_id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

           public function supplierledger_searchdata($supplier_id, $start, $end) {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where(array('b.supplier_id' => $supplier_id, 'a.VDate >=' => $start, 'a.VDate <=' => $end));
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function supplier_list_advance(){
        $this->db->select('*');
        $this->db->from('supplier_information');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function advance_details($transaction_id,$supplier_id){

        $headcode = $this->db->select('HeadCode')->from('acc_coa')->where('supplier_id',$supplier_id)->get()->row();
        return $data  = $this->db->select('*')
                        ->from('acc_transaction')
                        ->where('VNo',$transaction_id)
                        ->where('COAID',$headcode->HeadCode)
                        ->get()
                        ->result_array();

    }

        public function supplier_product_sale_info($supplier_id) {
        $this->db->select('a.*,b.HeadName');
        $this->db->from('acc_transaction a');
        $this->db->join('acc_coa b','a.COAID=b.HeadCode');
        $this->db->where('b.supplier_id',$supplier_id);
        $this->db->where('a.IsAppove',1);
        $this->db->order_by('a.VDate','desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

}

