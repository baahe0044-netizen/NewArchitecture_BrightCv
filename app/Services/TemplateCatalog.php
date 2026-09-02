<?php

declare(strict_types=1);

/**
 * Single source of truth for the CV templates.
 *
 * The layout and section order live here rather than in the database so the
 * catalogue can grow without a schema migration. `resume_templates` still owns
 * the marketing copy (name, category, description) and the active flag.
 */
final class TemplateCatalog
{
    public const LAYOUTS = ['stacked', 'sidebar'];
    public const ORDERS = ['standard', 'skills_first'];

    /**
     * layout: how the body flows. `stacked` is one vertical column across the
     * full page width; `sidebar` keeps the narrower two-column arrangement.
     */
    private const TEMPLATES = [
        'modern' => ['color' => '#5b4df7', 'layout' => 'stacked', 'order' => 'standard'],
        'classic' => ['color' => '#1f2933', 'layout' => 'stacked', 'order' => 'skills_first'],
        'minimal' => ['color' => '#202124', 'layout' => 'stacked', 'order' => 'standard'],
        'elegant' => ['color' => '#6b4423', 'layout' => 'stacked', 'order' => 'standard'],
        'compact' => ['color' => '#334155', 'layout' => 'stacked', 'order' => 'standard'],
        'timeline' => ['color' => '#0f766e', 'layout' => 'stacked', 'order' => 'standard'],
        'tech' => ['color' => '#075985', 'layout' => 'stacked', 'order' => 'skills_first'],
        'graduate' => ['color' => '#087f5b', 'layout' => 'stacked', 'order' => 'skills_first'],
        'academic' => ['color' => '#3b3663', 'layout' => 'stacked', 'order' => 'standard'],
        'executive' => ['color' => '#16324f', 'layout' => 'sidebar', 'order' => 'standard'],
        'bold' => ['color' => '#c2255c', 'layout' => 'stacked', 'order' => 'standard'],
        'creative' => ['color' => '#e25241', 'layout' => 'sidebar', 'order' => 'standard'],
        'editorial' => ['color' => '#7f1d1d', 'layout' => 'stacked', 'order' => 'standard'],
        'metro' => ['color' => '#1d4ed8', 'layout' => 'stacked', 'order' => 'standard'],
        'ledger' => ['color' => '#3f3f46', 'layout' => 'stacked', 'order' => 'standard'],
        'spectrum' => ['color' => '#7c3aed', 'layout' => 'stacked', 'order' => 'skills_first'],
        'slate' => ['color' => '#0f172a', 'layout' => 'sidebar', 'order' => 'skills_first'],
        'aurora' => ['color' => '#be185d', 'layout' => 'stacked', 'order' => 'standard'],
    ];

    public const DEFAULT_KEY = 'modern';

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function supports(string $key): bool
    {
        return isset(self::TEMPLATES[$key]);
    }

    public static function color(string $key): string
    {
        return self::TEMPLATES[$key]['color'] ?? self::TEMPLATES[self::DEFAULT_KEY]['color'];
    }

    public static function layout(string $key): string
    {
        return self::TEMPLATES[$key]['layout'] ?? 'stacked';
    }

    public static function order(string $key): string
    {
        return self::TEMPLATES[$key]['order'] ?? 'standard';
    }
}
