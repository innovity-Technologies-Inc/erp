 <!-- date between search -->
 <div class="row">
     <div class="col-sm-12">
         <div class="panel panel-default">
             <div class="panel-body">
                 <div class="col-sm-10">
                     <?php echo form_open('', array('class' => 'form-inline', 'method' => 'get')) ?>
                     <?php
                      
                        $today = date('Y-m-d');
                        ?>
                     <div class="form-group">
                         <label class="" for="from_date"><?php echo display('start_date') ?></label>
                         <input type="text" name="from_date" class="form-control datepicker" id="from_date" value=""
                             placeholder="<?php echo display('start_date') ?>">
                     </div>

                     <div class="form-group">
                         <label class="" for="to_date"><?php echo display('end_date') ?></label>
                         <input type="text" name="to_date" class="form-control datepicker" id="to_date"
                             placeholder="<?php echo display('end_date') ?>" value="">
                     </div>

                     <button type="button" id="btn-filter"
                         class="btn btn-success"><?php echo display('find') ?></button>

                     <?php echo form_close() ?>
                 </div>


             </div>
         </div>
     </div>
 </div>
 <div class="row">
 </div>
 <!-- Manage Invoice report -->
 <div class="row">
     <div class="col-sm-12">
         <div class="panel panel-bd lobidrag">
             <div class="panel-heading">
                 <div class="panel-title">
                     <span><?php echo display('manage_invoice') ?></span>
                     <span class="padding-lefttitle">
                         <?php if($this->permission1->method('new_invoice','create')->access()){ ?>
                         <a href="<?php echo base_url('add_invoice') ?>" class="btn btn-info m-b-5 m-r-2"><i
                                 class="ti-plus"> </i> <?php echo display('new_invoice') ?> </a>
                         <?php }?>


                         <?php if($this->permission1->method('gui_pos','create')->access()){ ?>
                         <a href="<?php echo base_url('gui_pos') ?>" class="btn btn-success m-b-5 m-r-2"><i
                                 class="ti-plus"> </i> <?php echo display('pos_invoice') ?> </a>
                         <?php }?>
                     </span>
                 </div>
             </div>
             <div class="panel-body">
                 <div class="table-responsive">
                     <table class="table table-hover table-bordered" cellspacing="0" width="100%" id="InvList">
                         <thead>
                             <tr>
                                 <th><?php echo display('sl') ?></th>
                                 <th><?php echo display('invoice_no') ?></th>
                                 <th><?php echo display('sale_by') ?></th>
                                 <th><?php echo display('customer_name') ?></th>
                                 <th><?php echo display('delivery_note') ?></th>
                                 <th><?php echo display('date') ?></th>
                                 <th><?php echo display('total_amount') ?></th>
                                 <th class="text-center"><?php echo display('action') ?></th>
                             </tr>
                         </thead>
                         <tbody>

                         </tbody>
                         <tfoot>
                             <th colspan="6" class="text-right"><?php echo display('total') ?>:</th>

                             <th></th>
                             <th></th>
                         </tfoot>
                     </table>

                 </div>


             </div>
         </div>
         <input type="hidden" id="total_invoice" value="<?php echo $total_invoice;?>" name="">

     </div>

     <div id="add0" class="modal fade" role="dialog">
        <div class="modal-dialog" >
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <strong><?php echo display('delivery_note') ?></strong>
                </div>
                <div class="modal-body" id="invoice_note_show">


                </div>
                

            </div>
        </div>
    </div>
    <!-- delivery note modal -->
     <!-- Delivery Status Modal -->
    <div class="modal fade" id="deliveryStatusModal" tabindex="-1" aria-labelledby="deliveryStatusLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="deliveryStatusForm">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="deliveryStatusLabel">Update Delivery Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="invoice_id" id="modal_invoice_id">
                <div class="form-group">
                    <label for="delivery_status">Select Delivery Status</label>
                    <select class="form-control" id="delivery_status" name="delivery_status" required>
                    <option value="">-- Select --</option>
                    <option value="0">Pending</option>
                    <option value="1">Confirmed</option>
                    <option value="2">Picked Up</option>
                    <option value="3">On The Way</option>
                    <option value="4">Delivered</option>
                    <option value="5">Cancelled</option>
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

 <script>
    $(document).ready(function () {

        // Trigger delivery modal
        $(document).on('click', '.open-delivery-modal', function () {
            const invoiceId = $(this).data('id');
            const currentStatus = $(this).data('status');
            console.log('Opening modal for Invoice ID:', invoiceId, '| Current Status:', currentStatus);

            $('#modal_invoice_id').val(invoiceId);
            $('#delivery_status').val(currentStatus);
            $('#deliveryStatusModal').modal('show');
        });

        // Submit delivery form
        $('#deliveryStatusForm').on('submit', function (e) {
            e.preventDefault();

            const invoice_id = $('#modal_invoice_id').val();
            const delivery_status = $('#delivery_status').val();

            console.log('Submitting form with Invoice ID:', invoice_id, '| New Status:', delivery_status);

            if (invoice_id && delivery_status !== "") {
                $.ajax({
                    url: "<?php echo base_url('invoice/update_delivery_note'); ?>",
                    type: "POST",
                    data: {
                        invoice_id: invoice_id,
                        delivery_note: delivery_status
                    },
                    dataType: "json",
                    success: function (res) {
                        console.log('Backend Response:', res);

                        if (res.success) {
                            $('#deliveryStatusModal').modal('hide');
                            $('#InvList').DataTable().ajax.reload(null, false);
                            alert(res.api_message || res.message || 'Delivery status updated.');
                        } else {
                            alert('Update failed: ' + (res.message || 'Unknown error'));
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                        alert('An error occurred while updating delivery status.');
                    }
                });
            } else {
                console.warn('Invoice ID or Delivery Status is missing.');
                alert('Please select a delivery status.');
            }
        });

    });
</script>