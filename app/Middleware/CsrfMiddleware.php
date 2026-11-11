<?php

declare(strict_types=1);

final class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        if ($request->hasInvalidJson()) {
            return Response::json([
                'success' => false,
                'message' => 'The request body is not valid JSON.',
            ], 400);
        }

        $token = $request->header('X-CSRF-Token') ?? $request->input('_token');
        if (Csrf::verify(is_string($token) ? $token : null)) {
            return null;
        }

        return $request->expectsJson()
            ? Response::json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419)
            : Response::view('errors/419', [], 419);
    }
}
