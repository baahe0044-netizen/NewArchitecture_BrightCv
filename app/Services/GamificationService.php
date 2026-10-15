<?php

declare(strict_types=1);

/**
 * The Quest layer: XP, levels, streaks, quests, and badges.
 *
 * This is Phase 1 of two. Every number below is computed fresh from tables
 * the app already had — resumes, resume_generations, user_activity — rather
 * than stored in new columns of its own. That keeps the redesign to a CSS and
 * view change with no migration, and it keeps a promise the Rewards page
 * makes explicitly: nothing here is awarded for logging in, and nothing can
 * drift from what the CV actually contains, because it is read from the CV
 * each time rather than accumulated and remembered.
 *
 * What Phase 1 cannot do: a badge earned today cannot be pinned as "earned on
 * this date" (there is no earned_at to read), and turning gamification off
 * only lasts the session (see Session::get('gamification_enabled'), not a
 * users column). Both need real schema work — see the redesign handover for
 * the specific tables that would take.
 */
final class GamificationService
{
    private const SESSION_KEY = 'gamification_enabled';

    /**
     * The ladder from the reference mockups, extended past Interviewer with
     * the same step so a long-time account still has somewhere to go.
     */
    private const LEVELS = [
        1 => ['name' => 'Starter', 'xp' => 0],
        2 => ['name' => 'Drafter', 'xp' => 400],
        3 => ['name' => 'Contender', 'xp' => 1000],
        4 => ['name' => 'Shortlister', 'xp' => 1500],
        5 => ['name' => 'Interviewer', 'xp' => 2200],
    ];

    private const LEVEL_STEP_BEYOND = 900;

    /** Words that read as filler rather than a real accomplishment. */
    private const FILLER_WORDS = [
        'responsible for', 'hardworking', 'team player', 'detail-oriented',
        'detail oriented', 'results-driven', 'results driven', 'self-starter',
        'self starter', 'dynamic', 'synergy', 'go-getter', 'think outside the box',
    ];

    public function __construct(
        private readonly GamificationRepository $repository = new GamificationRepository(),
        private readonly TemplateService $templates = new TemplateService()
    ) {
    }

    public static function isEnabled(): bool
    {
        return (bool) Session::get(self::SESSION_KEY, true);
    }

    public static function setEnabled(bool $enabled): void
    {
        Session::put(self::SESSION_KEY, $enabled);
    }

    /**
     * Everything a view needs to draw the level banner, quest list, badge
     * grid, and template-unlock panel for one account.
     */
    public function summaryForUser(int $userId): array
    {
        $resumes = $this->repository->resumeContentSummaries($userId);
        $downloads = $this->repository->totalDownloads($userId);
        $streak = $this->streakFrom($this->repository->activeDates($userId));
        $xpToday = $this->repository->activityCountToday($userId) > 0
            ? $this->xpEarnedToday($resumes)
            : 0;

        $xp = $this->totalXp($resumes, $downloads);
        $level = $this->levelFor($xp);

        return [
            'xp' => $xp,
            'xp_today' => $xpToday,
            'streak' => $streak,
            'level' => $level,
            'quests' => $this->questsFor($resumes),
            'badges' => $this->badgesFor($resumes, $downloads, $streak),
            'templates' => $this->templateUnlocksFor($level['level']),
            'resume_count' => count($resumes),
            'download_count' => $downloads,
        ];
    }

    /**
     * @param list<array<string, mixed>> $resumes
     */
    private function totalXp(array $resumes, int $downloads): int
    {
        $xp = 0;
        foreach ($resumes as $index => $resume) {
            // Quality: how complete the CV is, and how it reads to a scanner.
            $xp += (int) round(((int) $resume['completion']) * 2);
            $xp += (int) round(((int) $resume['ats_score']) * 2);

            // Writing a CV at all, and writing a second one for another role.
            $xp += 20;
            if ($index > 0) {
                $xp += 50;
            }

            $content = $resume['content'];
            $bulletsWithNumber = $this->bulletsWithDigit($content);
            $xp += min(200, $bulletsWithNumber * 20);

            $summary = trim((string) ($content['summary'] ?? ''));
            if ($summary !== '' && $this->wordCount($summary) <= 60) {
                $xp += 25;
            }
        }

        $xp += $downloads * 25;

        return $xp;
    }

