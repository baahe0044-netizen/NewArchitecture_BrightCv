<?php

declare(strict_types=1);

final class AIService
{
    public function run(string $action, array $input, array $resume): array
    {
        return match ($action) {
            'summary' => $this->summary($input, $resume),
            'bullet' => $this->bullet($input),
            'keywords' => $this->keywords($input, $resume),
            'tips' => $this->tips($resume),
            default => throw new InvalidArgumentException('Unsupported assistant action.'),
        };
    }

    private function summary(array $input, array $resume): array
    {
        $content = $resume['content'] ?? [];
        $personal = $content['personal'] ?? [];
        $role = trim((string) ($input['target_role'] ?? $personal['headline'] ?? 'professional'));
        $years = max(0, (int) ($input['years'] ?? count($content['experience'] ?? [])));
        $skills = array_values(array_filter(array_map(
            static fn ($skill) => trim((string) ($skill['name'] ?? '')),
            $content['skills'] ?? []
        )));
        $topSkills = implode(', ', array_slice($skills, 0, 3));
        $value = trim((string) ($input['value'] ?? 'delivering reliable, measurable results'));

        $text = sprintf(
            '%s with %s of experience in %s. Skilled in %s, with a record of %s. Known for clear communication, ownership, and turning priorities into practical outcomes.',
            ucfirst($role),
            $years > 0 ? $years . '+ years' : 'hands-on experience',
            $role,
            $topSkills !== '' ? $topSkills : 'cross-functional collaboration and problem solving',
            rtrim($value, '.')
        );

        return ['text' => $text, 'label' => 'Professional summary'];
    }

    private function bullet(array $input): array
    {
        $text = trim((string) ($input['text'] ?? ''));
        $outcome = trim((string) ($input['outcome'] ?? ''));
        if ($text === '') {
            throw new InvalidArgumentException('Add a responsibility or achievement first.');
        }

        $text = preg_replace('/^(responsible for|helped to|worked on|tasked with)\s+/i', '', $text) ?: $text;
        $verbs = ['Delivered', 'Implemented', 'Improved', 'Coordinated', 'Developed', 'Optimized'];
        $startsWithVerb = preg_match('/^(achieved|built|created|delivered|designed|developed|drove|implemented|improved|increased|launched|led|managed|optimized|reduced|resolved|streamlined)\b/i', $text);
        $result = $startsWithVerb ? ucfirst($text) : $verbs[abs(crc32($text)) % count($verbs)] . ' ' . lcfirst($text);
        $result = rtrim($result, '.');

        if ($outcome !== '') {
            $result .= ', resulting in ' . lcfirst(rtrim($outcome, '.'));
        } elseif (!preg_match('/\d|%/', $result)) {
            $result .= ' while improving quality and delivery consistency';
        }

        return [
            'text' => $result . '.',
            'label' => 'Achievement bullet',
            'tip' => 'Replace the general outcome with a real number or result when possible.',
        ];
    }

    private function keywords(array $input, array $resume): array
    {
        $report = (new AtsService())->analyze(
            $resume['content'] ?? [],
            (string) ($input['job_description'] ?? $resume['job_description'] ?? '')
        );

        return [
            'keywords' => $report['missing_keywords'],
            'matched' => $report['matched_keywords'],
            'text' => $report['missing_keywords'] === []
                ? 'Your CV already covers the strongest extracted keywords.'
                : 'Consider these terms only where they truthfully match your experience.',
            'label' => 'Keyword suggestions',
        ];
    }

    private function tips(array $resume): array
    {
        $report = (new AtsService())->analyze(
            $resume['content'] ?? [],
            (string) ($resume['job_description'] ?? '')
        );
        return [
            'items' => $report['recommendations'],
            'text' => $report['recommendations'][0] ?? 'Your CV has a strong foundation. Tailor it for each application.',
            'label' => 'Smart suggestions',
        ];
    }
}
