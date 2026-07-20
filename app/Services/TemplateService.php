<?php

declare(strict_types=1);

final class TemplateService
{
    private const SUPPORTED = ['modern', 'executive', 'minimal', 'creative', 'graduate', 'tech'];

    public function __construct(private readonly TemplateRepository $templates = new TemplateRepository())
    {
    }

    public function all(): array
    {
        return array_values(array_filter(array_map(
            fn (array $template): ?array => $this->normalize($template),
            $this->templates->allActive()
        )));
    }

    public function find(string $key): ?array
    {
        return $this->normalize($this->templates->findByKey($key));
    }

    private function normalize(?array $template): ?array
    {
        if (!$template || !in_array((string) ($template['template_key'] ?? ''), self::SUPPORTED, true)) {
            return null;
        }

        $color = (string) ($template['color'] ?? '');
        $template['color'] = preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : '#5b4df7';
        foreach (['name' => 100, 'category' => 60, 'description' => 255] as $field => $limit) {
            $value = $template[$field] ?? '';
            $template[$field] = is_string($value)
                ? mb_substr(trim(strip_tags($value)), 0, $limit)
                : '';
        }
        $template['is_premium'] = (int) ($template['is_premium'] ?? 0);
        return $template;
    }
}
