<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard = new DashboardService(),
        private readonly GamificationService $gamification = new GamificationService(),
        private readonly TemplateService $templates = new TemplateService()
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $data = $this->dashboard->forUser($userId);
        $gamification = null;
        if (GamificationService::isEnabled()) {
            $gamification = $this->gamification->summaryForUser($userId);
            // The "Continue with" journey stepper reads the same resume row
            // the library already fetched, so it draws from what is on
            // screen rather than a second, possibly different, query.
            $topResume = $data['resumes'][0] ?? null;
            $gamification['journey'] = $topResume ? $this->gamification->journeyFor($topResume) : [];
        }

        $topResume = $data['resumes'][0] ?? null;
        $topTemplateName = $topResume ? ($this->templates->find((string) $topResume['template_key'])['name'] ?? '') : '';

        return $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'dashboard' => $data,
            'gamification' => $gamification,
            'topTemplateName' => $topTemplateName,
            'message' => Session::pullFlash('message'),
        ]);
    }

    public function data(Request $request): Response
    {
        return $this->success($this->dashboard->forUser((int) Auth::id()));
    }
}
