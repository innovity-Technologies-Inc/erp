<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo $title ?> </h4>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table id="dataTableExample3" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php echo display('sl')?></th>
                                <th class="text-center"><?php echo display('category_name') ?></th>
                                <th class="text-center"><?php echo display('status') ?></th>
                                <th class="text-center"><?php echo display('action') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($category_list) {
                                $sl = 1;
                                $CI = &get_instance(); // Get CI instance for model calls
                                foreach ($category_list as $category) {
                                    if ($category->parent_id == NULL) { // Show only main categories initially
                                        display_category_row($category, $sl++, 0, $category_list, $CI);
                                    }
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="4" class="text-center"><?php echo display('no_record_found')?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Recursive Function to Display Categories Hierarchically
function display_category_row($category, $sl, $level, $category_list, $CI) {
    $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level); // Indentation for subcategories
    $is_last_layer = $CI->product_model->is_last_layer_category($category->category_id);
?>
    <tr>
        <td><?php echo $sl; ?></td>
        <td class="text-left"><?php echo $indent . ($level == 0 ? "* " : "# ") . $category->category_name; ?></td>
        <td class="text-center">
            <?php echo ($category->status == 1) ? display('active') : display('inactive'); ?>
        </td>
        <td class="text-center">
            <center>
                <?php if ($CI->permission1->method('manage_category', 'update')->access()) { ?>
                    <a href="<?php echo base_url().'category_form/'.$category->category_id; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" data-placement="left" title="<?php echo display('update') ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                <?php } ?>
                <?php if ($is_last_layer && $CI->permission1->method('manage_category', 'delete')->access()) { // Show Delete button only for last-layer categories ?>
                    <a href="<?php echo base_url().'product/product/paysenz_deletecategory/'.$category->category_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are You Sure To Want To Delete ?')" data-toggle="tooltip" data-placement="right" title="<?php echo display('delete') ?>"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                <?php } ?>
            </center>
        </td>
    </tr>
<?php
    // Display Subcategories
    foreach ($category_list as $sub_category) {
        if ($sub_category->parent_id == $category->category_id) {
            display_category_row($sub_category, $sl, $level + 1, $category_list, $CI);
        }
    }
}
?>