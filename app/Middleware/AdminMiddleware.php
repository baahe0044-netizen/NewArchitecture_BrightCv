<?php

declare(strict_types=1);

final class AdminMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (Auth::isAdmin()) {
            return null;
        }

        return $request->expectsJson()
            ? Response::json(['success' => false, 'message' => 'Administrator access required.'], 403)
            : Response::view('errors/403', [], 403);
    }
}
