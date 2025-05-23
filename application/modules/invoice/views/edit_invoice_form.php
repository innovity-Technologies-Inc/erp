<script src="<?php echo base_url() ?>my-assets/js/admin_js/invoice.js" type="text/javascript"></script>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo display('invoice_edit') ?></h4>
                </div>
            </div>
            <?php echo form_open('invoice/invoice/paysenz_update_invoice', array('class' => 'form-vertical', 'id' => 'update_invoice')) ?>
            <div class="panel-body">

                <div class="row">
                    <div class="col-sm-6" id="payment_from_1">
                        <div class="form-group row">
                            <label for="product_name"
                                class="col-sm-3 col-form-label"><?php echo display('customer_name').'/'.display('phone') ?>
                                <i class="text-danger">*</i></label>
                            <div class="col-sm-6">
                                <input type="text" name="customer_name" value="<?php echo $customer_name?>"
                                    onkeyup="customer_autocomplete()" class="form-control customerSelection"
                                    placeholder='<?php echo display('customer_name') ?>' required id="customer_name"
                                    tabindex="1">

                                <input type="hidden" class="customer_hidden_value" name="customer_id"
                                    value="<?php echo $customer_id;?>" id="autocomplete_customer_id" />
                            </div>
                        </div>
                    </div>
                    
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="product_name" class="col-sm-3 col-form-label"><?php echo display('date') ?> <i
                                    class="text-danger">*</i></label>
                            <div class="col-sm-6">
                                <input type="text" tabindex="2" class="form-control datepicker" name="invoice_date"
                                    value="<?php echo $date?>" required />
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="invoice_no" class="col-sm-3 col-form-label"><?php
                                    echo display('invoice_no');
                                    ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-6">
                                <input class="form-control" type="text" name="invoice_no" id="invoice_no" required
                                    value="<?php echo html_escape($invoice); ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6" id="bank_div">
                        <div class="form-group row">
                            <label for="bank" class="col-sm-3 col-form-label"><?php
                                    echo display('bank');
                                    ?> <i class="text-danger">*</i></label>
                            <div class="col-sm-6">
                                <select name="bank_id" class="form-control bankpayment" id="bank_id">
                                    <option value="">Select Location</option>
                                    <?php foreach($bank_list as $bank){?>
                                    <option value="<?php echo html_escape($bank['bank_id'])?>"
                                        <?php if($bank['bank_id'] == $bank_id){echo 'selected';}?>>
                                        <?php echo html_escape($bank['bank_name']);?></option>
                                    <?php }?>
                                </select>
                                <input type="hidden" id="editpayment_type" value="<?php echo $paytype;?>" name="">
                            </div>

                        </div>
                    </div>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="normalinvoice">
                        <thead>
                            <tr>
                                <th class="text-center"><?php echo display('item_information') ?> <i
                                        class="text-danger">*</i></th>
                                <th class="text-center"><?php echo display('item_description')?></th>
                                <th class="text-center"><?php echo display('batch_no')?><i class="text-danger">*</i>
                                </th>
                                <th class="text-center"><?php echo display('available_qnty') ?></th>
                                <th class="text-center"><?php echo display('unit') ?></th>
                                <th class="text-center"><?php echo display('quantity') ?> <i class="text-danger">*</i>
                                </th>

                                <th class="text-center"><?php echo display('rate') ?> <i class="text-danger">*</i></th>

                                <?php if ($discount_type == 1) { ?>
                                <th class="text-center"><?php echo display('discount_percentage') ?> %</th>
                                <?php } elseif ($discount_type == 2) { ?>
                                <th class="text-center"><?php echo display('discount') ?> </th>
                                <?php } elseif ($discount_type == 3) { ?>
                                <th class="text-center"><?php echo display('fixed_dis') ?> </th>
                                <?php } ?>
                                <th class="text-center invoice_fields"><?php echo display('dis_val') ?> </th>
                                <th class="text-center invoice_fields"><?php echo display('vat').' %' ?> </th>
                                <th class="text-center invoice_fields"><?php echo display('vat_val') ?> </th>
                                <th class="text-center"><?php echo display('total') ?> <i class="text-danger">*</i></th>
                                <th class="text-center"><?php echo display('action') ?></th>
                            </tr>
                        </thead>
                        <tbody id="addinvoiceItem">

                            <?php
                            foreach($invoice_all_data as $details){?>
                            <tr>
                                <td class="product_field">
                                    <input type="text" name="product_name"
                                        onkeypress="invoice_productList(<?php echo $details['sl']?>);"
                                        value="<?php echo $details['product_name']?>-(<?php echo $details['product_model']?>)"
                                        class="form-control productSelection" required
                                        placeholder='<?php echo display('product_name') ?>'
                                        id="product_name_<?php echo $details['sl']?>" tabindex="3">

                                    <input type="hidden"
                                        class="product_id_<?php echo $details['sl']?> autocomplete_hidden_value"
                                        name="product_id[]" value="<?php echo $details['product_id']?>"
                                        id="SchoolHiddenId" />
                                </td>
                                <td>
                                    <input type="text" name="desc[]" class="form-control text-right "
                                        value="<?php echo $details['description']?>" />
                                </td>
                                <td>
                                    <select class="form-control invoice_fields"
                                        id="serial_no_<?php echo $details['sl']?>" required name="serial_no[]">

                                        <option value="<?php echo $details['batch_id']?>">
                                            <?php echo $details['batch_id']?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="available_quantity[]"
                                        class="form-control text-right available_quantity_<?php echo $details['sl']?>"
                                        value="<?php echo $details['stock_qty']?>" readonly="" />
                                </td>
                                <td>
                                    <input type="text" name="unit[]" class="form-control text-right " readonly=""
                                        value="<?php echo $details['unit']?>" />
                                </td>
                                <td>
                                    <input type="text" name="product_quantity[]"
                                        onkeyup="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        onchange="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        value="<?php echo $details['quantity']?>"
                                        class="total_qntt_<?php echo $details['sl']?> form-control text-right"
                                        id="total_qntt_<?php echo $details['sl']?>" min="0" placeholder="0.00"
                                        tabindex="4" required="required" />
                                </td>

                                <td>
                                    <input type="text" name="product_rate[]"
                                        onkeyup="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        onchange="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        value="<?php echo $details['rate']?>"
                                        id="price_item_<?php echo $details['sl']?>"
                                        class="price_item<?php echo $details['sl']?> form-control text-right" min="0"
                                        tabindex="5" required="" placeholder="0.00" />
                                </td>
                                <!-- Discount -->
                                <td>
                                    <input type="text" name="discount[]"
                                        onkeyup="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        onchange="(<?php echo $details['sl']?>);"
                                        id="discount_<?php echo $details['sl']?>" class="form-control text-right"
                                        placeholder="0.00" value="<?php echo $details['discount_per']?>" min="0"
                                        tabindex="6" />

                                    <input type="hidden" value="<?php echo $discount_type ?>" name="discount_type"
                                        id="discount_type_<?php echo $details['sl']?>">
                                </td>
                                <td>
                                    <input type="text" name="discountvalue[]"
                                        id="discount_value_<?php echo $details['sl']?>" class="form-control  text-right"
                                        min="0" tabindex="18" placeholder="0.00"
                                        value="<?php echo $details['discount']?>" readonly />
                                </td>
                                <!-- VAT  -->
                                <td>
                                    <input type="text" name="vatpercent[]"
                                        onkeyup="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        onchange="paysenz_invoice_quantity_calculate(<?php echo $details['sl']?>);"
                                        id="vat_percent_<?php echo $details['sl']?>" class="form-control text-right"
                                        min="0" tabindex="19" placeholder="0.00"
                                        value="<?php echo $details['vat_amnt_per']?>" />

                                </td>
                                <td>
                                    <input type="text" name="vatvalue[]" id="vat_value_<?php echo $details['sl']?>"
                                        class="form-control text-right total_vatamnt" min="0" tabindex="20"
                                        placeholder="0.00" value="<?php echo $details['vat_amnt']?>" readonly />
                                </td>
                                <!-- VAT end -->

                                <td>
                                    <input class="total_price form-control text-right" type="text" name="total_price[]"
                                        id="total_price_<?php echo $details['sl']?>"
                                        value="<?php echo $details['total_price']?>" readonly="readonly" />

                                    <input type="hidden" name="invoice_details_id[]" id="invoice_details_id"
                                        value="<?php echo $details['invoice_details_id']?>" />
                                </td>
                                <td>

                                    <input type="hidden" id="total_discount_<?php echo $details['sl']?>" class=""
                                        value="<?php echo $details['discount']?>" />

                                    <input type="hidden" id="all_discount_<?php echo $details['sl']?>"
                                        class="total_discount dppr" value="<?php echo $details['discount']?>"
                                        name="discount_amount[]" />


                                    <button class="btn btn-danger text-center" type="button"
                                        value="<?php echo display('delete') ?>" onclick="deleteRow_invoice(this)"
                                        tabindex="7"><i class="fa fa-close"></i></button>
                                </td>
                            </tr>
                            <?php }?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="10" rowspan="2">
                                    <center><label sclass="text-center" for="details"
                                            class="  col-form-label"><?php echo display('invoice_details') ?></label>
                                    </center>
                                    <textarea name="inva_details" id="details" class="form-control"
                                        placeholder="<?php echo display('invoice_details') ?>"><?php echo $invoice_details;?></textarea>
                                </td>
                                <td class="text-right" colspan="1"><b><?php echo display('invoice_discount') ?>:</b>
                                </td>
                                <td class="text-right">
                                    <input type="text" onkeyup="paysenz_invoice_quantity_calculate(1);"
                                        onchange="paysenz_invoice_quantity_calculate(1);" id="invoice_discount"
                                        class="form-control text-right total_discount" name="invoice_discount"
                                        placeholder="0.00" value="<?php echo $invoice_discount;?>" />
                                    <input type="hidden" id="txfieldnum" value="<?php echo count($taxes);?>">
                                </td>
                                <td><a id="add_invoice_item" class="btn btn-info" name="add-invoice-item"
                                        onClick="addInputField_invoice('addinvoiceItem');"><i
                                            class="fa fa-plus"></i></a></td>
                            </tr>
                            <tr>
                                <td class="text-right" colspan="1"><b><?php echo display('total_discount') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="total_discount_ammount" class="form-control text-right"
                                        name="total_discount" value="<?php echo $total_discount;?>"
                                        readonly="readonly" />
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right" colspan="11"><b><?php echo display('ttl_val') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="total_vat_amnt" class="form-control text-right"
                                        value="<?php echo $total_vat_amnt;?>" name="total_vat_amnt" value="0.00"
                                        readonly="readonly" />
                                </td>
                            </tr>
                            

                            <tr>
                                <td class="text-right" colspan="11"><b><?php echo display('shipping_cost') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="shipping_cost" class="form-control text-right"
                                        name="shipping_cost" onkeyup="paysenz_invoice_quantity_calculate(1);"
                                        onchange="paysenz_invoice_quantity_calculate(1);" placeholder="0.00"
                                        value="<?php echo $shipping_cost?>" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-right"><b><?php echo display('grand_total') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="grandTotal" class="form-control grandTotalamnt text-right"
                                        name="grand_total_price" value="<?php echo $total_amount?>"
                                        readonly="readonly" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-right"><b><?php echo display('previous'); ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="previous" class="form-control text-right" name="previous"
                                        value="<?php echo $prev_due?>" readonly="readonly" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-right"><b><?php echo display('net_total'); ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="n_total" class="form-control text-right" name="n_total"
                                        value="<?php echo $net_total;?>" readonly="readonly" placeholder="" />
                                </td>
                            </tr>
                            <tr>

                                <td class="text-right" colspan="11"><b><?php echo display('paid_ammount') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="paidAmount" onkeyup="invoice_paidamount();"
                                        class="form-control text-right" name="paid_amount" placeholder="0.00"
                                        tabindex="13" value="<?php echo $paid_amount;?>" />
                                </td>
                            </tr>
                            <tr>


                                <td class="text-right" colspan="11">
                                    <input type="hidden" name="baseUrl" class="baseUrl"
                                        value="<?php echo base_url(); ?>" />
                                    <input type="hidden" name="invoice_id" id="invoice_id"
                                        value="<?php echo $invoice?>" />
                                    <input type="hidden" name="invoice" id="invoice" value="<?php echo $invoice?>" />
                                    <input type="hidden" name="dbinv_id" id="invoice" value="<?php echo $dbinv_id?>" />
                                    <b><?php echo display('due') ?>:</b>
                                </td>
                                <td class="text-right">
                                    <input type="text" id="dueAmmount" class="form-control text-right" name="due_amount"
                                        value="<?php echo $due_amount?>" readonly="readonly" />
                                </td>
                            </tr>
                            <tr>

                                <td class="text-right" colspan="11"><b><?php echo display('change') ?>:</b></td>
                                <td class="text-right">
                                    <input type="text" id="change" class="form-control text-right" name="change"
                                        value="0" readonly="readonly" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <input type="hidden" name="finyear" value="<?php echo financial_year(); ?>">
                    <p hidden id="pay-amount"><?php echo $paid_amount;?></p>
                    <p hidden id="change-amount"></p>
                    <div class="col-sm-6 table-bordered p-20">
                        <div id="adddiscount" class="display-none">

                            <input type="hidden" id="invoice_edit_page" value="1">
                            <input type="hidden" id="is_credit_edit" value="<?php echo $is_credit?>">

                            <div class="" id="add_new_payment">

                            <?php if ($is_credit != 1) { 
                                    
                                foreach($multi_paytype as $all_paytype){?>
                                <div class="row no-gutters">
                                    <div class="form-group col-md-6">
                                        <label for="payments"
                                            class="col-form-label pb-2"><?php echo display('payment_type');?></label>

                                        <?php 
                                        echo form_dropdown('multipaytype[]',$all_pmethod,(!empty($all_paytype)?$all_paytype->COAID:null),'onchange = "check_creditsale()" class="card_typesl postform resizeselect form-control "') ?>

                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="4digit"
                                            class="col-form-label pb-2"><?php echo display('paid_amount');?></label>

                                        <input type="text" id="pamount_by_method"
                                            class="form-control number pay firstpay" name="pamount_by_method[]"
                                            value="<?php echo $all_paytype->Debit?>" onkeyup="changedueamount()"
                                            placeholder="0" />

                                    </div>
                                </div>
                                <?php } }else {?>
                                <div class="row no-gutters">
                                    <div class="form-group col-md-6">
                                        <label for="payments"
                                            class="col-form-label pb-2"><?php echo display('payment_type');?></label>

                                        <?php 
                                        echo form_dropdown('multipaytype[]',$all_pmethodwith_cr,0,'onchange = "check_creditsale()" class="card_typesl postform resizeselect form-control "') ?>

                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="4digit"
                                            class="col-form-label pb-2"><?php echo display('paid_amount');?></label>

                                        <input type="text" id="pamount_by_method"
                                            class="form-control number pay firstpay" name="pamount_by_method[]"
                                            value="<?php echo $paid_amount?>" onkeyup="changedueamount()"
                                            placeholder="0" />

                                    </div>
                                </div>
                                <?php }?>


                            </div>
                            <div class="form-group text-right">
                                <div class="col-sm-12 pr-0">

                                    <button <?php if(empty($multi_paytype)){echo 'disabled';}?> type="button"
                                        id="add_new_payment_type"
                                        class="btn btn-success w-md m-b-5"><?php echo display('new_p_method');?></button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="form-group row text-right">
                    <div class="col-sm-12 p-20">
                        <input type="submit" id="add_invoice" class="btn btn-success" name="add-invoice"
                            value="<?php echo display('submit') ?>" tabindex="17" />

                    </div>
                </div>
            </div>
            <?php echo form_close() ?>
        </div>
    </div>

    <div class="modal fade" id="printconfirmodal" tabindex="-1" role="dialog" aria-labelledby="printconfirmodal"
        aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel"><?php echo display('print') ?></h4>
                </div>
                <div class="modal-body">
                    <?php echo form_open('invoice_print', array('class' => 'form-vertical', 'id' => '', 'name' => '')) ?>
                    <div id="outputs" class="hide alert alert-danger"></div>
                    <h3> <?php echo display('successfully_inserted') ?></h3>
                    <h4><?php echo display('do_you_want_to_print') ?> ??</h4>
                    <input type="hidden" name="invoice_id" id="inv_id">
                </div>
                <div class="modal-footer">
                    <a href="<?php echo base_url('invoice_list')?>"
                        class="btn btn-default"><?php echo display('no') ?></a>

                    <button type="submit" class="btn btn-primary" id="yes"><?php echo display('yes') ?></button>
                    <?php echo form_close() ?>
                </div>
            </div>
        </div>
    </div>

</div>