<!-- Printable area start -->
<script type="text/javascript">
function printDiv() {
    var divName = "printArea";
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>
<!-- Printable area end -->


<?php
    $GLOBALS['TotalAssertF']   = 0;
    $GLOBALS['TotalLiabilityF']= 0;
  function AssertCoa($HeadName,$HeadCode,$GL,$oResultAsset,$Visited,$value,$dtpFromDate,$dtpToDate,$check){
      
      $CI =& get_instance();

      if($value==1)
      { 
      ?>
        <tr>
            <td colspan="2" style="font-size:20px; font-weight:bold; border-right:solid 1px #000; border-left:solid 1px #000; border-top:solid 1px #000;"><?php echo $HeadName;?></td>
        </tr>
      <?php
      }
      elseif($value>1)
      {
        $COAID=$HeadCode;
        if($check)
        {
          $sqlF="SELECT SUM(acc_transaction.Debit)-SUM(acc_transaction.Credit) AS Amount FROM acc_transaction INNER JOIN acc_coa ON acc_transaction.COAID = acc_coa.HeadCode WHERE VDate BETWEEN '$dtpFromDate' AND '$dtpToDate' AND COAID LIKE '$COAID%'";
        }
        else
        {
          $sqlF="SELECT SUM(acc_transaction.Credit)-SUM(acc_transaction.Debit) AS Amount FROM acc_transaction INNER JOIN acc_coa ON acc_transaction.COAID = acc_coa.HeadCode WHERE acc_transaction.IsAppove = 1 AND VDate BETWEEN '$dtpFromDate' AND '$dtpToDate' AND COAID LIKE '$COAID%'";
        }
        $q1 = $CI->db->query($sqlF);
        $oResultAmountPreF = $q1->row();
      
        if($value==2)
        {
          if($check==1)
          {
            $GLOBALS['TotalLiabilityF']=$GLOBALS['TotalLiabilityF']+$oResultAmountPreF->Amount;
          }
          else
          {
            $GLOBALS['TotalAssertF']=$GLOBALS['TotalAssertF']+$oResultAmountPreF->Amount;
          }
        }

      if($oResultAmountPreF->Amount!=0)
      {
      ?>
        <tr>
          <td align="left" style="border-left:solid 1px #000; border-top:solid 1px #000; font-size:<?php echo (int)(20-$value*1.5)."px;";
          echo ($value<=3?" font-weight:bold; ":" ");
          ?>"><?php echo ($value>=3?"&nbsp;&nbsp;":""). $HeadName; ?></td>
          <td align="right" style="border-left:solid 1px #000;  border-right:solid 1px #000; border-top:solid 1px #000;"><?php echo number_format($oResultAmountPreF->Amount,2);?></td>
        </tr>
      <?php
      }
      }
      for($i=0;$i<count($oResultAsset);$i++)
      {
        if (!$Visited[$i]&&$GL==0)
        {
          if ($HeadName==$oResultAsset[$i]->PHeadName)
          {
            $Visited[$i]=true;
            AssertCoa($oResultAsset[$i]->HeadName,$oResultAsset[$i]->HeadCode,$oResultAsset[$i]->IsGL,$oResultAsset,$Visited,$value+1,$dtpFromDate,$dtpToDate,$check);
          }
        }
      }
    }

?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="header-icon">
            <i class="pe-7s-note2"></i>
        </div>
        <div class="header-title">
            <h1><?php echo display('accounts') ?></h1>
            <small><?php echo display('profit_loss') ?></small>
            <ol class="breadcrumb">
                <li><a href="#"><i class="pe-7s-home"></i> <?php echo display('home') ?></a></li>
                <li><a href="#"><?php echo display('accounts') ?></a></li>
                <li class="active"><?php echo display('profit_loss') ?></li>
            </ol>
        </div>
    </section>

    <section class="content">
            <?php
        $message = $this->session->userdata('message');
        if (isset($message)) {
            ?>
            <div class="alert alert-info alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <?php echo $message ?>                    
            </div>
            <?php
            $this->session->unset_userdata('message');
        }
        $error_message = $this->session->userdata('error_message');
        if (isset($error_message)) {
            ?>
            <div class="alert alert-danger alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <?php echo $error_message ?>                    
            </div>
            <?php
            $this->session->unset_userdata('error_message');
        }
        ?>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4></h4>
                </div>
            </div>
            <div id="printArea">
                <div class="panel-body">
                  <table width="100%" class="table_boxnew" cellpadding="5" cellspacing="0">
                    <tr>
                        <td colspan="2" align="center" style="font-size:20px;"><b><?php echo display('statement_of_comprehensive_income')?><br/><?php echo display('from')?> <?php echo $dtpFromDate ?> <?php echo display('to')?> <?php echo $dtpToDate;?></b></td>
                    </tr>
                    <tr>
                      <td width="85%" bgcolor="#E7E0EE" align="center" style="font-size:18px; border-left:solid 1px #000; border-top:solid 1px #000;"><?php echo display('particulars')?></td>
                      <td width="15%" bgcolor="#E7E0EE" align="center" style="font-size:18px; border-left:solid 1px #000; border-right:solid 1px #000;border-top:solid 1px #000;"><?php echo display('amount')?></td>
                    </tr>
                    <?php
                    for($i=0;$i<count($oResultAsset);$i++)
                    {
                      $Visited[$i] = false;
                    }

                    AssertCoa("COA","0",0,$oResultAsset,$Visited,0,$dtpFromDate,$dtpToDate,0);

                    $TotalAssetF=$GLOBALS['TotalAssertF'];
                    ?>
                    <tr bgcolor="#E7E0EE">
                        <td align="right"><strong><?php echo display('total_income')?></strong></td>
                        <td align="right" style="border-style: double;
                        border-left: none; border-right:none; border-top:none;"><strong ><?php echo number_format($TotalAssetF,2); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2" align="right"></td>
                    </tr>
                    <?php
                    for($i=0;$i<count($oResultLiability);$i++)
                    {
                      $Visited[$i] = false;
                    }
                    $GLOBALS['TotalLiability']=0;
                    AssertCoa("COA","0",0,$oResultLiability,$Visited,0,$dtpFromDate,$dtpToDate,1);
                    $TotalLibilityF=$GLOBALS['TotalLiabilityF'];
                    ?>
                    <tr  bgcolor="#E7E0EE">
                        <td align="right"><strong><?php echo display('total_expenses')?></strong></td>
                        <td align="right" style="border-style: double;
                        border-left: none; border-right:none; border-top:none;"><strong><?php echo number_format($TotalLibilityF,2); ?></strong></td>
                    </tr>
                    <tr>
                        <td align="right" style="border-left:solid 1px #000; border-bottom:solid 1px #000; border-top:solid 1px #000;"><h4>Profit-Loss <?php echo $TotalAssetF>$TotalLibilityF?"(Profit)":"(Loss)";?></h4></td>
                        <td align="right" style="border:solid 1px #000;"><b><?php echo number_format($TotalAssetF-$TotalLibilityF,2); ?></b></td>
                    </tr>
                    <tr bgcolor="#FFF">
                      <td colspan="2" align="center" height="120" valign="bottom">
                          <table width="100%" cellpadding="1" cellspacing="20">
                            <tr>
                              <td width="20%" style="border-top: solid 1px #000;" align="center"><?php echo display('prepared_by')?></td>
                                <td width="20%" style="border-top: solid 1px #000;" align="center"><?php echo display('accounts')?></td>
                                <td width="20%" style="border-top: solid 1px #000;" align="center"><?php echo display('authorized_signature')?></td>
                                <td  width="20%" style="border-top: solid 1px #000;" align='center'><?php echo display('chairman')?></td>
                            </tr>
                          </table>
                      </td>
                    </tr>
                  </table>
                </div>
            </div>
            <div class="text-center" id="print" style="margin: 20px">
                <input type="button" class="btn btn-warning" name="btnPrint" id="btnPrint" value="Print" onclick="printDiv();"/>
             
            </div>
        </div>
    </div>
</div>
</section>
</div>
