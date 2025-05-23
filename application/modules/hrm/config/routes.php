<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*employee part*/
$route['designation_form']        = "hrm/hrm/paysenz_designation_form";
$route['designation_form/(:num)'] = 'hrm/hrm/paysenz_designation_form/$1';
$route['designation_list']        = 'hrm/hrm/paysenz_designation_list';
$route['employee_form']           = "hrm/hrm/paysenz_employee_form";
$route['employee_form/(:num)']    = 'hrm/hrm/paysenz_employee_form/$1';
$route['employee_list']           = 'hrm/hrm/paysenz_employee_list';
$route['employee_profile/(:num)'] = 'hrm/hrm/paysenz_employee_profile/$1';
/*attendance part*/
$route['add_attendance']          = "hrm/attendance/paysenz_add_attendance";
$route['attendance_list']         = "hrm/attendance/manage_attendance";
$route['attendance_list/(:num)']  = "hrm/attendance/manage_attendance/$1";
$route['edit_attendance/(:num)']  = "hrm/attendance/paysenz_edit_attendance/$1";
$route['attendance_report']       = "hrm/attendance/paysenz_attendance_report";
$route['datewise_attendance_report']= "hrm/attendance/paysenz_datewiseattendancereport";
$route['userwise_attendance_report']= "hrm/attendance/employeewise_att_report";
/*payroll part*/

$route['salary_advance_view']     = "hrm/salary_advance/salary_advance_view";
$route['manage_salary_advance']     = "hrm/salary_advance/manage_salary_advance";
$route['employee_salary_generate']= "hrm/payroll/employee_salary_generate";
$route['employee_salary_payment_view']= "hrm/payroll/employee_salary_payment_view";
$route['add_beneficials']         = "hrm/payroll/paysenz_beneficial_form";
$route['manage_benefits']         = "hrm/payroll/manage_benefits";
$route['edit_beneficial/(:num)']  = "hrm/payroll/benefits_update_form/$1";
$route['salary_setup']            = "hrm/payroll/paysenz_salary_setup_form";
$route['manage_salary_setup']     = "hrm/payroll/manage_salary_setup";
$route['edit_salary_setup/(:num)']= "hrm/payroll/salsetup_upform/$1";
$route['salary_generate']         = "hrm/payroll/paysenz_salary_generate";
$route['manage_salary_generate']  = "hrm/payroll/manage_salary_generate";
$route['manage_salary_generate/(:num)']  = "hrm/payroll/manage_salary_generate/$1";
$route['salary_payment']          = "hrm/payroll/salary_payment";
$route['salary_payment/(:num)']   = "hrm/payroll/salary_payment/$1";
$route['payslip/(:num)']          = "hrm/payroll/payslip/$1";
$route['employee_salary_approval/(:num)'] = "hrm/payroll/gmb_employee_salary_approval/$1";
$route['employee_salary_chart/(:num)'] = "hrm/payroll/gmb_employee_salary_chart/$1";
$route['salary_pay_slip/(:num)'] = "hrm/payroll/salary_pay_slip/$1";
/*expense part*/
$route['add_expense_item']        = "hrm/expense/paysenz_expense_item_form";
$route['manage_expense_item']     = "hrm/expense/paysenz_expense_item_list";
$route['add_expense']             = "hrm/expense/paysenz_add_expense";
$route['manage_expense']          = "hrm/expense/manage_expense";
$route['manage_expense/(:num)']   = "hrm/expense/manage_expense/$1";
$route['expense_statement']       = "hrm/expense/expense_statement";

/*office loan part*/
$route['add_officeloan_person']   = "hrm/loan/paysenz_add_office_loan_person";
$route['manage_office_loan_person']= "hrm/loan/manage_ofln_person";
$route['manage_office_loan_person/(:num)'] = "hrm/loan/manage_ofln_person/$1";
$route['office_loan_person_ledger/(:any)'] = "hrm/loan/office_loan_person_ledger/$1";
$route['office_loan_person_ledgerdata']    = "hrm/loan/office_loan_ledger_search";
$route['add_office_loan']          = "hrm/loan/paysenz_add_office_loan";
$route['add_office_loan_payment']  = "hrm/loan/paysenz_add_office_loan_payment";
$route['edit_office_loan_person/(:any)']= "hrm/loan/paysenz_edit_office_person/$1";
/*personal loan part start*/
$route['add_person']               = "hrm/loan/paysenz_add_person";
$route['manage_person']            = "hrm/loan/manage_person";
$route['manage_person/(:num)']     = "hrm/loan/manage_person/$1";
$route['person_ledger/(:any)']     = "hrm/loan/paysenz_personal_ledger/$1";
$route['personal_loan_summary']    = "hrm/loan/paysenz_personal_loan_summary";
$route['add_loan']                 = "hrm/loan/paysenz_add_loan";
$route['add_payment']              = "hrm/loan/paysenz_add_payment";
$route['personal_loan_edit/(:any)']= "hrm/loan/edit_person_loan/$1";
