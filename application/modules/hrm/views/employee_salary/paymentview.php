<div class="row">
    <!--  table area -->
    <div class="col-sm-12">

        <div class="panel panel-bd"> 

            <div class="panel-heading">
              <div class="panel-title">
                  <h4>
                    <?php echo display('view_employee_payment')?>
                  </h4>
              </div>
            </div>

            <div class="panel-body">
                <table width="100%" class="datatable table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th><?php echo display('Sl') ?></th>
                            <th><?php echo display('salary_month') ?></th>
                            <th><?php echo display('employee_name') ?></th>
                            <th><?php echo display('total_salary') ?></th>
                            
                            <th><?php echo display('action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($emp_pay)) { ?>
                            <?php $sl = 1; ?>
                            <?php foreach ($emp_pay as $que) { ?>
                                <tr class="<?php echo ($sl & 1)?"odd gradeX":"even gradeC" ?>">
                                        <td><?php echo $sl; ?></td>
                                        <td><?php echo $que->sal_month_year; ?></td>
                                        <td><?php echo $que->first_name.' '.$que->last_name; ?></td>
                                        <td><?php echo $currency.' '.$que->net_salary; ?></td>
                                        
                                        <td class="center">
                                   		
                                        <a target="_blank" href='<?php echo base_url("salary_pay_slip/$que->id") ?>' class='btn btn-info btn-xs'>Payslip</a>

                                        
                                       <?php 

                                        if(@$que->payment_due =='paid'){?>
                                       <?php } 
                                        else {?>
                                          
                                      <?php  }

                                        ?>
                                                                           
                                </td>
                                </tr>
                                <?php $sl++; ?>
                            <?php } ?> 
                        <?php } ?> 
                    </tbody>
                </table>  <!-- /.table-responsive -->
                 <?php echo  $links ?> 
            </div>
        </div>
    </div>
     <div id="PaymentMOdal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <strong><center> <?php echo display('payment')?></center></strong>
            </div>
            <div class="modal-body">
           
   <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4><?php echo 'Payment Form'; ?></h4>
                    </div>
                </div>
                <div class="panel-body">

                <?php echo  form_open('employee/Employees/payconfirm/') ?>
                

                    <input name="emp_sal_pay_id" id="salType" type="hidden" value="">
                 
                         <div class="form-group row">
                            <label for="employee_id" class="col-sm-3 col-form-label"><?php echo display('employee_name') ?> </label>
                            <div class="col-sm-9">
                                <input type="text" name="empname" class="form-control" id="employee_name" value="" readonly>
                                <input type="hidden" name="employee_id" class="form-control" id="employee_id" value="">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="total_salary" class="col-sm-3 col-form-label"><?php echo display('total_salary') ?> </label>
                            <div class="col-sm-9">
                                <input type="text" name="total_salary" class="form-control" id="total_salary" value="" readonly>
                            </div>
                        </div> 

                       <div class="form-group row">
                            <label for="total_working_minutes" class="col-sm-3 col-form-label"><?php echo display('total_working_minutes') ?> </label>
                            <div class="col-sm-9">
                                <input type="text" name="total_working_minutes" class="form-control" id="total_working_minutes" value="" readonly>
                            </div>
                        </div> 
                         <div class="form-group row">
                            <label for="working_period" class="col-sm-3 col-form-label"><?php echo display('working_period') ?> </label>
                            <div class="col-sm-9">
                                <input type="text" name="working_period" class="form-control" id="working_period" value="" readonly>
                            </div>
                        </div> 
                                <div class="form-group row">
                                    <label for="payment_type" class="col-sm-3 col-form-label"><?php
                                        echo display('payment_type');
                                        ?> <i class="text-danger">*</i></label>
                                    <div class="col-sm-9">
                                        <select name="paytype" class="form-control" required="" id="paytype" onchange="bank_paymet(this.value)" required="">
                                            <option value="">Select Payment Option</option>
                                            <option value="1">Cash Payment</option>
                                            <option value="2">Bank Payment</option>
                                        </select>
                                    </div>
                                 
                                </div>
                      
                          
                                <div class="form-group row" id="bank_div">
                                    <label for="payment_type" class="col-sm-3 col-form-label"><?php
                                        echo display('bank_name');
                                        ?> <i class="text-danger">*</i></label>
                                    <div class="col-sm-9">
                                    <select name="bank_name" class="form-control" id="bank">
                                    <option value="">Select Payment Option</option>
                                            <?php foreach($bank_list as $banks){?>
                                            <option value="<?php echo $banks['bank_name']?>"><?php echo $banks['bank_name']?></option>
                                            <?php }?>
                                            
                                        </select>
                                    </div>
                                 
                                </div>
                          
                    
               <div class="form-group text-center">
                            <button type="submit" class="btn btn-danger" data-dismiss="modal">&times; Cancel</button>
                            <button type="submit" class="btn btn-primary"><?php echo display('confirm')?></button>
                        </div>

                    <?php echo form_close() ?>


                </div>  
            </div>
        </div>
    </div>
             
    </div>
     
            </div>
            <div class="modal-footer">

            </div>

        </div>

    </div>
</div>
 
<script src="<?php echo base_url('my-assets/js/admin_js/payroll.js') ?>" type="text/javascript"></script>
