 <div class="row">
     <div class="col-sm-12">
         <div class="panel panel-bd lobidrag">
             <div class="panel-heading">
                 <div class="panel-title">
                     <h4><?php echo $title ?> </h4>
                 </div>
             </div>

             <div class="panel-body">
                <?php echo form_open_multipart('', 'class="" id="customer_form"') ?>

                 <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $customer->customer_id?>">

                 <div class="form-group row">
                    <!--Customer Name  -->
                    <label for="customer_name"
                         class="col-sm-2 text-right col-form-label"><?php echo display('customer_name')?> <i
                             class="text-danger"> * </i>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" name="customer_name" class="form-control" id="customer_name"
                                 placeholder="<?php echo display('customer_name')?>"
                                 value="<?php echo $customer->customer_name?>">
                             <input type="hidden" name="old_name" value="<?php echo $customer->customer_name?>">

                         </div>

                     </div>
                    <!-- Mobile No -->
                     <label for="customer_mobile"
                         class="col-sm-2 text-right col-form-label"><?php echo display('mobile_no')?> <i
                             class="text-danger"> * </i>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="number" name="customer_mobile"
                                 class="form-control input-mask-trigger text-left" id="customer_mobile"
                                 placeholder="<?php echo display('mobile_no')?>"
                                 value="<?php echo $customer->customer_mobile?>"
                                 data-inputmask="'alias': 'decimal', 'groupSeparator': '', 'autoGroup': true"
                                 im-insert="true">

                         </div>

                     </div>
                 </div>

                 <div class="form-group row">
                    <!-- Email Address -->
                     <label for="customer_email"
                         class="col-sm-2 text-right col-form-label"><?php echo display('email_address')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" class="form-control input-mask-trigger" name="customer_email" id="email"
                                 data-inputmask="'alias': 'email'" im-insert="true"
                                 placeholder="<?php echo display('email')?>"
                                 value="<?php echo $customer->customer_email?>">

                         </div>

                     </div>
                    <!-- EIN / VAT No -->
                     <label for="vat_no"
                         class="col-sm-2 text-right col-form-label"><?php echo display('vat_no')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" class="form-control" name="email_address" id="email_address"
                                 placeholder="<?php echo display('vat_no')?>"
                                 value="<?php echo $customer->email_address?>">

                         </div>

                     </div>
                 </div>
                 <div class="form-group row">
                    <!-- Phone No -->
                     <label for="phone"
                         class="col-sm-2 text-right col-form-label"><?php echo display('phone')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input class="form-control input-mask-trigger text-left" id="phone" type="number"
                                 name="phone" placeholder="<?php echo display('phone')?>"
                                 data-inputmask="'alias': 'decimal', 'groupSeparator': '', 'autoGroup': true"
                                 im-insert="true" value="<?php echo $customer->phone?>">

                         </div>

                     </div>

                     <label for="sales_permit_number" 
                     class="col-sm-2 text-right col-form-label"> <?php echo display('sellers_permit_number'); ?>:</label>
                    <div class="col-sm-4">
                        <input type="text" name="sales_permit_number" class="form-control" id="sales_permit_number"
                            placeholder="<?php echo display('sellers_permit_number'); ?>"
                            value="<?php echo $customer->sales_permit_number; ?>">
                    </div>

                 </div>
                 <div class="form-group row">
                    <!-- Address1 -->
                     <label for="address1"
                         class="col-sm-2 text-right col-form-label"><?php echo display('address1')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <textarea name="customer_address" id="customer_address" class="form-control"
                                 placeholder="<?php echo display('address1')?>"><?php echo $customer->customer_address?></textarea>

                         </div>

                     </div>

                     <label for="sales_permit" class="col-sm-2 text-right col-form-label"><?php echo display('sellers_permit_document'); ?>:</label>
                    <div class="col-sm-4">
                        <input type="file" name="sales_permit" id="sales_permit" class="form-control">
                        <?php if (!empty($customer->sales_permit)) { ?>
                            <a href="<?php echo base_url('uploads/sales_permits/' . $customer->sales_permit); ?>" target="_blank">
                                View Current File
                            </a>
                        <?php } ?>
                    </div>
                     
                 </div>
                 <div class="form-group row">
                    <!-- Fax -->
                     <label for="fax" class="col-sm-2 text-right col-form-label"><?php echo display('fax')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" name="fax" class="form-control" id="fax"
                                 placeholder="<?php echo display('fax')?>" value="<?php echo $customer->fax?>">

                         </div>

                     </div>
                     <!-- City -->
                     <label for="city" class="col-sm-2 text-right col-form-label"><?php echo display('city')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" name="city" class="form-control" id="city"
                                 placeholder="<?php echo display('city')?>" value="<?php echo $customer->city?>">

                         </div>

                     </div>
                 </div>
                 <div class="form-group row">
                    <!-- State -->
                     <label for="state"
                         class="col-sm-2 text-right col-form-label"><?php echo display('state')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input type="text" name="state" class="form-control" id="state"
                                 placeholder="<?php echo display('state')?>" value="<?php echo $customer->state?>">

                         </div>

                     </div>
                     <!-- Zip -->
                     <label for="zip" class="col-sm-2 text-right col-form-label"><?php echo display('zip')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input name="zip" type="text" class="form-control" id="zip"
                                 placeholder="<?php echo display('zip')?>" value="<?php echo $customer->zip?>">

                         </div>

                     </div>
                 </div>
                 <div class="form-group row">
                    <!-- Country -->
                     <label for="country"
                         class="col-sm-2 text-right col-form-label"><?php echo display('country')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input name="country" type="text" class="form-control "
                                 placeholder="<?php echo display('country')?>" value="<?php echo $customer->country?>"
                                 id="country">

                         </div>

                     </div>

                     <label for="status" class="col-sm-2 text-right col-form-label"><?php echo display('status')?>:</label>
                        <div class="col-sm-4">
                            <div class="">
                                <select name="status" id="status" class="form-control">
                                    <option value="2" <?php echo $customer->status == 2 ? 'selected' : ''; ?>>Deleted</option>
                                    <option value="1" <?php echo $customer->status == 1 ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $customer->status == 0 ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                     <?php if(empty($customer->customer_id)){?>

                     <!-- <label for="previous_balance"
                         class="col-sm-2 text-right col-form-label"><?php echo display('previous_balance')?>:</label>
                     <div class="col-sm-4">
                         <div class="">

                             <input name="previous_balance" type="number"
                                 class="form-control text-right input-mask-trigger"
                                 placeholder="<?php echo display('previous_balance')?>"
                                 data-inputmask="'alias': 'decimal', 'groupSeparator': '', 'autoGroup': true"
                                 im-insert="true">

                         </div>

                     </div> -->
                     <?php }?>

                 </div>
                 <div class="form-group row">
                    <!-- Comission Value -->
                    <label for="comission_value" class="col-sm-2 text-right col-form-label">
                        <?php echo display('comission_value'); ?>:
                    </label>
                    <div class="col-sm-4">
                        <input type="text" name="comission_value" class="form-control" id="comission_value"
                            placeholder="<?php echo display('comission_value'); ?>"
                            value="<?php echo !empty($customer->comission_value) ? $customer->comission_value : ''; ?>">
                    </div>

                    <!-- Comission Type -->
                    <label for="comission_type" class="col-sm-2 text-right col-form-label">
                        <?php echo display('comission_type'); ?>:
                    </label>
                    <div class="col-sm-4">
                        <select name="comission_type" id="comission_type" class="form-control">
                            <option value="1" <?php echo (isset($customer->comission_type) && $customer->comission_type == 1) ? 'selected' : ''; ?>>Percent</option>
                            <option value="0" <?php echo (isset($customer->comission_type) && $customer->comission_type == 0) ? 'selected' : ''; ?>>Flat</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <!-- Comission Note -->
                    <label for="comission_note" class="col-sm-2 text-right col-form-label">
                        <?php echo display('comission_note'); ?>:
                    </label>
                    <div class="col-sm-4">
                        <input type="text" name="comission_note" class="form-control" id="comission_note"
                            placeholder="<?php echo display('comission_note'); ?>"
                            value="<?php echo !empty($customer->comission_note) ? $customer->comission_note : ''; ?>">
                    </div>
                </div>


                 <div class="form-group row">
                    <label for="password_option" class="col-sm-2 text-right col-form-label">
                        <?php echo display('password_option'); ?>:
                    </label>
                    <div class="col-sm-4">
                        <select name="password_option" id="password_option" class="form-control" onchange="togglePasswordFields(this.value)">
                            <option value="">Select Option</option>
                            <option value="set">Set Password</option>
                            <option value="reset">Reset Password</option>
                        </select>
                    </div>

                    <label for="password" class="col-sm-2 text-right col-form-label">
                        <?php echo display('password'); ?>:
                    </label>
                    <div class="col-sm-4">
                        <input type="text" name="password" class="form-control" id="password"
                            placeholder="<?php echo display('password'); ?>" readonly>
                        <small id="password_help" class="form-text text-muted"></small>
                    </div>
                </div>

                <div class="form-group row" id="generate_password_section" style="display:none;">
                    <label class="col-sm-2 text-right col-form-label"></label>
                    <div class="col-sm-10">
                        <button type="button" class="btn btn-info" onclick="generateRandomPassword()">
                            Generate Password
                        </button>
                        <span class="text-success ml-2" id="generated_pass_msg" style="display:none;">Password Generated!</span>
                    </div>
                </div>

                 <div class="form-group row">
                     <div class="col-sm-6 text-right">
                     </div>
                     <div class="col-sm-6 text-right">
                         <div class="">

                         <button type="submit" class="btn btn-success">
                            <?php echo (empty($customer->customer_id) ? display('save') : display('update')) ?>
                        </button>

                         </div>

                     </div>
                 </div>

                 <?php echo form_close();?>
             </div>
         </div>
     </div>
 </div>

 <script>
    function togglePasswordFields(option) {
    const passwordInput = document.getElementById('password');
    const genSection = document.getElementById('generate_password_section');
    const helpText = document.getElementById('password_help');
    const msg = document.getElementById('generated_pass_msg');

    if (option === 'set') {
        passwordInput.removeAttribute('readonly');
        genSection.style.display = 'flex';
        helpText.innerText = 'You can either type a password manually or click "Generate Password".';
        msg.style.display = 'none';
    } else if (option === 'reset') {
        passwordInput.removeAttribute('readonly');
        genSection.style.display = 'none';
        passwordInput.value = '';
        helpText.innerText = 'Type the new password to reset.';
        msg.style.display = 'none';
    } else {
        passwordInput.setAttribute('readonly', true);
        passwordInput.value = '';
        genSection.style.display = 'none';
        helpText.innerText = '';
        msg.style.display = 'none';
    }
}

function generateRandomPassword() {
    const hexChars = '0123456789abcdef';
    let password = '';
    for (let i = 0; i < 8; i++) {
        password += hexChars[Math.floor(Math.random() * 16)];
    }
    document.getElementById('password').value = password;
    document.getElementById('generated_pass_msg').style.display = 'inline';
}
 </script>