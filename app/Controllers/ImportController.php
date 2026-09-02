<?php

declare(strict_types=1);

final class ImportController extends Controller
{
    public function __construct(
        private readonly ResumeService $resumes = new ResumeService(),
        private readonly CvImportService $importer = new CvImportService()
    ) {
    }

    /**
     * Parse an existing CV and hand the result back for review.
     *
     * The parsed content is never written here. The builder shows what was
     * detected, the writer confirms, and the normal save endpoint stores it —
     * so an imperfect parse can never quietly destroy an existing CV.
     */
    public function parseApi(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        if (!$resume) {
            return $this->error('CV not found.', 404);
        }

        $file = $request->file('cv_file');
        $text = $request->string('cv_text');

        // A request that arrives with neither part usually means the upload was
        // dropped for exceeding the server's own post size, which produces no
        // PHP upload error to report.
        if ($file === null && trim($text) === '') {
            return $this->error(
                'No CV reached the server. The file may be larger than this server accepts, so try a smaller export or paste the text instead.'
            );
        }

        try {
            $result = $file !== null
                ? $this->importer->fromUpload($file)
                : $this->importer->fromText($text);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage());
        } catch (Throwable) {
            return $this->error('That CV could not be read. Copy the text from it and paste it instead.', 500);
        }

        (new ActivityRepository())->record(
            (int) Auth::id(),
            'resume_imported',
            'Imported content into ' . $resume['name'],
            (int) $resume['id']
        );

        return $this->success($result, 'CV read successfully.');
    }
}
