<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login - Deshi Shad</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Bootstrap -->
    <link href="<?php echo base_url('assets/custom_assets/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css"/>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo base_url('assets/custom_assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/custom_assets/css/owl.carousel.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/custom_assets/fonts/icomoon/style.css'); ?>">

    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js"></script>
</head>
<body>

<div class="half">
    <div class="bg order-1 order-md-2 position-relative">
        <!-- Company Logo (Top Left) -->
        <img src="<?php echo base_url('assets/custom_assets/images/gen_tech_logo.png'); ?>" alt="Company Logo" class="top-left-logo">
    </div>

    <div class="contents order-2 order-md-1">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-md-6">
                    <div class="form-block custom-form">
                        <div class="text-center mb-4">
                            <img src="<?php echo base_url('assets/custom_assets/images/deshi_shad_logo_login.png'); ?>" alt="Deshi Shad Logo" class="company-logo">
                            <h3 class="mt-3">Welcome to <strong>Deshi Shad</strong></h3>
                        </div>

                        <!-- Alert Messages -->
                        <?php if ($this->session->flashdata('message')) { ?>
                        <div class="alert alert-info alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php echo $this->session->flashdata('message'); ?>
                        </div>
                        <?php } ?>

                        <?php if ($this->session->flashdata('exception')) { ?>
                        <div class="alert alert-danger alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php echo $this->session->flashdata('exception'); ?>
                        </div>
                        <?php } ?>

                        <?php if (validation_errors()) { ?>
                        <div class="alert alert-danger alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php echo validation_errors(); ?>
                        </div>
                        <?php } ?>

                        <!-- Login Form -->
                        <?php echo form_open('login', 'id="loginForm" novalidate'); ?>
                        <div class="form-group first">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group last mb-3 position-relative">
                            <label for="password">Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" id="password" class="form-control pr-5" placeholder="Your Password" required>
                                <span id="togglePassword" class="position-absolute toggle-password">
                                    <i class="fa fa-eye-slash"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-sm-flex mb-4 align-items-center">
                            <label class="control control--checkbox mb-3 mb-sm-0">
                                <span class="caption">Remember me</span>
                                <input type="checkbox" checked="checked"/>
                                <div class="control__indicator"></div>
                            </label>
                            <span class="ml-auto"><a href="#" data-toggle="modal" data-target="#passwordrecoverymodal" class="forgot-pass">Forgot Password?</a></span>
                        </div>

                        <?php if ($setting->captcha == 0 && $setting->site_key) { ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo $setting->site_key; ?>"></div>
                        <?php } ?>

                        <input type="submit" value="Log In" class="btn btn-block btn-primary">
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Recovery Modal -->
<div class="modal fade" id="passwordrecoverymodal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Password Recovery</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php echo form_open('dashboard/recoverydata/password_recovery', array('id' => 'passrecoveryform')); ?>
                <div class="form-group">
                    <label for="rec_email">Email</label>
                    <input class="form-control" name="rec_email" id="rec_email" type="email" placeholder="Enter your email" required>
                    <input type="hidden" name="csrf_test_name" value="<?php echo $this->security->get_csrf_hash(); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <input type="submit" id="submit_recovery" class="btn btn-success" value="Send">
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const togglePassword = document.querySelector("#togglePassword");
        const passwordField = document.querySelector("#password");

        togglePassword.addEventListener("click", function () {
            if (passwordField.type === "password") {
                passwordField.type = "text";
                this.innerHTML = '<i class="fa fa-eye"></i>';
            } else {
                passwordField.type = "password";
                this.innerHTML = '<i class="fa fa-eye-slash"></i>';
            }
        });
    });
</script>

<script src="<?php echo base_url('assets/custom_assets/js/jquery-3.3.1.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/custom_assets/js/popper.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/custom_assets/js/bootstrap.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/custom_assets/js/main.js'); ?>"></script>

</body>
</html>