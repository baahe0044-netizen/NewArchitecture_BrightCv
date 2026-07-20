<?php

declare(strict_types=1);

final class GuestMiddleware
{
    public function handle(Request $request): ?Response
    {
        return Auth::check() ? Response::redirect(base_url('/dashboard')) : null;
    }
}
