<!-- Purchase report -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <?php echo form_open('purchase_report', array('class' => 'form-inline', 'method' => 'get')) ?>
                <?php
                        $today = date('Y-m-d');
                        ?>
                <div class="form-group">
                    <label class="" for="from_date"><?php echo display('start_date') ?></label>
                    <input type="text" name="from_date" class="form-control datepicker" id="from_date"
                        placeholder="<?php echo display('start_date') ?>" value="<?php echo $from ?>">
                </div>

                <div class="form-group">
                    <label class="" for="to_date"><?php echo display('end_date') ?></label>
                    <input type="text" name="to_date" class="form-control datepicker" id="to_date"
                        placeholder="<?php echo display('end_date') ?>" value="<?php echo $to ?>">
                </div>

                <button type="button" id="btn-filter" class="btn btn-success"><?php echo display('find') ?></button>
                <a class="btn btn-warning" href="#"
                    onclick="printDiv('purchase_div')"><?php echo display('print') ?></a>
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
                    <span><?php echo display('purchase_report') ?></span>
                    <span class="padding-lefttitle">
                        <?php if($this->permission1->method('todays_sales_report','read')->access()){ ?>
                        <a href="<?php echo base_url('sales_report') ?>" class="btn btn-info m-b-5 m-r-2"><i
                                class="ti-align-justify"> </i> <?php echo display('sales_report') ?> </a>
                        <?php }?>
                        <?php if($this->permission1->method('product_sales_reports_date_wise','read')->access()){ ?>
                        <a href="<?php echo base_url('product_wise_sales_report') ?>"
                            class="btn btn-primary m-b-5 m-r-2"><i class="ti-align-justify"> </i>
                            <?php echo display('sales_report_product_wise') ?> </a>
                        <?php }?>
                        <?php if($this->permission1->method('todays_sales_report','read')->access() && $this->permission1->method('todays_purchase_report','read')->access()){ ?>
                        <a href="<?php echo base_url('profit_report') ?>" class="btn btn-warning m-b-5 m-r-2"><i
                                class="ti-align-justify"> </i> <?php echo display('profit_report') ?> </a>
                        <?php }?></span>
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
                                    <nobr><strong><?php echo display('purchase_report')?></strong></nobr>
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
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" cellspacing="0" width="100%" id="reportlist">
                            <thead>
                                <tr>
                                    <th><?php echo display('date') ?></th>
                                    <th><?php echo display('invoice_no') ?></th>
                                    <th><?php echo display('chalan_no') ?></th>
                                    <th><?php echo display('supplier_name') ?></th>
                                    <th><?php echo display('total_ammount') ?> </th>
                                    <th><?php echo display('paid_ammount') ?> </th>
                                    <th><?php echo display('due_amount') ?> </th>
                                    <th><?php echo display('payment_type') ?> </th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right"><?php echo display('total_purchase') ?>:</th>
                                    <th class="text-right" id="total_sales_amount">0.00</th>
                                    <th class="text-right" id="total_paid">0.00</th>
                                    <th class="text-right" id="total_due">0.00</th>
                                    <th></th> <!-- Payment Type Column (No Totals Needed) -->
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('my-assets/js/admin_js/purchase_report.js') ?>" type="text/javascript"></script>
