<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard = new DashboardService())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'dashboard' => $this->dashboard->forUser((int) Auth::id()),
            'message' => Session::pullFlash('message'),
        ]);
    }

    public function data(Request $request): Response
    {
        return $this->success($this->dashboard->forUser((int) Auth::id()));
    }

    /**
     * The Rewards page is gone along with levels, streaks, and badges. An
     * old bookmark or installed-app link to it lands on the dashboard rather
     * than a 404.
     */
    public function legacyRewards(Request $request): Response
    {
        return Response::redirect(base_url('/dashboard'), 301);
    }
}
