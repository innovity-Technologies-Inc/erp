<div class="row">
    <div class="col-sm-12">

        <?php if($this->permission1->method('manage_service','read')->access()){ ?>
        <a href="<?php echo base_url('manage_service') ?>" class="btn btn-info m-b-5 m-r-2"><i class="ti-align-justify">
            </i> <?php echo display('manage_service') ?> </a>
        <?php }?>
        <?php if($this->permission1->method('create_service','create')->access()){ ?>
        <button type="button" class="btn btn-info m-b-5 m-r-2" data-toggle="modal"
            data-target="#service_csv"><?php echo display('service_csv_upload')?></button>
        <?php }?>

    </div>
</div>

<!-- New Service -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-bd lobidrag">
            <div class="panel-heading">
                <div class="panel-title">
                    <h4><?php echo display('add_service') ?> </h4>
                </div>
            </div>
            <?php echo form_open('service/service/insert_service', array('class' => 'form-vertical', 'id' => 'insert_service')) ?>
            <div class="panel-body">

                <div class="form-group row">
                    <label for="service_name" class="col-sm-3 col-form-label"><?php echo display('service_name') ?> <i
                            class="text-danger">*</i></label>
                    <div class="col-sm-6">
                        <input class="form-control" name="service_name" id="service_name" type="text"
                            placeholder="<?php echo display('service_name') ?>" required="">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="charge" class="col-sm-3 col-form-label"><?php echo display('charge') ?> <i
                            class="text-danger">*</i></label>
                    <div class="col-sm-6">
                        <input class="form-control" name="charge" id="charge" type="text"
                            placeholder="<?php echo display('charge') ?>" required="">
                    </div>
                </div>
                <?php if($vattaxinfo->fixed_tax ==1){?>
                <div class="form-group row">
                    <label for="service_vat" class="col-sm-3 col-form-label"><?php echo display('service_vat').' %' ?> </label>
                    <div class="col-sm-6">
                        <input class="form-control" name="service_vat" id="service_vat" type="text"
                            placeholder="<?php echo display('service_vat').' %' ?>" >
                    </div>
                </div>
                <?php }?>

                <div class="form-group row">
                    <label for="description" class="col-sm-3 col-form-label"><?php echo display('description') ?> <i
                            class="text-danger"></i></label>
                    <div class="col-sm-6">
                        <textarea class="form-control" name="description" id="description"
                            placeholder="<?php echo display('description') ?>"></textarea>
                    </div>
                </div>

                 <?php if($vattaxinfo->dynamic_tax ==1){
                    $i=0;
                    foreach ($taxfield as $taxss) {?>

                         <div class="form-group row">
                            <label for="tax" class="col-sm-3 col-form-label"><?php echo $taxss['tax_name']; ?> <i class="text-danger"></i></label>
                            <div class="col-sm-6">
                              <input type="text" name="tax<?php echo $i;?>" class="form-control" value="<?php echo number_format($taxss['default_value'], 2, '.', ',');?>">
                            </div>
                            <div class="col-sm-1"> <i class="text-success">%</i></div>
                        </div>
                       <?php $i++;} }

                        ?>


                <div class="form-group row">
                    <label for="example-text-input" class="col-sm-4 col-form-label"></label>
                    <div class="col-sm-6">
                        <input type="submit" id="add-service" class="btn btn-success btn-large" name="add-service"
                            value="<?php echo display('save') ?>" />
                    </div>
                </div>
            </div>
            <?php echo form_close() ?>
        </div>
    </div>
    <div id="service_csv" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo display('service_csv_upload'); ?></h4>
                </div>
                <div class="modal-body">

                    <div class="panel">
                        <div class="panel-heading">

                            <div><a href="<?php echo base_url('assets/data/csv/service_csv_sample.csv') ?>"
                                    class="btn btn-primary pull-right"><i
                                        class="fa fa-download"></i><?php echo display('download_sample_file')?> </a>
                            </div>

                        </div>

                        <div class="panel-body">

                            <?php echo form_open_multipart('service/service/uploadCsv_service',array('class' => 'form-vertical', 'id' => 'validate','name' => 'insert_service'))?>
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <label for="upload_csv_file"
                                        class="col-sm-4 col-form-label"><?php echo display('upload_csv_file') ?> <i
                                            class="text-danger">*</i></label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="upload_csv_file" type="file"
                                            id="upload_csv_file" placeholder="<?php echo display('upload_csv_file') ?>"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 text-right">
                                        <input type="submit" id="add-product" class="btn btn-primary btn-large"
                                            name="add-product" value="<?php echo display('submit') ?>" />
                                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>

                                    </div>
                                </div>
                            </div>
                            <?php echo form_close()?>
                        </div>
                    </div>



                </div>

            </div>

        </div>
    </div>
</div>