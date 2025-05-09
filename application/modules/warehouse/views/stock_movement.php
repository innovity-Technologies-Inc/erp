<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo $title; ?></h4>
                </div>
            </div>
            <?php echo form_open('warehouse/warehouse/process_stock_movement', ['class' => 'form-vertical', 'id' => 'movement_form']) ?>
            <div class="panel-body">

            <div class="form-group row">
                <label class="col-sm-2 col-form-label"><?php echo display('batch_no') ?> *</label>
                <div class="col-sm-4">
                    <select id="batch_id" name="batch_id" class="form-control">
                        <option value="">Select Batch ID</option>
                    </select>
                </div>

                <label class="col-sm-2 col-form-label"><?php echo display('current_warehouse') ?></label>
                <div class="col-sm-4">
                    <input type="text" id="current_warehouse" class="form-control" readonly>
                    <input type="hidden" id="current_warehouse_id" name="current_warehouse_id">
                </div>
            </div>

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label"><?php echo display('product') ?></label>
                    <div class="col-sm-4">
                        <input type="text" id="product_name" class="form-control" readonly>
                    </div>

                    <label class="col-sm-2 col-form-label"><?php echo display('available_quantity') ?></label>
                    <div class="col-sm-4">
                        <input type="text" id="available_quantity" class="form-control" readonly>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label"><?php echo display('movement_category') ?> *</label>
                    <div class="col-sm-4">
                        <select class="form-control" name="movement_category" id="movement_category" required>
                            <option value=""><?php echo display('select_one') ?></option>
                            <option value="Inbound">Inbound</option>
                            <option value="Outbound">Outbound</option>
                            <option value="Internal">Internal</option>
                            <option value="Administrative">Administrative</option>
                        </select>
                    </div>

                    <label class="col-sm-2 col-form-label"><?php echo display('movement_type') ?> *</label>
                    <div class="col-sm-4">
                        <select class="form-control" name="movement_type" id="movement_type" required disabled>
                            <option value=""><?php echo display('select_category_first') ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group row" id="destination_warehouse_group">
                    <label class="col-sm-2 col-form-label"><?php echo display('destination_warehouse') ?> *</label>
                    <div class="col-sm-4">
                        <select class="form-control" name="destination_warehouse_id" id="destination_warehouse_id">
                            <option value=""><?php echo display('select_one') ?></option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo html_escape($warehouse->id) ?>">
                                    <?php echo html_escape($warehouse->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label"><?php echo display('quantity') ?> *</label>
                    <div class="col-sm-4">
                        <input type="number" name="quantity" class="form-control" required step="0.01" min="0.01">
                    </div>

                    <label class="col-sm-2 col-form-label"><?php echo display('reference_no') ?></label>
                    <div class="col-sm-4">
                        <input type="text" name="reference_no" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label"><?php echo display('remark') ?></label>
                    <div class="col-sm-10">
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-success"><?php echo display('save') ?></button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

    <script>
    $(document).ready(function() {
    // ✅ Populate Batch List
    $.ajax({
        url: '<?php echo base_url("warehouse/warehouse/get_all_batches") ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#batch_id').empty().append('<option value="">Select Batch ID</option>');
                $.each(response.data, function(index, batch) {
                    $('#batch_id').append(
                        `<option value="${batch.id}">
                            ${batch.batch_id} - (WH: ${batch.warehouse_name})
                        </option>`
                    );
                });
            } else {
                alert('No batches found.');
            }
        },
        error: function() {
            alert('Error fetching batch list.');
        }
    });

    // ✅ Get batch details when batch is selected
    $('#batch_id').change(function() {
        var batch_id = $(this).val();
        
        if (batch_id) {
            $.ajax({
                url: '<?php echo base_url("warehouse/warehouse/get_batch_details") ?>',
                type: 'POST',
                data: {
                    id: batch_id,
                    csrf_test_name: $('#CSRF_TOKEN').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#product_name').val(response.data.product_name);
                        $('#current_warehouse').val(response.data.warehouse_name);
                        $('#current_warehouse_id').val(response.data.warehouse_id);
                        $('#available_quantity').val(response.data.available_quantity);
                        $('#total_quantity').val(response.data.total_quantity);

                        // ✅ Populate Destination Warehouses (Always Show)
                        $('#destination_warehouse_group').show(); // <-- Always Show
                        var currentWarehouseId = response.data.warehouse_id;
                        
                        $.ajax({
                            url: '<?php echo base_url("warehouse/warehouse/get_all_warehouses") ?>',
                            type: 'GET',
                            dataType: 'json',
                            success: function(warehouseResponse) {
                                if (warehouseResponse.success) {
                                    $('#destination_warehouse_id').empty();
                                    $('#destination_warehouse_id').append('<option value="">Select Destination Warehouse</option>');
                                    
                                    $.each(warehouseResponse.data, function(index, warehouse) {
                                        // ✅ Add only if it's not the current warehouse
                                        if (parseInt(warehouse.id) !== parseInt(currentWarehouseId)) {
                                            $('#destination_warehouse_id').append(
                                                `<option value="${warehouse.id}">
                                                    ${warehouse.name}
                                                </option>`
                                            );
                                        }
                                    });

                                    // ✅ If there are no other warehouses, disable it
                                    if ($('#destination_warehouse_id option').length <= 1) {
                                        $('#destination_warehouse_id').append('<option value="">No other warehouses available</option>');
                                    }
                                } else {
                                    alert('No warehouses found.');
                                }
                            },
                            error: function() {
                                alert('Error fetching warehouse list.');
                            }
                        });

                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error fetching batch details.');
                }
            });
        } else {
            // Clear fields if no batch is selected
            $('#product_name').val('');
            $('#current_warehouse').val('');
            $('#current_warehouse_id').val('');
            $('#available_quantity').val('');
            $('#total_quantity').val('');
            $('#destination_warehouse_id').empty().append('<option value="">Select Destination Warehouse</option>');
            $('#destination_warehouse_group').hide(); // Hide when nothing is selected
        }
    });

    // ✅ Get movement types when category is selected
    $('#movement_category').change(function() {
        var category = $(this).val();
        
        if (category) {
            $.ajax({
                url: '<?php echo base_url("warehouse/warehouse/get_movement_types") ?>',
                type: 'POST',
                data: { category: category },
                dataType: 'json',
                success: function(response) {
                    $('#movement_type').empty(); // Clear existing options
                    $('#movement_type').append('<option value=""><?php echo display('select_type') ?></option>');

                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(index, value) {
                            $('#movement_type').append('<option value="'+ value.type_name +'">'+ value.type_name +'</option>');
                        });
                        $('#movement_type').prop('disabled', false);
                    } else {
                        alert('No types found for the selected category.');
                        $('#movement_type').prop('disabled', true);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('Failed to fetch movement types.');
                }
            });
        } else {
            $('#movement_type').empty().append('<option value=""><?php echo display('select_category_first') ?></option>');
            $('#movement_type').prop('disabled', true);
        }
    });
});
    </script>