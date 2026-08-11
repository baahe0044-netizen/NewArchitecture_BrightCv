<?php

declare(strict_types=1);

final class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts = new AccountService())
    {
    }

    public function profile(Request $request): Response
    {
        return $this->view('account/profile', [
            'title' => 'Profile settings',
            'user' => Auth::user(),
            'message' => Session::pullFlash('message'),
            'error' => Session::pullFlash('error'),
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $result = $this->accounts->updateProfile((int) Auth::id(), $request->only(['name', 'job_title', 'locale']));
        if (!$result['success']) {
            Session::flash('errors', $result['errors']);
        } else {
            Session::flash('message', 'Profile updated.');
        }
        return Response::redirect(base_url('/account/profile'));
    }

    public function security(Request $request): Response
    {
        return $this->view('account/security', [
            'title' => 'Security',
            'user' => Auth::user(),
            'message' => Session::pullFlash('message'),
            'error' => Session::pullFlash('error'),
        ]);
    }

    public function appearance(Request $request): Response
    {
        return $this->view('account/appearance', [
            'title' => 'Appearance',
            'user' => Auth::user(),
        ]);
    }

    public function updatePassword(Request $request): Response
    {
        $result = $this->accounts->updatePassword(
            (int) Auth::id(),
            $request->string('current_password'),
            $request->string('password'),
            $request->string('password_confirmation')
        );
        Session::flash($result['success'] ? 'message' : 'error', $result['success'] ? 'Password changed.' : $result['message']);
        return Response::redirect(base_url('/account/security'));
    }
}
