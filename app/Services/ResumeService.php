<?php

declare(strict_types=1);

final class ResumeService
{
    private const TEMPLATES = ['modern', 'executive', 'minimal', 'creative', 'graduate', 'tech'];
    private const LANGUAGES = ['en', 'fr', 'es'];
    private const FONTS = ['Inter', 'Arial', 'Georgia', 'Poppins', 'Source Sans 3'];

    public function __construct(private readonly ResumeRepository $resumes = new ResumeRepository())
    {
    }

    public function all(int $userId): array
    {
        return $this->resumes->listByUser($userId);
    }

    public function find(int $id, int $userId): ?array
    {
        return $this->resumes->findOwned($id, $userId);
    }

    public function create(int $userId, string $name = 'Untitled CV', string $templateKey = 'modern'): array
    {
        $name = $this->clean($name, 150) ?: 'Untitled CV';
        $templateKey = $this->allowed($templateKey, self::TEMPLATES, 'modern');
        $accentColors = [
            'modern' => '#5b4df7',
            'executive' => '#16324f',
            'minimal' => '#202124',
            'creative' => '#e25241',
            'graduate' => '#087f5b',
            'tech' => '#075985',
        ];
        $resume = $this->resumes->create(
            $userId,
            $name,
            self::defaultContent(),
            $templateKey,
            $accentColors[$templateKey]
        );
        (new ActivityRepository())->record($userId, 'resume_created', 'Created ' . $name, (int) $resume['id']);
        return $resume;
    }

    public function save(int $id, int $userId, array $payload): ?array
    {
        $content = $this->sanitizeContent(is_array($payload['content'] ?? null) ? $payload['content'] : []);
        $completion = $this->completion($content);

        $attributes = [
            'name' => $this->clean($payload['name'] ?? 'Untitled CV', 150) ?: 'Untitled CV',
            'template_key' => $this->allowed($payload['template_key'] ?? 'modern', self::TEMPLATES, 'modern'),
            'language' => $this->allowed($payload['language'] ?? 'en', self::LANGUAGES, 'en'),
            'accent_color' => $this->validColor($payload['accent_color'] ?? '#5b4df7'),
            'font_family' => $this->allowed($payload['font_family'] ?? 'Inter', self::FONTS, 'Inter'),
            'job_description' => $this->clean($payload['job_description'] ?? '', 15000),
            'content' => $content,
            'completion' => $completion,
            'status' => $completion >= 80 ? 'completed' : 'draft',
            'version' => isset($payload['version']) && is_numeric($payload['version'])
                ? (int) $payload['version']
                : null,
        ];

        $resume = $this->resumes->save($id, $userId, $attributes);
        if ($resume) {
            $activityKey = '_resume_activity_' . $id;
            $lastActivity = (int) Session::get($activityKey, 0);
            if ($lastActivity <= time() - 300) {
                (new ActivityRepository())->record($userId, 'resume_saved', 'Updated ' . $attributes['name'], $id);
                Session::put($activityKey, time());
            }
        }
        return $resume;
    }

    public function duplicate(int $id, int $userId): ?array
    {
        $resume = $this->resumes->duplicate($id, $userId);
        if ($resume) {
            (new ActivityRepository())->record($userId, 'resume_duplicated', 'Duplicated ' . $resume['name'], (int) $resume['id']);
        }
        return $resume;
    }

    public function delete(int $id, int $userId): bool
    {
        $resume = $this->find($id, $userId);
        $deleted = $this->resumes->softDelete($id, $userId);
        if ($deleted) {
            (new ActivityRepository())->record($userId, 'resume_deleted', 'Moved ' . ($resume['name'] ?? 'a CV') . ' to trash');
        }
        return $deleted;
    }

    public function completion(array $content): int
    {
        $personal = $content['personal'] ?? [];
        $score = 0;
        foreach (['full_name', 'headline', 'email', 'phone', 'location'] as $field) {
            $score += trim((string) ($personal[$field] ?? '')) !== '' ? 4 : 0;
        }
        $score += mb_strlen(trim((string) ($content['summary'] ?? ''))) >= 80 ? 15 : 0;
        $score += $this->hasMeaningfulEntry($content['experience'] ?? [], ['role', 'company']) ? 25 : 0;
        $score += $this->hasMeaningfulEntry($content['education'] ?? [], ['degree', 'school']) ? 15 : 0;
        $skills = array_filter($content['skills'] ?? [], static fn ($skill) => trim((string) ($skill['name'] ?? '')) !== '');
        $score += count($skills) >= 5 ? 15 : (count($skills) >= 2 ? 8 : 0);
        $score += (
            $this->hasMeaningfulEntry($content['projects'] ?? [], ['name'])
            || $this->hasMeaningfulEntry($content['certifications'] ?? [], ['name'])
            || $this->hasMeaningfulEntry($content['languages'] ?? [], ['name'])
        ) ? 10 : 0;

        return min(100, $score);
    }

