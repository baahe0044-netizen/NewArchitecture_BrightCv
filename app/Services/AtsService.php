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

    /** Words a keyboard-mash or a filler draft is made of. */
    private const JUNK_WORDS = [
        'lorem', 'ipsum', 'dolor', 'amet', 'consectetur', 'adipiscing', 'blah', 'bla',
        'qwerty', 'asdf', 'asdfgh', 'xxx', 'xxxx', 'yyy', 'zzz', 'aaa', 'abc', 'abcd',
    ];

    /** Runs along a keyboard row that only show up when someone mashes keys. */
    private const KEYBOARD_RUNS = [
        'qwer', 'wert', 'erty', 'rtyu', 'tyui', 'yuio', 'uiop', 'asdf', 'sdfg', 'dfgh',
        'fghj', 'ghjk', 'hjkl', 'zxcv', 'xcvb', 'cvbn', 'vbnm', 'poiu', 'lkjh', 'mnbv',
    ];

    /** Short terms with no vowel that are real words, not gibberish. */
    private const ACRONYMS = [
        'html', 'css', 'sql', 'php', 'aws', 'gcp', 'xml', 'npm', 'crm', 'erp', 'sap',
        'ssl', 'http', 'https', 'kpi', 'kpis', 'cpr', 'bsc', 'msc', 'phd', 'mba', 'dvd',
        'pmp', 'cpd', 'ccna', 'mcsd', 'mcp', 'cnc', 'plc', 'scm', 'wms', 'tms', 'pms',
        'pwc', 'kpmg', 'ltd', 'llc', 'mrs', 'nhs', 'ngo', 'ngos', 'tv', 'cv', 'hr', 'pr',
    ];

    public function analyze(array $content, string $jobDescription = ''): array
    {
        // The photo is image data, not words, so it plays no part in scoring.
        unset($content['personal']['photo']);
        $personal = $content['personal'] ?? [];
        $experience = $content['experience'] ?? [];
        // A skill only counts when its name reads as a real term.
        $skills = array_values(array_filter(array_map(
            static fn ($item) => trim((string) ($item['name'] ?? '')),
            $content['skills'] ?? []
        ), fn (string $name) => $name !== '' && $this->isMeaningful($name)));
        $summary = trim((string) ($content['summary'] ?? ''));
        $resumeText = mb_strtolower($this->flatten($content));

        $categories = [];
        $recommendations = [];
        $strengths = [];
        // Sections whose text reads as random typing or filler rather than
        // real content. They earn nothing, and the report names them.
        $junkSections = [];

        $contactScore = 0;
        $name = trim((string) ($personal['full_name'] ?? ''));
        if ($name !== '' && preg_match('/\p{L}/u', $name) && $this->isMeaningful($name)) {
            $contactScore += 3;
        } elseif ($name !== '') {
            $junkSections[] = 'Name';
        }
        $contactScore += filter_var($personal['email'] ?? '', FILTER_VALIDATE_EMAIL) ? 6 : 0;
        // A phone number has at least seven digits, whatever its format.
        $contactScore += preg_match_all('/\d/', (string) ($personal['phone'] ?? '')) >= 7 ? 3 : 0;
        $location = trim((string) ($personal['location'] ?? ''));
        $contactScore += $location !== '' && $this->isMeaningful($location) ? 3 : 0;
        $categories['Contact details'] = min(15, $contactScore);
        if ($contactScore < 12) {
            $recommendations[] = 'Complete your name, professional email, phone number, and location.';
        } else {
            $strengths[] = 'Contact information is complete and easy for recruiters to find.';
        }

        $summaryLength = mb_strlen($summary);
        $summaryScore = $summaryLength >= 80 && $summaryLength <= 750 ? 15 : ($summaryLength >= 40 ? 9 : 0);
        if ($summary !== '' && !$this->isMeaningful($summary)) {
            $summaryScore = 0;
            $junkSections[] = 'Summary';
        }
        $categories['Professional summary'] = $summaryScore;
        if ($summaryScore < 15) {
            $recommendations[] = 'Write a focused 3–5 line summary with your target role, experience, and strongest value.';
        } else {
            $strengths[] = 'Your summary has an ATS-friendly length.';
        }

        $validExperience = 0;
        $bulletCount = 0;
        $impactBullets = 0;
        $junkExperience = false;
        foreach ($experience as $entry) {
            $role = trim((string) ($entry['role'] ?? ''));
            $company = trim((string) ($entry['company'] ?? ''));
            if ($role !== '' && $company !== '') {
                if ($this->isMeaningful($role) && $this->isMeaningful($company)) {
                    $validExperience++;
                } else {
                    $junkExperience = true;
                }
            }
            foreach ($entry['bullets'] ?? [] as $bullet) {
                $bullet = trim((string) $bullet);
                if ($bullet === '') {
                    continue;
                }
                // An achievement is a sentence: at least three real words.
                if (!$this->isMeaningful($bullet) || $this->realWordCount($bullet) < 3) {
                    $junkExperience = true;
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
        if ($junkExperience) {
            $junkSections[] = 'Experience';
        }
        if ($validExperience === 0) {
            $recommendations[] = 'Add at least one role with employer, dates, and achievement-focused bullets.';
        } elseif ($impactBullets < 2) {
            $recommendations[] = 'Start more experience bullets with action verbs and add numbers, percentages, or outcomes.';
        } else {
            $strengths[] = 'Your work history includes measurable, action-oriented impact.';
        }

        $namedSkills = array_filter(
            $content['skills'] ?? [],
            static fn ($item) => trim((string) ($item['name'] ?? '')) !== ''
        );
        if (count($namedSkills) > count($skills)) {
            $junkSections[] = 'Skills';
        }
        $skillScore = count($skills) >= 8 ? 15 : (count($skills) >= 5 ? 12 : count($skills) * 2);
        $categories['Relevant skills'] = min(15, $skillScore);
        if (count($skills) < 5) {
            $recommendations[] = 'Add at least five role-specific skills that also appear in relevant job descriptions.';
        } else {
            $strengths[] = 'Your skills section gives ATS software useful search terms.';
        }

        $educationScore = 0;
        $junkEducation = false;
        foreach ($content['education'] ?? [] as $education) {
            $degree = trim((string) ($education['degree'] ?? ''));
            $school = trim((string) ($education['school'] ?? ''));
            if ($degree !== '' && $school !== '') {
                if ($this->isMeaningful($degree) && $this->isMeaningful($school)) {
                    $educationScore = 10;
                    break;
                }
                $junkEducation = true;
            }
        }
        if ($educationScore === 0 && $junkEducation) {
            $junkSections[] = 'Education';
        }
        $categories['Education'] = $educationScore;
        if ($educationScore === 0) {
            $recommendations[] = 'Include your highest relevant qualification and institution.';
        }

        // Length is measured in real words, so padding a CV with random
        // typing does not make it look substantial.
        $wordCount = $this->realWordCount($this->flatten($content));
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

        if ($junkSections !== []) {
            // Leads the list: nothing else matters until the text is real.
            array_unshift(
                $recommendations,
                'Some text looks like random typing or filler (' . implode(', ', array_unique($junkSections))
                . '). Replace it with real information so recruiters and ATS software can read it.'
            );
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
            'quality_issues' => array_values(array_unique($junkSections)),
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

    /**
     * Whether a piece of text reads as real writing rather than random
     * typing, keyboard mashing, filler ("lorem ipsum"), or one word repeated.
     * At least 70% of its words must look like words, and longer text must
     * use a reasonable variety of them.
     */
    private function isMeaningful(string $text): bool
    {
        $words = $this->words($text);
        if ($words === []) {
            // Numbers or symbols only ("2024", "C++"): nothing to judge.
            return trim($text) !== '' && preg_match('/[\p{N}+#]/u', $text) === 1;
        }

        $real = count(array_filter($words, fn (string $word) => $this->looksLikeWord($word)));
        if ($real / count($words) < 0.7) {
            return false;
        }

        // "good good good good good good" is not a summary.
        if (count($words) >= 6 && count(array_unique($words)) / count($words) < 0.4) {
            return false;
        }

        return true;
    }

    private function realWordCount(string $text): int
    {
        return count(array_filter($this->words($text), fn (string $word) => $this->looksLikeWord($word)));
    }

    /** @return list<string> lower-cased, letters-only words */
    private function words(string $text): array
    {
        preg_match_all('/\p{L}+/u', mb_strtolower($text), $matches);
        return $matches[0] ?? [];
    }

    private function looksLikeWord(string $word): bool
    {
        if (in_array($word, self::JUNK_WORDS, true)) {
            return false;
        }
        $length = mb_strlen($word);
        if ($length <= 2 || in_array($word, self::ACRONYMS, true)) {
            return true;
        }
        if ($length > 25) {
            return false;
        }
        // The same letter three times running: "aaaa", "hhhhh".
        if (preg_match('/(\p{L})\1\1/u', $word)) {
            return false;
        }
        foreach (self::KEYBOARD_RUNS as $run) {
            if (str_contains($word, $run)) {
                return false;
            }
        }
        // Every real word of three or more letters has a vowel (y counts),
        // and none has six consonants in a row.
        $vowels = 'aeiouyàáâãäåæèéêëìíîïòóôõöøùúûüýÿœɛɔ';
        if (!preg_match('/[' . $vowels . ']/u', $word)) {
            return false;
        }
        return !preg_match('/[^' . $vowels . ']{6,}/u', $word);
    }

    private function flatten(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(fn ($item) => $this->flatten($item), $value));
        }
        return is_scalar($value) ? (string) $value : '';
    }
}
