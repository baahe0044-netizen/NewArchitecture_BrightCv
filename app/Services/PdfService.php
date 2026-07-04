<?php

use Dompdf\Dompdf;

class PdfService
{
    public function generateResumePdf($resumeId, $data)
    {
        $html = $this->buildHtml(
            $data['personal'],
            $data['experience'],
            $data['education'],
            $data['skills']
        );

        $dompdf = new Dompdf();

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $filename =
            'resume_' .
            $resumeId .
            '_' .
            time() .
            '_' .
            bin2hex(random_bytes(4)) .
            '.pdf';

        $output =
            __DIR__ .
            '/../../storage/pdfs/' .
            $filename;

        file_put_contents(
            $output,
            $dompdf->output()
        );

        $repo = new ResumeRepository();

        $repo->savePdfRecord(
            $resumeId,
            $filename
        );

        $dompdf->stream(
            $filename,
            [
                "Attachment" => 1
            ]
        );
    }

    private function buildHtml(
        $personal,
        $experience,
        $education,
        $skills
    ) {

        ob_start();

        include
            __DIR__ .
            '/../Views/resume/pdf.php';

        return ob_get_clean();
    }
}
