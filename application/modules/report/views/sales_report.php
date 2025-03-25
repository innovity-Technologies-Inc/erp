<!-- Sales Report -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <?php echo form_open('datewise_sales_report', array('class' => 'form-inline', 'method' => 'get')) ?>
                <?php $today = date('Y-m-d'); ?>

                <div class="form-group">
                    <label for="from_date"><?php echo display('start_date') ?></label>
                    <input type="text" name="from_date" class="form-control datepicker" id="from_date"
                        placeholder="<?php echo display('start_date') ?>" value="<?php echo html_escape($today); ?>">
                </div>

                <div class="form-group">
                    <label for="to_date"><?php echo display('end_date') ?></label>
                    <input type="text" name="to_date" class="form-control datepicker" id="to_date"
                        placeholder="<?php echo display('end_date') ?>" value="<?php echo html_escape($today); ?>">
                </div>

                <!-- ✅ Merchant filter -->
                <div class="form-group">
                    <label for="merchant_id"><?php echo display('customer') ?></label>
                    <select name="merchant_id" id="merchant_id" class="form-control" style="width: 200px;">
                        <option value=""><?php echo display('select_merchant') ?></option>
                        <?php foreach ($merchant_list as $merchant): ?>
                            <option value="<?php echo html_escape($merchant->id); ?>">
                                <?php echo html_escape($merchant->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" id="btn-filter" class="btn btn-success"><?php echo display('find') ?></button>
                <button type="button" class="btn btn-warning" onclick="printDiv('purchase_div')">
                    <?php echo display('print') ?>
                </button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <span><?php echo display('sales_report') ?></span>
                    <span class="padding-lefttitle">
                        <?php if ($this->permission1->method('all_report', 'read')->access()) { ?>
                            <a class="btn btn-primary" href="<?php echo base_url('todays_report') ?>">
                                <?php echo display('todays_report') ?>
                            </a>
                        <?php } ?>

                        <?php if ($this->permission1->method('todays_purchase_report', 'read')->access()) { ?>
                            <a href="<?php echo base_url('purchase_report') ?>" class="btn btn-success">
                                <i class="ti-align-justify"></i> <?php echo display('purchase_report') ?>
                            </a>
                        <?php } ?>

                        <?php if ($this->permission1->method('product_sales_reports_date_wise', 'read')->access()) { ?>
                            <a href="<?php echo base_url('product_wise_sales_report') ?>" class="btn btn-primary">
                                <i class="ti-align-justify"></i> <?php echo display('sales_report_product_wise') ?>
                            </a>
                        <?php } ?>

                        <?php if ($this->permission1->method('todays_sales_report', 'read')->access() && $this->permission1->method('todays_purchase_report', 'read')->access()) { ?>
                            <a href="<?php echo base_url('profit_report') ?>" class="btn btn-warning">
                                <i class="ti-align-justify"></i> <?php echo display('profit_report') ?>
                            </a>
                        <?php } ?>
                    </span>
                </div>
            </div>

            <div class="panel-body">
                <div id="purchase_div">
                    <div class="paddin5ps">
                        <table class="print-table" width="100%">
                            <tr>
                                <td align="left" class="print-table-tr">
                                    <img src="<?php echo base_url(html_escape($setting->logo)); ?>" alt="logo">
                                </td>
                                <td align="center" class="print-cominfo">
                                    <span class="company-txt">
                                        <?php echo isset($company_info[0]['company_name']) ? html_escape($company_info[0]['company_name']) : 'Company Name'; ?>
                                    </span><br>
                                    <?php echo isset($company_info[0]['address']) ? html_escape($company_info[0]['address']) : 'Company Address'; ?><br>
                                    <?php echo isset($company_info[0]['email']) ? html_escape($company_info[0]['email']) : 'Company Email'; ?><br>
                                    <?php echo isset($company_info[0]['mobile']) ? html_escape($company_info[0]['mobile']) : 'Company Mobile'; ?><br>
                                    <strong><?php echo display('sales_report') ?></strong>
                                </td>
                                <td align="right" class="print-table-tr">
                                    <date>
                                        <?php echo display('date') ?>: <?php echo date('d-M-Y'); ?>
                                    </date>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="table-responsive paddin5ps">
                        <table class="table table-striped table-bordered" cellspacing="0" width="100%" id="reportlist">
                            <thead>
                                <tr>
                                    <th><?php echo display('sales_date') ?></th>
                                    <th><?php echo display('invoice_no') ?></th>
                                    <th><?php echo display('customer_name') ?></th>
                                    <th><?php echo display('total_amount') ?></th>
                                    <th><?php echo display('total_discount') ?></th>
                                    <th><?php echo display('payable_amount') ?></th>
                                    <th><?php echo display('paid_amount') ?></th>
                                    <th><?php echo display('balance') ?></th>
                                    <th><?php echo display('payment_type') ?></th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Data will be populated by DataTables -->
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right"><?php echo display('total_purchase') ?>:</th>
                                    <th class="text-right" id="total_sales_amount">0.00</th>
                                    <th class="text-right" id="total_discount">0.00</th>
                                    <th class="text-right" id="total_payable">0.00</th>
                                    <th class="text-right" id="total_paid">0.00</th>
                                    <th class="text-right" id="total_balance">0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('my-assets/js/admin_js/sales_report.js') ?>" type="text/javascript"></script>