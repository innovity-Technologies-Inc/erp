<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['add_invoice']         = "invoice/invoice/paysenz_invoice_form";
$route['pos_invoice']         = "invoice/invoice/paysenz_pos_invoice";
$route['gui_pos']             = "invoice/invoice/paysenz_gui_pos";
$route['invoice_list']        = "invoice/invoice/paysenz_invoice_list";

$route['invoice_payment_list']        = "invoice/invoice/paysenz_invoice_payment_list";
$route['invoice/update_delivery_note'] = 'invoice/update_delivery_note';
$route['invoice_payment_list_data'] = "invoice/invoice/invoice_payment_list_data";
$route['invoice/update_status'] = 'invoice/invoice/update_status';
$route['invoice_details/(:num)'] = 'invoice/invoice/paysenz_invoice_details/$1';
$route['delivery_invoice_details/(:num)'] = 'invoice/invoice/paysenz_delivery_invoice_details/$1';
$route['invoice_pad_print/(:num)'] = 'invoice/invoice/paysenz_invoice_pad_print/$1';
$route['pos_print/(:num)']    = 'invoice/invoice/paysenz_invoice_pos_print/$1';
$route['invoice_pos_print']    = 'invoice/invoice/paysenz_pos_print_direct';
$route['download_invoice/(:num)']  = 'invoice/invoice/paysenz_download_invoice/$1';
$route['invoice_edit/(:num)'] = 'invoice/invoice/paysenz_edit_invoice/$1';
$route['invoice_print'] = 'invoice/invoice/invoice_inserted_data_manual';

$route['terms_list'] = 'invoice/invoice/paysenz_terms_list';
$route['terms_add'] = 'invoice/invoice/paysenz_terms_form';
$route['terms_add/(:num)'] = 'invoice/invoice/paysenz_terms_form/$1';

