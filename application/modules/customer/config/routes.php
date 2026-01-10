<?php
defined('BASEPATH') OR exit('No direct script access allowed');



$route['add_customer']         = "customer/customer/paysenz_form";
$route['customer_list']        = "customer/customer/index";
$route['edit_customer/(:num)'] = 'customer/customer/paysenz_form/$1';
$route['credit_customer']      = "customer/customer/paysenz_credit_customer";
$route['paid_customer']        = "customer/customer/paysenz_paid_customer";
$route['customer_ledger']      = "customer/customer/paysenz_customer_ledger";
$route['customer_ledger/(:num)']      = "customer/customer/paysenz_customer_ledger/$1";
$route['customer_ledgerdata']  = "customer/customer/paysenz_customer_ledgerData";
$route['customer_advance']     = "customer/customer/paysenz_customer_advance";
$route['advance_receipt/(:any)/(:num)']= "customer/customer/customer_advancercpt/$1/$1";