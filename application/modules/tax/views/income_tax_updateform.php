
       
 <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4><?php echo (!empty($title)?$title:null) ?></h4>
                    </div>
                </div>
                <div class="panel-body">

                <?php echo  form_open('tax/tax/update_income_tax/') ?>
                

                    <input name="tax_setup_id" type="hidden" value="<?php echo html_escape($data[0]['tax_setup_id']) ?>">
                 
                         <div class="form-group row">
                            <label for="start_amount" class="col-sm-3 col-form-label"><?php echo display('start_amount') ?><span class="text-danger"> *</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="start_amount" class="form-control" id="start_amount" value="<?php echo html_escape($data[0]['start_amount'])?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="end_amount" class="col-sm-3 col-form-label"><?php echo display('end_amount') ?> <span class="text-danger"> *</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="end_amount" class="form-control" id="end_amount" value="<?php echo html_escape($data[0]['end_amount'])?>">
                            </div>
                        </div> 

                       <div class="form-group row">
                            <label for="rate" class="col-sm-3 col-form-label"><?php echo display('rate') ?> <span class="text-danger"> *</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="rate" class="form-control" id="rate" value="<?php echo html_escape($data[0]['rate'])?>">
                            </div>
                        </div> 
                         
                        


                        <div class="form-group text-right">
                            
                            <button type="submit" class="btn btn-success w-md m-b-5"><?php echo display('update') ?></button>
                        </div>

                    <?php echo form_close() ?>


                </div>  
            </div>
        </div>
    </div>
    


