<div class="row">
  <div class="col-sm-12">
    <div class="panel panel-bd lobidrag">
      <div class="panel-heading">
        <div class="panel-title">
          <h4><?php echo $title ?> </h4>
        </div>
      </div>

      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="CustomerList" width="100%">
            <thead>
              <tr>
                <th><?php echo display('sl') ?></th>
                <th><?php echo display('customer_name') ?></th>
                <th><?php echo display('mobile_no') ?></th>
                <th><?php echo display('email') ?></th>
                <th><?php echo display('vat_no') ?></th>
                <th><?php echo display('sp_no') ?></th>
                <th><?php echo display('sp_file') ?></th>
                <th><?php echo display('balance') ?></th>
                <th><?php echo display('created_by') ?></th>
                <th><?php echo display('created_date') ?></th>
                <th><?php echo display('status') ?></th>
                <th width="50px;"><?php echo display('action') ?></th>
              </tr>
            </thead>
            <tbody id="customer_tablebody">
              <!-- DataTables will populate this -->
            </tbody>
            <tfoot>
              <tr>
                <th colspan="8" class="text-right"><?php echo display('total') ?>:</th>
                <th id="stockqty"></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>