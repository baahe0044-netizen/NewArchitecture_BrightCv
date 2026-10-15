<?php

declare(strict_types=1);

final class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        return $request->expectsJson()
            ? Response::json(['success' => false, 'message' => 'Authentication required.'], 401)
            : Response::redirect(base_url('/login'));
    }
}
