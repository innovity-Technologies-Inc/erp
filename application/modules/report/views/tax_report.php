

        <!-- Sales report -->
        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-body"> 
                        <?php echo form_open('tax_report', array('class' => 'form-inline', 'method' => 'get')) ?>
                        <?php
                     
                        $today = date('Y-m-d');
                        ?>
                        <div class="form-group">
                            <label class="" for="from_date"><?php echo display('start_date') ?></label>
                            <input type="text" name="from_date" class="form-control datepicker" id="from_date" placeholder="<?php echo display('start_date') ?>" value="<?php echo $from_date ?>">
                        </div> 

                        <div class="form-group">
                            <label class="" for="to_date"><?php echo display('end_date') ?></label>
                            <input type="text" name="to_date" class="form-control datepicker" id="to_date" placeholder="<?php echo display('end_date') ?>" value="<?php echo $to_date ?>">
                        </div>  

                        <button type="submit" class="btn btn-success"><?php echo display('search') ?></button>
                        <a  class="btn btn-warning" href="#" onclick="printDiv('purchase_div')"><?php echo display('print') ?></a>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <span><?php echo display('tax_report'); ?> </span>
                            <span class="padding-lefttitle">
                                          <?php if($this->permission1->method('todays_sales_report','read')->access()){ ?>
                    <a href="<?php echo base_url('sales_report') ?>" class="btn btn-info m-b-5 m-r-2"><i class="ti-align-justify"> </i> <?php echo display('sales_report') ?> </a>
                <?php }?>
        <?php if($this->permission1->method('todays_purchase_report','read')->access()){ ?>
                    <a href="<?php echo base_url('purchase_report') ?>" class="btn btn-success m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('purchase_report') ?> </a>
                  <?php }?>
                  <?php if($this->permission1->method('product_sales_reports_date_wise','read')->access()){ ?>
                    <a href="<?php echo base_url('product_wise_sales_report') ?>" class="btn btn-primary m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('sales_report_product_wise') ?> </a>
                    <?php }?>
    <?php if($this->permission1->method('todays_sales_report','read')->access() && $this->permission1->method('todays_purchase_report','read')->access()){ ?>
                    <a href="<?php echo base_url('profit_report') ?>" class="btn btn-warning m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('profit_report') ?> </a>
                    <?php }?>
                            </span>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div id="purchase_div">
                            <div class="paddin5ps">
                             <table class="print-table" width="100%">
                                                
                    <tr>
                        <td align="left" class="print-table-tr">
                            <img src="<?php echo base_url().$setting->logo;?>" alt="logo">
                        </td>
                        <td align="center" class="print-cominfo">
                            <span class="company-txt">
                                <?php echo $company_info[0]['company_name'];?>
                               
                            </span><br>
                            <?php echo $company_info[0]['address'];?>
                            <br>
                            <?php echo $company_info[0]['email'];?>
                            <br>
                             <?php echo $company_info[0]['mobile'];?>
                             <br>
                                    <strong><?php echo display('tax_report')?></strong>
                        </td>
                       
                         <td align="right" class="print-table-tr">
                            <date>
                            <?php echo display('date')?>: <?php
                            echo date('d-M-Y');
                            ?> 
                        </date>
                        </td>
                    </tr>            
                                   
                                </table>
                            </div>
                            <div class="table-responsive paddin5ps">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                    <tr>
                        <th><?php echo display('sales_date') ?></th>
                        <th><?php echo display('invoice_no') ?></th>
                        <th><?php echo display('total_tax')?></th>
                    </tr>
                                    </thead>
                                   
                                    <tbody>
                                        <?php
                                         $total_tax = 0;
                                        if ($sales_report) {
                                            ?>
                                    
                                            <?php 
                                            $total_tax = 0;
                                            foreach($sales_report as $sales){ ?>
                                            <tr>
                                                <td><?php echo html_escape($sales['sales_date'])?></td>
                                                <td> <?php echo html_escape($sales['invoice'])?>
                                                 
                                                </td>
                                       
                                                <td class="text-right"><?php
                                                 if($position == 0){
                                              echo $currency.' '.number_format($sales['total_tax'],2);  
                                            }else{
                                            echo number_format($sales['total_tax'],2).$currency; 
                                            }
                                                 $total_tax += $sales['total_tax'];
                                                ?></td>
                                            
                                            </tr>
                                            <?php } ?>
                                        <?php } else {
                                            ?>

                                            <tr>
                                                <th class="text-center" colspan="3"><?php echo display('not_found'); ?></th>
                                            </tr> 
                                        <?php } ?>
                                    </tbody>
                                     <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><b><?php echo display('total') ?></b></td>
                                            <td class="text-right"><b><?php echo (($position == 0) ? $currency.' '. number_format($total_tax) : number_format($total_tax).' '. $currency) ?></b></td>
                                          
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
