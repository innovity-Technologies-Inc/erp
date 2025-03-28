<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo $title ?> </h4>
                </div>
            </div>
            <div class="panel-body">
                <?php echo form_open('category_form/'.$category->category_id, 'class="" id="category_form"'); ?>

                <!-- Hidden Field: Store Category ID -->
                <input type="hidden" name="category_id" id="category_id" value="<?php echo $category->category_id; ?>">

                <!-- Hidden Field: Store Final Category Name -->
                <input type="hidden" name="final_category_name" id="final_category_name">

                <!-- Category Name Input -->
                <div class="form-group row">
                    <label for="category_name" class="col-sm-2 text-right col-form-label" id="category_name_label" 
                        data-default-name="<?php echo display('category_name'); ?>">
                        <span id="category_name_text"><?php echo display('category_name'); ?></span> 
                        <i class="text-danger"> * </i>:
                    </label>
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text" id="parent_category_label"></span> <!-- Prefix for Parent -->
                            <input type="text" name="category_name" class="form-control" id="category_name"
                                placeholder="<?php echo display('category_name'); ?>"
                                value="<?php echo $category->category_name; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Parent Category Selection -->
                <div class="form-group row">
                <label for="category_name" class="col-sm-2 text-right col-form-label" 
                    id="category_name_label" 
                    data-default-name="<?php echo display('parent_category'); ?>">
                    <span id="category_name_text"><?php echo display('parent_category'); ?></span> 
                    <i class="text-danger"> * </i>:
                </label>
                    <div class="col-sm-4">
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="0" data-name=""><?php echo display('main_category'); ?></option>
                            <?php 
                            foreach ($categories as $cat) { 
                                $category_level = $this->product_model->get_category_level($cat->category_id);
                                $disabled = ($category_level >= 3) ? 'disabled' : ''; // ✅ Disable 3rd-level categories
                            ?>
                                <option value="<?php echo $cat->category_id ?>" 
                                    data-name="<?php echo $this->product_model->get_category_name($cat->category_id); ?>" 
                                    <?php echo $disabled; ?>
                                    <?php if ($category->parent_id == $cat->category_id) echo 'selected'; ?>>
                                    <?php echo str_repeat('-- ', $category_level - 1) . $cat->category_name; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">
                            <?php echo display('select_main_for_main_category_or_parent_for_sub_or_child_category'); ?>
                        </small>
                    </div>
                </div>

                <!-- Status Dropdown -->
                <div class="form-group row">
                    <label for="status" class="col-sm-2 text-right col-form-label">
                        <?php echo display('status'); ?> <i class="text-danger"> * </i>:
                    </label>
                    <div class="col-sm-4">
                        <select name="status" id="status" class="form-control" required>
                            <option value="1" <?php if($category->status == 1) echo 'selected'; ?>>
                                <?php echo display('active'); ?>
                            </option>
                            <option value="0" <?php if($category->status == 0) echo 'selected'; ?>>
                                <?php echo display('inactive'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Save & Save Another Buttons -->
                <div class="form-group row">
                    <div class="col-sm-6 text-right">
                        <button type="submit" class="btn btn-success" name="submit_action" value="save">
                            <?php echo (empty($category->category_id) ? display('save') : display('update')); ?>
                        </button>

                        <?php if(empty($category->category_id)) { ?>
                            <button type="submit" class="btn btn-success" name="submit_action" value="add_another">
                                <?php echo display('save_and_add_another'); ?>
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
