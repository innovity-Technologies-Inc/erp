
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd">
<!-- track -->
            <div id="printableArea" onload="printDiv('printableArea')">
                <div class="panel-body print-font-size">
                    <div class="row print_header">

                        <div class="col-xs-4">
                            <?php foreach($company_info as $company){?>
                            <img src="<?php
                                    if (isset($setting->invoice_logo)) {
                                        echo base_url().$setting->invoice_logo;
                                    }
                                    ?>" class="img-bottom-m print-logo invoice-img-position" alt=""
                                >
                            <br>
                            <span
                                class="label label-success-outline m-r-15 p-10"><?php echo display('billing_from') ?></span>
                            <address class="margin-top10">
                                <strong class=""><?php echo $company['company_name']?></strong><br>
                                <span class="comp-web"><?php echo $company['address']?></span><br>
                                <abbr class="font-bold"><?php echo display('mobile') ?>: </abbr>
                                <?php echo $company['mobile']?><br>
                                <abbr><b><?php echo display('email') ?>:</b></abbr>
                                <?php echo $company['email']?><br>
                                <abbr><b><?php echo display('website') ?>:</b></abbr>
                                <span class="comp-web"><?php echo $company['website']?></span><br>
                                <?php if (!empty($company['vat_no'])) {?>
                                <abbr class="font-bold"><?php echo display('vat_no') ?>: </abbr>
                                <?php echo $company['vat_no']?><br>
                                <?php }?>
                                <?php if (!empty($company['cr_no'])) {?>
                                <abbr class="font-bold"><?php echo display('cr_no') ?>: </abbr>
                                <?php echo $company['cr_no']?><br>
                                <?php }?>
                                <?php }?>
                                <abbr><?php echo $tax_regno?></abbr>
                            </address>



                        </div>
                        <div class="col-xs-4">
                            <?php $web_setting = $this->db->select("*")->from("web_setting")->get()->row();
                            if ($web_setting->is_qr == 1) { ?>
                            <div class="print-qr">
                                <?php  $text = base64_encode(display('invoice_no').': '.$invoice_no.' '.display('customer_name').': '. $customer_name);
                                ?>
                                <img src="http://chart.apis.google.com/chart?cht=qr&chs=250x250&chld=L|4&chl=<?php echo $text?>"
                                    alt="">
                            </div>
                            <?php }?>
                        </div>

                        <div class="col-xs-4 text-left ">
                            <h2 class="m-t-0"><?php echo display('invoice_draft') ?></h2>
                            <div>
                                <abbr class="font-bold">
                                    <?php echo display('invoice_draft_no') ?>: <span dir="ltr"></span>
                                </abbr>
                                <?php echo $invoice_id?>
                            </div>
                            <div class="m-b-15">
                                <abbr class="font-bold"><?php echo display('billing_date') ?></abbr>
                                <?php echo date("d-M-Y",strtotime($invoice_date));?>
                                <br>

                                <?php $create_at = $this->db->select('CreateDate')
                                            ->from('acc_vaucher')
                                            ->where('referenceNo',$invoice_no)
                                            ->get()
                                            ->row();?>
                                <abbr class="font-bold"><?php echo display('create_time') ?>:</abbr>
                                <?php echo date("H:i:s",strtotime($create_at->CreateDate));?>
                            </div>

                            <span class="label label-success-outline m-r-15"><?php echo display('billing_to') ?></span>

                            <address style="margin-top: 10px;" class="">
                                <strong class=""><?php echo $customer_name?> </strong><br>
                                <?php if ($customer_address) { ?>
                                <?php echo $customer_address;?>
                                <br>
                                <?php } ?>
                                <?php if ($customer_mobile) { ?>
                                <abbr class="font-bold"><?php echo display('mobile') ?>: </abbr>
                                <?php echo $customer_mobile;?>
                                <br>
                                <?php }  ?>
                                <?php  if ($customer_email){ ?>
                                <abbr class="font-bold"><?php echo display('email') ?>: </abbr>
                                <?php echo $customer_email;?>
                                <br>
                                <?php } ?>
                                <?php if (!empty($email_address)) {?>
                                <abbr class="font-bold"><?php echo display('vat_no') ?>: </abbr>
                                <?php echo $email_address?>
                                <br>
                                <?php } ?>
                                <?php if (!empty($contact)) {?>
                                <abbr class="font-bold"><?php echo display('cr_no') ?>: </abbr>
                                <?php echo $contact?>
                                <?php } ?>


                            </address>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped print-font-size">
                        <thead>
                            <tr>
                                <th class="text-center"><?php echo display('sl'); ?></th>
                                <th class="text-center"><?php echo display('product_name'); ?></th>
                                <th class="text-center"><?php echo display('warehouse'); ?></th>
                                <th class="text-center"><?php echo display('quantity'); ?></th>
                                <th class="text-center"><?php echo display('rate'); ?></th>
                                <th class="text-center"><?php echo display('discount'); ?></th>
                                <th class="text-center"><?php echo display('amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice_all_data as $details) { ?>
                                <tr>
                                    <td class="text-center"><?php echo $details['sl']; ?></td>
                                    <td class="text-center"><?php echo $details['product_name']; ?></td>
                                    <td class="text-center"><?php echo $details['warehouse_name']; ?></td>
                                    <td class="text-right"><?php echo $details['product_quantity']; ?></td>
                                    <td class="text-right"><?php echo $details['product_rate']; ?></td>
                                    <td class="text-right"><?php echo isset($details['discount']) ? $details['discount'] : '0.00'; ?></td>
                                    <td class="text-right"><?php echo $details['total_value']; ?></td>
                                </tr>
                            <?php } ?>

                            <tr>
                                <td class="text-left" colspan="3"><b><?php echo display('total'); ?>:</b></td>
                                <td class="text-right"><b><?php echo number_format($subTotal_quantity, 2); ?></b></td>
                                <td colspan="2" class="text-right"><b><?php echo display('sub_total'); ?>:</b></td>
                                <td class="text-right">
                                    <b>
                                        <?php
                                            $sub_total_clean = (float) str_replace(',', '', $subTotal_ammount);
                                            echo ($position == 0)
                                                ? $currency . ' ' . number_format($sub_total_clean, 2)
                                                : number_format($sub_total_clean, 2) . ' ' . $currency;
                                        ?>
                                    </b>
                                </td>
                            </tr>
                            </tbody>

                        </table>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <p></p>
                            <p><strong><?php echo $invoice_details ?></strong></p>
                        </div>
                        <div class="col-xs-6 inline-block">
                            <table class="table print-font-size">
                                <?php
                                    $clean_sub_total = (float) str_replace(',', '', $subTotal_amount_cal);
                                    $clean_discount = (float) str_replace(',', '', $total_discount_cal);
                                    $price_after_discount = $clean_sub_total - $clean_discount;

                                    $clean_vat = (float) str_replace(',', '', $total_vat);
                                    $clean_tax = (float) str_replace(',', '', $total_tax);
                                    $clean_shipping = (float) str_replace(',', '', $shipping_cost);

                                    $grand_total = $price_after_discount + $clean_vat + $clean_tax + $clean_shipping;
                                    $clean_paid_amount = (float) str_replace(',', '', $paid_amount);
                                    $clean_due_amount = (float) str_replace(',', '', $due_amount);
                                ?>

                                <?php if ($clean_discount > 0): ?>
                                    <tr>
                                        <th><?php echo 'Total Price Before Discount' ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($subTotal_amount_cal, 2)
                                                : number_format($subTotal_amount_cal, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php echo display('dis_val') ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($total_discount_cal, 2)
                                                : number_format($total_discount_cal, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php echo 'Total Price After Discount' ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($price_after_discount, 2)
                                                : number_format($price_after_discount, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($total_vat) && $clean_vat > 0): ?>
                                    <tr>
                                        <th><?php echo display('vat_val') ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($total_vat, 2)
                                                : number_format($total_vat, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($total_tax) && $clean_tax > 0): ?>
                                    <tr>
                                        <th class="text-left"><?php echo display('tax') ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($total_tax, 2)
                                                : number_format($total_tax, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($shipping_cost) && $clean_shipping > 0): ?>
                                    <tr>
                                        <th class="text-left"><?php echo 'Shipping Cost' ?> :</th>
                                        <td class="text-right">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($shipping_cost, 2)
                                                : number_format($shipping_cost, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr style="border-top: 3px double #000;">
                                    <th class="text-left grand_total"><?php echo display('grand_total'); ?> :</th>
                                    <td class="text-right grand_total">
                                        <?php echo ($position == 0)
                                            ? $currency . ' ' . number_format($grand_total, 2)
                                            : number_format($grand_total, 2) . ' ' . $currency; ?>
                                    </td>
                                </tr>

                                <?php if (!empty($previous) && $previous > 0): ?>
                                    <tr>
                                        <th class="text-left grand_total"><?php echo display('previous'); ?> :</th>
                                        <td class="text-right grand_total">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($previous, 2)
                                                : number_format($previous, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr style="border-top: 3px double #000;">
                                    <th class="text-left grand_total"><?php echo display('paid_ammount'); ?> :</th>
                                    <td class="text-right grand_total">
                                        <?php echo ($position == 0)
                                            ? $currency . ' ' . number_format($clean_paid_amount, 2)
                                            : number_format($clean_paid_amount, 2) . ' ' . $currency; ?>
                                    </td>
                                </tr>

                                <?php if ($clean_due_amount > 0): ?>
                                    <tr>
                                        <th class="text-left grand_total"><?php echo display('due_amount'); ?> :</th>
                                        <td class="text-right grand_total">
                                            <?php echo ($position == 0)
                                                ? $currency . ' ' . number_format($clean_due_amount, 2)
                                                : number_format($clean_due_amount, 2) . ' ' . $currency; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    <div class="row margin-top50">
                        <div class="col-sm-4">
                            <div class="inv-footer-left">
                                <?php echo display('received_by') ?>
                            </div>
                        </div>
                        <div class="col-sm-4"></div>
                        <div class="col-sm-4">
                            <div class="inv-footer-right">
                                <?php echo display('authorised_by') ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="panel-footer text-left">
            <button class="btn btn-info" onclick="printDiv('printableArea')">
                <span class="fa fa-print"></span> Print
            </button>
            <div id="iosPrintMessage" style="display:none; color:red; margin-top:10px;">
                On iOS devices, please use the browser's share menu and select "Print".
            </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Replace your printDiv function with this:
function printDiv(divId) {
  if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
    // For iOS devices, use the native print dialog
    window.print();
  } else {
    // Your existing print logic for other devices
    const printContents = document.getElementById(divId).innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
  }
}
</script>