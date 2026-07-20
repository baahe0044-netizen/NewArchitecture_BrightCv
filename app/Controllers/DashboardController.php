<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard = new DashboardService())
    {
    }

    public function index(Request $request): Response
    {
        $data = $this->dashboard->forUser((int) Auth::id());
        return $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'dashboard' => $data,
            'message' => Session::pullFlash('message'),
        ]);
    }

    public function data(Request $request): Response
    {
        return $this->success($this->dashboard->forUser((int) Auth::id()));
    }
}
