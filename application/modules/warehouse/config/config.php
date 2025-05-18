<?php
// Module directory name
$HmvcConfig['warehouse']["_title"]       = "Warehouse Management";
$HmvcConfig['warehouse']["_description"] = "Warehouse and batch-wise inventory management system";

$HmvcConfig['warehouse']['_database'] = TRUE;

$HmvcConfig['warehouse']["_tables"] = array(
    'warehouse',
    'batch_master',
    'stock_movement'
);