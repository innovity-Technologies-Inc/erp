<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail_lib {

    protected $CI;

    public function __construct()
    {
        // Load CodeIgniter super-object
        $this->CI =& get_instance();

        // Load email library and configuration
        $this->CI->load->library('email');
        $this->CI->config->load('email'); // optional: if you're using email config from config/email.php
    }

    /**
     * Send an email via configured SMTP
     *
     * @param string $to          Recipient email address
     * @param string $subject     Email subject
     * @param string $message     HTML content of the email
     * @param string $from        (optional) Sender email address, default: noreply@paysenz.com
     * @param string $from_name   (optional) Sender name, default: Paysenz
     * @return bool               TRUE on success, FALSE on failure
     */
    public function send($to, $subject, $message, $from = 'noreply@hostelevate.com', $from_name = 'DeshiShad')
    {
        // Prepare headers
        $this->CI->email->from($from, $from_name);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($message);

        // Attempt to send email
        if ($this->CI->email->send()) {
            log_message('debug', "✅ Email successfully sent to: $to | Subject: $subject");
            return true;
        } else {
            $debug_output = $this->CI->email->print_debugger(['headers']);
            log_message('error', "❌ Failed to send email to: $to | Subject: $subject");
            log_message('error', "📧 Email Debug Info:\n" . $debug_output);
            return false;
        }
    }

    /**
     * Send a test email to verify SMTP settings
     *
     * @param string|null $recipient Optional recipient email for testing (defaults to system admin)
     * @return bool
     */
    public function test($recipient = null)
    {
        $to = $recipient ?? 'faizshiraji@gmail.com'; // Replace with a default testing email
        $subject = '✅ Test Email from Sendmail_lib';
        $body = '<p>This is a test email from <strong>Sendmail_lib</strong>.</p>
                 <p>If you received this, SMTP and email config are working!</p>';

        return $this->send($to, $subject, $body);
    }
}