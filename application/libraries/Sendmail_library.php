<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail_library extends CI_Controller {

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('email');
        $this->CI->config->load('email');
    }

    public function send_verification($email = null, $verify_url = null)
    {
        log_message('debug', "📨 Sending verification email to: $email");

        if (empty($email) || empty($verify_url)) {
            log_message('error', "❌ Missing parameters in send_verification | Email: $email | URL: $verify_url");
            return;
        }

        try {
            $this->email->from('noreply@deshishad.com', 'Deshi Shad');
            $this->email->to($email);
            $this->email->subject('Verify your email address');
            $this->email->message("
                <h3>Registration Successful!</h3>
                <p>Thank you for registering. Please click below to verify your email:</p>
                <a href='$verify_url' style='padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none;'>Verify Email</a>
            ");

            if ($this->email->send()) {
                log_message('debug', "✅ Verification email successfully sent to: $email");
            } else {
                log_message('error', '❌ Email sending failed: ' . $this->email->print_debugger(['headers']));
            }
        } catch (Exception $e) {
            log_message('error', '❌ Exception while sending email: ' . $e->getMessage());
        }
    }

    public function send_confirmation($email = null)
    {
        log_message('debug', "📨 Sending confirmation email to: $email");

        if (empty($email)) {
            log_message('error', "❌ Missing email in send_confirmation");
            return;
        }

        try {
            $this->email->from('noreply@deshishad.com', 'Deshi Shad');
            $this->email->to($email);
            $this->email->subject('Email Verified Successfully');
            $this->email->message("
                <h3>Email Verified!</h3>
                <p>You have successfully verified your email.</p>
                <p>Deshi Shad support team will now contact you to activate your account.</p>
            ");

            if ($this->email->send()) {
                log_message('debug', "✅ Confirmation email sent to: $email");
            } else {
                log_message('error', '❌ Email sending failed: ' . $this->email->print_debugger(['headers']));
            }
        } catch (Exception $e) {
            log_message('error', '❌ Exception while sending confirmation email: ' . $e->getMessage());
        }
    }
}