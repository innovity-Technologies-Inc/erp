<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo $title; ?></h4>
                </div>
            </div>

            <?php echo form_open_multipart('warehouse/warehouse/insert', array('class' => 'form-vertical', 'id' => 'insert_warehouse', 'name' => 'insert_warehouse')) ?>
            <div class="panel-body">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="warehouse_code" class="col-sm-4 col-form-label"><?php echo display('warehouse_code') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="warehouse_code" id="warehouse_code" type="text" placeholder="<?php echo display('warehouse_code') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="name" class="col-sm-4 col-form-label"><?php echo display('warehouse_name') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="name" id="name" type="text" placeholder="<?php echo display('warehouse_name') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact + Email -->
                <div class="row">
                <div class="col-sm-6">
                    <div class="form-group row">
                        <label for="contact_person" class="col-sm-4 col-form-label"><?php echo display('contact_person') ?></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="contact_person" id="contact_person">
                                <option value=""><?php echo display('select_one') ?></option>
                                <?php foreach ($employees as $employee) { ?>
                                    <option value="<?php echo $employee->id; ?>">
                                        <?php echo $employee->first_name . ' ' . $employee->last_name . ' (' . $employee->designation . ')'; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="phone" class="col-sm-4 col-form-label"><?php echo display('phone') ?></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="phone" id="phone" type="text" placeholder="<?php echo display('phone') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="email" class="col-sm-4 col-form-label"><?php echo display('email') ?></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="email" id="email" type="email" placeholder="<?php echo display('email') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="address_line1" class="col-sm-4 col-form-label"><?php echo display('address_line1') ?></label>
                            <div class="col-sm-8">
                                <textarea class="form-control" name="address_line1" id="address_line1" rows="1" placeholder="<?php echo display('address_line1') ?>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- City, Country -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="city" class="col-sm-4 col-form-label"><?php echo display('city') ?></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="city" id="city" type="text" placeholder="<?php echo display('city') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="country" class="col-sm-4 col-form-label"><?php echo display('country') ?></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="country" id="country" type="text" placeholder="<?php echo display('country') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location and Description -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="location" class="col-sm-4 col-form-label"><?php echo display('location') ?></label>
                            <div class="col-sm-8">
                                <textarea class="form-control" name="location" id="location" rows="1" placeholder="<?php echo display('location') ?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="description" class="col-sm-4 col-form-label"><?php echo display('description') ?></label>
                            <div class="col-sm-8">
                                <textarea class="form-control" name="description" id="description" rows="1" placeholder="<?php echo display('description') ?>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="status" class="col-sm-4 col-form-label"><?php echo display('status') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <select class="form-control" name="status" id="status" required>
                                    <option value="1"><?php echo display('active') ?></option>
                                    <option value="0"><?php echo display('inactive') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-group row">
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-success"><?php echo display('save') ?></button>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#warehouse_code').on('blur', function () {
        var warehouse_code = $(this).val();
        var csrf_test_name = $('#CSRF_TOKEN').val(); // optional if you use CSRF

        $.ajax({
            type: 'POST',
            url: '<?php echo base_url("warehouse/warehouse/check_duplicate_code"); ?>',
            data: {
                warehouse_code: warehouse_code,
                csrf_test_name: csrf_test_name
            },
            success: function (response) {
                if (response === 'exists') {
                    alert('Warehouse Code already exists!');
                    $('#warehouse_code').val('');
                    $('#warehouse_code').focus();
                }
            }
        });
    });

    $('#name').on('blur', function () {
        var name = $(this).val();
        var csrf_test_name = $('#CSRF_TOKEN').val(); // optional if you use CSRF

        $.ajax({
            type: 'POST',
            url: '<?php echo base_url("warehouse/warehouse/check_duplicate_name"); ?>',
            data: {
                name: name,
                csrf_test_name: csrf_test_name
            },
            success: function (response) {
                if (response === 'exists') {
                    alert('Warehouse Name already exists!');
                    $('#name').val('');
                    $('#name').focus();
                }
            }
        });
    });
});
</script>