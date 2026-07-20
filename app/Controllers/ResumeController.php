<?php

declare(strict_types=1);

final class ResumeController extends Controller
{
    public function __construct(
        private readonly ResumeService $resumes = new ResumeService(),
        private readonly TemplateService $templates = new TemplateService(),
        private readonly AIService $assistant = new AIService(),
        private readonly AtsService $ats = new AtsService()
    ) {
    }

    public function builder(Request $request, string $id = ''): Response
    {
        $resumeId = (int) ($id !== '' ? $id : $request->input('resume_id', 0));
        if ($resumeId <= 0) {
            Session::flash('message', 'Create a CV from your dashboard to start building.');
            return Response::redirect(base_url('/dashboard'));
        }

        $resume = $this->resumes->find($resumeId, (int) Auth::id());
        if (!$resume) {
            return $this->view('errors/404', ['title' => 'CV not found'], 404);
        }

        return $this->view('resume/builder', [
            'title' => 'Edit ' . $resume['name'],
            'user' => Auth::user(),
            'resume' => $resume,
            'templates' => $this->templates->all(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function indexApi(Request $request): Response
    {
        return $this->success(['resumes' => $this->resumes->all((int) Auth::id())]);
    }

    public function createApi(Request $request): Response
    {
        $resume = $this->resumes->create(
            (int) Auth::id(),
            $request->string('name', 'Untitled CV'),
            $request->string('template_key', 'modern')
        );

        return $this->success([
            'resume' => $resume,
            'builder_url' => base_url('/resume/builder/' . $resume['id']),
        ], 'CV created.', 201);
    }

    public function showApi(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        return $resume
            ? $this->success(['resume' => $resume])
            : $this->error('CV not found.', 404);
    }

    public function updateApi(Request $request, string $id): Response
    {
        $payload = $request->input();
        if (
            $request->hasInvalidJson()
            || !is_array($payload['content'] ?? null)
            || !isset($payload['version'])
            || !is_numeric($payload['version'])
            || (int) $payload['version'] < 1
        ) {
            return $this->error('Invalid CV document.');
        }

        try {
            $resume = $this->resumes->save((int) $id, (int) Auth::id(), $payload);
        } catch (ConflictException $exception) {
            return $this->error(
                $exception->getMessage() . ' Your unsaved work is safe on this device. Refresh to reconcile it.',
                409
            );
        }

        return $resume
            ? $this->success(['resume' => $resume], 'Changes saved.')
            : $this->error('CV not found.', 404);
    }

    public function deleteApi(Request $request, string $id): Response
    {
        return $this->resumes->delete((int) $id, (int) Auth::id())
            ? $this->success([], 'CV moved to trash.')
            : $this->error('CV not found.', 404);
    }

    public function duplicateApi(Request $request, string $id): Response
    {
        $resume = $this->resumes->duplicate((int) $id, (int) Auth::id());
        return $resume
            ? $this->success([
                'resume' => $resume,
                'builder_url' => base_url('/resume/builder/' . $resume['id']),
            ], 'CV duplicated.', 201)
            : $this->error('CV not found.', 404);
    }

    public function atsApi(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        if (!$resume) {
            return $this->error('CV not found.', 404);
        }

        $jobDescription = $request->string('job_description', (string) ($resume['job_description'] ?? ''));
        $report = $this->ats->analyze($resume['content'], $jobDescription);
        (new ResumeRepository())->saveAtsScore(
            (int) $resume['id'],
            (int) $report['score'],
            (int) $report['keyword_score'],
            $report
        );
        (new ActivityRepository())->record(
            (int) Auth::id(),
            'ats_scan',
            'Scanned ' . $resume['name'] . ' for ATS compatibility',
            (int) $resume['id']
        );

        return $this->success(['report' => $report], 'ATS scan complete.');
    }

    public function assistantApi(Request $request, string $id): Response
    {
        $resume = $this->resumes->find((int) $id, (int) Auth::id());
        if (!$resume) {
            return $this->error('CV not found.', 404);
        }

        $action = $request->string('action', 'tips');
        $input = $request->input('input', []);
        $input = is_array($input) ? $input : [];

        try {
            $result = $this->assistant->run($action, $input, $resume);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        (new ResumeRepository())->saveAssistantHistory(
            (int) $resume['id'],
            $action,
            json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''
        );

        return $this->success(['result' => $result], 'Suggestion ready.');
    }
}
