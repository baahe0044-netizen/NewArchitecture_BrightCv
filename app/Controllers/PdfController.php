<?php

declare(strict_types=1);

final class PdfController extends Controller
{
    public function __construct(private readonly ResumeService $resumes = new ResumeService())
    {
    }

    public function preview(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        if (!$resume) {
            return $this->view('errors/404', ['title' => 'CV not found'], 404);
        }

        return $this->view('resume/print', [
            'title' => $resume['name'],
            'resume' => $resume,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function recordExport(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        if (!$resume) {
            return $this->error('CV not found.', 404);
        }

        $format = $request->string('format', 'pdf');
        if (!in_array($format, ['pdf', 'json', 'print'], true)) {
            return $this->error('Unsupported export format.');
        }

        (new ResumeRepository())->recordExport(
            (int) $resume['id'],
            $format,
            (string) ($resume['language'] ?? 'en')
        );
        (new ActivityRepository())->record(
            (int) Auth::id(),
            'resume_exported',
            'Exported ' . $resume['name'] . ' as ' . strtoupper($format),
            (int) $resume['id']
        );

        return $this->success([], 'Export recorded.');
    }
}
