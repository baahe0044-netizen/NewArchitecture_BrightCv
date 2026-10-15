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
            'gamificationEnabled' => GamificationService::isEnabled(),
            'message' => Session::pullFlash('message'),
        ]);
    }

    /**
     * The Rewards page's own transparency panel says this switch exists, so
     * it has to actually work. Phase 1 stores the choice for this session
     * only (see GamificationService) -- a setting that survives logout needs
     * a users column, which is Phase 2.
     */
    public function updateGamification(Request $request): Response
    {
        GamificationService::setEnabled($request->boolean('enabled'));
        Session::flash(
            'message',
            GamificationService::isEnabled()
                ? 'Levels, streaks, and badges are on for this session.'
                : 'Levels, streaks, and badges are off for this session. Your CVs are unaffected.'
        );
        return Response::redirect(base_url('/account/appearance'));
    }

    /**
     * The claim step reached from the builder's download gate -- turns the
     * signed-in guest account into a real one in place. Auth::user() still
     * reflects the pre-claim state for this request (it is cached per
     * request in Auth::$user), so is_guest is read fresh from the session's
     * own user id rather than trusted from an already-fetched $user array.
     */
    public function claimApi(Request $request): Response
    {
        $user = Auth::user();
        if (!$user || empty($user['is_guest'])) {
            return $this->error('This account has already been set up.', 409);
        }

        $result = $this->accounts->claimGuestAccount(
            (int) Auth::id(),
            $request->only(['name', 'email', 'password', 'password_confirmation'])
        );

        if (!$result['success']) {
            return $this->error('Check the highlighted fields.', 422, $result['errors']);
        }

        return $this->success(['user' => $result['user']], 'Account created.');
    }

    /**
     * The claim modal's other path: a guest who already has an account logs
     * into it instead of creating a second one. This switches the session
     * to that real account (see AuthService::loginAndClaimGuest -- same
     * rate limiting and timing-safe failure as /login itself), which resets
     * the session's CSRF token, so the client cannot just keep calling the
     * save/print APIs with the token it already has -- it reloads the
     * builder page instead, which is what actually needs a fresh one.
     */
    public function loginClaimApi(Request $request): Response
    {
        $user = Auth::user();
        if (!$user || empty($user['is_guest'])) {
            return $this->error('This account has already been set up.', 409);
        }

        $result = (new AuthService())->loginAndClaimGuest(
            $request->string('email'),
            $request->string('password'),
            $request->ip(),
            (int) $user['id']
        );

        if (!$result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success(['user' => $result['user']], 'Signed in.');
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
