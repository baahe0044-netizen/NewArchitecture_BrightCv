<?php

declare(strict_types=1);

final class LandingController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('landing/index', [
            'title' => 'Build a CV that gets noticed',
            'user' => Auth::user(),
        ]);
    }
}
