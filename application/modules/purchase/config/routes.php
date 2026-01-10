<?php
defined('BASEPATH') OR exit('No direct script access allowed');



$route['add_purchase']         = "purchase/purchase/paysenz_purchase_form";
$route['purchase_list']        = "purchase/purchase/paysenz_purchase_list";
$route['purchase_details/(:num)'] = 'purchase/purchase/paysenz_purchase_details/$1';
$route['purchase_edit/(:num)'] = 'purchase/purchase/paysenz_purchase_edit_form/$1';

