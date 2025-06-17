<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('email');
        $this->CI->load->database();
    }

    /**
     * Send an email via configured SMTP from email_config table
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $from        Optional
     * @param string $from_name   Optional
     * @return bool
     */
    public function send($to, $subject, $message, $from = null, $from_name = 'DeshiShad')
    {
        // ⏱️ Allow email script more time to run
        set_time_limit(60); // adjust as needed

        $smtp = $this->CI->db->get('email_config')->row_array(); // no 'status' column assumed

        if (!$smtp) {
            log_message('error', '[Sendmail_lib] No SMTP configuration found in email_config table.');
            return false;
        }

        $from = $from ?? $smtp['smtp_user'];

        $config = [
            'protocol'     => $smtp['protocol'],
            'smtp_host'    => $smtp['smtp_host'],
            'smtp_port'    => $smtp['smtp_port'],
            'smtp_user'    => $smtp['smtp_user'],
            'smtp_pass'    => $smtp['smtp_pass'],
            'smtp_crypto'  => 'ssl',
            'mailtype'     => $smtp['mailtype'] ?? 'html',
            'charset'      => 'utf-8',
            'newline'      => "\r\n",
            'crlf'         => "\r\n",
            'wordwrap'     => TRUE,
            'smtp_timeout' => 10,
        ];

        $this->CI->email->initialize($config);
        $this->CI->email->from($from, $from_name);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($message);

        if ($this->CI->email->send()) {
            log_message('debug', "[Sendmail_lib] ✅ Email sent to: $to | Subject: $subject");
            return true;
        } else {
            log_message('error', "[Sendmail_lib] ❌ Email failed to: $to | Subject: $subject");
            log_message('error', "[Sendmail_lib] 📧 Debug Info:\n" . $this->CI->email->print_debugger(['headers']));
            return false;
        }
    }

    public function test($recipient = null)
    {
        $to = $recipient ?? 'faizshiraji@gmail.com';
        $subject = '✅ Test Email from Sendmail_lib';
        $body = '<p>This is a test email from <strong>Sendmail_lib</strong>.</p><p>If you received this, SMTP config is working!</p>';
        return $this->send($to, $subject, $body);
    }
}