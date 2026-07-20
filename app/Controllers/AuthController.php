<?php

declare(strict_types=1);

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth = new AuthService())
    {
    }

    public function loginPage(Request $request): Response
    {
        return $this->view('auth/login', [
            'title' => 'Sign in',
            'message' => Session::pullFlash('message'),
            'error' => Session::pullFlash('error'),
            'oldEmail' => Session::pullFlash('old_email', ''),
        ]);
    }

    public function login(Request $request): Response
    {
        $result = $this->auth->login(
            $request->string('email'),
            $request->string('password'),
            $request->ip(),
            $request->boolean('remember')
        );

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            Session::flash('old_email', $request->string('email'));
            return Response::redirect(base_url('/login'));
        }

        return Response::redirect(base_url('/dashboard'));
    }

    public function registerPage(Request $request): Response
    {
        return $this->view('auth/register', [
            'title' => 'Create account',
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function register(Request $request): Response
    {
        $data = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $result = $this->auth->register($data);

        if (!$result['success']) {
            Session::flash('errors', $result['errors']);
            Session::flash('old', ['name' => $data['name'] ?? '', 'email' => $data['email'] ?? '']);
            return Response::redirect(base_url('/register'));
        }

        return Response::redirect(base_url('/dashboard'));
    }

    public function forgotPasswordPage(Request $request): Response
    {
        return $this->view('auth/forgot_password', [
            'title' => 'Reset password',
            'message' => Session::pullFlash('message'),
        ]);
    }

    public function forgotPassword(Request $request): Response
    {
        $email = mb_strtolower(trim($request->string('email')));
        $key = 'password-reset|' . $email . '|' . $request->ip();
        if (!RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 3600);
            $this->auth->requestPasswordReset($email);
        }
        Session::flash(
            'message',
            'If an account exists for that email, password reset instructions have been sent.'
        );
        return Response::redirect(base_url('/forgot-password'));
    }

    public function resetPasswordPage(Request $request, string $token): Response
    {
        return $this->view('auth/reset_password', [
            'title' => 'Choose a new password',
            'token' => $token,
            'email' => $request->string('email'),
            'error' => Session::pullFlash('error'),
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $result = $this->auth->resetPassword(
            $request->string('email'),
            $request->string('token'),
            $request->string('password'),
            $request->string('password_confirmation')
        );

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            return Response::redirect(
                base_url('/reset-password/' . rawurlencode($request->string('token')))
                . '?email=' . rawurlencode($request->string('email'))
            );
        }

        Session::flash('message', 'Your password has been reset. You can now sign in.');
        return Response::redirect(base_url('/login'));
    }

    public function logout(Request $request): Response
    {
        $userId = Auth::id();
        if ($userId) {
            (new ActivityRepository())->record($userId, 'signed_out', 'Signed out of LunettiStar');
        }
        Auth::logout();
        return Response::redirect(base_url('/'));
    }
}
