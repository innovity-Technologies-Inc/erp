<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Invoice_model extends CI_Model {


 public function customer_list(){
     $query = $this->db->select('*')
                ->from('customer_information')
                ->where('status', '1')
                ->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
 }

    public function tax_fileds(){
        return $taxfield = $this->db->select('tax_name,default_value')
                ->from('tax_settings')
                ->get()
                ->result_array();
    }

        public function pos_customer_setup() {
        $query = $this->db->select('*')
                ->from('customer_information')
                ->where('customer_name', 'Walking Merchant')
                ->get();
                if ($query->num_rows() > 0) {
                    return $query->result_array();
                }
            return false;
    }
 
      public function allproduct(){
        $this->db->select('*');
        $this->db->from('product_information');
        $this->db->order_by('product_name','asc');
        $this->db->limit(30);
        $query   = $this->db->get();
        $itemlist=$query->result();
        return $itemlist;
    }

    public function vat_tax_setting(){
        $this->db->select('*');
        $this->db->from('vat_tax_setting');
        $query   = $this->db->get();
        return $query->row();
    }


   
    public function todays_invoice(){
        $this->db->select('a.*,b.customer_name');
        $this->db->from('invoice a');
        $this->db->join('customer_information b', 'b.customer_id = a.customer_id','left');
        $this->db->where('a.date',date('Y-m-d'));
        $this->db->order_by('a.invoice', 'desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

    public function customer_dropdown(){
        $data = $this->db->select("*")
            ->from('customer_information')
            ->get()
            ->result();

        $list[''] = 'Select Merchant';
        if (!empty($data)) {
            foreach($data as $value)
                $list[$value->customer_id] = $value->customer_name;
            return $list;
        } else {
            return false; 
        }
    }

    public function customer_search($customer_id){
        $query = $this->db->select('*')
                          ->from('customer_information')
                          ->group_start()
                          ->like('customer_name', $customer_id)
                          ->or_like('customer_mobile', $customer_id)
                          ->group_end()
                          ->limit(30)
                          ->get();
                          if ($query->num_rows() > 0) {
                              return $query->result_array();  
                          }
                          return false;
    }

    public function count_invoice() {
        return $this->db->count_all("invoice");
    }

    public function getInvoiceList($postData = null) {
        $response = array();
        $usertype = $this->session->userdata('user_type');
        $user_id = $this->session->userdata('user_id');
        $fromdate = $this->input->post('fromdate', TRUE);
        $todate = $this->input->post('todate', TRUE);
    
        $datbetween = "";
        if (!empty($fromdate) && !empty($todate)) {
            $datbetween = "(a.date BETWEEN '$fromdate' AND '$todate')";
        }
    
        ## Read values
        $draw            = isset($postData['draw']) ? $postData['draw'] : 1;
        $start           = isset($postData['start']) ? $postData['start'] : 0;
        $rowperpage      = isset($postData['length']) ? $postData['length'] : 10;
        $columnIndex     = isset($postData['order'][0]['column']) ? $postData['order'][0]['column'] : 0;
        $columnName      = isset($postData['columns'][$columnIndex]['data']) ? $postData['columns'][$columnIndex]['data'] : 'a.date';
        $columnSortOrder = isset($postData['order'][0]['dir']) ? $postData['order'][0]['dir'] : 'desc';
        $searchValue     = isset($postData['search']['value']) ? $postData['search']['value'] : '';
    
        ## Column map
        $columnMap = [
            'invoice'       => 'a.invoice',
            'salesman'      => 'u.first_name',
            'customer_name' => 'b.customer_name',
            'delivery_note' => 'a.delivery_note',
            'final_date'    => 'a.date',
            'total_amount'  => 'a.total_amount',
        ];
    
        $orderColumn = isset($columnMap[$columnName]) ? $columnMap[$columnName] : 'a.date';
    
        ## Search filter
        $searchQuery = "";
        if (!empty($searchValue)) {
            $searchQuery = " (b.customer_name LIKE '%{$searchValue}%' OR 
                              a.invoice LIKE '%{$searchValue}%' OR 
                              a.date LIKE '%{$searchValue}%' OR 
                              a.invoice_id LIKE '%{$searchValue}%' OR 
                              u.first_name LIKE '%{$searchValue}%' OR 
                              u.last_name LIKE '%{$searchValue}%')";
        }
    
        ## Count total records
        $this->db->select('COUNT(*) AS allcount');
        $this->db->from('invoice a');
        $this->db->join('customer_information b', 'b.customer_id = a.customer_id', 'left');
        $this->db->join('users u', 'u.user_id = a.sales_by', 'left');
        if ($usertype == 2) {
            $this->db->where('a.sales_by', $user_id);
        }
        if (!empty($datbetween)) {
            $this->db->where($datbetween);
        }
        if (!empty($searchQuery)) {
            $this->db->where($searchQuery);
        }
        $totalRecords = $this->db->get()->row()->allcount;
    
        ## Fetch filtered data
        $this->db->select("a.*, b.customer_name, u.first_name, u.last_name");
        $this->db->from('invoice a');
        $this->db->join('customer_information b', 'b.customer_id = a.customer_id', 'left');
        $this->db->join('users u', 'u.user_id = a.sales_by', 'left');
        if ($usertype == 2) {
            $this->db->where('a.sales_by', $user_id);
        }
        if (!empty($datbetween)) {
            $this->db->where($datbetween);
        }
        if (!empty($searchQuery)) {
            $this->db->where($searchQuery);
        }
        $this->db->order_by($orderColumn, $columnSortOrder);
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
    
        ## Delivery status mapping
        $deliveryStatusMap = [
            '0' => 'Pending',
            '1' => 'Confirmed',
            '2' => 'Picked Up',
            '3' => 'On The Way',
            '4' => 'Delivered',
            '5' => 'Cancelled'
        ];
    
        ## Data response
        $data = array();
        $sl = $start + 1;
        $base_url = base_url();
    
        foreach ($records as $record) {
            $button = '';
            $button .= ' <button type="button" class="btn btn-info btn-sm open-delivery-modal" data-id="'.$record->invoice_id.'" data-status="'.$record->delivery_note.'" title="Update Delivery"><i class="fa fa-truck"></i></button>';
            $button .= '<a href="'.$base_url.'invoice_details/'.$record->invoice_id.'" class="btn btn-success btn-sm" data-toggle="tooltip" title="'.display('invoice').'"><i class="fa fa-window-restore"></i></a>';
            $button .= ' <a href="'.$base_url.'invoice_pad_print/'.$record->invoice_id.'" class="btn btn-primary btn-sm" data-toggle="tooltip" title="'.display('pad_print').'"><i class="fa fa-fax"></i></a>';
            $button .= ' <a href="'.$base_url.'pos_print/'.$record->invoice_id.'" class="btn btn-warning btn-sm" data-toggle="tooltip" title="'.display('pos_invoice').'"><i class="fa fa-fax"></i></a>';
    
            if ($this->permission1->method('manage_invoice','update')->access()) {
                $approve = $this->db->select('status,referenceNo')
                            ->from('acc_vaucher')
                            ->where('referenceNo', $record->invoice_id)
                            ->where('status', 1)
                            ->get()->num_rows();
                if ($approve == 0 && $record->ret_adjust_amnt == '') {
                    $button .= ' <a href="'.$base_url.'invoice_edit/'.$record->invoice_id.'" class="btn btn-info btn-sm" data-toggle="tooltip" title="'.display('update').'"><i class="fa fa-pencil"></i></a>';
                }
            }
    
            $delivery_note_label = isset($deliveryStatusMap[$record->delivery_note]) ? $deliveryStatusMap[$record->delivery_note] : 'N/A';
    
            $details = '<a href="'.$base_url.'invoice_details/'.$record->invoice_id.'">'.$record->invoice.'</a>';
    
            $data[] = array(
                'sl'             => $sl++,
                'invoice'        => $details,
                'salesman'       => $record->first_name . ' ' . $record->last_name,
                'customer_name'  => $record->customer_name,
                'delivery_note'  => $delivery_note_label,
                'final_date'     => date("d-M-Y", strtotime($record->date)),
                'total_amount'   => number_format($record->total_amount, 2),
                'button'         => $button
            );
        }
    
        ## Final response
        return array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecords,
            "aaData" => $data
        );
    }

    public function count_invoice_payment() {
        return $this->db->count_all("invoice_payment");
    }

    public function getInvoicePaymentList($postData = null) {
        $response = array();
        $usertype = $this->session->userdata('user_type');
        $user_id  = $this->session->userdata('user_id');
        $fromdate = $this->input->post('fromdate', TRUE);
        $todate   = $this->input->post('todate', TRUE);
    
        $dateBetween = (!empty($fromdate) && !empty($todate)) ? "(ip.invoice_date BETWEEN '$fromdate' AND '$todate')" : "";
    
        $draw       = $postData['draw'] ?? 1;
        $start      = $postData['start'] ?? 0;
        $rowPerPage = $postData['length'] ?? 10;
    
        $columnIndex     = $postData['order'][0]['column'] ?? 0;
        $columnName      = $postData['columns'][$columnIndex]['data'] ?? 'invoice_date';
        $columnSortOrder = $postData['order'][0]['dir'] ?? 'desc';
        $searchValue     = $postData['search']['value'] ?? '';
    
        $columnMap = [
            'invoice_date'  => 'ip.invoice_date',
            'customer_name' => 'ci.customer_name',
            'salesman'      => 'u.first_name',
            'total_amount'  => 'ip.total_amount',
            'paid_amount'   => 'ip.paid_amount',
            'due_amount'    => 'ip.due_amount'
        ];
        $orderColumn = $columnMap[$columnName] ?? 'ip.invoice_date';
    
        $searchQuery = "";
        if (!empty($searchValue)) {
            $searchQuery = "(ci.customer_name LIKE '%$searchValue%' 
                            OR ip.transaction_ref LIKE '%$searchValue%' 
                            OR ip.invoice_date LIKE '%$searchValue%')";
        }
    
        // Count total records
        $this->db->select("COUNT(*) as allcount");
        $this->db->from("invoice_payment ip");
        $this->db->join("customer_information ci", "ci.customer_id = ip.customer_id", "left");
        $this->db->join("users u", "u.user_id = ip.createby", "left");
    
        if ($usertype == 2) {
            $this->db->where("ip.createby", $user_id);
        }
        if (!empty($dateBetween)) {
            $this->db->where($dateBetween);
        }
        if (!empty($searchQuery)) {
            $this->db->where($searchQuery);
        }
    
        $totalRecords = $this->db->get()->row()->allcount;
    
        // Fetch filtered records
        $this->db->select("ip.*, ci.customer_name, u.first_name, u.last_name");
        $this->db->from("invoice_payment ip");
        $this->db->join("customer_information ci", "ci.customer_id = ip.customer_id", "left");
        $this->db->join("users u", "u.user_id = ip.createby", "left");
    
        if ($usertype == 2) {
            $this->db->where("ip.createby", $user_id);
        }
        if (!empty($dateBetween)) {
            $this->db->where($dateBetween);
        }
        if (!empty($searchQuery)) {
            $this->db->where($searchQuery);
        }
    
        $this->db->order_by($orderColumn, $columnSortOrder);
        $this->db->limit($rowPerPage, $start);
        $records = $this->db->get()->result();
    
        // Format response data
        $data = array();
        $sl = $start + 1;
        $totalAmount = $totalPaid = $totalDue = 0;
    
        foreach ($records as $record) {
            $salesman = (!empty($record->first_name) || !empty($record->last_name))
                        ? $record->first_name . ' ' . $record->last_name : 'N/A';
        
            // Detect file type
            $filePath = $record->payment_ref_doc;
            $fileUrl  = base_url($filePath);
            $fileExt  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileName = basename($filePath);
        
            if (!empty($filePath)) {
                if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $imageTag = "<a href='$fileUrl' target='_blank' download='$fileName'>
                                    <img src='$fileUrl' width='50' height='50' style='object-fit:cover;' />
                                 </a>";
                } elseif ($fileExt === 'pdf') {
                    $imageTag = "<a href='$fileUrl' target='_blank' download='$fileName'>
                                    <i class='fa fa-file-pdf-o fa-2x text-danger'></i>
                                 </a>";
                } else {
                    $imageTag = "<a href='$fileUrl' target='_blank' download='$fileName'>
                                    <i class='fa fa-download'></i> Download
                                 </a>";
                }
            } else {
                $imageTag = '<span class="text-muted">No File</span>';
            }
        
            switch ($record->status) {
                case 1:
                    $statusText = '<span class="label label-success">Approved</span>';
                    break;
                case 2:
                    $statusText = '<span class="label label-warning">Pending</span>';
                    break;
                case 0:
                    $statusText = '<span class="label label-danger">Unapproved</span>';
                    break;
                default:
                    $statusText = '<span class="label label-default">N/A</span>';
                    break;
            }
        
            $button = '<button type="button" class="btn btn-warning btn-sm change-status" 
                data-id="' . $record->id . '" 
                data-status="' . $record->status . '">
                <i class="fa fa-pencil"></i> Change Status
            </button>';
        
            $data[] = array(
                'sl'              => $sl++,
                'date'            => date("d-M-Y", strtotime($record->invoice_date)),
                'payment_ref'     => $record->payment_ref ?? 'N/A',
                'payment_ref_doc' => $imageTag,
                'transaction_ref' => $record->transaction_ref ?? 'N/A',
                'salesman'        => $salesman,
                'customer_name'   => $record->customer_name ?? 'N/A',
                'total_amount'    => number_format($record->total_amount, 2),
                'paid_amount'     => number_format($record->paid_amount, 2),
                'due_amount'      => number_format($record->due_amount, 2),
                'status'          => $statusText,
                'button'          => $button
            );
        
            $totalAmount += $record->total_amount;
            $totalPaid   += $record->paid_amount;
            $totalDue    += $record->due_amount;
        }
    
        return array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecords,
            "aaData" => $data,
            "total_amount" => number_format($totalAmount, 2),
            "total_paid"   => number_format($totalPaid, 2),
            "total_due"    => number_format($totalDue, 2),
        );
    }

    public function invoice_taxinfo($invoice_id){
       return $this->db->select('*')   
            ->from('tax_collection')
            ->where('relation_id',$invoice_id)
            ->get()
            ->result_array(); 
    }

    public function retrieve_invoice_editdata($invoice_id) {
        $this->db->select('a.*, sum(c.quantity) as sum_quantity,a.id as dbinv_id, a.total_tax as taxs,a. prevous_due,b.customer_name,c.*,c.tax as total_tax,c.product_id,d.product_name,d.product_model,d.tax,d.unit,d.*');
        $this->db->from('invoice a');
        $this->db->join('customer_information b', 'b.customer_id = a.customer_id');
        $this->db->join('invoice_details c', 'c.invoice_id = a.id');
        $this->db->join('product_information d', 'd.product_id = c.product_id');
        $this->db->where('a.invoice_id', $invoice_id);
        $this->db->group_by('d.product_id');

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

    public function number_generator() {
        $this->db->select_max('invoice', 'invoice_no');
        $query      = $this->db->get('invoice');
        $result     = $query->result_array();
        $invoice_no = $result[0]['invoice_no'];
        
        if ($invoice_no != '') {
            $invoice_no = $invoice_no + 1;
        } else {
            $invoice_no = 1000;
        }
        
        return $invoice_no;
    }

public function pmethod_dropdown(){
        
        $data = $this->db->select('HeadName, HeadCode')
                ->from('acc_coa')
                ->where('PHeadName','Cash')
                ->or_where('PHeadName','Cash at Bank')
                ->get()
                ->result(); 
                
       $list[''] = 'Select Method';
       if (!empty($data)) {
        $list[0] = 'Credit Sale';
           foreach($data as $value)
               $list[$value->HeadCode] = $value->HeadName;
           return $list;
       } else {
           return false; 
       }
}
public function pmethod_dropdown_new(){
        $data = $this->db->select('*')
                ->from('acc_coa')
                ->where('PHeadName','Cash')
                ->or_where('PHeadName','Cash at Bank')
                ->get()
                ->result(); 
    
       $list[''] = 'Select Method';
       if (!empty($data)) {
        
           foreach($data as $value)
               $list[$value->HeadCode] = $value->HeadName;
           return $list;
       } else {
           return false; 
       }
}
     
public function invoice_entry($incremented_id) {
    $log_path = APPPATH . 'logs/invoice_model.log';
    file_put_contents($log_path, "\n===== [START INVOICE ENTRY] $incremented_id ===== " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($log_path, "[POST DATA] " . json_encode($_POST) . "\n", FILE_APPEND);

        $tablecolumn         = $this->db->list_fields('tax_collection');
        $num_column          = count($tablecolumn)-4;
        
        $createby            = $this->session->userdata('id');
        $createdate          = date('Y-m-d H:i:s');
        $product_id          = $this->input->post('product_id');
        $currency_details    = $this->db->select('*')->from('web_setting')->get()->result_array();
        $quantity            = $this->input->post('product_quantity',TRUE);
        $invoice_no_generated= $this->input->post('invoic_no');
        $changeamount        = $this->input->post('change',TRUE);
        $multipayamount      = $this->input->post('pamount_by_method',TRUE);
        $multipaytype        = $this->input->post('multipaytype',TRUE);
        $paidamount          = $this->input->post('paid_amount',TRUE);
        $invoice_no          = $incremented_id;
        
        file_put_contents($log_path, "[INFO] Basic data initialized\n", FILE_APPEND);

        $bank_id = $this->input->post('bank_id',TRUE);
        if(!empty($bank_id)){
            $bankname = $this->db->select('bank_name')->from('bank_add')->where('bank_id',$bank_id)->get()->row()->bank_name;
        
            $bankcoaid = $this->db->select('HeadCode')->from('acc_coa')->where('HeadName',$bankname)->get()->row()->HeadCode;
            file_put_contents($log_path, "[BANK INFO] ID: $bank_id, Name: $bankname, COAID: $bankcoaid\n", FILE_APPEND);
        }else{
            $bankcoaid='';
        }
        $available_quantity = $this->input->post('available_quantity',TRUE);
        $result = array();
        foreach ($available_quantity as $k => $v) {
            if ($v < $quantity[$k]) {
                file_put_contents($log_path, "[ERROR] Product ID {$product_id[$k]} - Requested: {$quantity[$k]}, Available: $v\n", FILE_APPEND);
                $this->session->set_userdata(array('error_message' => display('you_can_not_buy_greater_than_available_qnty')));
                redirect('Cinvoice');
            }
        }

        $customer_id = $this->input->post('customer_id',TRUE);
      
        //Full or partial Payment record.
        $paid_amount    = $this->input->post('paid_amount',TRUE);
        $transection_id = $this->generator(8);
        $tax_v = 0;
        for($j=0;$j<$num_column;$j++){
            $taxfield        = 'tax'.$j;
            $taxvalue        = 'total_tax'.$j;
            $taxdata[$taxfield]=$this->input->post($taxvalue);
            $tax_v    += $this->input->post($taxvalue);
        }
        $taxdata['customer_id'] = $customer_id;
        $taxdata['date']        = (!empty($this->input->post('invoice_date',TRUE))?$this->input->post('invoice_date',TRUE):date('Y-m-d'));
        $taxdata['relation_id'] = $invoice_no;
        if($tax_v > 0){
            $this->db->insert('tax_collection',$taxdata);
            file_put_contents($log_path, "[TAX] Inserted: " . json_encode($taxdata) . "\n", FILE_APPEND);
        }

        if ($multipaytype[0] == 0) {
            $is_credit = 1;
        }
        else {
            $is_credit = '';
        }

        $fixordyn = $this->db->select('*')->from('vat_tax_setting')->get()->row();
        $is_fixed   = '';
        $is_dynamic = '';

        if($fixordyn->fixed_tax == 1 ){
            $is_fixed   = 1;
            $is_dynamic = 0;
            $paid_tax = $this->input->post('total_vat_amnt',TRUE);
        }elseif($fixordyn->dynamic_tax == 1 ){
            $is_fixed   = 0;
            $is_dynamic = 1;
            $paid_tax = $this->input->post('total_tax',TRUE);
        }
        //Data inserting into invoice table
        $datainv = array(
            'invoice_id'      => $invoice_no,
            'customer_id'     => $customer_id,
            'date'            => (!empty($this->input->post('invoice_date',TRUE))?$this->input->post('invoice_date',TRUE):date('Y-m-d')),
            'total_amount'    => $this->input->post('grand_total_price',TRUE),
            'total_tax'       => $this->input->post('total_tax',TRUE),
            'invoice'         => $incremented_id,
            'invoice_details' => (!empty($this->input->post('inva_details',TRUE))?$this->input->post('inva_details',TRUE):'Thank you for shopping with us'),
            'delivery_note'   => $this->input->post('delivery_note',TRUE),
            'invoice_discount'=> $this->input->post('invoice_discount',TRUE),
            'total_discount'  => $this->input->post('total_discount',TRUE),
            'total_vat_amnt'  => $this->input->post('total_vat_amnt',TRUE),
            'paid_amount'     => $this->input->post('paid_amount',TRUE),
            'due_amount'      => $this->input->post('due_amount',TRUE),
            'prevous_due'     => $this->input->post('previous',TRUE),
            'shipping_cost'   => $this->input->post('shipping_cost',TRUE),
            'sales_by'        => $this->session->userdata('id'),
            'status'          => 1,
            'payment_type'    => 1,
            'bank_id'         => (!empty($this->input->post('bank_id',TRUE))?$this->input->post('bank_id',TRUE):null),
            'is_credit'       => $is_credit,
            'is_fixed'        => $is_fixed,
            'is_dynamic'      => $is_dynamic,
        );
        file_put_contents($log_path, "[INVOICE DATA] " . json_encode($datainv) . "\n", FILE_APPEND);

        $this->db->insert('invoice', $datainv);
        $inv_insert_id =  $this->db->insert_id();  

        file_put_contents($log_path, "[INVOICE INSERTED] ID: $inv_insert_id\n", FILE_APPEND);

        $prinfo  = $this->db->select('product_id,Avg(rate) as product_rate')->from('product_purchase_details')->where_in('product_id',$product_id)->group_by('product_id')->get()->result(); 
        $purchase_ave = [];
        $i=0;
        foreach ($prinfo as $avg) {
            $purchase_ave [] =  $avg->product_rate*$quantity[$i];

            file_put_contents($log_path, "[PRODUCT AVG] ID: {$avg->product_id}, Qty: {$quantity[$i]}, Rate: {$avg->product_rate}", FILE_APPEND);

            $i++;
        }
        $sumval   = array_sum($purchase_ave);
        file_put_contents($log_path, "[PRODUCT AVG TOTAL] $sumval\n", FILE_APPEND);

        $predefine_account  = $this->db->select('*')->from('acc_predefine_account')->get()->row();
        $Narration          = "Sales Voucher";
        $Comment            = "Sales Voucher for customer";
        $reVID              = $predefine_account->salesCode;

        if($multipaytype && $multipayamount){

            if ($multipaytype[0] == 0) { 

                $amount_pay = $datainv['total_amount'];
                $amnt_type  = 'Debit';
                $COAID      = $predefine_account->customerCode;
                $subcode    = $this->db->select('*')->from('acc_subcode')->where('referenceNo', $customer_id)->where('subTypeId', 3)->get()->row()->id;
                file_put_contents($log_path, "[CREDIT VOUCHER] Single Pay: COAID: $COAID, Amount: $amount_pay\n", FILE_APPEND);

                $this->insert_sale_creditvoucher($is_credit,$invoice_no,$COAID,$amnt_type,$amount_pay,$Narration,$Comment,$reVID,$subcode);

            }else {
                $amnt_type = 'Debit';
                for ($i=0; $i < count($multipaytype); $i++) {

                    $COAID = $multipaytype[$i];
                    $amount_pay = $multipayamount[$i];

                    $this->insert_sale_creditvoucher($is_credit,$invoice_no,$COAID,$amnt_type,$amount_pay,$Narration,$Comment,$reVID);
                    
                }
            }
            
        }
        // for inventory & cost of goods sold start
        $goodsCOAID     = $predefine_account->costs_of_good_solds;
        $purchasevalue  = $sumval;
        $goodsNarration = "Sales cost of goods Voucher";
        $goodsComment   = "Sales cost of goods Voucher for customer";
        $goodsreVID     = $predefine_account->inventoryCode;

        file_put_contents($log_path, "[INVENTORY VOUCHER] Inserting\n", FILE_APPEND);

        $this->insert_sale_inventory_voucher($invoice_no,$goodsCOAID,$purchasevalue,$goodsNarration,$goodsComment,$goodsreVID);
        // for inventory & cost of goods sold end

        // for taxs start
        $taxCOAID     = $predefine_account->tax;
        $taxvalue     = $paid_tax;
        $taxNarration = "Tax for Sales Voucher";
        $taxComment   = "Tax for Sales Voucher for customer";
        $taxreVID     = $predefine_account->prov_state_tax;

        file_put_contents($log_path, "[TAX VOUCHER] Inserting\n", FILE_APPEND);

        $this->insert_sale_taxvoucher($invoice_no,$taxCOAID,$taxvalue,$taxNarration,$taxComment,$taxreVID);
        // for taxs end

        $customerinfo = $this->db->select('*')->from('customer_information')->where('customer_id',$customer_id)->get()->row();
        $rate                = $this->input->post('product_rate',TRUE);
        $p_id                = $this->input->post('product_id',TRUE);
        $total_amount        = $this->input->post('total_price',TRUE);
        $discount_rate       = $this->input->post('discountvalue',TRUE);
        $discount_per        = $this->input->post('discount',TRUE);
        $vat_amnt            = $this->input->post('vatvalue',TRUE);
        $vat_amnt_pcnt       = $this->input->post('vatpercent',TRUE);
        $tax_amount          = $this->input->post('tax',TRUE);
        $invoice_description = $this->input->post('desc',TRUE);
        $serial_n            = $this->input->post('serial_no',TRUE);

        file_put_contents($log_path, "\n===== [PRODUCT DETAILS START] ===== " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

        for ($i = 0, $n = count($p_id); $i < $n; $i++) {
            $product_quantity = $quantity[$i];
            $product_rate     = $rate[$i];
            $product_id       = $p_id[$i];
            $serial_no        = (!empty($serial_n[$i])?$serial_n[$i]:null);
            $total_price      = $total_amount[$i];
            $supplier_rate    = $this->supplier_price($product_id);
            $disper           = $discount_per[$i];
            $discount         = $discount_rate[$i];
            $vatper           = (is_array($vat_amnt_pcnt) && isset($vat_amnt_pcnt[$i])) ? $vat_amnt_pcnt[$i] : 0;
            $vatanmt          = (is_array($vat_amnt) && isset($vat_amnt[$i])) ? $vat_amnt[$i] : 0;
            $tax              = ($tax_amount?$tax_amount[$i]:0);
            $description      = (!empty($invoice_description)?$invoice_description[$i]:null);
           
            $data1 = array(
                'invoice_details_id' => $this->generator(15),
                'invoice_id'         => $inv_insert_id,
                'product_id'         => $product_id,
                'serial_no'          => '',
                'batch_id'           => $serial_no,
                'quantity'           => $product_quantity,
                'rate'               => $product_rate,
                'discount'           => $discount,
                'description'        => $description,
                'discount_per'       => $disper,
                'vat_amnt'           => $vatanmt,
                'vat_amnt_per'       => $vatper,
                'tax'                => $tax,
                'paid_amount'        => $paidamount,
                'due_amount'         => $this->input->post('due_amount',TRUE),
                'supplier_rate'      => $supplier_rate,
                'total_price'        => $total_price,
                'status'             => 1
            );

            $product_price = array( 'price' => $product_rate);

            file_put_contents($log_path, "[PRODUCT $i] Insert Data: " . json_encode($data1) . "\n", FILE_APPEND);

            if (!empty($quantity)) {
                $this->db->insert('invoice_details', $data1);
                $this->db->where('product_id', $product_id)->update('product_information', $product_price);

                file_put_contents($log_path, "[PRODUCT $i] DB Inserted and Updated price to: {$product_price['price']} for Product ID: $product_id\n", FILE_APPEND);
            }
        }

        file_put_contents($log_path, "===== [PRODUCT DETAILS END] =====\n", FILE_APPEND);
        
        if (!empty($cusinfo)) {
        $message = 'Mr.'.$customerinfo->customer_name.',
        '.'You have purchase  '.$this->input->post('grand_total_price',TRUE).' '. $currency_details[0]['currency'].' You have paid .'.$this->input->post('paid_amount',TRUE).' '. $currency_details[0]['currency'];
        }
        
        $config_data = $this->db->select('*')->from('sms_settings')->get()->row();
        if($config_data->isinvoice == 1){
           $smsapi =   $this->smsgateway->send([
                'apiProvider' => 'nexmo',
                'username'    => $config_data->api_key,
                'password'    => $config_data->api_secret,
                'from'        => $config_data->from,
                'to'          => $customerinfo->customer_mobile,
                'message'     => $message
            ]);
        }
        file_put_contents($log_path, "===== [END INVOICE ENTRY] $invoice_no =====\n", FILE_APPEND);
        return  $invoice_no;
    }

    // insert sales debitvoucher
    public function insert_sale_creditvoucher($is_credit = null,$invoice_id = null,$dbtid = null,$amnt_type = null,$amnt = null,$Narration = null,$Comment = null,$reVID = null,$subcode = null){  

        $fyear = financial_year();          
        $VDate = date('Y-m-d');
        $CreateBy=$this->session->userdata('id');
        $createdate=date('Y-m-d H:i:s');
        // Cash & credit voucher insert
        if ($is_credit == 1) {
            $maxid = $this->getMaxFieldNumber('id','acc_vaucher','Vtype','JV','VNo');             
            $vaucherNo = "JV-". ($maxid +1);

            $debitinsert = array(
                'fyear'          =>  $fyear,
                'VNo'            =>  $vaucherNo,
                'Vtype'          =>  'JV',
                'referenceNo'    =>  $invoice_id,
                'VDate'          =>  $VDate,
                'COAID'          =>  $dbtid,    
                'Narration'      =>  $Narration,     
                'ledgerComment'  =>  $Comment,   
                'RevCodde'       =>  $reVID,    
                'subType'        =>  3,    
                'subCode'        =>  $subcode,    
                'isApproved'     =>  0,                      
                'CreateBy'       =>  $CreateBy,
                'CreateDate'     =>  $createdate,      
                'status'         =>  0,      
            );

            
        }else {
            $maxid = $this->getMaxFieldNumber('id','acc_vaucher','Vtype','CV','VNo');             
            $vaucherNo = "CV-". ($maxid +1);
            $debitinsert = array(
                'fyear'          =>  $fyear,
                'VNo'            =>  $vaucherNo,
                'Vtype'          =>  'CV',
                'referenceNo'    =>  $invoice_id,
                'VDate'          =>  $VDate,
                'COAID'          =>  $dbtid,     
                'Narration'      =>  $Narration,     
                'ledgerComment'  =>  $Comment,   
                'RevCodde'       =>  $reVID,    
                'isApproved'     =>  0,                      
                'CreateBy'       => $CreateBy,
                'CreateDate'     => $createdate,      
                'status'         => 0,      
            );

        }
        if($amnt_type == 'Debit'){
            
            $debitinsert['Debit']  = $amnt;
            $debitinsert['Credit'] =  0.00;    
        }else{

            $debitinsert['Debit']  = 0.00;
            $debitinsert['Credit'] =  $amnt; 
        }
        
        $this->db->insert('acc_vaucher',$debitinsert);

	    return true;
	}
    public function insert_sale_inventory_voucher($invoice_id = null,$dbtid = null,$amnt = null,$Narration = null,$Comment = null,$reVID = null){

        $fyear = financial_year();          
        $VDate = date('Y-m-d');
        $CreateBy=$this->session->userdata('id');
        $createdate=date('Y-m-d H:i:s');
        
        // cost of goods sold voucher insert
        $maxidforgoods = $this->getMaxFieldNumber('id','acc_vaucher','Vtype','JV','VNo');             
        $vaucherNogoods = "JV-". ($maxidforgoods +1);
        $debitinsert = array(
            'fyear'          =>  $fyear,
            'VNo'            =>  $vaucherNogoods,
            'Vtype'          =>  'JV',
            'referenceNo'    =>  $invoice_id,
            'VDate'          =>  $VDate,
            'COAID'          =>  $dbtid,     
            'Narration'      =>  $Narration,     
            'ledgerComment'  =>  $Comment,   
            'Debit'          =>  $amnt,   
            'RevCodde'       =>  $reVID,    
            'isApproved'     =>  0,                      
            'CreateBy'       =>  $CreateBy,
            'CreateDate'     =>  $createdate,      
            'status'         => 0,      
        );
        
        $this->db->insert('acc_vaucher',$debitinsert);
       
	    return true;
	}
    public function insert_sale_taxvoucher($invoice_id = null,$dbtid = null,$amnt = null,$Narration = null,$Comment = null,$reVID = null){

        $fyear = financial_year();          
        $VDate = date('Y-m-d');
        $CreateBy=$this->session->userdata('id');
        $createdate=date('Y-m-d H:i:s');
        
        // cost of goods sold voucher insert
        $maxidtax = $this->getMaxFieldNumber('id','acc_vaucher','Vtype','JV','VNo');             
        $vauchertax = "JV-". ($maxidtax +1);
        $debitinsert = array(
            'fyear'          =>  $fyear,
            'VNo'            =>  $vauchertax,
            'Vtype'          =>  'JV',
            'referenceNo'    =>  $invoice_id,
            'VDate'          =>  $VDate,
            'COAID'          =>  $dbtid,     
            'Narration'      =>  $Narration,     
            'ledgerComment'  =>  $Comment,   
            'Debit'          =>  $amnt,   
            'RevCodde'       =>  $reVID,    
            'isApproved'     =>  0,                      
            'CreateBy'       =>  $CreateBy,
            'CreateDate'     =>  $createdate,      
            'status'         => 0,      
        );
        
        $this->db->insert('acc_vaucher',$debitinsert);
       
	    return true;
	}

    public function getMaxFieldNumber($field, $table,$where=null,$type=null,$fild2=null) {
  
        $this->db->select("$field,$fild2");
        $this->db->from($table); 
        if($where != null) {
            $this->db->where($where, $type);
        } 
        $this->db->order_by('id','desc')->limit(1) ; 
        $record = $this->db->get() ; 
        if($record->num_rows() >0) {     
         if($fild2 != null) {
            $num = $record->row($fild2);
            list($txt, $intval) = explode('-', $num);        
            return $intval;
         } else { 
         $num = $record->row($field);       
           return $num;
         }     
        } else {
            return 0;
        }
    }


    public function update_invoice() {
        $tablecolumn = $this->db->list_fields('tax_collection');
        $num_column  = count($tablecolumn)-4;
        $dbinv_id    = $this->input->post('dbinv_id',TRUE);
        $invoice_id  = $this->input->post('invoice_id',TRUE);
        $invoice_no  = $this->input->post('invoice',TRUE);
        $createby    = $this->session->userdata('id');
        $createdate  = date('Y-m-d H:i:s');
        $customer_id = $this->input->post('customer_id',TRUE);
        $quantity    = $this->input->post('product_quantity',TRUE);
        $product_id  = $this->input->post('product_id',TRUE);
        $multipayamount = $this->input->post('pamount_by_method',TRUE);
        $multipaytype = $this->input->post('multipaytype',TRUE);
       $changeamount = $this->input->post('change',TRUE);
        if($changeamount > 0){
        $paidamount = $this->input->post('n_total',TRUE);

        }else{
        $paidamount = $this->input->post('paid_amount',TRUE);
        }


        $bank_id = $this->input->post('bank_id',TRUE);
        if(!empty($bank_id)){
       $bankname = $this->db->select('bank_name')->from('bank_add')->where('bank_id',$bank_id)->get()->row()->bank_name;
    
       $bankcoaid = $this->db->select('HeadCode')->from('acc_coa')->where('HeadName',$bankname)->get()->row()->HeadCode;
        }else{
            $bankcoaid='';
        }
   
             $transection_id =$this->generator(8);


            $this->db->where('referenceNo', $invoice_id);
            $this->db->delete('acc_vaucher');

            $this->db->where('relation_id', $invoice_id);
            $this->db->delete('tax_collection');
            if ($multipaytype[0] == 0) {
                $is_credit = 1;
            }
            else {
                $is_credit = '';
            }

            $fixordyn = $this->db->select('*')->from('vat_tax_setting')->get()->row();
              
            if($fixordyn->fixed_tax == 1 ){
                
                $paid_tax = $this->input->post('total_vat_amnt',TRUE);
            }elseif($fixordyn->dynamic_tax == 1 ){
               
                $paid_tax = $this->input->post('total_tax',TRUE);
            }

            
      
        $data = array(
            'invoice_id'      => $invoice_id,
            'customer_id'     => $this->input->post('customer_id',TRUE),
            'date'            => $this->input->post('invoice_date',TRUE),
            'total_amount'    => $this->input->post('grand_total_price',TRUE),
            'total_tax'       => $this->input->post('total_tax',TRUE),
            'invoice_details' => $this->input->post('inva_details',TRUE),
            'due_amount'      => $this->input->post('due_amount',TRUE),
            'paid_amount'     => $this->input->post('paid_amount',TRUE),
            'invoice_discount'=> $this->input->post('invoice_discount',TRUE),
            'total_discount'  => $this->input->post('total_discount',TRUE),
            'total_vat_amnt'  => $this->input->post('total_vat_amnt',TRUE),
            'prevous_due'     => $this->input->post('previous',TRUE),
            'shipping_cost'   => $this->input->post('shipping_cost',TRUE),
            'payment_type'    =>  $this->input->post('paytype',TRUE),
            'bank_id'         =>  (!empty($this->input->post('bank_id',TRUE))?$this->input->post('bank_id',TRUE):null),
            'is_credit'       =>  $is_credit,   
        );
      

     
        $prinfo  = $this->db->select('product_id,Avg(rate) as product_rate')->from('product_purchase_details')->where_in('product_id',$product_id)->group_by('product_id')->get()->result(); 
        $purchase_ave = [];
        $i=0;
        foreach ($prinfo as $avg) {
        $purchase_ave [] =  $avg->product_rate*$quantity[$i];
        $i++;
        }
        $sumval = array_sum($purchase_ave);

        if ($invoice_id != '') {
            $this->db->where('invoice_id', $invoice_id);
            $this->db->update('invoice', $data);
        }

        $predefine_account  = $this->db->select('*')->from('acc_predefine_account')->get()->row();
        $Narration          = "Sales Voucher";
        $Comment            = "Sales Voucher for customer";
        $reVID              = $predefine_account->salesCode;

        if($multipaytype && $multipayamount){

            if ($multipaytype[0] == 0) { 

                $amount_pay = $data['total_amount'];
                $amnt_type  = 'Debit';
                $COAID      = $predefine_account->customerCode;
                $subcode    = $this->db->select('*')->from('acc_subcode')->where('referenceNo', $customer_id)->where('subTypeId', 3)->get()->row()->id;
                $this->insert_sale_creditvoucher($is_credit,$invoice_id,$COAID,$amnt_type,$amount_pay,$Narration,$Comment,$reVID,$subcode);

            }else {
                
                $amnt_type = 'Debit';
                for ($i=0; $i < count($multipaytype); $i++) {

                    $COAID = $multipaytype[$i];
                    $amount_pay = $multipayamount[$i];

                    $this->insert_sale_creditvoucher($is_credit,$invoice_id,$COAID,$amnt_type,$amount_pay,$Narration,$Comment,$reVID);
                    
                }
            }
            
        }
        // for inventory & cost of goods sold start
        $goodsCOAID     = $predefine_account->costs_of_good_solds;
        $purchasevalue  = $sumval;
        $goodsNarration = "Sales cost of goods Voucher";
        $goodsComment   = "Sales cost of goods Voucher for customer";
        $goodsreVID     = $predefine_account->inventoryCode;

        $this->insert_sale_inventory_voucher($invoice_id,$goodsCOAID,$purchasevalue,$goodsNarration,$goodsComment,$goodsreVID);
        // for inventory & cost of goods sold end

        // for taxs start
        $taxCOAID     = $predefine_account->tax;
        $taxvalue     = $paid_tax;
        $taxNarration = "Tax for Sales Voucher";
        $taxComment   = "Tax for Sales Voucher for customer";
        $taxreVID     = $predefine_account->prov_state_tax;

        $this->insert_sale_taxvoucher($invoice_id,$taxCOAID,$taxvalue,$taxNarration,$taxComment,$taxreVID);
        // for taxs end

        for($j=0;$j<$num_column;$j++){
                $taxfield = 'tax'.$j;
                $taxvalue = 'total_tax'.$j;
              $taxdata[$taxfield]=$this->input->post($taxvalue);
            }
            $taxdata['customer_id'] = $customer_id;
            $taxdata['date']        = (!empty($this->input->post('invoice_date',TRUE))?$this->input->post('invoice_date',TRUE):date('Y-m-d'));
            $taxdata['relation_id'] = $invoice_id;
            $this->db->insert('tax_collection',$taxdata);

        // Inserting for Accounts adjustment.
        ############ default table :: customer_payment :: inflow_92mizdldrv #################

        $invoice_d_id  = $this->input->post('invoice_details_id',TRUE);
        $quantity      = $this->input->post('product_quantity',TRUE);
        $rate          = $this->input->post('product_rate',TRUE);
        $p_id          = $this->input->post('product_id',TRUE);
        $total_amount  = $this->input->post('total_price',TRUE);
        $discount_rate = $this->input->post('discountvalue',TRUE);
        $discount_per  = $this->input->post('discount',TRUE);
        $vat_amnt      = $this->input->post('vatvalue',TRUE);
        $vat_amnt_pcnt = $this->input->post('vatpercent',TRUE);
        $invoice_description = $this->input->post('desc',TRUE);
        $this->db->where('invoice_id', $dbinv_id);
        $this->db->delete('invoice_details');
        $serial_n       = $this->input->post('serial_no',TRUE);
        for ($i = 0, $n = count($p_id); $i < $n; $i++) {
            $product_quantity = $quantity[$i];
            $product_rate     = $rate[$i];
            $product_id       = $p_id[$i];
            $serial_no        =$serial_n[$i];
            $total_price      = $total_amount[$i];
            $supplier_rate    = $this->supplier_price($product_id);
            $discount         = $discount_rate[$i];
            $vatper           = $vat_amnt_pcnt[$i];
            $vatanmt          = $vat_amnt[$i];
            $dis_per          = $discount_per[$i];
           $desciption        = $invoice_description[$i];
            if (!empty($tax_amount[$i])) {
                $tax = $tax_amount[$i];
            } else {
                $tax = $this->input->post('tax');
            }


            $data1 = array(
                'invoice_details_id' => $this->generator(15),
                'invoice_id'         => $dbinv_id,
                'product_id'         => $product_id,
                'serial_no'          => '',
                'batch_id'           => $serial_no,
                'quantity'           => $product_quantity,
                'rate'               => $product_rate,
                'discount'           => $discount,
                'total_price'        => $total_price,
                'discount_per'       => $dis_per,
                'tax'                => $this->input->post('total_tax',TRUE),
                'vat_amnt'           => $vatanmt,
                'vat_amnt_per'       => $vatper,
                'paid_amount'        => $paidamount,
                'supplier_rate'     => $supplier_rate,
                'due_amount'         => $this->input->post('due_amount',TRUE),
                'description'       => $desciption,
            );

            $product_price = array(

                'price' => $product_rate
            );
            $this->db->insert('invoice_details', $data1);

            $this->db->where('product_id', $product_id)->update('product_information', $product_price);
            

           

            $customer_id = $this->input->post('customer_id',TRUE);
          
        }

        return $invoice_id;
    }


    //POS invoice entry
    public function pos_invoice_setup($product_id) {
        $product_information = $this->db->select('*')
                ->from('product_information')
                ->join('supplier_product', 'product_information.product_id = supplier_product.product_id')
                ->where('product_information.product_id', $product_id)
                ->get()
                ->row();

        if ($product_information != null) {

            $this->db->select('SUM(a.quantity) as total_purchase');
            $this->db->from('product_purchase_details a');
            $this->db->where('a.product_id', $product_id);
            $total_purchase = $this->db->get()->row();

            $this->db->select('SUM(b.quantity) as total_sale');
            $this->db->from('invoice_details b');
            $this->db->where('b.product_id', $product_id);
            $total_sale = $this->db->get()->row();

            $available_quantity = ($total_purchase->total_purchase - $total_sale->total_sale);
          
          $data2 = (object) array(
                        'total_product'  => $available_quantity,
                        'supplier_price' => $product_information->supplier_price,
                        'price'          => $product_information->price,
                        'supplier_id'    => $product_information->supplier_id,
                        'product_id'     => $product_information->product_id,
                        'product_name'   => $product_information->product_name,
                        'product_model'  => $product_information->product_model,
                        'unit'           => $product_information->unit,
                        'tax'            => $product_information->tax,
                        'image'          => $product_information->image,
                        'serial_no'      => $product_information->serial_no,
                        'product_vat'      => $product_information->product_vat,
            );

        

            return $data2;
        } else {
            return false;
        }
    }



 public function searchprod($cid)
    { 
        $this->db->select('*');
        $this->db->from('product_information');
        if($cid !='all'){
        $this->db->where('category_id',$cid);
      }
        $this->db->order_by('product_name','asc');
        $query   = $this->db->get();
        $itemlist=$query->result();
        if($cid = ''){
          return false;
        }else{
           return $itemlist;
        }
       
    }
 public function searchprod_byname($pname= null)
    { 
        $this->db->select('*');
        $this->db->from('product_information');
        $this->db->like('product_name',$pname);
        $this->db->order_by('product_name','asc');
        $this->db->limit(20);
        $query = $this->db->get();
        $itemlist=$query->result();
        return $itemlist;
    }


    public function walking_customer(){
       return $data = $this->db->select('*')->from('customer_information')->like('customer_name','walking','after')->get()->result_array();
    }

        public function category_dropdown()
    {
        $data = $this->db->select("*")
            ->from('product_category')
            ->get()
            ->result();

        $list = array('' => 'select_category');
        if (!empty($data)) {
            foreach($data as $value)
                $list[$value->category_id] = $value->category_name;
            return $list;
        } else {
            return false; 
        }
    }

     public function category_list() {
        $this->db->select('*');
        $this->db->from('product_category');
        $this->db->where('status',1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

      //Retrieve company Edit Data
    public function retrieve_company() {
        $this->db->select('*');
        $this->db->from('company_information');
        $this->db->limit('1');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

       public function retrieve_setting_editdata() {
        $this->db->select('*');
        $this->db->from('web_setting');
        $this->db->where('setting_id', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }
        //Get Supplier rate of a product
    public function supplier_rate($product_id) {
        $this->db->select('supplier_price');
        $this->db->from('supplier_product');
        $this->db->where(array('product_id' => $product_id));
        $query = $this->db->get();
        return $query->result_array();

        $this->db->select('Avg(rate) as supplier_price');
        $this->db->from('product_purchase_details');
        $this->db->where(array('product_id' => $product_id));
        $query = $this->db->get()->row();
        return $query->result_array();
    }

     public function supplier_price($product_id) {
        $this->db->select('supplier_price');
        $this->db->from('supplier_product');
        $this->db->where(array('product_id' => $product_id));
        $supplier_product = $this->db->get()->row();
   

        $this->db->select('Avg(rate) as supplier_price');
        $this->db->from('product_purchase_details');
        $this->db->where(array('product_id' => $product_id));
        $purchasedetails = $this->db->get()->row();
      $price = (!empty($purchasedetails->supplier_price)?$purchasedetails->supplier_price:$supplier_product->supplier_price);
 
        return (!empty($price)?$price:0);
    }


        public function autocompletproductdata($product_name){
            $query=$this->db->select('*')
                ->from('product_information')
                ->like('product_name', $product_name, 'both')
                ->or_like('product_model', $product_name, 'both')
                ->order_by('product_name','asc')
                ->limit(15)
                ->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();  
        }
        return false;
    }


        public function retrieve_invoice_html_data($invoice_id) {
        $this->db->select('a.total_tax,
                        a.*,
                        b.*,
                        c.*,
                        d.product_id,
                        d.product_name,
                        d.product_details,
                        d.unit,
                        d.product_model,
                        a.paid_amount as paid_amount,
                        a.due_amount as due_amount'
                    );
        $this->db->from('invoice a');
        $this->db->join('invoice_details c', 'c.invoice_id = a.id');
        $this->db->join('customer_information b', 'b.customer_id = a.customer_id');
        $this->db->join('product_information d', 'd.product_id = c.product_id');
        $this->db->where('a.invoice_id', $invoice_id);
        $this->db->where('c.quantity >', 0);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

     public function user_invoice_data($user_id){
   return  $this->db->select('*')->from('users')->where('user_id',$user_id)->get()->row();
 }

   // product information retrieve by product id
   public function get_total_product_invoic($product_id) {
    $this->db->select('SUM(a.quantity) as total_purchase');
    $this->db->from('product_purchase_details a');
    $this->db->where('a.product_id', $product_id);
    $total_purchase = $this->db->get()->row();

    $this->db->select('SUM(b.quantity) as total_sale');
    $this->db->from('invoice_details b');
    $this->db->where('b.product_id', $product_id);
    $total_sale = $this->db->get()->row();

    $this->db->select('a.*,b.*');
    $this->db->from('product_information a');
    $this->db->join('supplier_product b', 'a.product_id=b.product_id');
    $this->db->where(array('a.product_id' => $product_id, 'a.status' => 1));
    $product_information = $this->db->get()->row();

    $this->db->select('SUM(quantity) as purchase_qty,batch_id,product_id');
    $this->db->from('product_purchase_details');
    $this->db->where('product_id', $product_id);
    $this->db->group_by('batch_id');
    $pur_product_batch = $this->db->get()->result();

    $this->db->select('SUM(quantity) as sale_qty,batch_id');
    $this->db->from('invoice_details');
    $this->db->where('product_id', $product_id);
    $this->db->group_by('batch_id');
    $sell_product_batch = $this->db->get()->result();

    $available_quantity = ($total_purchase->total_purchase - $total_sale->total_sale);
    $tablecolumn = $this->db->list_fields('tax_collection');
           $num_column = count($tablecolumn)-4;
$taxfield='';
$taxvar = [];
for($i=0;$i<$num_column;$i++){
$taxfield = 'tax'.$i;
$data2[$taxfield] = (!empty($product_information->$taxfield)?$product_information->$taxfield:0);
$taxvar[$i]       = (!empty($product_information->$taxfield)?$product_information->$taxfield:0);
$data2['taxdta']  = $taxvar;
}

$content =explode(',', $product_information->serial_no);


    $html = "";
    if (empty($pur_product_batch)) {
        $html .="No Serial Found !";
    }else{
        // Select option created for product
        $html .="<select name=\"serial_no[]\" onchange=\"invoice_product_batch()\"  class=\"serial_no_1 form-control basic-single\" id=\"serial_no_1\">";
            $html .= "<option value=''>".display('select_one')."</option>";
            foreach ($pur_product_batch as $p_batch) {
                $sellt_prod_batch = $this->db->select('SUM(quantity) as sale_qty,batch_id, product_id')->from('invoice_details')->where('product_id', $p_batch->product_id)->where('batch_id', $p_batch->batch_id)->get()->row();
                $pur_prod = (empty($sellt_prod_batch->sale_qty)?0:$sellt_prod_batch->sale_qty);
                $available_prod = $p_batch->purchase_qty - $pur_prod;
                if ($available_prod > 0) {
                    # code...
                    $html .="<option value=".$p_batch->batch_id.">".$p_batch->batch_id."</option>";
                }

            }   
        $html .="</select>";
    }

        $data2['total_product']  = $available_quantity;
        $data2['supplier_price'] = $product_information->supplier_price;
        $data2['price']          = $product_information->price;
        $data2['supplier_id']    = $product_information->supplier_id;
        $data2['unit']           = $product_information->unit;
        $data2['tax']            = $product_information->tax;
        $data2['product_vat']    = $product_information->product_vat;
        $data2['serial']         = $html;
        $data2['txnmber']        = $num_column;
    

    return $data2;
}

        public function generator($lenth) { 
        $number = array("1", "2", "3", "4", "5", "6", "7", "8", "9");

        for ($i = 0; $i < $lenth; $i++) {
            $rand_value = rand(0, 8);
            $rand_number = $number["$rand_value"];

            if (empty($con)) {
                $con = $rand_number;
            } else {
                $con = "$con" . "$rand_number";
            }
        }
        return $con;
    }


       public function stock_qty_check($product_id){
        $this->db->select('SUM(a.quantity) as total_purchase');
        $this->db->from('product_purchase_details a');
        $this->db->where('a.product_id', $product_id);
        $total_purchase = $this->db->get()->row();

        $this->db->select('SUM(b.quantity) as total_sale');
        $this->db->from('invoice_details b');
        $this->db->where('b.product_id', $product_id);
        $total_sale = $this->db->get()->row();

        $this->db->select('a.*,b.*');
        $this->db->from('product_information a');
        $this->db->join('supplier_product b', 'a.product_id=b.product_id');
        $this->db->where(array('a.product_id' => $product_id, 'a.status' => 1));
        $product_information = $this->db->get()->row();

        $available_quantity = ($total_purchase->total_purchase - $total_sale->total_sale);
        return (!empty($available_quantity)?$available_quantity:0);

    }


    public function paysenz_invoice_pos_print_direct($invoice_id = null){
        $invoice_detail = $this->retrieve_invoice_html_data($invoice_id);
        $taxfield = $this->db->select('*')
                ->from('tax_settings')
                ->where('is_show',1)
                ->get()
                ->result_array();
        $txregname ='';
        foreach($taxfield as $txrgname){
        $regname = $txrgname['tax_name'].' Reg No  - '.$txrgname['reg_no'].', ';
        $txregname .= $regname;
        }  
        $subTotal_quantity  = 0;
        $subTotal_cartoon   = 0;
        $subTotal_discount  = 0;
        $subTotal_ammount   = 0;
        $descript           = 0;
        $isserial           = 0;
        $is_discount        = 0;
        $is_dis_val         = 0;
        $vat_amnt_per       = 0;
        $vat_amnt           = 0;
        $isunit             = 0;
        if (!empty($invoice_detail)) {
            foreach ($invoice_detail as $k => $v) {
                $invoice_detail[$k]['final_date'] = $this->occational->dateConvert($invoice_detail[$k]['date']);
                $subTotal_quantity = $subTotal_quantity + $invoice_detail[$k]['quantity'];
                $subTotal_ammount = $subTotal_ammount + $invoice_detail[$k]['total_price'];
            }

            $i = 0;
            foreach ($invoice_detail as $k => $v) {
                $i++;
                $invoice_detail[$k]['sl'] = $i;
                 if(!empty($invoice_detail[$k]['description'])){
                    $descript = $descript+1;
                    
                }
                 if(!empty($invoice_detail[$k]['serial_no'])){
                    $isserial = $isserial+1;
                    
                }
                 if(!empty($invoice_detail[$k]['unit'])){
                    $isunit = $isunit+1;
                    
                }
                    if(!empty($invoice_detail[$k]['discount_per'])){
                    $is_discount = $is_discount+1;
                    
                }
                if(!empty($invoice_detail[$k]['discount'])){
                    $is_dis_val = $is_dis_val+1;
                    
                }
                    if(!empty($invoice_detail[$k]['vat_amnt_per'])){
                    $vat_amnt_per = $vat_amnt_per+1;
                    
                }
                    if(!empty($invoice_detail[$k]['vat_amnt'])){
                    $vat_amnt = $vat_amnt+1;
                    
                }
            }
        }

        $payment_method_list = $this->invoice_method_wise_balance($invoice_id); 
        $terms_list = $this->db->select('*')->from('seles_termscondi')->get()->result(); 
        $totalbal = $invoice_detail[0]['total_amount']+$invoice_detail[0]['prevous_due'];
        $user_id  = $invoice_detail[0]['sales_by'];
        $currency_details = $this->retrieve_setting_editdata();
        $users    = $this->user_invoice_data($user_id);
        $data = array(
        'title'                => display('pos_print'),
        'invoice_id'           => $invoice_detail[0]['invoice_id'],
        'invoice_no'           => $invoice_detail[0]['invoice'],
        'customer_name'        => $invoice_detail[0]['customer_name'],
        'customer_address'     => $invoice_detail[0]['customer_address'],
        'customer_mobile'      => $invoice_detail[0]['customer_mobile'],
        'customer_email'       => $invoice_detail[0]['customer_email'],
        'final_date'           => $invoice_detail[0]['final_date'],
        'invoice_details'      => $invoice_detail[0]['invoice_details'],
        'total_amount'         => number_format($totalbal, 2, '.', ','),
        'grand_total'          => $invoice_detail[0]['total_amount'],
        'subTotal_cartoon'     => $subTotal_cartoon,
        'subTotal_quantity'    => $subTotal_quantity,
        'invoice_discount'     => number_format($invoice_detail[0]['invoice_discount'], 2, '.', ','),
        'total_discount'       => number_format($invoice_detail[0]['total_discount'], 2, '.', ','),
        'total_tax'            => number_format($invoice_detail[0]['total_tax'], 2, '.', ','),
        'subTotal_ammount'     => number_format($subTotal_ammount, 2, '.', ','),
        'paid_amount'          => number_format($invoice_detail[0]['paid_amount'], 2, '.', ','),
        'due_amount'           => number_format($invoice_detail[0]['due_amount'], 2, '.', ','),
        'shipping_cost'        => number_format($invoice_detail[0]['shipping_cost'], 2, '.', ','),
        'invoice_all_data'     => $invoice_detail,
        'previous'             => number_format($invoice_detail[0]['prevous_due'], 2, '.', ','),
        'is_discount'         => $is_discount,
        'users_name'           => $users->first_name.' '.$users->last_name,
        'tax_regno'            => $txregname,
        'is_desc'              => $descript,
        'is_serial'            => $isserial,
        'is_dis_val'           => $is_dis_val,
        'vat_amnt_per'         => $vat_amnt_per,
        'vat_amnt'             => $vat_amnt,
        'is_unit'              => $isunit,
        'company_info'         => $this->retrieve_company(),
        'currency'             => $currency_details[0]['currency'],
        'position'             => $currency_details[0]['currency_position'],
        'discount_type'        => $currency_details[0]['discount_type'],
        'logo'                 => $currency_details[0]['invoice_logo'],
       
        'all_discount'         => number_format($invoice_detail[0]['total_discount'], 2, '.', ','),
        'p_method_list'        => $payment_method_list,
        'terms_list'           => $terms_list,
        'total_vat'            => number_format($invoice_detail[0]['total_vat_amnt'], 2, '.', ','),

        );

       return $data;

    }


       public function product_list() {
        $this->db->select('*');
        $this->db->from('product_information');
        $this->db->where('status',1);
        $this->db->limit(30);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

    public function paysenz_print_settingdata(){
        $this->db->select('*');
        $this->db->from('print_setting');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }

    public function allterms_list(){
        return $this->db->select('*')
      ->from('seles_termscondi')
      ->get()
      ->result();
     }


    public function create_terms($data = [])
    {    
        return $this->db->insert('seles_termscondi',$data);
    }
 
    public function update_terms($data = [])
    {
        return $this->db->where('id',$data['id'])
            ->update('seles_termscondi',$data); 
    } 

    public function single_terms_data($id){
        return $this->db->select('*')
            ->from('seles_termscondi')
            ->where('id', $id)
            ->get()
            ->row();
    }

    public function delete_terms($id){
        $this->db->where('id', $id)
            ->delete("seles_termscondi");
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
    }

    public function invoice_method_wise_balance($invoice_id){

       return $this->db->select('acc_vaucher.Debit,acc_vaucher.COAID,acc_coa.HeadName')
             ->from('acc_vaucher')
             ->join('acc_coa', 'acc_coa.HeadCode=acc_vaucher.COAID', 'left')
             ->where('acc_vaucher.referenceNo',$invoice_id)
             ->where('acc_vaucher.Vtype','CV')
             ->get()->result(); 
    }

}

