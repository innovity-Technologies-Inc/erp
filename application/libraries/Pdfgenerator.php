<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php'; // Adjust path if necessary

use Dompdf\Dompdf;
use Dompdf\Options;

class Pdfgenerator {

    public function generate($html, $filename = '', $stream = TRUE, $paper = 'A4', $orientation = 'portrait') {
      $options = new Options();
      $options->set('isHtml5ParserEnabled', true);
      $options->set('isRemoteEnabled', true); // For loading external images or fonts

      $dompdf = new Dompdf($options);
      $dompdf->loadHtml($html);
      $dompdf->setPaper($paper, $orientation);
      $dompdf->render();

      if ($stream) {
          $dompdf->stream($filename . ".pdf", array("Attachment" => 0));
      } else {
          return $dompdf->output();
      }
    }
}