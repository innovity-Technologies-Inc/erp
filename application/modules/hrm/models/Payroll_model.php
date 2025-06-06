<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends CI_Model {

     //insert beneficial type
   public function add_beneficial($data = array())
    {
        return $this->db->insert('salary_type',$data);
    }



//beneficial list
  public function salary_setupView()
    {
        return $this->db->select('*')   
            ->from('salary_type')
            ->order_by('salary_type_id', 'desc')
            ->get()
            ->result();
    }

     public function salarysetup_updateForm($id){
        $this->db->where('salary_type_id',$id);
        $query = $this->db->get('salary_type');
        return $query->result_array();
    }


    public function update_benefits($data = []){
        return $this->db->where('salary_type_id', $data["salary_type_id"])
            ->update("salary_type", $data);
            
    }

 
    public function benefits_delete($id = null)
    {
        $this->db->where('salary_type_id',$id)
            ->delete('salary_type');

        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
    } 


       public function salary_typeName()
    {
        return $this->db->select('*')   
            ->from('salary_type')
             ->where('salary_type',1)
            ->get()
            ->result();
    }

        public function salary_typedName()
    {
        return $this->db->select('*')   
            ->from('salary_type')
             ->where('salary_type',0)
            ->get()
            ->result();
    }

        public function empdropdown(){
        $this->db->select('*');
        $this->db->from('employee_history');
        $query = $this->db->get();
        $data = $query->result();
       
        $list = array('' => 'Select One...');
        if (!empty($data) ) {
            foreach ($data as $value) {
                $list[$value->id] = $value->first_name." ".$value->last_name;
            } 
        }
        return $list;
    }


     public function check_exist($employee_id){
         return $this->db->select('*')   
            ->from('employee_salary_setup')
            ->where('employee_id',$employee_id)
            ->get()
            ->num_rows();

    }

     public function salary_setup_create($data = array())
    {
        return $this->db->insert('employee_salary_setup', $data);
    }

             public function salary_setupindex()
    {
             return $this->db->select('count(DISTINCT(sstp.e_s_s_id)) as e_s_s_id,sstp.*,p.id,p.first_name,p.last_name')   
            ->from('employee_salary_setup sstp')
            ->join('employee_history p', 'sstp.employee_id = p.id', 'left')
            ->group_by('sstp.employee_id')
            ->order_by('sstp.salary_type_id', 'desc')
            ->get()
            ->result();
    }


     public function salary_amountlft($id){
        return $result = $this->db->select('employee_salary_setup.*,salary_type.*') 
             ->from('employee_salary_setup')
             ->join('salary_type','salary_type.salary_type_id=employee_salary_setup.salary_type_id')
             ->where('employee_salary_setup.employee_id',$id)
             ->where('salary_type.salary_type',0)
             ->get()
             ->result();
    }

        public function salary_amount($id){
          return $result = $this->db->select('employee_salary_setup.*,salary_type.*') 
             ->from('employee_salary_setup')
             ->join('salary_type','salary_type.salary_type_id=employee_salary_setup.salary_type_id')
             ->where('employee_salary_setup.employee_id',$id)
             ->where('salary_type.salary_type',1)
             ->get()
             ->result();
    }


    public function employee_informationId($id)
    {
        return $result = $this->db->select('hrate as rate,rate_type')
                       ->from('employee_history')
                       ->where('id',$id)
                       ->get()
                       ->result_array();

    }

    public function update_sal_stup($data = array())
    {
        $term = array('employee_id' => $data['employee_id'], 'salary_type_id' => $data['salary_type_id']);

        return $this->db->where($term)
            ->update("employee_salary_setup", $data);
    }

    public function emp_salstup_delete($id = null){
        $this->db->where('employee_id',$id)
            ->delete('employee_salary_setup');
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
    } 

           public function salary_generateView($limit = null, $start = null)
    {
             return $this->db->select('*')   
            ->from('salary_sheet_generate')
            ->group_by('ssg_id')
            ->order_by('ssg_id', 'desc')
            ->limit($limit, $start)
            ->get()
            ->result();
    }

        public function sal_generate_delete($id = null) {
         $this->db->where('ssg_id',$id)
            ->delete('salary_sheet_generate');
            $this->db->where('generate_id',$id)
            ->delete('employee_salary_payment');
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
    } 


      public function emp_paymentView($limit = null, $start = null)
    {
            return $this->db->select('count(DISTINCT(pment.emp_sal_pay_id)) as emp_sal_pay_id,pment.*,p.id as employee_id,p.first_name,p.last_name')   
            ->from('employee_salary_payment pment')
            ->join('employee_history p', 'pment.employee_id = p.id', 'left')
            ->group_by('pment.emp_sal_pay_id')
            ->order_by('pment.emp_sal_pay_id', 'desc')
            ->limit($limit, $start)
            ->get()
            ->result();
    }

        public function update_payment($data = array())
    {
        return $this->db->where('emp_sal_pay_id', $data["emp_sal_pay_id"])
            ->update("employee_salary_payment", $data);
    }


        public function salary_paymentinfo($id = null){
            return $this->db->select('count(DISTINCT(pment.emp_sal_pay_id)) as emp_sal_pay_id,pment.*,p.id as employee_id,p.first_name,p.last_name,desig.designation as position_name,p.hrate as basic,p.rate_type as salarytype')   
            ->from('employee_salary_payment pment')
            ->join('employee_history p', 'pment.employee_id = p.id', 'left')
            ->join('designation desig', 'desig.id = p.designation', 'left')
            ->where('pment.emp_sal_pay_id',$id)
            ->group_by('pment.emp_sal_pay_id')
            ->get()
            ->result_array();

    }


    public function salary_addition_fields($id)
         {
        return $result = $this->db->select('employee_salary_setup.*,salary_type.*') 
             ->from('employee_salary_setup')
             ->join('salary_type','salary_type.salary_type_id=employee_salary_setup.salary_type_id')
             ->where('employee_salary_setup.employee_id',$id)
             ->where('salary_type.salary_type',1)
             ->get()
             ->result();
    }


    public function salary_deduction_fields($id){
        return $result = $this->db->select('employee_salary_setup.*,salary_type.*') 
             ->from('employee_salary_setup')
             ->join('salary_type','salary_type.salary_type_id=employee_salary_setup.salary_type_id')
             ->where('employee_salary_setup.employee_id',$id)
             ->where('salary_type.salary_type',0)
             ->get()
             ->result();
    }

        public function setting()
    {
        return $this->db->get('web_setting')->result_array();
    }
    
        public function companyinfo()
    {
        return $this->db->get('company_information')->result_array();
    }

    // --------------------------------For new Payrole-----------------------------------

public function emp_salsetup_create($data = array())
	{
		return $this->db->insert('salary_type', $data);
	}

	public function delete_s_type($id = null)
	{
		$this->db->where('salary_type_id',$id)
			->delete('salary_type');

		if ($this->db->affected_rows()) {
			return true;
		} else {
			return false;
		}
	} 

	public function update_em_salstup($data = array())
	{
		return $this->db->where('salary_type_id', $data["salary_type_id"])
			->update("salary_type", $data);
	}
	

	public function s_delete($id = null)
	{
		$this->db->where('employee_id',$id)
			->delete('employee_salary_setup');

		if ($this->db->affected_rows()) {
			return true;
		} else {
			return false;
		}
	} 

	
	/* salary sheet generate  */
	public function salary_genrate_create($data = array())
	{
		return $this->db->insert('salary_sheet_generate', $data);
	}
	
	public function salary_gen_delete($id = null,$salname = null)
	{
		$this->db->where('ssg_id',$id)
			->delete('salary_sheet_generate');
		$this->db->where('salary_name',$salname)
			->delete('employee_salary_payment');
		$this->db->where('VNo',$salname)
			->delete('acc_transaction');	

		if ($this->db->affected_rows()) {
			return true;
		} else {
			return false;
		}
	} 

	public function update_sal_gen($data = array())
	{
		return $this->db->where('ssg_id', $data["ssg_id"])
			->update("salary_sheet_generate", $data);
	}
	public function salargen_updateForm($id){
        $this->db->where('ssg_id',$id);
        $query = $this->db->get('salary_sheet_generate');
        return $query->row();
    }
    public function salary_head_create($data = array())
	{
		return $this->db->insert('salary_setup_header', $data);
	}

/* salary setup Update ********************************************/


	public function update_sal_head($data = array())
	{
		return $this->db->where('employee_id', $data["employee_id"])
			->update("salary_setup_header", $data);
	}

	public function salary_s_updateForm($id){
        $this->db->where('employee_id',$id);
        $query = $this->db->get('employee_salary_setup','salary_setup_header');
        return $query->row();
    }
/* salary setup Update ********************************************/


	public  function get_empid($id)
    {
        $query=$this->db->get_where('employee_salary_setup',array('employee_id'=>$id));
        return $query->row_array();
    } 
    public  function get_type($id)
    {
       
        return $result = $this->db->select('sal_type')
                       ->from('employee_salary_setup')
                       ->where('employee_id',$id)
                       ->get()
                       ->row_array();
    } 


    public function type()
	{
		$this->db->select('*');
        $this->db->from('employee_salary_setup');
        $query = $this->db->get();
        $data = $query->result();
       
        $list = array('' => 'Select One...');
       	if (!empty($data) ) {
       		foreach ($data as $value) {
       			$list[$value->sal_type] = $value->sal_type;
       		} 
       	}
       	return $list;
	}

	public function payable()
	{
		$this->db->select('*');
        $this->db->from('salary_setup_header');
        $query = $this->db->get();
        $data = $query->result();
       
         $list = array('' => 'Select One...');
       	if (!empty($data) ) {
       		foreach ($data as $value) {
       			$list[$value->salary_payable] = $value->salary_payable;
       		} 
       	}
       	return $list;
	}
	public  function get_payable($id)
    {
        
        return $result = $this->db->select('salary_payable')
                       ->from('salary_setup_header')
                       ->where('employee_id',$id)
                       ->get()
                       ->row_array();
    } 


public function create_employee_payment($data = array())
	{
		return $this->db->insert('employee_salary_payment', $data);

	}

	public function gmb_salary_generateView($limit = null, $start = null)
	{

        return  $this->db->select('ssg.*,u.first_name,u.last_name,uu.first_name as firstname_apv_by,uu.last_name as lastname_apv_by')   
            ->from('gmb_salary_sheet_generate ssg')
            ->join('users u', 'ssg.generate_by = u.id', 'left')
            ->join('users uu', 'ssg.approved_by = uu.id', 'left')
            ->order_by('ssg_id', 'desc')
            ->limit($limit, $start)
            ->get()
            ->result();

            
	}

	public function gmb_salary_generate_delete($id = null,$salname = null)
	{
		$this->db->where('ssg_id',$id)
			->delete('gmb_salary_sheet_generate');
		$this->db->where('sal_month_year',$salname)
			->delete('gmb_salary_generate');
		

		if ($this->db->affected_rows()) {
			return true;
		} else {
			return false;
		}
	} 

	public function emp_salary_paymentView($limit = null, $start = null)
	{
			return $this->db->select('count(DISTINCT(pment.id)) as emp_sal_pay_id,pment.*,p.id as employee_id,p.first_name,p.last_name')   
            ->from('gmb_salary_generate pment')
            ->join('employee_history p', 'pment.employee_id = p.id', 'left')
            ->group_by('pment.id')
            ->order_by('pment.id', 'desc')
            ->limit($limit, $start)
            ->get()
            ->result();
	}

	public function salary_sheet_generate_info($ssg_id)
	{

		$salary_sheet_generate_info = $this->db->select('*')
                       ->from('gmb_salary_sheet_generate')
                       ->where('ssg_id',$ssg_id)
                       ->get()
                       ->row();
                       
        return $salary_sheet_generate_info;
	}


	public function employee_salary_charts($ssg_id)
	{
			$salary_sheet_generate_info = $this->db->select('*')
                       ->from('gmb_salary_sheet_generate')
                       ->where('ssg_id',$ssg_id)
                       ->get()
                       ->row();

			return $this->db->select('count(DISTINCT(pment.id)) as emp_sal_pay_id,pment.*,p.id as employee_id,p.first_name,p.last_name')   
            ->from('gmb_salary_generate pment')
            ->join('employee_history p', 'pment.employee_id = p.id', 'left')
            ->group_by('pment.id')
            ->order_by('pment.id', 'desc')
            ->where('pment.sal_month_year',$salary_sheet_generate_info->name)
            ->get()
            ->result();
	}

	/* Payroll related functionality starts from 16th april 2022*/

	public function salary_advance_deduction($emp_id,$salary_month)
	{

		$query = 'SELECT * FROM `gmb_salary_advance` WHERE `salary_month` = '."'".$salary_month."'".' AND `employee_id` = '.$emp_id.' AND (`amount` - `release_amount`) > 0';

		return $this->db->query($query)->row();
	}

	public function update_sal_advance($data = array())
	{
		

		return $this->db->where('id', $data['id'])
			->update("gmb_salary_advance", $data);
	}

	public function loan_installment_deduction($emp_id)
	{
		$loan_status = 1;

		$query = 'SELECT * FROM `grand_loan` WHERE `employee_id` = '.$emp_id.' AND `loan_status` = '.$loan_status.' AND (`installment_period` - `installment_cleared`) > 0';

		return $this->db->query($query)->row();
	}

	public function update_loan_installment($data = array())
	{
		

		return $this->db->where('loan_id', $data['loan_id'])
			->update("grand_loan", $data);
	}

	public function employee_salary_generate_info($id)
	{

		return $this->db->select('pment.*,p.id as employee_id,p.first_name,p.last_name')   
        ->from('gmb_salary_generate pment')
        ->join('employee_history p', 'pment.employee_id = p.id', 'left')
        ->where('pment.id', $id)
        ->get()
        ->row();
	}

	// employee Information
	public function employee_info($id)
	{
		return $result = $this->db->select('emp.*,p.designation')
                       ->from('employee_history emp')
                       ->join('designation p', 'emp.designation = p.id', 'left')
                       ->where('emp.id',$id)
                       ->get()
                       ->row();

	}

	// employee Information
	public function payment_natures()
	{
		$results = $this->db->select('HeadCode,HeadName,PHeadName,IsActive,isCashNature,isBankNature')
                       ->from('acc_coa')
                       ->where('isCashNature',1)
                       ->or_where('isBankNature',1)
                       ->get()
                       ->result();

        $respo_arr = array();
        foreach ($results as $key => $value) {
        	if($value->IsActive == 1){
        		$respo_arr[$value->HeadCode] = $value->HeadName;
        	}
        }
        return $respo_arr;

	}

	// employee Information
	public function payment_natures_bank()
	{
		$results = $this->db->select('HeadCode,HeadName,PHeadName,IsActive,isCashNature,isBankNature')
                       ->from('acc_coa')
                       ->or_where('isBankNature',1)
                       ->get()
                       ->result();

        $respo_arr = array();
        foreach ($results as $key => $value) {
        	if($value->IsActive == 1){
        		$respo_arr[$value->HeadCode] = $value->HeadName;
        	}
        }
        return $respo_arr;

	}

	public function update_salary_as_approved($ssg_id,$data = array())
	{
		return $this->db->where('ssg_id', $ssg_id)
			->update("gmb_salary_sheet_generate", $data);
	}
}

