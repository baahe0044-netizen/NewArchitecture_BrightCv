<?php

declare(strict_types=1);

final class RewardsController extends Controller
{
    public function __construct(
        private readonly GamificationService $gamification = new GamificationService(),
        private readonly ResumeService $resumes = new ResumeService()
    ) {
    }

    public function index(Request $request): Response
    {
        if (!GamificationService::isEnabled()) {
            Session::flash('message', 'Rewards are off for this account. Turn them back on in Appearance to see this page.');
            return Response::redirect(base_url('/account/appearance'));
        }

        $userId = (int) Auth::id();
        $gamification = $this->gamification->summaryForUser($userId);
        $resumes = $this->resumes->all($userId);

        return $this->view('rewards/index', [
            'title' => 'Rewards',
            'user' => Auth::user(),
            'gamification' => $gamification,
            // The template someone would use "Use" on lands here -- the CV
            // they last touched, the same one the dashboard's hero panel and
            // builder open to by default.
            'topResumeId' => $resumes[0]['id'] ?? null,
            'message' => Session::pullFlash('message'),
        ]);
    }
}
