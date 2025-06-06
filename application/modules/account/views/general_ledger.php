<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4>
                        <?php echo display('general_ledger')?>
                    </h4>
                </div>
            </div>
            <div class="panel-body">
                <?php echo  form_open_multipart('accounts_report_search') ?>
                <div class="row" id="">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="date" class="col-sm-4 col-form-label"><?php echo display('transaction_head')?></label>
                            <div class="col-sm-8">
                                <select class="form-control" name="cmbCode" id="cmbCode" required>
                                    <option></option>
                                    <?php
                                    foreach($general_ledger as $g_data){
                                        ?>
                                        <option value="<?php echo $g_data->HeadCode;?>"><?php echo $g_data->HeadName;?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                      
                        <div class="form-group row">
                            <label for="date" class="col-sm-4 col-form-label"><?php echo display('from_date') ?></label>
                            <div class="col-sm-8">
                                <input type="text" name="dtpFromDate" value="<?php echo date('Y-m-d');?>" placeholder="<?php echo display('date') ?>" class="datepicker form-control">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="date" class="col-sm-4 col-form-label"><?php echo display('to_date') ?></label>
                            <div class="col-sm-8">
                                <input type="text"  name="dtpToDate" value="<?php echo date('Y-m-d');?>" placeholder="<?php echo display('date') ?>" class="datepicker form-control">
                            </div>
                        </div>                       
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-success w-md m-b-5"><?php echo display('find') ?></button>
                        </div>
                    </div>
                </div>
                <?php echo form_close() ?>
            </div>
        </div>
    </div>
</div>

 <script src="<?php echo base_url('application/modules/account/assets/js/general_ledger_script.js'); ?>" type="text/javascript"></script> 