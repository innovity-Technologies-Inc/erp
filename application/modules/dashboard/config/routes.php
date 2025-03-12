<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['home']     = "dashboard/home";
$route['language'] = "dashboard/language";
$route['editPhrase/(:any)'] = "dashboard/language/editPhrase/$1";
$route['editPhrase/(:any)/(:any)'] = "dashboard/language/editPhrase/$1/$1";
$route['phrases']  = "dashboard/language/phrase";
$route['phrases/(:num)']  = "dashboard/language/phrase/$1";
$route['settings'] = "dashboard/setting";
$route['company_list'] = "dashboard/setting/paysenz_company_info";
$route['edit_company/(:any)'] = "dashboard/setting/paysenz_edit_company/$1";
$route['user_list'] = "dashboard/user/paysenz_userlist";
$route['add_user'] = "dashboard/user/paysenz_userform";
$route['add_user/(:num)'] = "dashboard/user/paysenz_userform/$1";
$route['currency_form'] = "dashboard/setting/paysenz_currencyform";
$route['currency_form/(:num)'] = "dashboard/setting/paysenz_currencyform/$1";
$route['currency_list'] = "dashboard/setting/paysenz_currency_list";
$route['mail_setting']  = "dashboard/setting/paysenz_mail_config";
$route['sms_setting']  = "dashboard/setting/paysenz_sms_config";
$route['app_setting']  = "dashboard/setting/paysenz_app_setting";
$route['add_role']  = "dashboard/permission/paysenz_add_role";
$route['role_list']  = "dashboard/permission/paysenz_role_list";
$route['edit_role/(:num)']  = "dashboard/permission/paysenz_edit_role/$1";
$route['assign_role']  = "dashboard/permission/paysenz_user_roleassign";
$route['restore']      = "dashboard/backup_restore/restore_form";
$route['db_import']    = "dashboard/backup_restore/import_form";
$route['commission']   = "dashboard/setting/commission";
$route['commission_generate']   = "dashboard/setting/commission_generate";
$route['out_of_stock']   = "dashboard/home/out_of_stock";
$route['edit_profile']   = "dashboard/home/profile";
$route['change_password']= "dashboard/home/change_password_form";
$route['print_setting']  = "dashboard/padprint/index";

