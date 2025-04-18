<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['protocol']     = 'smtp';
$config['smtp_host']    = 'mail.paysenz.com';
$config['smtp_port']    = 465;
$config['smtp_user']    = 'noreply@paysenz.com';
$config['smtp_pass']    = 'P@ySenz2024';
$config['smtp_crypto']  = 'ssl';
$config['mailtype']     = 'html';
$config['charset']      = 'utf-8';
$config['newline']      = "\r\n";
$config['crlf']         = "\r\n";
$config['wordwrap']     = TRUE;
$config['smtp_timeout'] = 30;