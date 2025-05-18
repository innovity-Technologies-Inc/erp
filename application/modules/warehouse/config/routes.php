<?php
defined('BASEPATH') OR exit('No direct script access allowed');


$route['warehouse/manage_batch'] = 'warehouse/manage_batch';
$route['warehouse/stock_movement'] = 'warehouse/stock_movement';
$route['warehouse/manage'] = 'warehouse/index';
$route['warehouse/add'] = 'warehouse/Warehouse/add';
$route['warehouse/insert'] = 'warehouse/Warehouse/insert';
$route['warehouse/edit_batch/(:num)'] = 'warehouse/edit_batch/$1';
$route['warehouse/update_batch'] = 'warehouse/update_batch';
$route['warehouse/stock_movement_history'] = 'warehouse/stock_movement_history';
$route['warehouse/check_batch_id'] = 'warehouse/warehouse/check_batch_id';
$route['warehouse/get_all_batches'] = 'warehouse/warehouse/get_all_batches';
$route['warehouse/get_all_warehouses'] = 'warehouse/warehouse/get_all_warehouses';
// $route['warehouse/warehouse/get_all_batches'] = 'warehouse/Warehouse/get_all_batches';