    public static function defaultContent(): array
    {
        return [
            'personal' => [
                'full_name' => '',
                'headline' => '',
                'email' => '',
                'phone' => '',
                'location' => '',
                'website' => '',
                'linkedin' => '',
            ],
            'summary' => '',
            'experience' => [[
                'id' => self::uuid(),
                'role' => '',
                'company' => '',
                'location' => '',
                'start_date' => '',
                'end_date' => '',
                'current' => false,
                'bullets' => [],
            ]],
            'education' => [[
                'id' => self::uuid(),
                'degree' => '',
                'school' => '',
                'location' => '',
                'start_date' => '',
                'end_date' => '',
                'details' => '',
            ]],
            'skills' => [],
            'projects' => [],
            'certifications' => [],
            'languages' => [],
            'references' => [],
            'interests' => [],
            'settings' => [
                'density' => 'comfortable',
            ],
        ];
    }

    private function sanitizeContent(array $content): array
    {
        $clean = self::defaultContent();
        $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];
        foreach (array_keys($clean['personal']) as $field) {
            $clean['personal'][$field] = $this->clean($personal[$field] ?? '', 255);
        }

        $clean['summary'] = $this->clean($content['summary'] ?? '', 3000);
        $clean['experience'] = $this->sanitizeEntries($content['experience'] ?? [], [
            'id' => 60, 'role' => 160, 'company' => 160, 'location' => 160,
            'start_date' => 30, 'end_date' => 30,
        ], true);
        $clean['education'] = $this->sanitizeEntries($content['education'] ?? [], [
            'id' => 60, 'degree' => 180, 'school' => 180, 'location' => 160,
            'start_date' => 30, 'end_date' => 30, 'details' => 1000,
        ]);
        $clean['skills'] = $this->sanitizeEntries($content['skills'] ?? [], [
            'id' => 60, 'name' => 100, 'level' => 30,
        ]);
        $clean['projects'] = $this->sanitizeEntries($content['projects'] ?? [], [
            'id' => 60, 'name' => 180, 'role' => 160, 'url' => 255,
            'start_date' => 30, 'end_date' => 30, 'description' => 1600,
        ]);
        $clean['certifications'] = $this->sanitizeEntries($content['certifications'] ?? [], [
            'id' => 60, 'name' => 180, 'issuer' => 180, 'date' => 30, 'url' => 255,
        ]);
        $clean['languages'] = $this->sanitizeEntries($content['languages'] ?? [], [
            'id' => 60, 'name' => 100, 'level' => 60,
        ]);
        $clean['references'] = $this->sanitizeEntries($content['references'] ?? [], [
            'id' => 60, 'name' => 160, 'position' => 160, 'company' => 160,
            'email' => 190, 'phone' => 80,
        ]);

        $interests = is_array($content['interests'] ?? null) ? $content['interests'] : [];
        $clean['interests'] = array_values(array_filter(array_map(
            fn ($value) => $this->clean($value, 80),
            array_slice($interests, 0, 20)
        )));
        $settings = is_array($content['settings'] ?? null) ? $content['settings'] : [];
        $clean['settings']['density'] = $this->allowed(
            $settings['density'] ?? 'comfortable',
            ['compact', 'comfortable', 'spacious'],
            'comfortable'
        );

        return $clean;
    }

    private function sanitizeEntries(mixed $entries, array $fields, bool $withBullets = false): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $result = [];
        foreach (array_slice($entries, 0, 25) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $clean = [];
            foreach ($fields as $field => $limit) {
                $clean[$field] = $this->clean($entry[$field] ?? '', $limit);
            }
            $clean['id'] = $clean['id'] ?: self::uuid();
            if (array_key_exists('current', $entry)) {
                $clean['current'] = (bool) $entry['current'];
            }
            if ($withBullets) {
                $bullets = is_array($entry['bullets'] ?? null) ? $entry['bullets'] : [];
                $clean['bullets'] = array_values(array_filter(array_map(
                    fn ($bullet) => $this->clean($bullet, 600),
                    array_slice($bullets, 0, 12)
                )));
            }
            $result[] = $clean;
        }
        return $result;
    }

    private function hasMeaningfulEntry(array $entries, array $fields): bool
    {
        foreach ($entries as $entry) {
            $valid = true;
            foreach ($fields as $field) {
                if (trim((string) ($entry[$field] ?? '')) === '') {
                    $valid = false;
                }
            }
            if ($valid) {
                return true;
            }
        }
        return false;
    }

    private function clean(mixed $value, int $limit): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }
        $value = trim(strip_tags((string) $value));
        return mb_substr($value, 0, $limit);
    }

    private function allowed(mixed $value, array $allowed, string $fallback): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return $fallback;
        }
        $value = (string) $value;
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function validColor(mixed $color): string
    {
        if (!is_string($color)) {
            return '#5b4df7';
        }
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#5b4df7';
    }

    private static function uuid(): string
    {
        return bin2hex(random_bytes(8));
    }
}