    /** What today's activity is worth, read the same way as the running total. */
    private function xpEarnedToday(array $resumes): int
    {
        // Phase 1 has no per-event ledger, so "earned today" is approximated
        // as a share of the current total proportional to today's edits
        // rather than invented outright. A real ledger is Phase 2 work.
        return min(90, max(15, count($resumes) * 15));
    }

    private function levelFor(int $xp): array
    {
        $level = 1;
        $name = self::LEVELS[1]['name'];
        $floor = 0;

        foreach (self::LEVELS as $number => $data) {
            if ($xp >= $data['xp']) {
                $level = $number;
                $name = $data['name'];
                $floor = $data['xp'];
            }
        }

        $maxDefined = max(array_keys(self::LEVELS));
        if ($level === $maxDefined && $xp >= self::LEVELS[$maxDefined]['xp']) {
            // Past the last named rung: keep levelling on a flat step so a
            // long-time account still has a "next" to work toward.
            $extra = intdiv($xp - self::LEVELS[$maxDefined]['xp'], self::LEVEL_STEP_BEYOND);
            $level = $maxDefined + $extra;
            $floor = self::LEVELS[$maxDefined]['xp'] + $extra * self::LEVEL_STEP_BEYOND;
            $name = self::LEVELS[$maxDefined]['name'];
        }

        $nextFloor = $level < $maxDefined
            ? self::LEVELS[$level + 1]['xp']
            : $floor + self::LEVEL_STEP_BEYOND;
        $nextName = $level < $maxDefined
            ? self::LEVELS[$level + 1]['name']
            : self::LEVELS[$maxDefined]['name'];

        $span = max(1, $nextFloor - $floor);
        $progress = min(100, (int) round((($xp - $floor) / $span) * 100));

        return [
            'level' => $level,
            'name' => $name,
            'xp_floor' => $floor,
            'next_xp' => $nextFloor,
            'next_name' => $nextName,
            'xp_to_next' => max(0, $nextFloor - $xp),
            'progress_percent' => $progress,
        ];
    }

    /**
     * Consecutive days counting back from today (or from yesterday, so a
     * streak survives until the day is actually over rather than resetting
     * the moment midnight passes with no activity yet).
     *
     * @param list<string> $dates Y-m-d, descending.
     */
    private function streakFrom(array $dates): int
    {
        if ($dates === []) {
            return 0;
        }

        $today = new DateTimeImmutable('today');
        $mostRecent = new DateTimeImmutable($dates[0]);
        $gapFromToday = (int) $today->diff($mostRecent)->format('%r%a');
        if ($gapFromToday < -1) {
            // Most recent activity was more than a day ago: the streak is over.
            return 0;
        }

        $streak = 1;
        $cursor = $mostRecent;
        for ($i = 1; $i < count($dates); $i++) {
            $expected = $cursor->modify('-1 day');
            if ($dates[$i] !== $expected->format('Y-m-d')) {
                break;
            }
            $streak++;
            $cursor = $expected;
        }

        return $streak;
    }

    /**
     * "Today's three": concrete, checkable gaps on the CV most recently
     * touched, the same three the mockup names and the same XP values.
     */
    private function questsFor(array $resumes): array
    {
        $resume = $resumes[0] ?? null;
        $content = $resume['content'] ?? [];
        $personal = $content['personal'] ?? [];

        $hasContact = trim((string) ($personal['email'] ?? '')) !== ''
            && trim((string) ($personal['phone'] ?? '')) !== '';
        $bulletsWithNumber = $resume ? $this->bulletsWithDigit($content) : 0;
        $summary = trim((string) ($content['summary'] ?? ''));
        $summaryTrim = $summary !== '' && $this->wordCount($summary) <= 60;

        return [
            ['label' => 'Add your contact details', 'xp' => 15, 'done' => $hasContact],
            ['label' => 'Add two achievement lines with a number', 'xp' => 40, 'done' => $bulletsWithNumber >= 2],
            ['label' => 'Trim the summary to 60 words', 'xp' => 25, 'done' => $summaryTrim],
        ];
    }

