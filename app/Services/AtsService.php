<?php

declare(strict_types=1);

final class AtsService
{
    private const ACTION_VERBS = [
        'achieved', 'accelerated', 'built', 'created', 'delivered', 'designed',
        'developed', 'drove', 'generated', 'grew', 'implemented', 'improved',
        'increased', 'launched', 'led', 'managed', 'optimized', 'reduced',
        'resolved', 'saved', 'scaled', 'streamlined', 'transformed',
    ];

    private const STOP_WORDS = [
        'about', 'after', 'also', 'and', 'are', 'been', 'being', 'but', 'can',
        'company', 'from', 'have', 'into', 'job', 'more', 'must', 'our', 'role',
        'that', 'the', 'their', 'this', 'through', 'with', 'will', 'work', 'you',
        'your', 'years', 'looking', 'candidate', 'required', 'preferred',
    ];

    public function analyze(array $content, string $jobDescription = ''): array
    {
        $personal = $content['personal'] ?? [];
        $experience = $content['experience'] ?? [];
        $skills = array_values(array_filter(array_map(
            static fn ($item) => trim((string) ($item['name'] ?? '')),
            $content['skills'] ?? []
        )));
        $summary = trim((string) ($content['summary'] ?? ''));
        $resumeText = mb_strtolower($this->flatten($content));

        $categories = [];
        $recommendations = [];
        $strengths = [];

        $contactScore = 0;
        foreach (['full_name', 'email', 'phone', 'location'] as $field) {
            $contactScore += trim((string) ($personal[$field] ?? '')) !== '' ? 3 : 0;
        }
        $contactScore += filter_var($personal['email'] ?? '', FILTER_VALIDATE_EMAIL) ? 3 : 0;
        $categories['Contact details'] = min(15, $contactScore);
        if ($contactScore < 12) {
            $recommendations[] = 'Complete your name, professional email, phone number, and location.';
        } else {
            $strengths[] = 'Contact information is complete and easy for recruiters to find.';
        }

        $summaryLength = mb_strlen($summary);
        $summaryScore = $summaryLength >= 80 && $summaryLength <= 750 ? 15 : ($summaryLength >= 40 ? 9 : 0);
        $categories['Professional summary'] = $summaryScore;
        if ($summaryScore < 15) {
            $recommendations[] = 'Write a focused 3–5 line summary with your target role, experience, and strongest value.';
        } else {
            $strengths[] = 'Your summary has an ATS-friendly length.';
        }

        $validExperience = 0;
        $bulletCount = 0;
        $impactBullets = 0;
        foreach ($experience as $entry) {
            if (trim((string) ($entry['role'] ?? '')) !== '' && trim((string) ($entry['company'] ?? '')) !== '') {
                $validExperience++;
            }
            foreach ($entry['bullets'] ?? [] as $bullet) {
                $bullet = trim((string) $bullet);
                if ($bullet === '') {
                    continue;
                }
                $bulletCount++;
                $lower = mb_strtolower($bullet);
                $hasVerb = array_filter(self::ACTION_VERBS, static fn ($verb) => str_starts_with($lower, $verb));
                if ($hasVerb || preg_match('/\d|%|\b(GH₵|USD|EUR)\b/u', $bullet)) {
                    $impactBullets++;
                }
            }
        }
        $experienceScore = min(14, $validExperience * 7)
            + min(6, $bulletCount * 2)
            + min(5, $impactBullets * 2);
        $categories['Experience impact'] = min(25, $experienceScore);
        if ($validExperience === 0) {
            $recommendations[] = 'Add at least one role with employer, dates, and achievement-focused bullets.';
        } elseif ($impactBullets < 2) {
            $recommendations[] = 'Start more experience bullets with action verbs and add numbers, percentages, or outcomes.';
        } else {
            $strengths[] = 'Your work history includes measurable, action-oriented impact.';
        }

        $skillScore = count($skills) >= 8 ? 15 : (count($skills) >= 5 ? 12 : count($skills) * 2);
        $categories['Relevant skills'] = min(15, $skillScore);
        if (count($skills) < 5) {
            $recommendations[] = 'Add at least five role-specific skills that also appear in relevant job descriptions.';
        } else {
            $strengths[] = 'Your skills section gives ATS software useful search terms.';
        }

        $educationScore = 0;
        foreach ($content['education'] ?? [] as $education) {
            if (trim((string) ($education['degree'] ?? '')) !== '' && trim((string) ($education['school'] ?? '')) !== '') {
                $educationScore = 10;
                break;
            }
        }
        $categories['Education'] = $educationScore;
        if ($educationScore === 0) {
            $recommendations[] = 'Include your highest relevant qualification and institution.';
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $this->flatten($content), $wordMatches);
        $wordCount = count($wordMatches[0] ?? []);
        $formatScore = 10;
        if ($wordCount < 120) {
            $formatScore -= 4;
            $recommendations[] = 'Your CV is very short. Add evidence and context while keeping each point concise.';
        } elseif ($wordCount > 1200) {
            $formatScore -= 3;
            $recommendations[] = 'Your CV may be too long. Remove repetition and keep only role-relevant evidence.';
        }
        if ($bulletCount > 0 && $bulletCount < 3) {
            $formatScore -= 2;
        }
        $categories['Readability'] = max(0, $formatScore);

        [$keywordScore, $matched, $missing] = $this->keywordMatch($resumeText, $jobDescription);
        $categories['Job keywords'] = (int) round($keywordScore / 10);
        if ($jobDescription === '') {
            $recommendations[] = 'Paste a target job description to unlock keyword matching.';
        } elseif ($keywordScore < 60) {
            $recommendations[] = 'Use relevant missing keywords naturally in your summary, skills, and achievement bullets.';
        } else {
            $strengths[] = 'Your CV matches many of the target role’s important terms.';
        }

        $score = min(100, array_sum($categories));

        return [
            'score' => $score,
            'keyword_score' => $keywordScore,
            'categories' => $categories,
            'strengths' => array_slice($strengths, 0, 5),
            'recommendations' => array_slice($recommendations, 0, 7),
            'matched_keywords' => $matched,
            'missing_keywords' => $missing,
            'word_count' => $wordCount,
            'grade' => match (true) {
                $score >= 85 => 'Excellent',
                $score >= 70 => 'Strong',
                $score >= 55 => 'Developing',
                default => 'Needs work',
            },
        ];
    }

    private function keywordMatch(string $resumeText, string $jobDescription): array
    {
        if (trim($jobDescription) === '') {
            return [0, [], []];
        }

        preg_match_all('/[\p{L}\p{N}+#.]{3,}/u', mb_strtolower($jobDescription), $matches);
        $counts = array_count_values(array_filter(
            $matches[0] ?? [],
            static fn ($word) => (
                mb_strlen($word) >= 4
                || preg_match('/^(php|sql|aws|git|css|api|seo|sap|erp|crm)$/', $word)
            ) && !in_array($word, self::STOP_WORDS, true)
        ));
        arsort($counts);
        $keywords = array_slice(array_keys($counts), 0, 20);
        $matched = [];
        $missing = [];

        foreach ($keywords as $keyword) {
            if (str_contains($resumeText, $keyword)) {
                $matched[] = $keyword;
            } else {
                $missing[] = $keyword;
            }
        }

        $score = $keywords === [] ? 0 : (int) round((count($matched) / count($keywords)) * 100);
        return [$score, array_slice($matched, 0, 12), array_slice($missing, 0, 12)];
    }

    private function flatten(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(fn ($item) => $this->flatten($item), $value));
        }
        return is_scalar($value) ? (string) $value : '';
    }
}
