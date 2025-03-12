<script src="<?php echo base_url() ?>my-assets/js/admin_js/json/product.js" type="text/javascript"></script>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo $title; ?></h4>
                </div>
            </div>
            <?php echo form_open_multipart('product_form/'.$id, array('class' => 'form-vertical', 'id' => 'insert_product', 'name' => 'insert_product')) ?>
            <div class="panel-body">
                <?php if(empty($id)){?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group row">
                            <label for="barcode_or_qrcode"
                                class="col-sm-2 col-form-label"><?php echo display('barcode_or_qrcode') ?> <i
                                    class="text-danger"></i></label>
                            <div class="col-sm-10">
                                <input class="form-control" name="product_id" type="text" id="product_id"
                                    placeholder="<?php echo display('barcode_or_qrcode') ?>" tabindex="1">
                            </div>
                        </div>
                    </div>
                </div>
                <?php }?>


                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="product_name"
                                class="col-sm-4 col-form-label"><?php echo display('product_name') ?> <i
                                    class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <input class="form-control" name="product_name" type="text" id="product_name"
                                    placeholder="<?php echo display('product_name') ?>"
                                    value="<?php echo $product->product_name?>" required tabindex="1">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="serial_no" class="col-sm-4 col-form-label"><?php echo display('serial_no') ?>
                            </label>
                            <div class="col-sm-8">
                                <input type="text" tabindex="" class="form-control " id="serial_no" name="serial_no"
                                    placeholder="111,abc,XYz" value="<?php echo $product->serial_no?>" />
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Dynamic Category Selection -->
                <div class="row">
                    <!-- Parent Category -->
                    <!-- Parent Category -->
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="parent_category" class="col-sm-4 col-form-label"><?php echo display('parent_category') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="parent_category" name="parent_category" required>
                                    <option value=""><?php echo display('select_parent_category') ?></option>
                                    <?php foreach ($parent_categories as $category) { ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sub Category (Initially Hidden) -->
                    <div class="col-sm-6" id="sub_category_section" style="display: none;">
                        <div class="form-group row">
                            <label for="sub_category" class="col-sm-4 col-form-label"><?php echo display('sub_category') ?></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="sub_category" name="sub_category">
                                    <option value=""><?php echo display('select_sub_category') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Child Category (Initially Hidden) -->
                    <div class="col-sm-6" id="child_category_section" style="display: none;">
                        <div class="form-group row">
                            <label for="child_category" class="col-sm-4 col-form-label"><?php echo display('child_category') ?></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="child_category" name="child_category">
                                    <option value=""><?php echo display('select_child_category') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field to store the last selected category -->
                    <input type="hidden" id="category_id" name="category_id" required>
                </div>


                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="sell_price" class="col-sm-4 col-form-label"><?php echo display('sell_price') ?>
                                </label>
                            <div class="col-sm-8">
                                <input class="form-control text-right" id="sell_price" name="price" type="text"
                                   placeholder="0.00" tabindex="5" min="0"
                                    value="<?php echo $product->price?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="unit" class="col-sm-4 col-form-label"><?php echo display('unit') ?></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="unit" name="unit" tabindex="-1" aria-hidden="true">
                                    <option value="">Select One</option>
                                    <?php if ($unit_list) { ?>
                                    <?php foreach($unit_list as $units){?>
                                    <option value="<?php echo $units['unit_name']?>"
                                        <?php if($product->unit ==$units['unit_name']){echo 'selected';}?>>
                                        <?php echo $units['unit_name']?></option>

                                    <?php }} ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if(empty($supplier_pr)){?>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="cost_price" class="col-sm-4 col-form-label"><?php echo display('cost_price') ?>
                                </label>
                            <div class="col-sm-8">
                                <input class="form-control text-right" id="cost_price" name="supplier_price" type="text"
                                     placeholder="0.00" tabindex="5" min="0">
                            </div>
                        </div>
                    </div>
                    <?php }else{
                    foreach($supplier_pr as $supplier_product){
                    ?>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="cost_price" class="col-sm-4 col-form-label"><?php echo display('cost_price') ?>
                                </label>
                            <div class="col-sm-8">
                                <input class="form-control text-right" id="cost_price" name="supplier_price" type="text"
                                     placeholder="0.00" tabindex="5" min="0"
                                    value="<?php echo $supplier_product['supplier_price']?>">
                            </div>
                        </div>
                    </div>
                    <?php }}?>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="serial_no"
                                class="col-sm-4 col-form-label"><?php echo display('product_details') ?> </label>
                            <div class="col-sm-8">
                            <textarea class="form-control" name="description" id="description" rows="1"
                            placeholder="<?php echo display('product_details') ?>"
                            tabindex="2"><?php echo $product->product_details?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="image" class="col-sm-4 col-form-label"><?php echo display('image') ?> </label>
                            <div class="col-sm-8">
                                <input type="file" name="image" class="form-control" id="image" tabindex="4">
                                <input type="hidden" name="old_image" value="<?php echo $product->image;?>">
                            </div>
                        </div>
                    </div>
                    
                    
                    

                    <?php if($supplier_pr){?>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="image" class="col-sm-4 col-form-label"> </label>
                            <div class="col-sm-8">
                                <img src="<?php echo base_url().$product->image?>" alt="" width="100" height="80">

                            </div>
                        </div>
                    </div>
                    <?php }?>

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="category_id" class="col-sm-4 col-form-label"><?php echo display('supplier') ?>
                                <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <select name="supplier_id[]" class="form-control" required="">
                                        <option value=""> select Supplier</option>
                                        <?php if ($supplier) { ?>
                                        <?php foreach($supplier as $suppliers){?>
                                        <option value="<?php echo $suppliers['supplier_id']?>"
                                        <?php if($supplier_pr[0]['supplier_id']==$suppliers['supplier_id']){echo 'selected';}?>
                                        >
                                            <?php echo $suppliers['supplier_name']?></option>

                                        <?php }} ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                                 $i=0;

                    foreach ($taxfield as $taxss) {?>

                    <div class="col-sm-6" <?php if($vattaxinfo->dynamic_tax != 1){ echo 'hidden';}?>>
                        <div class="form-group row">
                            <label for="tax" class="col-sm-4 col-form-label"><?php echo $taxss['tax_name']; ?> <i
                                    class="text-danger"></i></label>
                            <div class="col-sm-7">
                                <input type="text" name="tax<?php echo $i;?>" class="form-control" value="<?php  $taxv = 'tax'.$i;
                              echo (!empty($supplier_pr)?($product->$taxv*100): number_format($taxss['default_value'], 2, '.', ','));
                              ?>">
                            </div>
                            <div class="col-sm-1"> <i class="text-success">%</i></div>
                        </div>
                    </div>

                    <?php $i++;} ?>
                </div>

                <div class="form-group row">
                    <div class="col-sm-6">

                        <input type="submit" id="add-product" class="btn btn-primary btn-large" name="add-product"
                            value="<?php echo display('save') ?>" tabindex="10" />
                    </div>
                </div>
            </div>
            <?php echo form_close() ?>
        </div>
        <input type="hidden" id="supplier_list"
            value='<?php if ($supplier) { ?><?php foreach($supplier as $suppliers){?><option value="<?php echo $suppliers['supplier_id']?>"><?php echo $suppliers['supplier_name']?></option><?php }}?>'
            name="">
    </div>
</div>
<!-- AJAX Script for Dynamic Categories -->
<script>
$(document).ready(function() {
    // Load Sub Categories when Parent Category Changes
    $('#parent_category').change(function() {
        var parent_id = $(this).val();
        $('#sub_category_section').hide();
        $('#child_category_section').hide();
        $('#sub_category').html('<option value=""><?php echo display('loading') ?>...</option>');
        $('#child_category').html('<option value=""><?php echo display('select_child_category') ?></option>');

        if (parent_id) {
            $.ajax({
                url: '<?php echo base_url("product/get_subcategories") ?>',
                type: 'POST',
                data: { parent_id: parent_id },
                dataType: 'json',
                success: function(response) {
                    if (response.length > 0) {
                        var options = '<option value=""><?php echo display('select_sub_category') ?></option>';
                        $.each(response, function(index, category) {
                            options += '<option value="' + category.category_id + '">' + category.category_name + '</option>';
                        });
                        $('#sub_category').html(options);
                        $('#sub_category_section').show();
                    } else {
                        $('#category_id').val(parent_id); // If no subcategory, save parent category
                    }
                }
            });
        }
    });

    // Load Child Categories when Sub Category Changes
    $('#sub_category').change(function() {
        var sub_id = $(this).val();
        $('#child_category_section').hide();
        $('#child_category').html('<option value=""><?php echo display('loading') ?>...</option>');

        if (sub_id) {
            $.ajax({
                url: '<?php echo base_url("product/get_childcategories") ?>',
                type: 'POST',
                data: { sub_id: sub_id },
                dataType: 'json',
                success: function(response) {
                    if (response.length > 0) {
                        var options = '<option value=""><?php echo display('select_child_category') ?></option>';
                        $.each(response, function(index, category) {
                            options += '<option value="' + category.category_id + '">' + category.category_name + '</option>';
                        });
                        $('#child_category').html(options);
                        $('#child_category_section').show();
                    } else {
                        $('#category_id').val(sub_id); // If no child category, save sub category
                    }
                }
            });
        }
    });

    // Set Last Selected Category before Submitting Form
    $('form').submit(function() {
        var category = $('#child_category').val() || $('#sub_category').val() || $('#parent_category').val();
        $('#category_id').val(category);
    });
});
</script>