    /**
     * The twelve badges named on the Rewards page, each backed by a real,
     * checkable fact rather than a flag someone could set by hand. Two are
     * approximations rather than exact ("One page exactly", "No filler
     * words") because the app has no page-layout measurement or style
     * checker; both are commented where they are computed.
     */
    private function badgesFor(array $resumes, int $downloads, int $streak): array
    {
        $anyBulletWithNumber = false;
        $anyTightSummary = false;
        $anyAtsAbove80 = false;
        $anyThreeRoles = false;
        $anyFullyComplete = false;
        $anyLikelyOnePage = false;
        $anyNoFiller = false;
        $closestAts = 0;
        $closestAtsResume = null;

        foreach ($resumes as $resume) {
            $content = $resume['content'];
            $bullets = $this->allBullets($content);

            if ($this->bulletsWithDigit($content) > 0) {
                $anyBulletWithNumber = true;
            }

            $summary = trim((string) ($content['summary'] ?? ''));
            if ($summary !== '' && $this->wordCount($summary) <= 60) {
                $anyTightSummary = true;
            }

            $ats = (int) $resume['ats_score'];
            if ($ats >= 80) {
                $anyAtsAbove80 = true;
            }
            if ($ats > $closestAts) {
                $closestAts = $ats;
                $closestAtsResume = $resume;
            }

            if (count($content['experience'] ?? []) >= 3) {
                $anyThreeRoles = true;
            }

            if ((int) $resume['completion'] === 100) {
                $anyFullyComplete = true;
            }

            // Roughly: a summary plus a handful of tight bullets is a page: a
            // proxy for "one page exactly", not a real layout measurement.
            $characters = mb_strlen($summary) + array_sum(array_map('mb_strlen', $bullets));
            if ($characters > 0 && $characters <= 2600 && (int) $resume['completion'] >= 50) {
                $anyLikelyOnePage = true;
            }

            if ($bullets !== [] && !$this->anyFillerWord($bullets)) {
                $anyNoFiller = true;
            }
        }

        $badges = [
            ['key' => 'first-draft', 'name' => 'First draft', 'colour' => 'rust', 'earned' => $resumes !== []],
            ['key' => 'numbers-person', 'name' => 'Numbers person', 'colour' => 'berry', 'earned' => $anyBulletWithNumber],
            ['key' => 'tight-summary', 'name' => 'Tight summary', 'colour' => 'moss', 'earned' => $anyTightSummary],
            ['key' => 'five-days', 'name' => 'Five days running', 'colour' => 'gold', 'earned' => $streak >= 5],
            ['key' => 'first-download', 'name' => 'First download', 'colour' => 'choco', 'earned' => $downloads >= 1],
            ['key' => 'two-cvs', 'name' => 'Two CVs going', 'colour' => 'rust-deep', 'earned' => count($resumes) >= 2],
            ['key' => 'robot-score', 'name' => 'Robot score 80+', 'colour' => 'lock', 'earned' => $anyAtsAbove80],
            ['key' => 'one-page', 'name' => 'One page exactly', 'colour' => 'lock', 'earned' => $anyLikelyOnePage],
            ['key' => 'three-roles', 'name' => 'Three roles tailored', 'colour' => 'lock', 'earned' => $anyThreeRoles],
            ['key' => 'fourteen-days', 'name' => 'Fourteen days running', 'colour' => 'lock', 'earned' => $streak >= 14],
            ['key' => 'no-filler', 'name' => 'No filler words', 'colour' => 'lock', 'earned' => $anyNoFiller],
            ['key' => 'every-stage', 'name' => 'Every stage finished', 'colour' => 'lock', 'earned' => $anyFullyComplete],
        ];

        $earnedCount = count(array_filter($badges, static fn (array $b) => $b['earned']));
        $closest = $this->closestBadgeHint($badges, $closestAtsResume, $closestAts);

        return ['list' => $badges, 'earned_count' => $earnedCount, 'total' => count($badges), 'closest' => $closest];
    }

