 <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-body"> 
                        <?php echo form_open('customer_ledgerdata', array('class' => '', 'id' => 'validate')) ?>
                        <?php $today = date('Y-m-d'); ?>
                        <div class="row">
                       <div class="col-sm-4">
                        <div class="form-group row">
                            <label for="customer_name" class="col-sm-4 col-form-label"><?php echo display('customer') ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-8">
                                <select name="customer_id"  class="form-control" required="">
                                    <option value=""></option>
                                   <?php foreach($customer as $customers){?>
                                    <option value="<?php echo html_escape($customers['customer_id'])?>"  <?php if($customers['customer_id'] == $customer_id){echo 'selected';}?>><?php echo html_escape($customers['customer_name'])?></option>
                                   <?php }?>
                                </select>
                            </div>
                            </div>
                            </div> 
                            <div class="col-sm-5">
                        <div class="form-group row">
                                <label for="from_date " class="col-sm-2 col-form-label"> <?php echo display('from') ?></label>
                                <div class="col-sm-4">
                                    <input type="text" name="from_date"  value="<?php echo (!empty($start)?$start:$today); ?>" class="datepicker form-control" id="from_date"/>
                                </div>
                                 <label for="to_date" class="col-sm-2 col-form-label"> <?php echo display('to') ?></label>
                                <div class="col-sm-4">
                                    <input type="text" name="to_date" value="<?php echo (!empty($end)?$end:$today); ?>" class="datepicker form-control" id="to_date"/>
                                </div>
                          
                        </div>
                    </div>

                       <div class="col-sm-3">
                       
                                <button type="submit" class="btn btn-success "><i class="fa fa-search-plus" aria-hidden="true"></i> <?php echo display('search') ?></button>
                                <button type="button" class="btn btn-warning"  onclick="printDiv('printableArea')"><?php echo display('print') ?></button>
                        
                        
                    </div>
                </div>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- customer ledger -->
        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <span><?php echo display('customer_ledger') ?></span>
                             <span class="padding-lefttitle">
                                 <?php if($this->permission1->method('add_customer','create')->access()){ ?>
                    <a href="<?php echo base_url('add_customer') ?>" class="btn btn-info m-b-5 m-r-2"><i class="ti-plus"> </i> <?php echo display('add_customer') ?> </a>
                   <?php }?>
                <?php if($this->permission1->method('manage_customer','read')->access()){ ?>
                    <a href="<?php echo base_url('customer_list') ?>" class="btn btn-primary m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('manage_customer') ?> </a>
                 <?php }?>
                 <?php if($this->permission1->method('credit_customer','read')->access()){ ?>
                    <a href="<?php echo base_url('credit_customer') ?>" class="btn btn-success m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('credit_customer') ?> </a>
                    <?php }?>
                   <?php if($this->permission1->method('paid_customer','read')->access()){ ?>
                    <a href="<?php echo base_url('paid_customer') ?>" class="btn btn-warning m-b-5 m-r-2"><i class="ti-align-justify"> </i>  <?php echo display('paid_customer') ?> </a>
                       <?php }?>
                   </span>

                        </div>
                    </div>
                    <div class="panel-body">
                        <div id="printableArea">

                            <?php if ($customer_name) { ?>
                                <div class="text-center">
                                    <h3> <?php echo $customer_name;?> </h3>
                                    <h4><?php echo display('address') ?> : <?php echo $address?> </h4>
                                    <h4> <?php echo display('print_date') ?>: <?php echo date("d/m/Y h:i:s"); ?> </h4>
                                </div>
                            <?php } ?>

                            <div class="table-responsive">

                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center"><?php echo display('date') ?></th>
                                            <th class="text-center"><?php echo display('description') ?></th>
                                            <th class="text-center"><?php echo display('voucher_no') ?></th>
                                            <th class="text-right"><?php echo display('debit') ?></th>
                                            <th class="text-right"><?php echo display('credit') ?></th>
                                            <th class="text-right"><?php echo display('balance') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($ledgers) {
                                            $sl = 0;
                                            $debit = $credit = $balance = 0;
                                            foreach ($ledgers as $ledger) {
                                                $sl++;
                                                ?>
                                                <tr>
                                                    <td class="text-center"><?php echo html_escape($ledger['VDate']); ?></td>
                                                    <td><?php echo html_escape($ledger['Narration']); ?></td>
                                                    <td>
                                                 
                                                    <?php echo html_escape($ledger['VNo']); ?>
                                                </td>
                                                   
                                                    <td align="right">
                                                        <?php
                                                        
                                                            echo (($position == 0) ? "$currency " : " $currency");
                                                            echo html_escape(number_format($ledger['Debit'], 2, '.', ','));
                                                            $debit += $ledger['Debit'];
                                                       
                                                        ?>
                                                    </td>
                                                    <td align="right">
                                                        <?php
                                                        
                                                            echo (($position == 0) ? "$currency " : " $currency");
                                                            echo html_escape(number_format($ledger['Credit'], 2, '.', ','));
                                                            $credit += $ledger['Credit'];
                                                      
                                                        ?>
                                                    </td>
                                                    <td align='right'>
                                                        <?php
                                                        $balance = $debit - $credit;
                                                        echo (($position == 0) ? "$currency " : " $currency");
                                                        echo html_escape(number_format($balance, 2, '.', ','));
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }else{
                                        ?>
                                        <tr><td colspan="6"><center>No Record Found</center></td></tr>
                                        
                                        <?php }?>
                                    
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" align="right"><b><?php echo display('grand_total') ?>:</b></td>
                                            <td align="right"><b><?php
                                                    echo (($position == 0) ? "$currency " : "$currency");
                                                    echo html_escape(number_format((@$debit), 2, '.', ','));
                                                    ?></b>
                                            </td>
                                            <td align="right"><b><?php
                                                    echo (($position == 0) ? "$currency " : "$currency");
                                                    echo html_escape(number_format((@$credit), 2, '.', ','));
                                                    ?></b>
                                            </td>
                                            <td align="right"><b><?php
                                                    echo (($position == 0) ? "$currency " : "$currency");
                                                    echo html_escape(number_format((@$balance), 2, '.', ','));
                                                    ?></b></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                            </div>
                        </div>
                        <div class="text-right"><?php echo $links ?></div>
                    </div>
                </div>
            </div>
        </div>