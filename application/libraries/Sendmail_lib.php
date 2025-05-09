<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendmail_lib {

    protected $CI;

    public function __construct()
    {
        // Get CI instance
        $this->CI =& get_instance();
        // Load email library
        $this->CI->load->library('email');
    }

    public function send($to, $subject, $message, $from = 'noreply@paysenz.com', $from_name = 'Paysenz')
    {
        $this->CI->email->from($from, $from_name);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($message);

        if ($this->CI->email->send()) {
            log_message('debug', "✅ Email sent to: $to");
            return true;
        } else {
            log_message('error', "❌ Email failed to: $to");
            log_message('error', $this->CI->email->print_debugger(['headers']));
            return false;
        }
    }

    // Optional quick test function
    public function test()
    {
        return $this->send(
            'faizshiraji@gmail.com',
            'Test Mail',
            'This is a test mail from Sendmail_lib.'
        );
    }
}