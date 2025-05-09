<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php'; // Composer autoloader

use Dompdf\Dompdf;

if (!function_exists('pdf_create')) {
    function pdf_create($html, $filename = '', $stream = true, $paper = 'A4', $orientation = "portrait")
    {
        $dompdf = new Dompdf();

        // Optional: enable HTML5 parser
        $dompdf->set_option('isHtml5ParserEnabled', true);

        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        if ($stream) {
            $dompdf->stream($filename, ['Attachment' => 0]); // 1 = force download, 0 = open in browser
        } else {
            return $dompdf->output();
        }
    }
}