<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4>Edit Batch - <?= $batch->batch_id ?></h4>
                </div>
            </div>

            <?php echo form_open_multipart('warehouse/warehouse/update_batch', array('class' => 'form-vertical', 'id' => 'edit_batch', 'name' => 'edit_batch')) ?>
            <div class="panel-body">

                <!-- Hidden field to store batch ID -->
                <input type="hidden" name="id" value="<?= $batch->id ?>">

                <!-- Warehouse Selection -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="warehouse_id" class="col-sm-4 col-form-label"><?php echo display('warehouse_name') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <select class="form-control" name="warehouse_id" id="warehouse_id" required>
                                    <option value=""><?php echo display('select_one') ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <option value="<?= $warehouse->id ?>" <?= $warehouse->id == $batch->warehouse_id ? 'selected' : '' ?>>
                                            <?= $warehouse->name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="expiry_date" class="col-sm-4 col-form-label"><?php echo display('expiry_date') ?></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="expiry_date" id="expiry_date" type="date" value="<?= $batch->expiry_date ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group row">
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-success"><?php echo display('update') ?></button>
                    </div>
                </div>

            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#warehouse_id').on('change', function () {
        var warehouse_id = $(this).val();
        if (warehouse_id === "") {
            alert('Please select a warehouse.');
        }
    });

    $('#expiry_date').on('blur', function () {
        var expiry_date = $(this).val();
        if (expiry_date === "") {
            alert('Please select an expiry date.');
        }
    });
});
</script>