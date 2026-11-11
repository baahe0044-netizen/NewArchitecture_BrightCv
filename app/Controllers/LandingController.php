<?php

declare(strict_types=1);

final class LandingController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('landing/index', [
            'title' => 'Build a CV that gets noticed',
            'user' => Auth::user(),
            'templates' => $this->templates(),
        ]);
    }

    /**
     * Every template, for the showcase. The landing page must still open
     * when the database cannot be reached, so the names then come from the
     * template keys themselves.
     *
     * @return list<array{template_key: string, name: string, color: string}>
     */
    private function templates(): array
    {
        try {
            $templates = (new TemplateService())->all();
            if ($templates !== []) {
                return $templates;
            }
        } catch (Throwable) {
            // Fall through to the catalogue.
        }

        return array_map(static fn (string $key): array => [
            'template_key' => $key,
            'name' => ucfirst($key),
            'color' => TemplateCatalog::color($key),
        ], TemplateCatalog::keys());
    }
}
