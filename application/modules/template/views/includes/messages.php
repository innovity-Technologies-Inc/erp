<?php if ($this->session->flashdata('message')): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <?php echo $this->session->flashdata('message'); ?>
    </div>
    <?php $this->session->unset_userdata('message'); // Explicitly clear the flash message ?>
<?php endif; ?>

<?php if ($this->session->flashdata('exception')): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <?php echo $this->session->flashdata('exception'); ?>
    </div>
    <?php $this->session->unset_userdata('exception'); // Explicitly clear the flash exception ?>
<?php endif; ?>

<?php if (validation_errors()): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>