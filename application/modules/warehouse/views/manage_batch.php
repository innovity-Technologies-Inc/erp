<!-- Manage Batch -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <span><?php echo display('manage_batch') ?></span>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table id="BatchList" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th><?php echo display('sl') ?></th>
                            <th><?php echo display('batch_no') ?></th>
                            <th><?php echo display('product_name') ?></th>
                            <th><?php echo display('warehouse_name') ?></th>
                            <th><?php echo display('expiry_date') ?></th>
                            <th><?php echo display('total_quantity') ?></th> <!-- Updated to total_quantity -->
                            <th><?php echo display('available_quantity') ?></th> <!-- Added available_quantity -->
                            <th><?php echo display('status') ?></th>
                            <th><?php echo display('action') ?></th>
                        </tr>
                    </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#BatchList').DataTable({
            responsive: true,
            serverSide: true,
            ajax: {
                url: '<?php echo base_url("warehouse/warehouse/CheckBatchList"); ?>',
                type: 'POST',
                data: { csrf_test_name: $('#CSRF_TOKEN').val() }
            },
            columns: [
                { data: 'sl' },
                { data: 'batch_name' },
                { data: 'product_name' },
                { data: 'warehouse_name' },
                { 
                    data: 'expiry_date', 
                    render: function(data) {
                        return data ? data : '-';
                    }
                },
                { 
                    data: 'total_quantity', 
                    render: function(data) {
                        return data ? parseFloat(data).toFixed(2) : '0.00';
                    }
                },
                { 
                    data: 'available_quantity', 
                    render: function(data) {
                        return data ? parseFloat(data).toFixed(2) : '0.00';
                    }
                },
                { data: 'status' },
                { data: 'action' }
            ],
            columnDefs: [
                { targets: [0, 5, 6], className: 'text-center' },
                { targets: [4], className: 'text-right' }
            ]
        });
    });
</script>