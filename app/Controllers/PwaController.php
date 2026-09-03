<?php

declare(strict_types=1);

final class PwaController extends Controller
{
    /**
     * The web app manifest.
     *
     * Generated rather than served as a static file because `start_url` and
     * `scope` have to match wherever the app is installed, and BrightCV
     * commonly runs from a subdirectory such as /NewArchitecture_BrightCv/public.
     */
    public function manifest(Request $request): Response
    {
        $scope = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '/', '/') . '/';

        $manifest = [
            'name' => APP_NAME . ' — CV Builder',
            'short_name' => APP_NAME,
            'description' => 'Write, tailor, and export a professional CV from any device.',
            'id' => $scope,
            'start_url' => $scope . 'dashboard',
            'scope' => $scope,
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'portrait-primary',
            'background_color' => '#EBD9BB',
            'theme_color' => '#C1442A',
            'lang' => 'en',
            'dir' => 'ltr',
            'categories' => ['productivity', 'business'],
            'icons' => [
                [
                    'src' => asset('icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/maskable-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => asset('icons/maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'My CVs',
                    'short_name' => 'Dashboard',
                    'url' => $scope . 'dashboard',
                ],
                [
                    'name' => 'Browse templates',
                    'short_name' => 'Templates',
                    'url' => $scope . 'templates',
                ],
            ],
        ];

        return (new Response(
            json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        ))
            ->withHeader('Content-Type', 'application/manifest+json; charset=utf-8')
            // The session cookie rides along on this response, so it must not
            // be held in a shared cache. Browsers re-read the manifest cheaply.
            ->withHeader('Cache-Control', 'no-cache');
    }
}
