<?php

declare(strict_types=1);

/**
 * Turns an existing CV into editable BrightCV content.
 *
 * Accepts a PDF, Word (.docx), plain text, or a BrightCV JSON backup, reduces
 * it to plain text, then reads that text with heading and date heuristics.
 * Nothing is saved here: the builder shows what was detected and the writer
 * confirms before it replaces their CV, because no parser gets every layout
 * right and a CV is not something to overwrite silently.
 */
final class CvImportService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const EXTENSIONS = ['pdf', 'docx', 'txt', 'md', 'json'];

    /** Enough readable characters to be worth showing the writer. */
    private const MIN_USEFUL_CHARS = 60;

    private const MONTHS = 'jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec';

    /** Heading text a section is skipped under, because it has no BrightCV equivalent. */
    public const SKIPPED = '_skipped';

    /** A contact block, scanned for personal details rather than imported as content. */
    public const CONTACT = '_contact';

    private const HEADINGS = [
        'summary' => [
            'summary', 'professional summary', 'career summary', 'profile', 'professional profile',
            'personal profile', 'about', 'about me', 'objective', 'career objective',
            'professional objective', 'personal statement', 'professional overview', 'overview',
            'introduction', 'career profile', 'executive summary', 'summary of qualifications',
        ],
        'experience' => [
            'experience', 'work experience', 'professional experience', 'employment',
            'employment history', 'work history', 'career history', 'relevant experience',
            'professional background', 'positions held', 'career experience', 'industry experience',
            'work',
        ],
        'education' => [
            'education', 'education and training', 'academic background', 'academic qualifications',
            'qualifications', 'educational background', 'academic history', 'schooling',
            'academic record', 'academics', 'education history',
        ],
        'skills' => [
            'skills', 'technical skills', 'key skills', 'core skills', 'core competencies',
            'competencies', 'areas of expertise', 'expertise', 'technologies', 'technical proficiencies',
            'skills and competencies', 'professional skills', 'computer skills', 'it skills',
            'technical expertise', 'skills summary', 'key competencies', 'tools and technologies',
            'software skills', 'soft skills',
        ],
        'projects' => [
            'projects', 'key projects', 'personal projects', 'selected projects', 'portfolio',
            'project experience', 'notable projects', 'academic projects', 'side projects',
        ],
        'certifications' => [
            'certifications', 'certificates', 'certification', 'licenses', 'licences',
            'courses', 'training', 'professional development', 'awards and certifications',
            'certifications and licenses', 'courses and certifications', 'workshops',
            // Awards are credentials too, and this is the closest place BrightCV
            // has for them, so they are carried over rather than dropped.
            'awards', 'honours', 'honors', 'awards and honours', 'awards and honors',
            'awards and recognition', 'scholarships',
        ],
        'languages' => ['languages', 'language proficiency', 'language skills', 'spoken languages'],
        'references' => ['references', 'referees', 'referrals', 'referees available'],
        'interests' => [
            'interests', 'hobbies', 'hobbies and interests', 'interests and activities',
            'activities', 'extracurricular activities', 'personal interests', 'other interests',
        ],
        // Recognised so their content is never filed under the previous heading,
        // but deliberately not imported: BrightCV has nowhere honest to put them,
        // and inventing a category would put text in a section the writer never
        // wrote it for. The review panel names them so nothing goes missing
        // silently.
        self::SKIPPED => [
            'publications', 'papers', 'research', 'research experience', 'conference papers',
            'presentations', 'patents', 'exhibitions',
            'volunteer', 'volunteering', 'volunteer experience', 'volunteer work',
            'community service', 'community involvement', 'leadership',
            'personal details', 'personal information', 'personal data', 'biodata', 'bio data',
            'additional information', 'other information', 'declaration', 'availability',
            'memberships', 'professional memberships', 'affiliations', 'professional affiliations',
            'military service', 'national service', 'salary expectations',
        ],
        // Read for the email, phone, location, and links it holds. Nothing from
        // it becomes CV content, so there is nothing to report as skipped.
        self::CONTACT => [
            'contact', 'contact details', 'contact information', 'get in touch',
            'details', 'personal contact',
        ],
    ];

    /**
     * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file
     * @return array{content: array, source: string, detected: array, characters: int}
     */
    public function fromUpload(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('That file is larger than this server accepts. Try a file under 5 MB.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The file did not upload completely. Please try again.');
        }

        $path = (string) ($file['tmp_name'] ?? '');
        if ($path === '' || !is_readable($path)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('That file is larger than 5 MB. Export a smaller copy and try again.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new RuntimeException('Supported files are PDF, Word (.docx), plain text, and BrightCV JSON backups.');
        }

        $raw = (string) file_get_contents($path);
        if ($extension === 'json') {
            return $this->fromJson($raw);
        }

        $text = $this->extractText($raw, $extension);
        if (mb_strlen(preg_replace('/[^\p{L}\p{N}]/u', '', $text) ?? '') < self::MIN_USEFUL_CHARS) {
            throw new RuntimeException(
                $extension === 'pdf'
                    ? 'No readable text was found in that PDF. It is probably a scan or an image export, so copy the text from your CV and paste it instead.'
                    : 'No readable text was found in that file. Copy the text from your CV and paste it instead.'
            );
        }

        return $this->fromText($text, strtoupper($extension));
    }

    /**
     * @return array{content: array, source: string, detected: array, characters: int}
     */
    public function fromText(string $text, string $source = 'Pasted text'): array
    {
        $text = trim($text);
        if (mb_strlen($text) < self::MIN_USEFUL_CHARS) {
            throw new RuntimeException('Paste a little more of your CV so the sections can be recognised.');
        }

        $skipped = [];
        $content = $this->parse($text, $skipped);
        return [
            'content' => $content,
            'source' => $source,
            'detected' => $this->summarize($content) + ['skipped' => $skipped],
            'characters' => mb_strlen($text),
        ];
    }

    /**
     * A BrightCV backup already has the right shape, so it is passed through.
     *
     * @return array{content: array, source: string, detected: array, characters: int}
     */
    private function fromJson(string $raw): array
    {
        $parsed = json_decode($raw, true);
        $resume = is_array($parsed) ? ($parsed['resume'] ?? $parsed) : null;
        if (!is_array($resume) || !is_array($resume['content'] ?? null)) {
            throw new RuntimeException('That JSON file is not a BrightCV backup.');
        }

        return [
            'content' => $resume['content'],
            'source' => 'BrightCV backup',
            'detected' => $this->summarize($resume['content']) + ['skipped' => []],
            'characters' => strlen($raw),
        ];
    }

    public function extractText(string $raw, string $extension): string
    {
        // Nearly every PDF compresses its content streams, so without zlib
        // there is nothing to read. Saying so beats returning an empty page.
        if ($extension === 'pdf' && !function_exists('gzinflate')) {
            throw new RuntimeException(
                'PDF import needs the PHP zlib extension, which this server does not have. '
                . 'Copy the text from your CV and paste it instead.'
            );
        }

        return match ($extension) {
            'pdf' => PdfTextExtractor::extract($raw),
            'docx' => $this->extractDocx($raw),
            default => $this->normalizeEncoding($raw),
        };
    }

    private function extractDocx(string $raw): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Word import needs the PHP zip extension. Save your CV as PDF or plain text instead.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'brightcv_');
        if ($temporary === false) {
            throw new RuntimeException('The server could not open that Word file.');
        }

        try {
            file_put_contents($temporary, $raw);
            $zip = new ZipArchive();
            if ($zip->open($temporary) !== true) {
                throw new RuntimeException('That Word file could not be opened. Re-save it as .docx and try again.');
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (!is_string($xml) || $xml === '') {
                throw new RuntimeException('That Word file has no readable document body.');
            }
        } finally {
            @unlink($temporary);
        }

        // Word names its headings outright, which is how a section called
        // "Where I studied" can be recognised without the parser knowing the
        // phrase. Only the shallowest heading level marks sections: CVs
        // routinely style a job title as Heading 2, and treating those as
        // sections would cut every entry away from its own heading.
        preg_match_all('#<w:pStyle\s[^>]*w:val="Heading(\d)"#i', $xml, $levels);
        $sectionLevel = $levels[1] === [] ? null : min(array_map('intval', $levels[1]));

        $xml = preg_replace_callback(
            '#<w:p(?:\s[^>]*)?>(.*?)</w:p>#s',
            static function (array $match) use ($sectionLevel): string {
                $styled = preg_match('#<w:pStyle\s[^>]*w:val="(Heading(\d)|Title)"#i', $match[1], $style) === 1
                    && ($style[1] === 'Title' || (int) $style[2] === $sectionLevel);
                return ($styled ? PdfTextExtractor::HEADING_MARK : '') . $match[0];
            },
            $xml
        ) ?? $xml;

        // Paragraph and break tags carry the line structure the parser relies on.
        $xml = preg_replace('#<w:(?:p|br)\b[^>]*/>#', "\n", $xml) ?? $xml;
        $xml = str_replace(['</w:p>', '</w:tr>'], "\n", $xml);
        $xml = str_replace(['<w:tab/>', '</w:tc>'], ' ', $xml);
        $text = strip_tags($xml);

        return $this->normalizeEncoding(html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function normalizeEncoding(string $text): string
    {
        $text = str_replace(["\xEF\xBB\xBF", "\r\n", "\r"], ['', "\n", "\n"], $text);
        if (!mb_check_encoding($text, 'UTF-8')) {
            $converted = @mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
            $text = is_string($converted) ? $converted : '';
        }
        return $text;
    }

    // ------------------------------------------------------------------
    // Parsing
    // ------------------------------------------------------------------

    /**
     * @param list<string>|null $skipped filled with the headings that were
     *        recognised as sections but deliberately not imported
     */
    public function parse(string $text, array &$skipped = null): array
    {
        $lines = $this->toLines($text, $markedHeadings);
        // On a sidebar CV the name sits beside the contact column rather than
        // above it, so it has to be found before the text is cut into sections;
        // otherwise a name in capitals reads as a section heading and the lines
        // around it are filed under whatever heading the sidebar ended with.
        $identity = $this->findIdentity($lines);
        $sections = $this->splitSections($lines, $skipped, $identity['lines'], $markedHeadings);

        $content = [
            'personal' => $this->parsePersonal(
                array_merge($sections['_header'] ?? [], $sections[self::CONTACT] ?? []),
                $lines,
                $identity
            ),
            'summary' => $this->joinParagraph($sections['summary'] ?? []),
            'experience' => $this->parseHistory($sections['experience'] ?? [], 'experience'),
            'education' => $this->parseHistory($sections['education'] ?? [], 'education'),
            'skills' => $this->parseSkills($sections['skills'] ?? []),
            'projects' => $this->parseProjects($sections['projects'] ?? []),
            'certifications' => $this->parseCertifications($sections['certifications'] ?? []),
            'languages' => $this->parseLanguages($sections['languages'] ?? []),
            'references' => $this->parseReferences($sections['references'] ?? []),
            'interests' => $this->parseInterests($sections['interests'] ?? []),
            'settings' => ['density' => 'comfortable', 'layout' => 'stacked', 'section_order' => 'standard'],
        ];

        // A CV can list more links than BrightCV has places for. The extra ones
        // are named in the review rather than dropped without a word.
        $extraLinks = $this->unusedLinks(
            array_merge($sections['_header'] ?? [], $sections[self::CONTACT] ?? []),
            $content['personal']
        );
        foreach ($extraLinks as $link) {
            $skipped[] = $link;
        }

        // A CV with no summary heading often opens with one anyway, so the
        // leftover header prose is promoted. It has to read like a summary
        // rather than a tagline, otherwise a job title under the name would be
        // pulled in as one.
        if ($content['summary'] === '' && isset($sections['_header'])) {
            $candidate = $this->joinParagraph($this->prose($sections['_header']));
            if (str_word_count($candidate) >= 12) {
                $content['summary'] = $candidate;
            }
        }

        return $content;
    }

    /**
     * Reduce raw text to trimmed lines, keeping blank lines as section breaks
     * and re-marking bullet lines with one marker the parser can test for.
     *
     * @return list<string>
     */
    private function toLines(string $text, array &$marked = null): array
    {
        $marked = [];
        $text = $this->normalizeEncoding($text);
        $text = str_replace(
            ["\u{00A0}", "\u{25AA}", "\u{25CF}", "\u{2023}", "\u{2043}"],
            [' ', "\u{2022}", "\u{2022}", "\u{2022}", "\u{2022}"],
            $text
        );

        // An unmistakable bullet glyph may be written tight against its text.
        // The characters that double as punctuation need the following space
        // before they can be read as a bullet.
        $bulletPattern = '/^(?:[\x{2022}\x{25AA}\x{2023}\x{25CF}\x{25E6}\x{2043}]\s*|[*\x{00B7}\-\x{2013}\x{2014}]\s+)/u';
        $result = [];
        $headings = [];
        foreach (explode("\n", $text) as $original) {
            $collapsed = trim(preg_replace('/[ \t]+/', ' ', $original) ?? '');

            // The reader marks lines the page set as a section heading. A CV
            // signals its sections with type as well as with wording, and a
            // heading like "What I am good at" can be recognised no other way.
            $isHeading = str_contains($collapsed, PdfTextExtractor::HEADING_MARK);
            $collapsed = trim(str_replace(PdfTextExtractor::HEADING_MARK, '', $collapsed));

            $bulleted = (bool) preg_match($bulletPattern, $collapsed);
            $line = trim(preg_replace($bulletPattern, '', $collapsed) ?? '');
            $line = trim($line, " \t|");

            if ($line === '') {
                if ($result !== [] && end($result) !== '') {
                    $result[] = '';
                    $headings[] = false;
                }
                continue;
            }

            $result[] = ($bulleted ? "\u{2022} " : '') . mb_substr($line, 0, 600);
            $headings[] = $isHeading && !$bulleted;
        }

        // unwrap() and trimBlanks() move lines about, so the marks travel with
        // their text rather than with a line number.
        $tagged = [];
        foreach ($result as $index => $line) {
            $tagged[] = ($headings[$index] ?? false) ? PdfTextExtractor::HEADING_MARK . $line : $line;
        }
        $tagged = $this->trimBlanks($this->unwrap($tagged));

        $lines = [];
        foreach ($tagged as $index => $line) {
            if (str_starts_with($line, PdfTextExtractor::HEADING_MARK)) {
                $marked[] = $index;
                $line = substr($line, strlen(PdfTextExtractor::HEADING_MARK));
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Rejoin a sentence a PDF exporter broke across two lines.
     *
     * Only lines left with an unclosed bracket or a trailing comma are joined.
     * Both are unambiguous wrapping, whereas guessing from capitalisation would
     * merge the separate job title and employer lines that most CVs use.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function unwrap(array $lines): array
    {
        $result = [];
        foreach ($lines as $line) {
            $previous = $result === [] ? '' : $result[count($result) - 1];
            $unclosed = substr_count($previous, '(') > substr_count($previous, ')');
            // A line wrapped mid sentence carries on in lower case, and the
            // half left behind is not an entry of its own: "…and PDF" followed
            // by "generation." is one bullet, not a bullet and a project.
            $continues = $line !== ''
                && preg_match('/^\p{Ll}/u', $line)
                && !$this->isBullet($line)
                && !preg_match('/[.:;]$/u', $previous);

            if (
                $line !== ''
                && $previous !== ''
                && ($unclosed || str_ends_with($previous, ',') || $continues)
                && mb_strlen($previous) + mb_strlen($line) <= 600
            ) {
                $result[count($result) - 1] = $previous . ' ' . $line;
                continue;
            }
            $result[] = $line;
        }
        return $result;
    }

    /**
     * Group lines under the section heading they follow.
     *
     * @param list<string> $lines
     * @return array<string, list<string>>
     */
    private function splitSections(
        array $lines,
        array &$skipped = null,
        array $identityLines = [],
        array $markedHeadings = []
    ): array {
        $skipped = [];

        // Cut the CV into blocks first. A block whose heading names no section
        // this app has is decided afterwards from what it contains, which is the
        // only way to place a heading like "What I am good at".
        $blocks = [];
        $current = ['key' => '_header', 'label' => '', 'body' => []];
        $previousBlank = true;

        foreach ($lines as $index => $line) {
            // The name and job title are already held as personal details, so
            // they must not also land in whichever section surrounds them.
            if (in_array($index, $identityLines, true)) {
                continue;
            }

            if ($line === '') {
                $current['body'][] = $line;
                $previousBlank = true;
                continue;
            }

            $key = $this->headingKey($line);
            $isHeading = $key !== null
                || in_array($index, $markedHeadings, true)
                || ($current['key'] !== '_header' && $this->looksLikeUnknownHeading($line, $previousBlank));

            if ($isHeading) {
                $blocks[] = $current;
                $current = [
                    'key' => $key,
                    'label' => $this->headingLabel($line),
                    'raw' => $line,
                    'body' => [],
                ];
                $previousBlank = false;
                continue;
            }

            $current['body'][] = $line;
            $previousBlank = false;
        }
        $blocks[] = $current;

        // Plenty of CVs set a job title or a qualification in the same type as
        // their section headings, so looking like a heading is not enough to be
        // one. A heading is only a new section when it belongs somewhere other
        // than the section already being read. "BA Communication Studies" under
        // Education, or "Senior Experience Designer" under Experience, is the
        // title of an entry and goes back into the body where it was written.
        $resolved = [];
        foreach ($blocks as $block) {
            $key = $block['key'] ?? $this->inferCategory($this->trimBlanks($block['body']));
            $last = count($resolved) - 1;
            $open = $last >= 0 ? $resolved[$last]['key'] : null;

            // Each skipped section is named in the review, so two of them in a
            // row stay apart even though they share a key.
            $mergeable = $key !== null && $key !== self::SKIPPED && $key !== self::CONTACT;

            if ($mergeable && $key === $open && $open !== '_header') {
                $resolved[$last]['body'][] = $block['raw'] ?? '';
                $resolved[$last]['body'] = array_merge($resolved[$last]['body'], $block['body']);
                continue;
            }

            $block['key'] = $key;
            $resolved[] = $block;
        }

        $sections = ['_header' => []];
        foreach ($resolved as $block) {
            $body = $this->trimBlanks($block['body']);
            $key = $block['key'];

            if ($key === null || $key === self::SKIPPED) {
                if (($block['label'] ?? '') !== '') {
                    $skipped[] = $block['label'];
                }
                continue;
            }

            $sections[$key] = array_merge($sections[$key] ?? [], $body);
        }

        foreach ($sections as $key => $body) {
            $sections[$key] = $this->trimBlanks($body);
        }
        $skipped = array_values(array_unique($skipped));

        return $sections;
    }

    /**
     * Work out which section a block belongs to from what it holds, for a
     * heading whose wording names nothing this app knows.
     *
     * Deliberately cautious: a block that matches nothing is reported as not
     * imported rather than guessed into a section it does not belong in.
     *
     * @param list<string> $body
     */
    private function inferCategory(array $body): ?string
    {
        $lines = array_values(array_filter($body, static fn (string $line): bool => $line !== ''));
        if ($lines === []) {
            return null;
        }

        $text = implode("\n", $lines);
        $hasYear = (bool) preg_match('/\b(?:19|20)\d{2}\b/', $text);
        $bulleted = false;
        foreach ($lines as $line) {
            if ($this->isBullet($line)) {
                $bulleted = true;
                break;
            }
        }
        $dateRanges = 0;
        foreach ($lines as $line) {
            $dates = $this->extractDates($line);
            if ($dates['found'] && $dates['start'] !== '') {
                $dateRanges++;
            }
        }

        // A qualification names itself and always sits beside a year. So does
        // the place it was earned, which is all a degree line leaves for the
        // line beneath it.
        $qualification = '/\b(?:b\.?sc|m\.?sc|b\.?a|m\.?a|mba|ph\.?d|hnd|wassce|ssce|bachelor|master|doctorate|diploma|degree)\b/i';
        $institution = '/\b(?:university|college|polytechnic|institute|academy|school of|training college)\b/i';
        if ($hasYear && (preg_match($qualification, $text) || preg_match($institution, $text))) {
            return 'education';
        }

        if (preg_match_all('/\((?:fluent|native|basic|intermediate|conversational|beginner|advanced|proficient)\)/i', $text) >= 2) {
            return 'languages';
        }

        // A short list of short items, with no dates, is a list of skills.
        if (!$hasYear && count($lines) <= 3 && !$bulleted) {
            $items = $this->splitList($lines, 5);
            if (count($items) >= 3) {
                return 'skills';
            }
        }

        if ($dateRanges >= 1 && ($bulleted || count($lines) >= 3)) {
            return 'experience';
        }

        // A credential carries the year it was issued and who issued it.
        if ($hasYear && (str_contains($text, '|') || preg_match('/\b(?:certificate|certified|licen[cs]e|registered|member|award)\b/i', $text))) {
            return 'certifications';
        }

        // A paragraph of prose with no dates and no list about it is an
        // opening statement. Twelve words is about the shortest a real
        // summary runs to.
        if (!$hasYear && !$bulleted && count($lines) <= 4 && str_word_count($text) >= 12) {
            return 'summary';
        }

        return null;
    }

    /**
     * Words that name a section, checked when the whole heading is not a phrase
     * this parser already knows.
     *
     * Real CVs write "Professional Experience & Achievements" or "Areas of
     * Technical Competence" far more often than the bare noun, so matching on
     * the telling word covers vastly more wording than a list of exact phrases
     * ever could. Order matters: the first key whose word appears wins, so the
     * more specific sections are tested before the general ones.
     *
     * @var array<string, list<string>>
     */
    private const HEADING_WORDS = [
        'languages' => ['language', 'linguistic'],
        'references' => ['referee', 'reference'],
        'interests' => ['interest', 'hobb', 'pastime'],
        'certifications' => [
            'certif', 'licen', 'accredit', 'award', 'honour', 'honor', 'course',
            'training', 'scholarship', 'workshop', 'credential',
        ],
        'projects' => ['project', 'portfolio'],
        'education' => ['educat', 'academic', 'qualification', 'degree', 'school', 'studi', 'univers'],
        'skills' => ['skill', 'competen', 'proficien', 'expertise', 'technolog', 'tool', 'strength'],
        // Summary is tested before experience so that "Career Profile" and
        // "Career Objective" are not caught by the career in them.
        'summary' => ['summar', 'profile', 'objective', 'about', 'overview', 'statement', 'introduction'],
        'experience' => [
            'experience', 'employment', 'work history', 'career history', 'worked',
            // "position" alone is too broad: "Board Positions" and "Position
            // Applied For" are not a work history. The exact phrase
            // "positions held" is still matched above.
        ],
    ];

    private function headingKey(string $line): ?string
    {
        $bare = $this->undecorate($line);
        if ($bare === '' || mb_strlen($bare) > 48 || str_word_count($bare) > 6) {
            return null;
        }

        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $bare) ?? ''));
        // "Skills & Tools" is the same heading as "Skills and Tools".
        $normalized = trim(preg_replace('/\s*&\s*/', ' and ', $normalized) ?? $normalized);
        if ($normalized === '') {
            return null;
        }

        foreach (self::HEADINGS as $key => $phrases) {
            if (in_array($normalized, $phrases, true)) {
                return $key;
            }
        }

        // A telling word is only trusted on a line shaped like a heading.
        // Without that gate "University of Ghana | 2016 - 2018" reads as an
        // education heading and swallows the entry it belongs to.
        if (!$this->looksLikeHeadingShape($normalized)) {
            return null;
        }

        foreach (self::HEADING_WORDS as $key => $words) {
            foreach ($words as $word) {
                if (str_contains($normalized, $word)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Strip the rules, numbering, and punctuation headings are dressed in:
     * "--- 1. PROFILE ---", ">> EMPLOYMENT <<", "3. Academic Background:".
     */
    private function undecorate(string $line): string
    {
        $bare = trim($line, " \t:;.\-–—_=*#~<>«»|/\\[](){}" . "\u{2022}");
        // A leading section number, in digits or roman numerals.
        $bare = preg_replace('/^(?:\d{1,2}|[ivxIVX]{1,4})\s*[.)\-:]\s*/u', '', $bare) ?? $bare;
        return trim($bare, " \t:;.\-–—_=*#~<>«»|");
    }

    /**
     * A heading names a section: a few words, no dates, and none of the
     * separators an entry line uses to hold several fields.
     */
    private function looksLikeHeadingShape(string $normalized): bool
    {
        if ($normalized === '' || mb_strlen($normalized) > 48 || str_word_count($normalized) > 6) {
            return false;
        }
        // A comma means a list or an address, which is an entry line rather
        // than a heading: "Anglican Junior High School, Wamfie, Ghana" is an
        // employer, and the school in it must not make it an education heading.
        return !preg_match('/[\d|·@,]/u', $normalized);
    }

    /**
     * A section heading this parser has no name for.
     *
     * Kept deliberately narrow. Shouting is the signal — a heading is written in
     * capitals and stands on its own after a blank line, while the employer and
     * job title lines it could be confused with carry dates, commas, or pipes.
     */
    private function looksLikeUnknownHeading(string $line, bool $previousBlank): bool
    {
        if (!$previousBlank || $this->isBullet($line)) {
            return false;
        }
        if (mb_strlen($line) > 48 || str_word_count($line) > 5) {
            return false;
        }
        // Dates, separators, and contact punctuation all belong to entry lines.
        if (preg_match('/[\d,|@·\/]|\p{Pd}\s|\s\p{Pd}/u', $line)) {
            return false;
        }

        $letters = preg_replace('/[^\p{L}]/u', '', $line) ?? '';
        return mb_strlen($letters) >= 4 && $letters === mb_strtoupper($letters);
    }

    private function headingLabel(string $line): string
    {
        $clean = trim(preg_replace('/^[^\p{L}]+|[^\p{L}:]+$/u', '', $line) ?? $line);
        $clean = rtrim($clean, ':');
        return mb_substr(mb_convert_case(mb_strtolower($clean), MB_CASE_TITLE, 'UTF-8'), 0, 60);
    }

    /**
     * @param list<string> $body
     * @return list<string>
     */
    private function trimBlanks(array $body): array
    {
        while ($body !== [] && $body[0] === '') {
            array_shift($body);
        }
        while ($body !== [] && end($body) === '') {
            array_pop($body);
        }
        return array_values($body);
    }

    // ------------------------------------------------------------------
    // Personal details
    // ------------------------------------------------------------------

    /**
     * @param list<string> $header
     * @param list<string> $lines
     */
    /**
     * The writer's name and job title, wherever they sit on the page.
     *
     * Returned with the line numbers they came from so the section splitter can
     * leave them out of the body: they are personal details, not content for
     * whichever section happens to surround them.
     *
     * @param list<string> $lines
     * @return array{name: string, headline: string, lines: list<int>}
     */
    private function findIdentity(array $lines): array
    {
        $found = ['name' => '', 'headline' => '', 'lines' => []];

        foreach ($lines as $index => $line) {
            if ($line === '' || $this->isBullet($line)) {
                continue;
            }
            if ($this->headingKey($line) !== null || !$this->looksLikeName($line)) {
                continue;
            }

            $found['name'] = $this->titleCaseIfShouting($line);
            $found['lines'][] = $index;

            // The job title normally sits on the next written line.
            for ($next = $index + 1, $total = count($lines); $next < $total; $next++) {
                if ($lines[$next] === '') {
                    continue;
                }
                if ($this->headingKey($lines[$next]) === null && $this->looksLikeHeadline($lines[$next])) {
                    $found['headline'] = $lines[$next];
                    $found['lines'][] = $next;
                }
                break;
            }
            break;
        }

        return $found;
    }

    /**
     * @param array{name: string, headline: string, lines: list<int>} $identity
     */
    private function parsePersonal(array $header, array $lines, array $identity = ['name' => '', 'headline' => '', 'lines' => []]): array
    {
        $everything = implode("\n", $lines);
        // Contact details belong to the block at the top of a CV. A URL further
        // down is far more likely to be a project or an employer's site, so the
        // personal links are read from the header only.
        $top = implode("\n", $header);
        $personal = [
            'full_name' => '', 'headline' => '', 'email' => '', 'phone' => '',
            'location' => '', 'website' => '', 'linkedin' => '',
        ];

        // An address and a phone number are unambiguous wherever they appear, so
        // those two still fall back to the whole document.
        foreach ([$top, $everything] as $scope) {
            if ($personal['email'] === '' && preg_match('/[\w.+-]+@[\w-]+\.[\w.-]{2,}/u', $scope, $match)) {
                $personal['email'] = rtrim($match[0], '.');
            }
            if ($personal['phone'] === '') {
                $personal['phone'] = $this->findPhone($scope);
            }
        }

        if (preg_match('#(?:https?://)?(?:www\.)?linkedin\.com/[\w/%.-]+#i', $top, $match)) {
            $personal['linkedin'] = $match[0];
        }
        if (preg_match('#(?:https?://)?(?:www\.)?(?:github\.com|gitlab\.com)/[\w/%.-]+#i', $top, $match)) {
            $personal['website'] = $match[0];
        } elseif (preg_match('#(?:https?://)[\w.-]+\.[a-z]{2,}[\w/%.\#?=-]*#i', $top, $match)) {
            $personal['website'] = $match[0];
        }

        $personal['full_name'] = $identity['name'];
        $personal['headline'] = $identity['headline'];

        $candidates = array_slice(array_values(array_filter($header, static fn (string $line): bool => $line !== '')), 0, 10);

        foreach ($candidates as $line) {
            foreach (preg_split('/\s*[|·•]\s*/u', $line) ?: [] as $fragment) {
                $fragment = trim($fragment);
                if ($personal['location'] === '' && $this->looksLikeLocation($fragment)) {
                    $personal['location'] = $fragment;
                }
            }
        }

        foreach ($personal as $field => $value) {
            $personal[$field] = mb_substr(trim(strip_tags((string) $value)), 0, 255);
        }
        return $personal;
    }

    /**
     * Links in the contact block that no personal field could hold.
     *
     * A CV often lists a portfolio, a GitHub, and a LinkedIn, while BrightCV
     * keeps one website and one LinkedIn. Naming the remainder means the writer
     * can see what did not fit instead of noticing its absence later.
     *
     * @param list<string> $header
     * @param array<string, string> $personal
     * @return list<string>
     */
    private function unusedLinks(array $header, array $personal): array
    {
        $taken = array_filter([$personal['website'] ?? '', $personal['linkedin'] ?? '']);
        if (!preg_match_all('#https?://[\w.-]+\.[a-z]{2,}[\w/%.\#?=&+-]*#i', implode("\n", $header), $found)) {
            return [];
        }

        $extra = [];
        foreach ($found[0] as $link) {
            $link = rtrim($link, '.,);');
            foreach ($taken as $used) {
                if ($used !== '' && (str_contains($used, $link) || str_contains($link, $used))) {
                    continue 2;
                }
            }
            $extra[] = mb_substr($link, 0, 120);
        }

        return array_values(array_unique($extra));
    }

    private function findPhone(string $text): string
    {
        if (!preg_match_all('/\+?\(?\+?\d[\d()\s.-]{7,20}\d/', $text, $matches)) {
            return '';
        }
        foreach ($matches[0] as $candidate) {
            $digits = preg_replace('/\D/', '', $candidate) ?? '';
            // Long enough to be a phone number, short enough not to be a year
            // range, an ID, or a run of numbers from an achievement bullet.
            if (strlen($digits) >= 9 && strlen($digits) <= 15) {
                return trim($candidate);
            }
        }
        return '';
    }

    private function looksLikeName(string $line): bool
    {
        if (mb_strlen($line) > 60 || str_contains($line, '@') || preg_match('/\d/', $line)) {
            return false;
        }
        if (preg_match('#https?://|www\.|\.com#i', $line)) {
            return false;
        }
        $words = preg_split('/\s+/', trim($line)) ?: [];
        if (count($words) < 2 || count($words) > 5) {
            return false;
        }
        return (bool) preg_match('/^[\p{L}\p{M}.\x27-]+(?:\s+[\p{L}\p{M}.\x27-]+)+$/u', trim($line));
    }

    private function looksLikeHeadline(string $line): bool
    {
        if (mb_strlen($line) > 80 || str_contains($line, '@')) {
            return false;
        }
        if ($this->looksLikeLocation($line) || $this->findPhone($line) !== '') {
            return false;
        }
        return preg_match('/\p{L}/u', $line) === 1 && str_word_count($line) <= 9;
    }

    private function looksLikeLocation(string $fragment): bool
    {
        if ($fragment === '' || mb_strlen($fragment) > 60 || str_contains($fragment, '@')) {
            return false;
        }
        return (bool) preg_match('/^\p{Lu}[\p{L}. -]+,\s*\p{Lu}[\p{L}. -]+$/u', trim($fragment));
    }

    private function titleCaseIfShouting(string $line): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $line) ?? '';
        if ($letters !== '' && $letters === mb_strtoupper($letters)) {
            return mb_convert_case(mb_strtolower($line), MB_CASE_TITLE, 'UTF-8');
        }
        return $line;
    }

    // ------------------------------------------------------------------
    // Section bodies
    // ------------------------------------------------------------------

    /**
     * Join a section body into one paragraph. Everything under an explicit
     * heading is kept, including the short closing line a wrapped summary ends
     * on, which the stricter `prose()` filter would drop.
     *
     * @param list<string> $body
     */
    private function joinParagraph(array $body): string
    {
        $kept = [];
        foreach ($body as $line) {
            if ($line !== '') {
                $kept[] = $this->stripBullet($line);
            }
        }
        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $kept)) ?? '');
        return mb_substr($text, 0, 3000);
    }

    /**
     * Header lines that are prose rather than contact details.
     *
     * @param list<string> $body
     * @return list<string>
     */
    private function prose(array $body): array
    {
        $kept = [];
        foreach ($body as $line) {
            if ($line === '' || str_contains($line, '@') || $this->looksLikeName($line)) {
                continue;
            }
            if ($this->looksLikeLocation($line) || $this->findPhone($line) !== '') {
                continue;
            }
            if (preg_match('#https?://|www\.|linkedin\.com|github\.com#i', $line)) {
                continue;
            }
            if (str_word_count($line) < 6) {
                continue;
            }
            $kept[] = $this->stripBullet($line);
        }
        return $kept;
    }

    private function stripBullet(string $line): string
    {
        return trim(preg_replace('/^\x{2022}\s*/u', '', $line) ?? $line);
    }

    private function isBullet(string $line): bool
    {
        return str_starts_with($line, "\u{2022}");
    }

    /**
     * Split a section that arrived as one long sentence, which is how condensed
     * CVs and some PDF exports present experience and education.
     *
     * @param list<string> $body
     * @return list<string>
     */
    private function explodeRuns(array $body): array
    {
        $lines = array_values(array_filter($body, static fn (string $line): bool => $line !== ''));
        if (count($lines) > 2) {
            return $lines;
        }

        $result = [];
        foreach ($lines as $line) {
            if (mb_strlen($line) > 70 && substr_count($line, ';') >= 1) {
                foreach (explode(';', $line) as $part) {
                    $part = trim($part, " .;");
                    if ($part !== '') {
                        $result[] = $part;
                    }
                }
                continue;
            }
            $result[] = $line;
        }
        return $result;
    }

    /**
     * @param list<string> $body
     * @return list<array<string, mixed>>
     */
    private function parseHistory(array $body, string $kind): array
    {
        $entries = [];
        $current = null;
        $isExperience = $kind === 'experience';

        foreach ($this->explodeRuns($body) as $line) {
            if ($line === '') {
                continue;
            }

            // Education is commonly written as a bulleted list of
            // qualifications rather than as blocks, and each of those bullets
            // is an entry rather than a note about the entry above it.
            if (!$isExperience && $this->isBullet($line) && $this->namesAQualification($this->stripBullet($line))) {
                $line = $this->stripBullet($line);
            }

            if ($this->isBullet($line)) {
                if ($current === null) {
                    $current = $this->blankHistoryEntry($isExperience);
                }
                if ($isExperience && count($current['bullets']) < 12) {
                    $current['bullets'][] = mb_substr($this->stripBullet($line), 0, 600);
                } elseif (!$isExperience) {
                    $current['details'] = trim($current['details'] . ' ' . $this->stripBullet($line));
                }
                continue;
            }

            $dates = $this->extractDates($line);
            $rest = $dates['rest'];

            $startsNew = $current === null
                || ($isExperience && $current['bullets'] !== [])
                || (!$isExperience && $current['details'] !== '')
                || ($dates['found'] && $current['start_date'] !== '')
                || ($isExperience && $current['role'] !== '' && $current['company'] !== '')
                || (!$isExperience && $current['degree'] !== '' && $current['school'] !== '');

            if ($startsNew) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = $this->blankHistoryEntry($isExperience);
            }

            if ($dates['found'] && $current['start_date'] === '') {
                $current['start_date'] = $dates['start'];
                $current['end_date'] = $dates['end'];
                if ($isExperience) {
                    $current['current'] = $dates['current'];
                }
            }

            $this->assignHistoryText($current, $rest, $isExperience);

            if (count($entries) >= 20) {
                break;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        $entries = array_values(array_filter($entries, static function (array $entry) use ($isExperience): bool {
            return $isExperience
                ? ($entry['role'] !== '' || $entry['company'] !== '' || $entry['bullets'] !== [])
                : ($entry['degree'] !== '' || $entry['school'] !== '');
        }));

        if ($isExperience) {
            foreach ($entries as $index => $entry) {
                $entries[$index] = $this->uninvert($entry);
            }
        }

        return array_slice($entries, 0, 20);
    }

    /**
     * Put a role and its employer the right way round.
     *
     * Some CVs write the job title first and the employer under it, others the
     * employer first. Reading order alone cannot tell them apart, but the words
     * can: an employer is an Authority or a School, a role is an Engineer or an
     * Intern. Only a pair that is clearly the wrong way round is swapped.
     *
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function uninvert(array $entry): array
    {
        $role = (string) $entry['role'];
        $company = (string) $entry['company'];
        if ($role === '' || $company === '') {
            return $entry;
        }

        if ($this->namesAnEmployer($role) && $this->namesARole($company) && !$this->namesAnEmployer($company)) {
            $entry['role'] = $company;
            $entry['company'] = $role;

            // The employer line often ends with where it is: "…Authority
            // (NHIA), Accra, Ghana".
            if (preg_match('/^(.*?),\s*([^,]+,\s*[^,]+)$/u', $entry['company'], $match)
                && $this->looksLikeLocation(trim($match[2]))) {
                $entry['company'] = trim($match[1]);
                if ($entry['location'] === '') {
                    $entry['location'] = trim($match[2]);
                }
            }
        }

        return $entry;
    }

    /**
     * A line that names a qualification and where it was earned, rather than a
     * remark about one: "Diploma in Software Engineering – IPMC University".
     */
    private function namesAQualification(string $text): bool
    {
        $named = preg_match(
            '/\b(?:b\.?sc|m\.?sc|b\.?a|m\.?a|mba|ph\.?d|hnd|wassce|ssce|bachelor|master|'
            . 'doctorate|diploma|certificate|degree|nvq|hnc|higher national)\b/iu',
            $text
        );
        return $named === 1 && (bool) preg_match('/[–—|,]|\s-\s|\bin\b|\bat\b|\bfrom\b/iu', $text);
    }

    private function namesAnEmployer(string $text): bool
    {
        return (bool) preg_match(
            '/\b(?:ltd|limited|plc|inc|llc|gmbh|company|co|corp|group|holdings|authority|agency|'
            . 'ministry|commission|school|college|university|academy|institute|hospital|clinic|'
            . 'bank|insurance|foundation|trust|services|solutions|systems|technologies|labs|'
            . 'consult\w*|enterprise\w*|industries|media|studios?)\b/iu',
            $text
        );
    }

    private function namesARole(string $text): bool
    {
        return (bool) preg_match(
            '/\b(?:engineer|developer|programmer|analyst|manager|officer|director|supervisor|'
            . 'administrator|coordinator|consultant|specialist|assistant|associate|intern|'
            . 'internship|trainee|personnel|technician|designer|architect|scientist|researcher|'
            . 'teacher|tutor|lecturer|nurse|accountant|auditor|clerk|secretary|attendant|'
            . 'representative|executive|lead|head|chief|president|partner|volunteer)\b/iu',
            $text
        );
    }

    private function blankHistoryEntry(bool $isExperience): array
    {
        $base = [
            'id' => bin2hex(random_bytes(8)),
            'location' => '',
            'start_date' => '',
            'end_date' => '',
        ];
        return $isExperience
            ? $base + ['role' => '', 'company' => '', 'current' => false, 'bullets' => []]
            : $base + ['degree' => '', 'school' => '', 'details' => ''];
    }

    /**
     * Fill the title and organisation fields from one header line.
     */
    private function assignHistoryText(array &$entry, string $rest, bool $isExperience): void
    {
        $rest = trim($rest, " ,-–—|·.");
        if ($rest === '') {
            return;
        }

        $titleField = $isExperience ? 'role' : 'degree';
        $organisationField = $isExperience ? 'company' : 'school';

        // "Diploma in Software Engineering (IPMC, In Progress)" — the bracket
        // holds the organisation in condensed CVs.
        // While the title is still empty this line is the title, and a comma in
        // it is part of the title rather than a separator: "MBA, Marketing" is
        // one qualification, not a degree at a company called Marketing. Once
        // the title is known, a comma can separate the organisation from its
        // location.
        $allowComma = $entry[$titleField] !== '';

        // A separator the writer put there outranks a trailing bracket. In
        // "Diploma in Software Engineering – IPMC University College (In
        // Progress)" the dash divides the qualification from the institution,
        // and the bracket qualifies the institution rather than replacing it.
        $hasSeparator = (bool) preg_match('/\s*(?:\||·|—|–)\s*|\s+-\s+|\s+at\s+|\s+@\s+/u', $rest);

        if (!$hasSeparator && preg_match('/^(.{2,}?)\s*\((.+)\)\s*$/u', $rest, $match)) {
            $inner = trim($match[2], " ,");
            // Brackets hold a genuine list, so their contents always split.
            $parts = $inner !== '' && mb_strlen($inner) <= 160
                ? array_merge([trim($match[1])], $this->splitFields($inner))
                : [$rest];
        } else {
            $parts = $this->splitFields($rest, $allowComma);
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part, " ,-–—|·"),
            $parts
        ), static fn (string $part): bool => $part !== ''));

        // Title first, then organisation, then location. Checking location
        // first misread "(IPMC, In Progress)" as a city and country pair.
        foreach ($parts as $part) {
            if ($entry[$titleField] === '') {
                $entry[$titleField] = mb_substr($part, 0, 180);
                continue;
            }
            if ($entry[$organisationField] === '') {
                $entry[$organisationField] = mb_substr($part, 0, 180);
                continue;
            }
            if ($entry['location'] === '' && $this->looksLikeLocation($part)) {
                $entry['location'] = mb_substr($part, 0, 160);
                continue;
            }
            // Anything left is still the writer's own words, so education keeps
            // it as detail rather than dropping it.
            if (!$isExperience) {
                $entry['details'] = mb_substr(trim($entry['details'] . ' ' . $part, ' '), 0, 1000);
            } elseif ($entry['location'] === '' && mb_strlen($part) <= 60) {
                $entry['location'] = mb_substr($part, 0, 160);
            }
        }
    }

    /**
     * Split one line into its fields.
     *
     * Pipes and dashes are deliberate field separators, so they win. Commas are
     * only a fallback: splitting on them first would tear "Accra, Ghana" apart.
     *
     * @return list<string>
     */
    private function splitFields(string $line, bool $allowComma = true): array
    {
        $strong = '/\s*(?:\||·|—|–)\s*|\s+-\s+|\s+at\s+|\s+@\s+/u';
        if (preg_match($strong, $line)) {
            return preg_split($strong, $line) ?: [$line];
        }
        if (!$allowComma) {
            return [$line];
        }

        return preg_split('/\s*,\s*/u', $line) ?: [$line];
    }

    /**
     * @return array{found: bool, start: string, end: string, current: bool, rest: string}
     */
    private function extractDates(string $line): array
    {
        $month = self::MONTHS;
        $token = '(?:(?:' . $month . ')[a-z]*\.?\s*)?(?:\d{1,2}[\/.])?(?:19|20)\d{2}';
        $open = 'present|current|now|to date|ongoing|date';

        $range = '/\b(' . $token . ')\s*(?:-|–|—|until|through|\bto\b)\s*(' . $token . '|' . $open . ')\b/iu';
        if (preg_match($range, $line, $match, PREG_OFFSET_CAPTURE)) {
            $end = trim($match[2][0]);
            return [
                'found' => true,
                'start' => $this->tidyDate($match[1][0]),
                'current' => (bool) preg_match('/^(?:' . $open . ')$/i', $end),
                'end' => preg_match('/^(?:' . $open . ')$/i', $end) ? '' : $this->tidyDate($end),
                'rest' => $this->withoutDates($line, $match[0][0]),
            ];
        }

        // "Sep – Dec 2022": the start month carries no year of its own, so it
        // borrows the year written on the end of the range.
        $bareMonth = '(?:' . $month . ')[a-z]*\.?';
        $shared = '/\b(' . $bareMonth . ')\s*(?:-|–|—|\bto\b)\s*(' . $bareMonth . ')\s+((?:19|20)\d{2})\b/iu';
        if (preg_match($shared, $line, $match)) {
            return [
                'found' => true,
                'start' => $this->tidyDate($match[1] . ' ' . $match[3]),
                'end' => $this->tidyDate($match[2] . ' ' . $match[3]),
                'current' => false,
                'rest' => $this->withoutDates($line, $match[0]),
            ];
        }

        if (preg_match('/\b(' . $token . ')\b/iu', $line, $match)) {
            return [
                'found' => true,
                'start' => '',
                'end' => $this->tidyDate($match[1]),
                'current' => false,
                'rest' => $this->withoutDates($line, $match[0]),
            ];
        }

        return ['found' => false, 'start' => '', 'end' => '', 'current' => false, 'rest' => $line];
    }

    /**
     * Tidy a line the date range was cut out of, so an entry title does not
     * keep the empty bracket the dates were sitting in.
     */
    private function withoutDates(string $line, string $matched): string
    {
        $rest = str_replace($matched, ' ', $line);
        $rest = preg_replace('/\(\s*[-–—,]?\s*\)/u', ' ', $rest) ?? $rest;
        return trim(preg_replace('/\s+/u', ' ', $rest) ?? $rest);
    }

    private function tidyDate(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        return mb_substr(ucwords($value), 0, 30);
    }

    /**
     * @param list<string> $body
     * @return list<array<string, string>>
     */
    private function parseSkills(array $body): array
    {
        $skills = [];
        foreach ($this->splitList($body, 5) as $name) {
            $level = '';
            if (preg_match('/^(.+?)\s*[(\[]\s*([^)\]]{2,20})\s*[)\]]$/u', $name, $match)) {
                $name = trim($match[1]);
                $level = trim($match[2]);
            } elseif (preg_match('/^(.+?)\s*[-–:]\s*(beginner|basic|intermediate|advanced|expert|proficient|fluent|native)$/iu', $name, $match)) {
                $name = trim($match[1]);
                $level = ucfirst(mb_strtolower(trim($match[2])));
            }
            if ($name === '' || mb_strlen($name) > 100) {
                continue;
            }
            $skills[] = ['id' => bin2hex(random_bytes(8)), 'name' => $name, 'level' => mb_substr($level, 0, 30)];
            if (count($skills) >= 25) {
                break;
            }
        }
        return $skills;
    }

    /**
     * @param list<string> $body
     * @return list<array<string, string>>
     */
    private function parseLanguages(array $body): array
    {
        $languages = [];
        foreach ($this->splitList($body, 4) as $entry) {
            $level = '';
            if (preg_match('/^(.+?)\s*[(\[\-–:]\s*([^)\]]{2,25})[)\]]?$/u', $entry, $match)) {
                $entry = trim($match[1]);
                $level = trim($match[2], " )]");
            }
            if ($entry === '') {
                continue;
            }
            $languages[] = [
                'id' => bin2hex(random_bytes(8)),
                'name' => mb_substr($entry, 0, 100),
                'level' => mb_substr($level, 0, 60),
            ];
            if (count($languages) >= 12) {
                break;
            }
        }
        return $languages;
    }

    /**
     * @param list<string> $body
     * @return list<string>
     */
    private function parseInterests(array $body): array
    {
        $interests = [];
        foreach ($this->splitList($body, 6) as $interest) {
            if ($interest !== '' && mb_strlen($interest) <= 80) {
                $interests[] = $interest;
            }
            if (count($interests) >= 20) {
                break;
            }
        }
        return $interests;
    }

    /**
     * Comma, pipe, and bullet separated values, which is how nearly every CV
     * writes short lists.
     *
     * @param list<string> $body
     * @return list<string>
     */
    private function splitList(array $body, int $maxWords = 0): array
    {
        $values = [];
        foreach ($body as $line) {
            if ($line === '') {
                continue;
            }
            $line = $this->stripBullet($line);
            // A skills section is often grouped under labels: "Languages:
            // Python, PHP". The label names the group, not a skill, so only
            // what follows it is kept.
            $line = preg_replace('/^[\p{L} \/&+.-]{2,24}:\s*/u', '', $line) ?? $line;

            foreach (preg_split('/\s*[,;|·]\s*|\s{3,}/u', $line) ?: [] as $part) {
                $part = trim($part, " .\t");
                if ($part === '' || mb_strlen($part) > 120) {
                    continue;
                }
                // A skill or an interest is a short label. Anything longer is a
                // sentence that happened to sit under the heading, and turning
                // it into an entry would put words in a place the writer did
                // not write them.
                if ($maxWords > 0 && str_word_count($part) > $maxWords) {
                    continue;
                }
                $values[] = $part;
            }
        }
        return array_values(array_unique($values));
    }

    /**
     * @param list<string> $body
     * @return list<array<string, string>>
     */
    private function parseProjects(array $body): array
    {
        $lines = $this->explodeRuns($body);

        // A single comma separated sentence is a list of project names, not one
        // project with a very long title.
        if (count($lines) === 1 && substr_count($lines[0], ',') >= 2) {
            $projects = [];
            foreach ($this->splitList($lines) as $name) {
                $projects[] = [
                    'id' => bin2hex(random_bytes(8)),
                    'name' => mb_substr($name, 0, 180),
                    'role' => '', 'url' => '', 'start_date' => '', 'end_date' => '', 'description' => '',
                ];
                if (count($projects) >= 20) {
                    break;
                }
            }
            return $projects;
        }

        $projects = [];
        $current = null;
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            // A labelled line describes the project above it rather than
            // naming a new one: "Technologies: PHP, MySQL" belongs to BrightCV.
            $labelled = (bool) preg_match(
                '/^(?:technolog\w*|tech|stack|tools|built with|role|status)\s*:/iu',
                $line
            );

            if ($this->isBullet($line) || $labelled || ($current !== null && str_word_count($line) > 9)) {
                if ($current === null) {
                    continue;
                }
                $current['description'] = mb_substr(trim($current['description'] . ' ' . $this->stripBullet($line)), 0, 1600);
                continue;
            }

            if ($current !== null) {
                $projects[] = $current;
            }
            $dates = $this->extractDates($line);
            $name = trim($dates['rest'], " ,-–—|·");
            $url = '';
            if (preg_match('#(?:https?://)?(?:www\.)?[\w.-]+\.[a-z]{2,}[\w/%.\#?=-]*#i', $name, $match)) {
                $url = $match[0];
                $name = trim(str_replace($url, '', $name), " ,-–—|·");
            }
            $current = [
                'id' => bin2hex(random_bytes(8)),
                'name' => mb_substr($name, 0, 180),
                'role' => '',
                'url' => mb_substr($url, 0, 255),
                'start_date' => $dates['start'],
                'end_date' => $dates['end'],
                'description' => '',
            ];
            if (count($projects) >= 20) {
                break;
            }
        }
        if ($current !== null) {
            $projects[] = $current;
        }

        return array_values(array_filter($projects, static fn (array $project): bool => $project['name'] !== ''));
    }

    /**
     * @param list<string> $body
     * @return list<array<string, string>>
     */
    private function parseCertifications(array $body): array
    {
        $certifications = [];
        foreach ($this->explodeRuns($body) as $line) {
            if ($line === '') {
                continue;
            }
            $dates = $this->extractDates($this->stripBullet($line));
            $rest = trim($dates['rest'], " ,-–—|·");
            if ($rest === '') {
                continue;
            }

            $issuer = '';
            if (preg_match('/^(.+?)\s*(?:\||·|—|–|\s+by\s+|,)\s*(.+)$/u', $rest, $match)) {
                $rest = trim($match[1]);
                $issuer = trim($match[2]);
            }

            $certifications[] = [
                'id' => bin2hex(random_bytes(8)),
                'name' => mb_substr($rest, 0, 180),
                'issuer' => mb_substr($issuer, 0, 180),
                'date' => $dates['end'] !== '' ? $dates['end'] : $dates['start'],
                'url' => '',
            ];
            if (count($certifications) >= 20) {
                break;
            }
        }
        return $certifications;
    }

    /**
     * @param list<string> $body
     * @return list<array<string, string>>
     */
    private function parseReferences(array $body): array
    {
        $references = [];
        $current = null;

        foreach ($body as $line) {
            if ($line === '') {
                if ($current !== null) {
                    $references[] = $current;
                    $current = null;
                }
                continue;
            }

            $line = $this->stripBullet($line);
            if (stripos($line, 'available upon request') !== false || stripos($line, 'on request') !== false) {
                continue;
            }

            $email = '';
            if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]{2,}/u', $line, $match)) {
                $email = rtrim($match[0], '.');
            }
            $phone = $this->findPhone($line);

            if ($current === null || ($email === '' && $phone === '' && $this->looksLikeName($line))) {
                if ($current !== null) {
                    $references[] = $current;
                }
                $current = [
                    'id' => bin2hex(random_bytes(8)),
                    'name' => '', 'position' => '', 'company' => '', 'email' => '', 'phone' => '',
                ];
            }

            if ($email !== '' && $current['email'] === '') {
                $current['email'] = mb_substr($email, 0, 190);
            }
            if ($phone !== '' && $current['phone'] === '') {
                $current['phone'] = mb_substr($phone, 0, 80);
            }

            $rest = trim(str_replace([$email, $phone], ' ', $line), " ,-–—|·");
            if ($rest === '') {
                continue;
            }
            foreach (preg_split('/\s*(?:\||·|—|–|,)\s*/u', $rest) ?: [] as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                if ($current['name'] === '') {
                    $current['name'] = mb_substr($part, 0, 160);
                } elseif ($current['position'] === '') {
                    $current['position'] = mb_substr($part, 0, 160);
                } elseif ($current['company'] === '') {
                    $current['company'] = mb_substr($part, 0, 160);
                }
            }

            if (count($references) >= 10) {
                break;
            }
        }

        if ($current !== null) {
            $references[] = $current;
        }

        return array_values(array_filter($references, static fn (array $item): bool => $item['name'] !== ''));
    }

    /**
     * A short report the builder shows before the writer confirms the import.
     */
    private function summarize(array $content): array
    {
        $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];
        $count = static fn (string $key): int => is_array($content[$key] ?? null) ? count($content[$key]) : 0;

        return [
            'full_name' => (string) ($personal['full_name'] ?? ''),
            'headline' => (string) ($personal['headline'] ?? ''),
            'email' => (string) ($personal['email'] ?? ''),
            'phone' => (string) ($personal['phone'] ?? ''),
            'location' => (string) ($personal['location'] ?? ''),
            'summary_words' => str_word_count((string) ($content['summary'] ?? '')),
            'experience' => $count('experience'),
            'education' => $count('education'),
            'skills' => $count('skills'),
            'projects' => $count('projects'),
            'certifications' => $count('certifications'),
            'languages' => $count('languages'),
            'references' => $count('references'),
            'interests' => $count('interests'),
        ];
    }
}
