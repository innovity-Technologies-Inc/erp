<!-- Manage Warehouse -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <span><?php echo display('manage_warehouse') ?></span>
                    <span class="padding-lefttitle">
                        <?php if($this->permission1->method('warehouse', 'create')->access()) { ?>
                            <a href="<?php echo base_url('warehouse/warehouse/add'); ?>" class="btn btn-info m-b-5 m-r-2">
                                <i class="ti-plus"></i> <?php echo display('add_warehouse') ?>
                            </a>
                        <?php } ?>
                    </span>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                <table id="WarehouseList" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th><?php echo display('sl') ?></th>
                            <th><?php echo display('warehouse_code') ?></th>
                            <th><?php echo display('warehouse_name') ?></th>
                            <th><?php echo display('contact_person') ?></th>
                            <th><?php echo display('city') ?></th>
                            <th><?php echo display('phone') ?></th>
                            <th><?php echo display('email') ?></th>
                            <th><?php echo display('status') ?></th>
                            <th><?php echo display('action') ?></th>
                        </tr>
                    </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <input type="hidden" id="total_warehouse" value="<?php echo count($warehouses); ?>" name="">
    </div>
</div>

<script>
$(document).ready(function () {
    "use strict";
    var csrf_test_name = $('#CSRF_TOKEN').val();
    var base_url = $('#base_url').val();
    var total_warehouse = $("#total_warehouse").val();

    $('#WarehouseList').DataTable({
        
        responsive: true,
        "aaSorting": [[1, "asc"]],
        "columnDefs": [
            { "bSortable": false, "aTargets": [0, 2, 3, 4] },
        ],
        'processing': true,
        'serverSide': true,

        'lengthMenu': [[10, 25, 50, 100, 250, 500, total_warehouse], [10, 25, 50, 100, 250, 500, "All"]],
        dom: "'<'col-sm-4'l><'col-sm-4 text-center'><'col-sm-4'>Bfrtip",
        buttons: [
            { extend: "copy", className: "btn-sm prints", exportOptions: { columns: [0, 1, 2, 3] } },
            { extend: "csv", className: "btn-sm prints", title: "WarehouseList", exportOptions: { columns: [0, 1, 2, 3] } },
            { extend: "excel", className: "btn-sm prints", title: "WarehouseList", exportOptions: { columns: [0, 1, 2, 3] } },
            { extend: "pdf", className: "btn-sm prints", title: "WarehouseList", exportOptions: { columns: [0, 1, 2, 3] } },
            { extend: "print", className: "btn-sm prints", title: "<center>WarehouseList</center>", exportOptions: { columns: [0, 1, 2, 3] } }
        ],

        'serverMethod': 'post',
        'ajax': {
            'url': base_url + 'warehouse/warehouse/CheckWarehouseList',
            'data': {
                csrf_test_name: csrf_test_name
            }
        },

        'columns': [
            { data: 'sl' },
            { data: 'warehouse_code' },
            { data: 'name' },
            { data: 'contact_person_name' },
            { data: 'city' },
            { data: 'phone' },
            { data: 'email' },
            { data: 'status' },
            {
                data: 'action',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return data;
                }
            }
        ]
    });
});
</script>