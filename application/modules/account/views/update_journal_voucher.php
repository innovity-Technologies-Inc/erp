<link href="<?php echo base_url('application/modules/account/assets/css/update_journal_voucher.css'); ?>" rel="stylesheet" type="text/css"/>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4>
                     <?php echo display('journal_voucher')?>
                    </h4>
                </div>
            </div>
            <div class="panel-body">
                         <?php echo  form_open_multipart('account/accounts/update_journal_voucher') ?>
                     <div class="form-group row">
                         <label for="vo_no" class="col-sm-2 col-form-label"><?php echo display('voucher_no')?></label>
                        <div class="col-sm-4">
                       <input type="text" name="txtVNo" id="txtVNo" value="<?php echo $vNo;?>" class="form-control" readonly /> 
                       <input type="hidden" name="isApproved" id="isApproved" value="<?php echo $isApproved;?>">                      
                       <input type="hidden" name="fyear" id="fyear" value="<?php echo $fyear;?>">
                       <input type="hidden" name="CreateBy" id="CreateBy" value="<?php echo $CreateBy;?>">
                       <input type="hidden" name="CreateDate" id="CreateDate" value="<?php echo $CreateDate;?>">
                       <input type="hidden" name="finyear" value="<?php echo financial_year(); ?>">
                   </div>
                </div> 
                <div class="form-group row">
                <label for="date" class="col-sm-2 col-form-label"><?php echo display('date')?></label>
                  <div class="col-sm-4">
                     <input type="text" name="dtpDate" id="dtpDate" class="form-control datepicker" value="<?php  echo $VDate;?>">
                  </div>
               </div> 
              <div class="form-group row">
                <label for="txtRemarks" class="col-sm-2 col-form-label"><?php echo display('remark')?></label>
                <div class="col-sm-4">
                  <textarea  name="txtRemarks" id="txtRemarks" class="form-control"><?php echo $Narration;?></textarea>
                </div>
              </div>
           <div class="table-responsive update-journal-vc">
                <table class="table table-bordered table-hover" id="debtAccVoucher"> 
                    <thead>
                        <tr>
                <th width="18%" class="text-center"><?php echo display('account_name')?>*</th>
                 <th width="12%" class="text-center"><?php echo display('subtype')?></th>
                 <th width="20%" class="text-center"><?php echo display('ledger_comment')?></th>
                <th width="10%" class="text-center"><?php echo display('debit')?></th>
                <th width="10%" class="text-center"><?php echo display('credit')?></th>
                <th width="17%" class="text-center"><?php echo display('reverse_account_head')?>*</th>
                 <th width="8%" class="text-center"><?php echo display('action')?></th>
           </tr>
         </thead>
         <tbody id="debitvoucher">
       <?php
       $sl = 1;
       $total_debit = 0;
       $total_credit = 0;
       $sl = 1;
       foreach($voucher_info as $voucher){
        $total_debit += $voucher->Debit;
        $total_credit += $voucher->Credit; ?>

        <tr>
             <td class="expenseincometd"> 
                <select name="cmbCode[]" id="cmbCode_<?php echo $sl; ?>" required class="form-control" onchange="load_subtypeOpen(this.value,<?php echo $sl; ?>)">
                  <option value="">Please select One</option>
                  <?php foreach ($acc as $acc1) {?>
                  <option value="<?php echo $acc1->HeadCode;?>" <?php echo $voucher->COAID == $acc1->HeadCode?'selected' :'';?> ><?php echo $acc1->HeadName;?></option>
                  <?php }?>
                </select>
            </td>
             <?php if( $voucher->subType  != 1) {
                $subtpes = $this->accounts_model->get_subTypeItems($voucher->subType) ?>
             <td >  
                <select name="subtype[]" id="subtype_<?php echo $sl; ?>" class="form-control">
                  <?php foreach ($subtpes as $subtpe) {?>
                     <option value="<?php echo $subtpe->id;?>" <?php echo $voucher->subCode == $subtpe->id?'selected' :'';?> ><?php echo $subtpe->name;?></option>
                 <?php } ?>
                </select>
                <input type="hidden" name="isSubtype[]" id="isSubtype_<?php echo $sl; ?>" value="<?php echo $voucher->subType;?>" />
            </td>

            <?php } else { ?>
            <td >  
                <select name="subtype[]" id="subtype_<?php echo $sl; ?>" class="form-control" >  <option value="">Please select One</option></select>
                <input type="hidden" name="isSubtype[]" id="isSubtype_<?php echo $sl; ?>" value="1" />
            </td>
            <?php } ?>
           
            <td>
                <input type="text" name="txtComment[]" value="<?php echo $voucher->ledgerComment;?>" class="form-control "  id="txtComment_<?php echo $sl; ?>" >
            </td>
            <td><input type="number" name="txtAmount[]" value="<?php echo $voucher->Debit;?>" placeholder="0.00" class="form-control total_price text-right"  id="txtAmount_<?php echo $sl; ?>" onkeyup="calculationJournalv(<?php echo $sl; ?>)" >
               </td>
             <td ><input type="number" name="txtAmountcr[]" value="<?php echo $voucher->Credit;?>" placeholder="0.00" class="form-control total_price1 text-right"  id="txtAmount1_<?php echo $sl; ?>" onkeyup="calculationJournalv(<?php echo $sl; ?>)" >
           </td>

           <td> 
            <select name="cmbDebit[]" id="cmbDebit_<?php echo $sl; ?>" required class="form-control">
                   <option value="">Please select One</option>
                    <?php foreach ($acc as $cracc) { ?>
                    <option value="<?php echo $cracc->HeadCode?>"  <?php echo $voucher->RevCodde == $cracc->HeadCode?'selected' :'';?>><?php echo $cracc->HeadName?></option>
                   <?php  } ?>
           </select>

           </td>
           <td>
                <button  class="btn btn-danger red" type="button" value="<?php echo display('delete')?>" onclick="deleteRowJournalv(this)"><i class="fa fa-trash-o"></i></button>
          </td>
       </tr> 
        <?php $sl++;}?>        
 </tbody>                               
    <tfoot>

            <tr>
              <td >
                    <input type="button" id="add_more" class="btn btn-info" name="add_more"  onClick="addaccountJournalv('debitvoucher');" value="<?php echo display('add_more') ?>" />
                </td>
                <td colspan="1" class="text-right"><label  for="reason" class="  col-form-label"><?php echo display('total') ?></label>
                   </td>
                <td class="text-right">
                    <input type="text" id="grandTotal" class="form-control text-right " name="grand_total" value="<?php echo $total_debit;?>" readonly="readonly" value="0"/>
                </td>
                 <td class="text-right">
                    <input type="text" id="grandTotal1" class="form-control text-right " name="grand_total1" value="<?php echo $total_credit;?>" readonly="readonly" value="0"/>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
                    <div class="form-group form-group-margin row">
                       
                        <div class="col-sm-12 text-right">

                            <input type="submit" id="add_receive" class="btn btn-success btn-large" name="save" value="<?php echo display('update') ?>" tabindex="9"/>
                           <input type="hidden" name="" id="base_url" value="<?php echo base_url();?>">
                            <input type="hidden" name="" id="headoption" value="<option value=''> Please select</option><?php foreach ($acc as $acc2) {?><option value='<?php echo $acc2->HeadCode;?>'><?php echo $acc2->HeadName;?></option><?php }?>">
                        </div>
                    </div>
                  <?php echo form_close() ?>
            </div>  
        </div>
    </div>
</div>

<script src="<?php echo base_url() ?>assets/dist/jstree.min.js" ></script>
<script src="<?php echo base_url('assets/dist/account.js') ?>" type="text/javascript"></script>