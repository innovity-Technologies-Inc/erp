<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 #------------------------------------    
    # Author: PaySenz Ltd.
    # Author link: https://www.paysenz.com/
    # Dynamic style php file
    # Developed by :Faiz Shiraji
    #------------------------------------    

class Product_model extends CI_Model {

    public function get_category_level($category_id, $level = 0) {
        if ($category_id == NULL) {
            return $level; // Return depth level
        }
        $parent = $this->db->select('parent_id')
                           ->where('category_id', $category_id)
                           ->get('product_category')
                           ->row();
        if ($parent && $parent->parent_id != NULL) {
            return $this->get_category_level($parent->parent_id, $level + 1);
        }
        return $level + 1;
    }

    # ✅ Fetch All Categories for Parent Dropdown (Excluding Current)
    public function get_all_categories($exclude_id = null) {
        if ($exclude_id) {
            $this->db->where('category_id !=', $exclude_id);
        }
        return $this->db->order_by('parent_id', 'ASC')
                        ->order_by('category_name', 'ASC')
                        ->get('product_category')
                        ->result();
    }

    # ✅ Recursive Function to Get Nested Categories
    public function get_category_tree($parent_id = 0, $level = 0) {
        $result = $this->db->where('parent_id', $parent_id)
                           ->order_by('category_name', 'ASC')
                           ->get('product_category')
                           ->result();
        
        foreach ($result as $row) {
            echo str_repeat('-- ', $level) . $row->category_name . '<br>';
            $this->get_category_tree($row->category_id, $level + 1);
        }
    }

     # ✅ Fetch All Categories (For Hierarchical Display)
    public function category_list() {
        $query = $this->db->select('*')
            ->from('product_category')
            ->order_by('parent_id', 'ASC') // Order for hierarchy
            ->order_by('category_name', 'ASC')
            ->get();
        return $query->result();
    }


    # ✅ Create New Category
    public function create_category($data = []) {    
        return $this->db->insert('product_category', $data);
    }

    public function vat_tax_setting(){
        $this->db->select('*');
        $this->db->from('vat_tax_setting');
        $query   = $this->db->get();
        return $query->row();
    }
 

    
    # ✅ Update Existing Category
    public function update_category($data = []) {
        return $this->db->where('category_id', $data['category_id'])
            ->update('product_category', $data); 
    } 


   # ✅ Fetch Single Category
   public function single_category_data($id) {
    return $this->db->select('*')
        ->from('product_category')
        ->where('category_id', $id)
        ->get()
        ->row();
}

    # ✅ Delete Category (Including Subcategories)
    public function delete_category($id) {
        // First, delete child categories
        $this->db->where('parent_id', $id)->delete("product_category");

        // Now, delete the actual category
        $this->db->where('category_id', $id)->delete("product_category");

        return ($this->db->affected_rows() > 0);
    }



    // unit part
    public function unit_list(){
        return $this->db->select('*')
      ->from('units')
      ->get()
      ->result();
     }


    public function create_unit($data = [])
    {    
        return $this->db->insert('units',$data);
    }
 

    
    public function update_unit($data = [])
    {
        return $this->db->where('unit_id',$data['unit_id'])
            ->update('units',$data); 
    } 

    public function single_unit_data($id){
        return $this->db->select('*')
            ->from('units')
            ->where('unit_id', $id)
            ->get()
            ->row();
    }

    public function delete_unit($id){
        $this->db->where('unit_id', $id)
            ->delete("units");
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
    }

