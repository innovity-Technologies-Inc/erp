<!-- Date Range Search -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="col-sm-10">
                    <form id="filterForm" class="form-inline">
                        <div class="form-group">
                            <label for="from_date"><?php echo display('start_date') ?></label>
                            <input type="text" name="from_date" class="form-control datepicker" id="from_date"
                                placeholder="<?php echo display('start_date') ?>">
                        </div>
                        <div class="form-group">
                            <label for="to_date"><?php echo display('end_date') ?></label>
                            <input type="text" name="to_date" class="form-control datepicker" id="to_date"
                                placeholder="<?php echo display('end_date') ?>">
                        </div>
                        <button type="button" id="btn-filter" class="btn btn-success"><?php echo display('find') ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Invoice Payment -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <span><?php echo display('manage_invoice_payment') ?></span>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="invoicePayment" width="100%">
                    <thead>
                        <tr>
                            <th><?php echo display('sl'); ?></th>
                            <th><?php echo display('date'); ?></th>
                            <th><?php echo display('payment_ref'); ?></th>
                            <th><?php echo display('payment_ref_doc'); ?></th>
                            <th><?php echo display('transaction_ref'); ?></th>
                            <th><?php echo display('approved_by'); ?></th>
                            <th><?php echo display('customer_name'); ?></th>
                            <th class="text-right"><?php echo display('total_amount'); ?></th>
                            <th class="text-right"><?php echo display('paid_amount'); ?></th>
                            <th class="text-right"><?php echo display('due_amount'); ?></th>
                            <th class="text-center"><?php echo display('status'); ?></th>
                            <th class="text-center"><?php echo display('action'); ?></th>
                        </tr>
                    </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-right"><?php echo display('total') ?>:</th>
                                <th id="totalAmount" class="text-right">0.00</th>
                                <th id="totalPaid" class="text-right">0.00</th>
                                <th id="totalDue" class="text-right">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
<!-- Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="statusForm">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Change Payment Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="invoice_payment_id" id="invoice_payment_id">
                <div class="form-group">
                    <label for="new_status">Select Status</label>
                    <select name="new_status" id="new_status" class="form-control">
                    <option value="0">Unapproved</option>
                    <option value="1">Approved</option>
                    <option value="2">Pending</option>
                    </select>
                </div>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-success">Update</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTable Script -->
<script>
let table;

$(document).ready(function () {
    table = $('#invoicePayment').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [],
        ajax: {
            url: "<?php echo base_url('invoice_payment_list_data'); ?>",
            type: "POST",
            data: function (d) {
                d.fromdate = $('#from_date').val();
                d.todate = $('#to_date').val();
            },
            dataSrc: function (json) {
                $('#totalAmount').text(parseFloat(json.total_amount || 0).toFixed(2));
                $('#totalPaid').text(parseFloat(json.total_paid || 0).toFixed(2));
                $('#totalDue').text(parseFloat(json.total_due || 0).toFixed(2));
                return json.aaData;
            }
        },
        columns: [
            { data: "sl" },
            { data: "date" },
            { data: "payment_ref" },
            { data: "payment_ref_doc", orderable: false, className: "text-center" },
            { data: "transaction_ref" },
            { data: "salesman" },
            { data: "customer_name" },
            { data: "total_amount", className: "text-right" },
            { data: "paid_amount", className: "text-right" },
            { data: "due_amount", className: "text-right" },
            { data: "status", className: "text-center" },
            { data: "button", orderable: false, className: "text-center" }
        ]
    });

    $('#btn-filter').on('click', function () {
        table.ajax.reload(null, false);
    });

    $(document).on('click', '.change-status', function () {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        $('#invoice_payment_id').val(id);
        $('#new_status').val(currentStatus);
        $('#statusModal').modal('show');
    });

    $('#statusForm').on('submit', function (e) {
        e.preventDefault();

        // 🔄 Add loading overlay
        const overlay = document.createElement('div');
        overlay.id = 'tempLoadingOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(255,255,255,0.6)';
        overlay.style.zIndex = '9999';
        overlay.innerHTML = `
            <div style="position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%); font-size: 20px;">
                <i class="fa fa-spinner fa-spin"></i> Updating...
            </div>`;
        document.body.appendChild(overlay);

        $.ajax({
            url: "<?php echo base_url('invoice/update_status'); ?>",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                document.getElementById('tempLoadingOverlay')?.remove(); // ✅ Remove overlay
                if (res.success) {
                    $('#statusModal').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    alert('Failed to update status.');
                }
            },
            error: function () {
                document.getElementById('tempLoadingOverlay')?.remove(); // ❌ On error
                alert('Something went wrong.');
            }
        });
    });
});
</script>