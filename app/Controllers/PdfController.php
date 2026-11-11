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

        // The builder's own Print/PDF button already stops a guest before
        // they get here, but this is the actual page a print-to-PDF reads
        // from, so it is where the gate has to hold even if that button is
        // bypassed -- someone typing the URL directly, an old tab, a saved
        // link. ?claim=1 tells the builder to open the same account-creation
        // modal on arrival, rather than a visitor bouncing back with no
        // explanation for why the button they clicked took them here.
        if ((bool) (Auth::user()['is_guest'] ?? false)) {
            return Response::redirect(base_url('/resume/builder/' . $resume['id']) . '?claim=1');
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
