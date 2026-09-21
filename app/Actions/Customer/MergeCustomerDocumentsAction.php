<?php

namespace App\Actions\Customer;

use Mpdf\Mpdf;

class MergeCustomerDocumentsAction
{
    /**
     * @param  string[]  $pdfContents  Raw PDF byte strings, in the order they should appear in the merged output.
     */
    public function execute(array $pdfContents): string
    {
        $tempDir = storage_path('app/tmp/mpdf-merge');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf(['tempDir' => $tempDir, 'format' => 'A4']);
        $tempFiles = [];

        try {
            foreach ($pdfContents as $content) {
                $tempFile = tempnam($tempDir, 'src_');
                file_put_contents($tempFile, $content);
                $tempFiles[] = $tempFile;

                $pageCount = $mpdf->setSourceFile($tempFile);

                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $mpdf->importPage($i);
                    $mpdf->AddPage();
                    $mpdf->useTemplate($tplId, 0, 0, null, null, true);
                }
            }

            return $mpdf->Output('', 'S');
        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }
    }
}
