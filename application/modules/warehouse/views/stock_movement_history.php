<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
        <div class="panel-heading">
                <div class="panel-title">
                    <span><?php echo display('stock_move') ?></span>
                    <span class="padding-lefttitle">
                        <?php if ($this->permission1->method('warehouse', 'create')->access()) { ?>
                            <a href="<?php echo base_url('warehouse/warehouse/stock_movement'); ?>" class="btn btn-info m-b-5 m-r-2">
                                <i class="ti-plus"></i> <?php echo display('add_stock_movement') ?>
                            </a>
                        <?php } ?>
                    </span>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table id="StockMovementList" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th><?php echo display('sl') ?></th>
                                <th><?php echo display('batch_no') ?></th>
                                <th><?php echo display('product_name') ?></th>
                                <th><?php echo display('movement_type') ?></th>
                                <th><?php echo display('quantity') ?></th>
                                <th><?php echo display('source_warehouse') ?></th>
                                <th><?php echo display('destination_warehouse') ?></th>
                                <th><?php echo display('reference_no') ?></th>
                                <th><?php echo display('created_by') ?></th>
                                <th><?php echo display('movement_date') ?></th>
                                <th><?php echo display('remark') ?></th>
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
    $(document).ready(function() {
    $('#StockMovementList').DataTable({
        responsive: true,
        serverSide: true,
        ajax: {
            url: '<?php echo base_url("warehouse/warehouse/CheckStockMovementList"); ?>',
            type: 'POST',
            data: { 
                csrf_test_name: $('#CSRF_TOKEN').val() 
            },
            error: function(xhr, error, thrown) {
                console.error("DataTable Error: ", error, thrown);
                console.log(xhr.responseText);
            }
        },
        columns: [
            { 
                data: null,
                className: 'text-center',
                render: function(data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { data: 'batch_id' },
            { data: 'product_name' },
            { 
                data: 'movement_type',
                className: 'text-center',
                render: function(data) {
                    if (!data) {
                        data = 'N/A';
                    }
                    var badgeClass = {
                        'IN': 'badge-success',
                        'OUT': 'badge-danger',
                        'TRANSFER': 'badge-info',
                        'N/A': 'badge-secondary'
                    }[data];
                    
                    return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                }
            },
            { 
                data: 'quantity',
                className: 'text-right',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { data: 'source_warehouse_name' },
            { data: 'destination_warehouse_name' },
            { data: 'reference_no' },
            { data: 'created_by_name' },
            { 
                data: 'movement_date',
                className: 'text-nowrap',
                render: function(data) {
                    if (!data) return '-';
                    
                    const dateObj = new Date(data);
                    const options = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' };
                    
                    return dateObj.toLocaleDateString('en-GB', options).replace(',', '');
                }
            },
            { 
                data: 'remarks',
                render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            }
        ],
        order: [[9, 'desc']],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            emptyTable: "<?php echo display('no_stock_movement_found') ?>",
            info: "<?php echo display('showing') ?> _START_ <?php echo display('to') ?> _END_ <?php echo display('of') ?> _TOTAL_ <?php echo display('entries') ?>",
            infoEmpty: "<?php echo display('showing') ?> 0 <?php echo display('to') ?> 0 <?php echo display('of') ?> 0 <?php echo display('entries') ?>",
            infoFiltered: "(<?php echo display('filtered_from') ?> _MAX_ <?php echo display('total_entries') ?>)",
            lengthMenu: "<?php echo display('show') ?> _MENU_ <?php echo display('entries') ?>",
            search: "<?php echo display('search') ?>:",
            zeroRecords: "<?php echo display('no_matching_records_found') ?>",
            paginate: {
                first: "<?php echo display('first') ?>",
                last: "<?php echo display('last') ?>",
                next: "<?php echo display('next') ?>",
                previous: "<?php echo display('previous') ?>"
            }
        }
    });
});
</script>