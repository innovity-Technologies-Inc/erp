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
        $this->email->from('noreply@paysenz.com', 'Deshi Shad');
        $this->email->to($email);
        $this->email->subject('Verify your email address');
        $this->email->message("
            <h3>Registration Successful!</h3>
            <p>Thank you for registering. Please click below to verify your email:</p>
            <p><a href='$verify_url' style='padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none;'>Verify Email</a></p>
            <p>If you cannot click the button, copy and paste this URL into your browser:</p>
            <p>$verify_url</p>
        ");

        if ($this->email->send()) {
            echo "✅ Verification email sent.";
        } else {
            echo "❌ Email sending failed.";
            print_r($this->email->print_debugger(['headers']));
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

            $this->email->from('noreply@paysenz.com', 'Deshi Shad');
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

    public function test_email()
    {
        $this->email->from('noreply@paysenz.com', 'Test');
        $this->email->to('faizshiraji@gmail.com');
        $this->email->subject('Test Mail');
        $this->email->message('This is a test mail from CodeIgniter.');

        if ($this->email->send()) {
            echo "✅ Test mail sent.";
        } else {
            echo "❌ Mail send failed.";
            print_r($this->email->print_debugger(['headers']));
        }
    }
}