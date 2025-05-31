<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // Load necessary libraries
        $this->load->library('session');  // ensures session is available
        $this->load->library('email');    // ✅ email must be loaded here
        $this->config->load('email');     // load email config from application/config/email.php
    }

    /**
     * Send verification email to a customer
     *
     * @param string $email
     * @param string $verify_url
     * @return void
     */
    public function send_verification($email, $verify_url, $from_name, $subject, $message)
    {
        $this->email->from('noreply@hostelevate.com', $from_name);
        $this->email->to($email);
        $this->email->subject($subject);
        
        // Append the verification link to the message if not already included
        if (strpos($message, $verify_url) === false) {
            $message .= "<p>If the above button doesn't work, copy and paste this URL into your browser:</p><p>$verify_url</p>";
        }

        $this->email->message($message);

        if ($this->email->send()) {
            log_message('debug', "✅ Verification email sent to: $email");
        } else {
            $debug = $this->email->print_debugger(['headers']);
            log_message('error', "❌ Failed to send verification email to: $email");
            log_message('error', $debug);
        }
    }

    /**
     * Send confirmation email to a customer after verification
     *
     * @param string $email
     * @return void
     */
    public function send_confirmation($email, $from_name, $subject, $message)
    {
        try {
            log_message('debug', "📨 Preparing confirmation email to: $email");

            $this->email->from('noreply@hostelevate.com', $from_name);
            $this->email->to($email);
            $this->email->subject($subject);
            $this->email->message($message);

            if ($this->email->send()) {
                log_message('debug', "✅ Confirmation email sent to: $email");
            } else {
                $debug = $this->email->print_debugger(['headers']);
                log_message('error', "❌ Failed to send confirmation email to: $email");
                log_message('error', $debug);
            }
        } catch (Exception $e) {
            log_message('error', '❌ Exception in send_confirmation(): ' . $e->getMessage());
        }
    }

    /**
     * Test SMTP email config
     */
    public function test_email()
    {
        $this->email->from('noreply@hostelevate.com', 'HostElevate Test');
        $this->email->to('faizshiraji@gmail.com');
        $this->email->subject('Test Mail from Hostinger SMTP');
        $this->email->message('✅ This is a test mail sent from CodeIgniter using Hostinger SMTP and valid sender authentication.');

        if ($this->email->send()) {
            echo "✅ Test mail sent.";
        } else {
            echo "❌ Mail send failed.";
            print_r($this->email->print_debugger(['headers']));
        }
    }
}