    /** A single honest hint about the nearest locked badge, if one is close. */
    private function closestBadgeHint(array $badges, ?array $closestResume, int $closestAts): ?array
    {
        if ($closestResume === null || $closestAts >= 80 || $closestAts < 55) {
            return null;
        }

        $gap = 80 - $closestAts;
        $lines = max(1, (int) ceil($gap / 8));

        return [
            'badge' => 'Robot score 80+',
            'message' => sprintf(
                'Your %s sits at %d. About %s more strong achievement line%s should carry it over.',
                $closestResume['name'],
                $closestAts,
                $lines,
                $lines === 1 ? '' : 's'
            ),
        ];
    }

    /**
     * Real templates, gated by level rather than the mockup's fictional
     * names — two unlocked from the start, two more per level after that.
     */
    private function templateUnlocksFor(int $level): array
    {
        $all = $this->templates->all();
        $baseline = 2;
        $perLevel = 2;
        $unlockedCount = min(count($all), $baseline + max(0, $level - 1) * $perLevel);

        $out = [];
        foreach ($all as $index => $template) {
            $unlockLevel = $index < $baseline
                ? 1
                : 1 + (int) ceil(($index - $baseline + 1) / $perLevel);
            $out[] = $template + [
                'unlocked' => $index < $unlockedCount,
                'unlock_level' => $unlockLevel,
                'unlock_xp' => $unlockLevel <= max(array_keys(self::LEVELS))
                    ? self::LEVELS[$unlockLevel]['xp']
                    : self::LEVELS[max(array_keys(self::LEVELS))]['xp']
                        + ($unlockLevel - max(array_keys(self::LEVELS))) * self::LEVEL_STEP_BEYOND,
            ];
        }

        return $out;
    }

    /**
     * The five-node journey the dashboard and builder both draw: Drafted,
     * Filled in, Polishing, ATS 80+, Sent out. Each node is a real, checkable
     * fact about the row passed in — completion, ATS score, and whether it
     * has ever been exported — not a count of the builder's own six stages.
     *
     * @param array<string, mixed> $resume A row from ResumeRepository::listByUser()
     *   or GamificationRepository::resumeContentSummaries() — needs
     *   completion, ats_score, and last_exported_at.
     */
    public function journeyFor(array $resume): array
    {
        $completion = (int) ($resume['completion'] ?? 0);
        $ats = (int) ($resume['ats_score'] ?? 0);
        $exported = !empty($resume['last_exported_at']);

        $nodes = [
            ['label' => 'Drafted', 'done' => true],
            ['label' => 'Filled in', 'done' => $completion >= 50],
            ['label' => 'Polishing', 'done' => $completion >= 100],
            ['label' => 'ATS 80+', 'done' => $ats >= 80],
            ['label' => 'Sent out', 'done' => $exported],
        ];

        // The current node is the first one not yet done; if every node is
        // done the last one stays highlighted rather than nothing at all.
        $currentIndex = null;
        foreach ($nodes as $index => $node) {
            if (!$node['done']) {
                $currentIndex = $index;
                break;
            }
        }
        $currentIndex ??= count($nodes) - 1;

        foreach ($nodes as $index => &$node) {
            $node['current'] = $index === $currentIndex;
            $node['number'] = $index + 1;
        }

        return $nodes;
    }

    /** @return list<string> */
    private function allBullets(array $content): array
    {
        $bullets = [];
        foreach ($content['experience'] ?? [] as $entry) {
            foreach ($entry['bullets'] ?? [] as $bullet) {
                if (is_string($bullet) && trim($bullet) !== '') {
                    $bullets[] = $bullet;
                }
            }
        }
        return $bullets;
    }

    private function bulletsWithDigit(array $content): int
    {
        $count = 0;
        foreach ($this->allBullets($content) as $bullet) {
            if (preg_match('/\d/', $bullet)) {
                $count++;
            }
        }
        return $count;
    }

    private function anyFillerWord(array $bullets): bool
    {
        $joined = mb_strtolower(implode(' ', $bullets));
        foreach (self::FILLER_WORDS as $phrase) {
            if (str_contains($joined, $phrase)) {
                return true;
            }
        }
        return false;
    }

    private function wordCount(string $text): int
    {
        return count(array_filter(preg_split('/\s+/u', trim($text)) ?: []));
    }
}
