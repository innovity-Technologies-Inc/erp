<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // Check if session library is initialized
        if (!isset($this->session)) {
            log_message('debug', '📦 Session library not loaded. Attempting to load...');
            $this->load->library('session');
        } else {
            log_message('debug', '🧠 CI session is already initialized.');
        }

        $this->load->library('email');
        $this->config->load('email');
    }

    /**
     * Send verification email to a customer
     *
     * @param string $email
     * @param string $verify_url
     * @return void
     */
    public function send_verification($email, $verify_url)
    {
        try {
            log_message('debug', "📨 Preparing verification email to: $email");

            $this->email->from('noreply@deshishad.com', 'Deshi Shad');
            $this->email->to($email);
            $this->email->subject('Verify your email address');
            $this->email->message("
                <h3>Registration Successful!</h3>
                <p>Thank you for registering. Please click below to verify your email:</p>
                <a href='$verify_url' style='padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none;'>Verify Email</a>
            ");

            if ($this->email->send()) {
                log_message('debug', "✅ Verification email sent successfully to: $email");
            } else {
                $error = $this->email->print_debugger(['headers']);
                log_message('error', "❌ Verification email failed to: $email | Error: $error");
            }
        } catch (Exception $e) {
            log_message('error', '❌ Exception while sending verification email: ' . $e->getMessage());
        }
    }

    /**
     * Send confirmation email to a customer after verification
     *
     * @param string $email
     * @return void
     */
    public function send_confirmation($email)
    {
        try {
            log_message('debug', "📨 Preparing confirmation email to: $email");

            $this->email->from('noreply@deshishad.com', 'Deshi Shad');
            $this->email->to($email);
            $this->email->subject('Email Verified Successfully');
            $this->email->message("
                <h3>Email Verified!</h3>
                <p>You have successfully verified your email.</p>
                <p>Deshi Shad support team will now contact you to activate your account. You may also call us at <strong>+1234567890012</strong>.</p>
            ");

            if ($this->email->send()) {
                log_message('debug', "✅ Confirmation email sent successfully to: $email");
            } else {
                $error = $this->email->print_debugger(['headers']);
                log_message('error', "❌ Confirmation email failed to: $email | Error: $error");
            }
        } catch (Exception $e) {
            log_message('error', '❌ Exception while sending confirmation email: ' . $e->getMessage());
        }
    }
}