        public function supplier_list() {
        $this->db->select('*');
        $this->db->from('supplier_information');
        $this->db->order_by('supplier_name', 'asc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

       public function active_category() {
        $this->db->select('*');
        $this->db->from('product_category');
        $this->db->where('status', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function active_unit() {
        $this->db->select('*');
        $this->db->from('units');
        $this->db->where('status', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return FALSE;
    }

    public function supplier_product_list($id){
        $this->db->select('*');
        $this->db->from('supplier_product');
        $this->db->where('product_id', $id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return FALSE;
    }



    public function single_product_data($id){
         return $this->db->select('*')
            ->from('product_information')
            ->where('product_id', $id)
            ->get()
            ->row();
    }

    public function create_product($data = []){
        return $this->db->insert('product_information',$data);
    }
 

     public function update_product($data = [])
    {
        return $this->db->where('product_id',$data['product_id'])
            ->update('product_information',$data); 
    } 

    public function is_last_layer_category($category_id) {
        $query = $this->db->select('category_id')
                          ->from('product_category')
                          ->where('parent_id', $category_id)
                          ->get();
    
        return ($query->num_rows() == 0); // Returns true if no child categories exist
    }

    public function get_parent_categories() {
        return $this->db->select('category_id, category_name')
            ->from('product_category')
            ->where('parent_id IS NULL OR parent_id = 0') // Get only main categories
            ->get()
            ->result_array();
    }

    public function get_sub_categories($parent_id = null) {
        $this->db->select('category_id, category_name, parent_id');
        $this->db->from('product_category');
        
        if ($parent_id !== null) {
            $this->db->where('parent_id', $parent_id); // Fetch only subcategories of selected parent
        } else {
            $this->db->where('parent_id IS NOT NULL'); // Fetch all subcategories
        }
        
        return $this->db->get()->result_array();
    }

    public function get_child_categories() {
        return $this->db->select('c1.category_id, c1.category_name')
            ->from('product_category c1')
            ->join('product_category c2', 'c1.parent_id = c2.category_id', 'left')
            ->join('product_category c3', 'c2.parent_id = c3.category_id', 'left')
            ->where('c1.parent_id IS NOT NULL AND c2.parent_id IS NOT NULL') // Ensures it's a child category
            ->get()
            ->result_array();
    }

    public function get_last_layer_categories() {
        // Fetch all categories
        $categories = $this->db->select('category_id, category_name, parent_id')
                               ->from('product_category')
                               ->get()
                               ->result_array();
    
        // Store parent categories
        $parent_categories = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] != NULL) {
                $parent_categories[$category['parent_id']] = true;
            }
        }
    
        // Filter only last-layer categories
        $last_layer_categories = [];
        foreach ($categories as $category) {
            if (!isset($parent_categories[$category['category_id']])) {
                $last_layer_categories[] = $category;
            }
        }
    
        return $last_layer_categories;
    }


    public function getProductList($postData=null){

         $response = array();

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
            $searchQuery = " (a.product_name like '%".$searchValue."%' or a.product_model like '%".$searchValue."%' or a.price like'%".$searchValue."%' or c.supplier_price like'%".$searchValue."%' or m.supplier_name like'%".$searchValue."%') ";
         }

         ## Total number of records without filtering
         $this->db->select('count(*) as allcount');
         $this->db->from('product_information a');
         $this->db->join('supplier_product c','c.product_id = a.product_id','left');
         $this->db->join('supplier_information m','m.supplier_id = c.supplier_id','left');
          if($searchValue != '')
         $this->db->where($searchQuery);
         $records = $this->db->get()->result();
         $totalRecords = $records[0]->allcount;

         ## Total number of record with filtering
         $this->db->select('count(*) as allcount');
         $this->db->from('product_information a');
         $this->db->join('supplier_product c','c.product_id = a.product_id','left');
         $this->db->join('supplier_information m','m.supplier_id = c.supplier_id','left');
         if($searchValue != '')
            $this->db->where($searchQuery);
         $records = $this->db->get()->result();
         $totalRecordwithFilter = $records[0]->allcount;

         ## Fetch records
         $this->db->select("a.*,
                a.product_name,
                a.product_id,
                a.product_vat,
                a.image,
                c.supplier_price,
                c.supplier_id,
                m.supplier_name,
                pc.category_name"); // ✅ Add category_name
        $this->db->from('product_information a');
        $this->db->join('supplier_product c','c.product_id = a.product_id','left');
        $this->db->join('supplier_information m','m.supplier_id = c.supplier_id','left');
        $this->db->join('product_category pc','pc.category_id = a.category_id','left'); // ✅ Join with category table
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
          $jsaction = "return confirm('Are You Sure ?')";
            $image = '<img src="'.$base_url.$record->image.'" class="img img-responsive" height="50" width="50">';
           if($this->permission1->method('manage_product','delete')->access()){
                                  
           $button .= '<a href="'.$base_url.'product/product/paysenz_deleteproduct/'.$record->product_id.'" class="btn btn-xs btn-danger "  onclick="'.$jsaction.'"><i class="fa fa-trash"></i></a>';
         }

         $button .='  <a href="'.$base_url.'qrcode/'.$record->product_id.'" class="btn btn-success btn-xs" data-toggle="tooltip" data-placement="left" title="'.display('qr_code').'"><i class="fa fa-qrcode" aria-hidden="true"></i></a>';

         $button .='  <a href="'.$base_url.'barcode/'.$record->product_id.'" class="btn btn-warning btn-xs" data-toggle="tooltip" data-placement="left" title="'.display('barcode').'"><i class="fa fa-barcode" aria-hidden="true"></i></a>';
      if($this->permission1->method('manage_product','update')->access()){
         $button .=' <a href="'.$base_url.'product_form/'.$record->product_id.'" class="btn btn-info btn-xs" data-toggle="tooltip" data-placement="left" title="'. display('update').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
     }

         $product_name = '<a href="'.$base_url.'product_details/'.$record->product_id.'">'.$record->product_name.'</a>';
         $supplier = '<a href="'.$base_url.'supplier_ledgerinfo/'.$record->supplier_id.'">'.$record->supplier_name.'</a>';
               
         $data[] = array( 
            'sl'               => $sl,
            'product_name'     => $product_name,
            'category'         => $record->category_name, // ✅ Replace product_model with category
            'supplier_name'    => $supplier,
            'price'            => $record->price,
            'purchase_p'       => $record->supplier_price,
            'image'            => $image,
            'button'           => $button,
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

    public function delete_product($id){
    $this->db->where('product_id', $id)
            ->delete("supplier_product");
     $this->db->where('product_id', $id)
            ->delete("product_information");

        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
}

public function check_product($id){
        $this->db->select('*');
        $this->db->from('product_purchase_details');
        $this->db->where('product_id', $id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return FALSE;
}

    public function paysenz_barcode_productdata($id){
         return $this->db->select('*')
            ->from('product_information')
            ->where('product_id', $id)
            ->get()
            ->result_array();
    }

     public function product_purchase_info($product_id) {
        $this->db->select('a.*,b.*,sum(b.quantity) as quantity,sum(b.total_amount) as total_amount,c.supplier_name');
        $this->db->from('product_purchase a');
        $this->db->join('product_purchase_details b', 'b.purchase_id = a.purchase_id');
        $this->db->join('supplier_information c', 'c.supplier_id = a.supplier_id');
        $this->db->where('b.product_id', $product_id);
        $this->db->order_by('a.purchase_date', 'desc');
        $this->db->group_by('a.purchase_id');
        $this->db->limit(30);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

        public function invoice_data($product_id) {
        $this->db->select('a.*,b.*,c.customer_name');
        $this->db->from('invoice a');
        $this->db->join('invoice_details b', 'b.invoice_id = a.invoice_id');
        $this->db->join('customer_information c', 'c.customer_id = a.customer_id');
        $this->db->where('b.product_id', $product_id);
        $this->db->order_by('a.date', 'desc');
        $this->db->limit(30);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }

}

