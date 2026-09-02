<?php

declare(strict_types=1);

/**
 * Minimal PDF text extraction.
 *
 * BrightCV only needs the words out of a CV someone exported from Word, Google
 * Docs, Canva, or another builder, so this walks the content streams and reads
 * the text-showing operators rather than pulling in a full PDF library. It
 * deliberately does not try to handle scanned pages or fonts with custom
 * encodings: `extract()` returns whatever it could read and the caller decides
 * whether that is enough to work with.
 */
final class PdfTextExtractor
{
    /** A kerning gap wider than this (in thousandths of an em) reads as a space. */
    private const SPACE_GAP = 120.0;

    /** Text within this many units of the same baseline belongs to one line. */
    private const LINE_TOLERANCE = 3.0;

    /**
     * Marks a line the page laid out as a section heading, for CvImportService
     * to read. A CV signals its sections with type, not only with wording, and
     * headings such as "What I am good at" can be recognised no other way.
     */
    public const HEADING_MARK = "";

    /** Type this much larger than the body text is a heading. */
    private const HEADING_SCALE = 1.15;

    public static function extract(string $raw): string
    {
        $pages = [];
        foreach (PdfDocument::open($raw)->pages() as $page) {
            $text = self::layoutPage(self::readStream($page['content'], $page['fonts']));
            if (trim($text) !== '') {
                $pages[] = rtrim($text);
            }
        }

        // A blank line between pages keeps a section that ends a page from
        // being glued to whatever starts the next one.
        return self::toUtf8(implode("\n\n", $pages));
    }

    /**
     * Decoded streams that look like page content (they contain text operators).
     *
     * @return list<string>
     */
    private static function contentStreams(string $raw): array
    {
        // The dictionary before the stream names the filter chain, which real
        // exporters vary: Word writes plain FlateDecode, ReportLab writes
        // [/ASCII85Decode /FlateDecode].
        if (!preg_match_all('/<<(.*?)>>\s*stream\r?\n?(.*?)endstream/s', $raw, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $streams = [];
        foreach ($matches as $match) {
            $decoded = self::decodeStream($match[1], $match[2]);
            if ($decoded !== null && (str_contains($decoded, 'Tj') || str_contains($decoded, 'TJ'))) {
                $streams[] = $decoded;
            }
        }
        return $streams;
    }

    private static function decodeStream(string $dictionary, string $data): ?string
    {
        $filters = [];
        if (preg_match('#/Filter\s*(\[[^\]]*\]|/\w+)#', $dictionary, $match)) {
            preg_match_all('#/(\w+)#', $match[1], $names);
            $filters = $names[1];
        }

        foreach ($filters as $filter) {
            $data = match ($filter) {
                'ASCII85Decode', 'A85' => self::ascii85Decode($data),
                'ASCIIHexDecode', 'AHx' => self::asciiHexDecode($data),
                'FlateDecode', 'Fl' => self::inflate($data),
                // Anything else (LZW, DCT, image filters) is not page text.
                default => null,
            };
            if ($data === null) {
                return null;
            }
        }

        if ($filters === []) {
            // Some writers omit the filter entry for uncompressed content, and
            // a few compress anyway, so try both.
            $inflated = self::inflate($data);
            return $inflated ?? $data;
        }

        return $data;
    }

    private static function inflate(string $data): ?string
    {
        foreach (['gzuncompress', 'gzinflate'] as $function) {
            $result = @$function($data);
            if (is_string($result) && $result !== '') {
                return $result;
            }
        }
        $result = @gzinflate(ltrim($data, "\r\n"));
        return is_string($result) && $result !== '' ? $result : null;
    }

    private static function asciiHexDecode(string $data): string
    {
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', explode('>', $data, 2)[0]) ?? '';
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }
        return (string) hex2bin($hex);
    }

    private static function ascii85Decode(string $data): string
    {
        $data = preg_replace('/\s+/', '', $data) ?? '';
        if (str_starts_with($data, '<~')) {
            $data = substr($data, 2);
        }
        $end = strpos($data, '~>');
        if ($end !== false) {
            $data = substr($data, 0, $end);
        }

        $out = '';
        $group = [];
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $character = $data[$i];
            if ($character === 'z' && $group === []) {
                $out .= "\0\0\0\0";
                continue;
            }
            $value = ord($character) - 33;
            if ($value < 0 || $value > 84) {
                continue;
            }
            $group[] = $value;
            if (count($group) === 5) {
                $out .= self::ascii85Group($group, 4);
                $group = [];
            }
        }

        if ($group !== []) {
            $kept = count($group) - 1;
            while (count($group) < 5) {
                $group[] = 84;
            }
            $out .= self::ascii85Group($group, $kept);
        }

        return $out;
    }

    /** @param list<int> $group */
    private static function ascii85Group(array $group, int $keep): string
    {
        $total = 0;
        foreach ($group as $value) {
            $total = $total * 85 + $value;
        }
        return substr(pack('N', $total), 0, max(0, $keep));
    }

    /**
     * Which font resource names are bold, keyed by the name used in the page
     * content ("F2" and so on).
     *
     * Boldness is a heading signal on a CV that sets every section in the same
     * size, so it is worth resolving the resource name through to the font's
     * base name.
     *
     * @return array<string, bool>
     */
    private static function fontWeights(string $raw): array
    {
        // object number => is the base font a bold face
        $byObject = [];
        if (preg_match_all('/(\d+)\s+0\s+obj(.{0,400}?)endobj/s', $raw, $objects, PREG_SET_ORDER)) {
            foreach ($objects as $object) {
                if (preg_match('#/BaseFont\s*/([^\s/>\]]+)#', $object[2], $base)) {
                    $byObject[(int) $object[1]] = (bool) preg_match('/bold|black|heavy|semib/i', $base[1]);
                }
            }
        }

        $weights = [];
        // /Font << /F1 5 0 R /F2 6 0 R >>
        if (preg_match_all('#/Font\s*<<(.*?)>>#s', $raw, $dictionaries)) {
            foreach ($dictionaries[1] as $dictionary) {
                if (!preg_match_all('#/([^\s/]+)\s+(\d+)\s+0\s+R#', $dictionary, $entries, PREG_SET_ORDER)) {
                    continue;
                }
                foreach ($entries as $entry) {
                    $weights[$entry[1]] = $byObject[(int) $entry[2]] ?? false;
                }
            }
        }

        return $weights;
    }

    /**
     * Walk one content stream, recording where on the page each piece of text
     * was drawn.
     *
     * Position matters because a PDF stores drawing instructions, not reading
     * order. A sidebar CV is commonly written row by row across both columns,
     * so following the stream blindly interleaves the sidebar with the main
     * content and the result is unreadable.
     *
     * Both matrices have to be followed. Producers place a block with the
     * graphics matrix (`cm`) and then position text inside it with the text
     * matrix, so reading only the text matrix reports every block at roughly
     * the same place and the whole page collapses onto a few lines.
     *
     * @return list<array{x: float, y: float, t: string}>
     */
    private static function readStream(string $content, array $fonts = []): array
    {
        $length = strlen($content);
        $runs = [];
        $pending = '';
        $numbers = [];
        $position = 0;

        $identity = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $ctm = $identity;
        $stack = [];
        $text = $identity;
        $line = $identity;
        $leading = 0.0;

        $fontSize = 0.0;
        $bold = false;
        $font = [];
        // Only strings drawn between BT and ET are page text. Marked content
        // and other operators carry strings of their own that are not.
        $inText = false;

        $flush = static function () use (&$runs, &$pending, &$text, &$ctm, &$fontSize, &$bold): void {
            if (trim($pending) !== '') {
                $placed = self::multiply($text, $ctm);
                // The matrices may scale the type, so the size on the page is the
                // declared size times the vertical scale of the combined matrix.
                $scale = sqrt($placed[2] * $placed[2] + $placed[3] * $placed[3]) ?: 1.0;
                $runs[] = [
                    'x' => $placed[4],
                    'y' => $placed[5],
                    't' => $pending,
                    'size' => $fontSize * $scale,
                    'bold' => $bold,
                ];
            }
            $pending = '';
        };

        while ($position < $length) {
            $character = $content[$position];

            // A dictionary. Stepping over both angle brackets matters: leaving
            // the second one behind made it look like the start of a hex string,
            // and `<</MCID 0/Lang (en-US)>>` filtered down to hex digits became
            // three stray characters at the head of the line.
            if ($character === '<' && ($content[$position + 1] ?? '') === '<') {
                $position += 2;
                continue;
            }

            if ($character === '(') {
                [$bytes, $position] = self::readLiteral($content, $position);
                if ($inText) {
                    $pending .= self::decode($bytes, $font);
                }
                continue;
            }

            if ($character === '<') {
                [$bytes, $position] = self::readHex($content, $position);
                if ($inText) {
                    $pending .= self::decode($bytes, $font);
                }
                continue;
            }

            if ($character === '[') {
                [$string, $position] = self::readArray($content, $position, $font);
                if ($inText) {
                    $pending .= $string;
                }
                continue;
            }

            if ($character === '-' || $character === '+' || $character === '.' || ctype_digit($character)) {
                $start = $position;
                while ($position < $length && str_contains('+-.0123456789', $content[$position])) {
                    $position++;
                }
                $numbers[] = (float) substr($content, $start, $position - $start);
                if (count($numbers) > 6) {
                    array_shift($numbers);
                }
                continue;
            }

            if (ctype_alpha($character) || $character === "'" || $character === '"') {
                $start = $position;
                while ($position < $length && (ctype_alnum($content[$position]) || $content[$position] === '*'
                    || $content[$position] === "'" || $content[$position] === '"')) {
                    $position++;
                }
                $operator = substr($content, $start, $position - $start);
                $count = count($numbers);

                switch ($operator) {
                    case 'q':
                        $stack[] = $ctm;
                        break;

                    case 'Q':
                        $ctm = array_pop($stack) ?? $identity;
                        break;

                    case 'cm':
                        if ($count >= 6) {
                            $ctm = self::multiply(array_slice($numbers, -6), $ctm);
                        }
                        break;

                    case 'BT':
                        $text = $line = $identity;
                        $inText = true;
                        break;

                    case 'Tf':
                        if ($count >= 1) {
                            $fontSize = $numbers[$count - 1];
                        }
                        // The resource name sits just before the size.
                        if (preg_match('#/([^\s/]+)\s+[\d.]+\s*$#', substr($content, max(0, $start - 60), min(60, $start)), $named)) {
                            $font = $fonts[$named[1]] ?? [];
                            $bold = (bool) ($font['bold'] ?? false);
                        }
                        break;

                    case 'Tm':
                        if ($count >= 6) {
                            $text = $line = array_slice($numbers, -6);
                        }
                        break;

                    case 'TD':
                        if ($count >= 2) {
                            $leading = -$numbers[$count - 1];
                        }
                        // fall through
                    case 'Td':
                        if ($count >= 2) {
                            $shift = [1.0, 0.0, 0.0, 1.0, $numbers[$count - 2], $numbers[$count - 1]];
                            $text = $line = self::multiply($shift, $line);
                        }
                        break;

                    case 'TL':
                        if ($count >= 1) {
                            $leading = $numbers[$count - 1];
                        }
                        break;

                    case 'T*':
                        $text = $line = self::multiply([1.0, 0.0, 0.0, 1.0, 0.0, -$leading], $line);
                        break;

                    case "'":
                    case '"':
                        // Both move to the next line before showing the text.
                        $text = $line = self::multiply([1.0, 0.0, 0.0, 1.0, 0.0, -$leading], $line);
                        $flush();
                        break;

                    case 'Tj':
                    case 'TJ':
                        $flush();
                        break;

                    case 'ET':
                        $flush();
                        $inText = false;
                        break;

                    default:
                        break;
                }

                $numbers = [];
                continue;
            }

            $position++;
        }

        $flush();
        return $runs;
    }

    /**
     * Multiply two PDF matrices, each given as [a, b, c, d, e, f].
     *
     * @param array<int, float> $m
     * @param array<int, float> $n
     * @return array<int, float>
     */
    private static function multiply(array $m, array $n): array
    {
        return [
            $m[0] * $n[0] + $m[1] * $n[2],
            $m[0] * $n[1] + $m[1] * $n[3],
            $m[2] * $n[0] + $m[3] * $n[2],
            $m[2] * $n[1] + $m[3] * $n[3],
            $m[4] * $n[0] + $m[5] * $n[2] + $n[4],
            $m[4] * $n[1] + $m[5] * $n[3] + $n[5],
        ];
    }

    /**
     * Rebuild readable text from positioned runs.
     *
     * @param list<array{x: float, y: float, t: string}> $runs
     */
    private static function layoutPage(array $runs): string
    {
        if ($runs === []) {
            return '';
        }

        $split = self::columnSplit($runs);
        if ($split === null) {
            return self::linesFrom($runs);
        }

        // Read the narrow column first, then the wide one, the way a person
        // reads a sidebar CV.
        $left = array_values(array_filter($runs, static fn (array $r): bool => $r['x'] < $split));
        $right = array_values(array_filter($runs, static fn (array $r): bool => $r['x'] >= $split));

        return rtrim(self::linesFrom($left)) . "\n\n" . ltrim(self::linesFrom($right));
    }

    /**
     * Group runs into lines by their vertical position, top of the page first.
     *
     * @param list<array{x: float, y: float, t: string}> $runs
     */
    private static function linesFrom(array $runs): string
    {
        usort($runs, static function (array $a, array $b): int {
            // PDF space grows upwards, so a larger y is higher on the page.
            if (abs($a['y'] - $b['y']) > self::LINE_TOLERANCE) {
                return $b['y'] <=> $a['y'];
            }
            return $a['x'] <=> $b['x'];
        });

        $lines = [];
        $current = '';
        $size = 0.0;
        $bold = true;
        $currentY = null;
        $previousY = null;
        $penX = null;

        foreach ($runs as $run) {
            if ($currentY !== null && abs($run['y'] - $currentY) > self::LINE_TOLERANCE) {
                $gap = $previousY === null ? 0.0 : $previousY - $currentY;
                $lines[] = [trim($current), $currentY, $gap, $size, $bold];
                $previousY = $currentY;
                $current = '';
                $size = 0.0;
                $bold = true;
                $penX = null;
            }

            $piece = $run['t'];
            // A word can be split across two text objects, and a hyphen is
            // often drawn as one of its own. Joining every run with a space
            // turned those into "prop erty" and "hands - on", so the gap on the
            // page decides: only a real space between them becomes a space.
            if ($current !== '' && $penX !== null && !self::endsOpen($current) && !self::startsOpen($piece)) {
                if ($run['x'] - $penX > max(1.0, (float) ($run['size'] ?? 10.0) * 0.22)) {
                    $current .= ' ';
                }
            }
            $current .= $piece;

            $penX = $run['x'] + self::estimateWidth($piece, (float) ($run['size'] ?? 10.0));
            $size = max($size, (float) ($run['size'] ?? 0.0));
            $bold = $bold && (bool) ($run['bold'] ?? false);
            $currentY = $run['y'];
        }
        if ($current !== '') {
            $gap = $previousY === null ? 0.0 : $previousY - $currentY;
            $lines[] = [trim($current), $currentY, $gap, $size, $bold];
        }

        // A noticeably wider gap than the body leading reads as a paragraph
        // break, which is how section headings are separated from their content.
        $gaps = array_values(array_filter(array_map(static fn (array $l): float => (float) $l[2], $lines)));
        sort($gaps);
        $typical = $gaps === [] ? 0.0 : $gaps[(int) floor(count($gaps) / 2)];

        $body = self::bodySize($lines);

        // Whether this page marks any section with larger type. If it does,
        // bold on its own is not a heading signal here.
        $boldCounts = true;
        foreach ($lines as [$lineText, , , $lineSize, $lineBold]) {
            if (self::readsAsHeading((string) $lineText, (float) $lineSize, (bool) $lineBold, $body, false)) {
                $boldCounts = false;
                break;
            }
        }

        $out = '';
        foreach ($lines as $index => [$text, , $gap, $size, $bold]) {
            if ($index > 0 && $typical > 0 && $gap > $typical * 1.6) {
                $out .= "\n";
            }
            if (self::readsAsHeading($text, (float) $size, (bool) $bold, $body, $boldCounts)) {
                $out .= self::HEADING_MARK;
            }
            $out .= $text . "\n";
        }
        return $out;
    }

    /**
     * Roughly how wide a piece of text is on the page.
     *
     * Half an em per character is the usual average for proportional text, and
     * this is only ever used to tell a word split across two text objects from
     * two words with a real space between them, so an approximation is enough.
     */
    private static function estimateWidth(string $text, float $size): float
    {
        return mb_strlen($text) * $size * 0.5;
    }

    private static function endsOpen(string $text): bool
    {
        return $text === '' || str_ends_with($text, ' ');
    }

    private static function startsOpen(string $text): bool
    {
        return $text === '' || str_starts_with($text, ' ');
    }

    /**
     * The size the body text is set in: the size that covers the most lines.
     *
     * @param list<array{0: string, 1: float, 2: float, 3: float, 4: bool}> $lines
     */
    private static function bodySize(array $lines): float
    {
        $tally = [];
        foreach ($lines as [$text, , , $size]) {
            if ($size <= 0.0 || trim($text) === '') {
                continue;
            }
            // Round so that hinting differences do not split one size in two.
            $key = (string) round($size, 1);
            $tally[$key] = ($tally[$key] ?? 0) + max(1, str_word_count($text));
        }
        if ($tally === []) {
            return 0.0;
        }
        arsort($tally);
        return (float) array_key_first($tally);
    }

    /**
     * Whether a line was set as a section heading.
     *
     * A CV marks its sections with type as much as with wording. "What I am
     * good at" is a skills heading that no list of words would ever catch, but
     * it is set two points larger than the body and that is unmistakable.
     */
    private static function readsAsHeading(
        string $text,
        float $size,
        bool $bold,
        float $body,
        bool $boldCounts
    ): bool {
        $text = trim($text);
        $words = str_word_count($text);
        if ($text === '' || $words === 0 || $words > 6 || mb_strlen($text) > 60) {
            return false;
        }
        // An entry line carries dates and field separators; a heading does not.
        if (preg_match('/[|·]|\d{4}/u', $text)) {
            return false;
        }

        if ($body > 0.0 && $size >= $body * self::HEADING_SCALE) {
            return true;
        }

        // Bold alone only means a heading on a CV with no larger type to mark
        // its sections with. Where a page does set its headings larger, bold at
        // body size is an employer or a project name, and reading those as
        // sections cut every entry away from the heading it belonged under.
        return $boldCounts && $bold && $body > 0.0 && $size <= $body * 1.05;
    }

    /**
     * The x position that separates a genuine two column layout, or null.
     *
     * Right aligned dates also produce two clusters of x positions, so a split
     * is only accepted when both sides carry a substantial share of the page.
     *
     * @param list<array{x: float, y: float, t: string}> $runs
     */
    private static function columnSplit(array $runs): ?float
    {
        if (count($runs) < 12) {
            return null;
        }

        $positions = array_map(static fn (array $r): float => $r['x'], $runs);
        sort($positions);
        $spread = end($positions) - $positions[0];
        if ($spread <= 0.0) {
            return null;
        }

        // The widest horizontal gap between neighbouring text starts.
        $bestGap = 0.0;
        $bestAt = 0.0;
        for ($i = 1, $total = count($positions); $i < $total; $i++) {
            $gap = $positions[$i] - $positions[$i - 1];
            if ($gap > $bestGap) {
                $bestGap = $gap;
                $bestAt = $positions[$i - 1] + $gap / 2;
            }
        }
        if ($bestGap < $spread * 0.25) {
            return null;
        }

        $leftRuns = array_filter($runs, static fn (array $r): bool => $r['x'] < $bestAt);
        $rightRuns = array_filter($runs, static fn (array $r): bool => $r['x'] >= $bestAt);
        $smaller = min(count($leftRuns), count($rightRuns));
        if ($smaller < count($runs) * 0.25) {
            return null;
        }

        // Both sides must run down the page, otherwise this is a header band or
        // a column of dates rather than a second column of content.
        $rows = static function (array $side): int {
            $seen = [];
            foreach ($side as $run) {
                $seen[(int) round($run['y'] / max(1, self::LINE_TOLERANCE))] = true;
            }
            return count($seen);
        };
        if ($rows($leftRuns) < 5 || $rows($rightRuns) < 5) {
            return null;
        }

        return $bestAt;
    }

    /**
     * @return array{0: string, 1: int} decoded text and the position after it
     */
    private static function readLiteral(string $content, int $position): array
    {
        $length = strlen($content);
        $depth = 0;
        $out = '';
        for ($i = $position; $i < $length; $i++) {
            $character = $content[$i];

            if ($character === '\\') {
                $next = $content[$i + 1] ?? '';
                $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0C"];
                if (isset($escapes[$next])) {
                    $out .= $escapes[$next];
                    $i++;
                } elseif ($next !== '' && ctype_digit($next)) {
                    $octal = '';
                    while (strlen($octal) < 3 && ctype_digit($content[$i + 1] ?? '')) {
                        $octal .= $content[++$i];
                    }
                    $out .= chr(octdec($octal) % 256);
                } elseif ($next === "\n" || $next === "\r") {
                    $i++;
                } else {
                    $out .= $next;
                    $i++;
                }
                continue;
            }

            if ($character === '(') {
                $depth++;
                if ($depth > 1) {
                    $out .= $character;
                }
                continue;
            }

            if ($character === ')') {
                $depth--;
                if ($depth === 0) {
                    return [$out, $i + 1];
                }
                $out .= $character;
                continue;
            }

            $out .= $character;
        }

        return [$out, $length];
    }

    /** @return array{0: string, 1: int} */
    private static function readHex(string $content, int $position): array
    {
        $end = strpos($content, '>', $position);
        if ($end === false) {
            return ['', strlen($content)];
        }

        $hex = preg_replace('/[^0-9A-Fa-f]/', '', substr($content, $position + 1, $end - $position - 1)) ?? '';
        if ($hex === '') {
            return ['', $end + 1];
        }
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }
        return [(string) hex2bin($hex), $end + 1];
    }

    /**
     * Read a `[ (a) -250 (b) ] TJ` array, turning wide kerning gaps into spaces.
     *
     * @return array{0: string, 1: int}
     */
    private static function readArray(string $content, int $position, array $font = []): array
    {
        $length = strlen($content);
        $out = '';
        $i = $position + 1;

        while ($i < $length) {
            $character = $content[$i];

            if ($character === ']') {
                return [$out, $i + 1];
            }

            if ($character === '(') {
                [$bytes, $i] = self::readLiteral($content, $i);
                $out .= self::decode($bytes, $font);
                continue;
            }

            if ($character === '<') {
                [$bytes, $i] = self::readHex($content, $i);
                $out .= self::decode($bytes, $font);
                continue;
            }

            if ($character === '-' || $character === '+' || $character === '.' || ctype_digit($character)) {
                $start = $i;
                while ($i < $length && str_contains('+-.0123456789', $content[$i])) {
                    $i++;
                }
                $value = (float) substr($content, $start, $i - $start);
                if (-$value > self::SPACE_GAP && !str_ends_with($out, ' ')) {
                    $out .= ' ';
                }
                continue;
            }

            $i++;
        }

        return [$out, $length];
    }

    /**
     * Turn the bytes of a shown string into text, using the font that drew it.
     *
     * A subset font numbers its glyphs from one, so its bytes are glyph numbers
     * rather than characters, and only the font's own ToUnicode table can read
     * them. Without such a table the bytes are ordinary character codes.
     *
     * @param array{bytes?: int, map?: array<int, string>, symbolic?: bool} $font
     */
    private static function decode(string $bytes, array $font): string
    {
        if ($bytes === '') {
            return '';
        }

        $map = $font['map'] ?? [];
        $width = ($font['bytes'] ?? 1) === 2 ? 2 : 1;

        if ($map === [] && $width === 1) {
            // A symbol font's codes are picture numbers. Only its bullets carry
            // meaning here; the rest would read as stray accented letters.
            if ($font['symbolic'] ?? false) {
                return self::symbolBullets($bytes);
            }
            return self::fromWinAnsi($bytes);
        }

        $out = '';
        $length = strlen($bytes);
        for ($i = 0; $i + $width <= $length; $i += $width) {
            $code = $width === 2
                ? (ord($bytes[$i]) << 8) | ord($bytes[$i + 1])
                : ord($bytes[$i]);

            if (isset($map[$code])) {
                $out .= $map[$code];
                continue;
            }
            // No entry: a one byte code is still a character code, while a two
            // byte glyph number without a table cannot be read at all.
            if ($width === 1) {
                $out .= ($font['symbolic'] ?? false)
                    ? self::symbolBullets($bytes[$i])
                    : self::fromWinAnsi($bytes[$i]);
            }
        }

        return $out;
    }

    /** Keep a symbol font's list bullets and drop its decorative glyphs. */
    private static function symbolBullets(string $bytes): string
    {
        $out = '';
        foreach (str_split($bytes) as $byte) {
            if (in_array(ord($byte), [0x7F, 0x95, 0xA7, 0xB7, 0xD8, 0xFC], true)) {
                $out .= "\u{2022}";
            }
        }
        return $out;
    }

    /** Character codes in the encoding almost every simple font uses. */
    private static function fromWinAnsi(string $bytes): string
    {
        if (mb_check_encoding($bytes, 'ASCII')) {
            return $bytes;
        }
        $converted = @mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
        return is_string($converted) ? $converted : $bytes;
    }

    private static function toUtf8(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        // Producers do not agree on where the bullet glyph lives. ReportLab
        // emits 0x7F, Word and friends emit the Windows-1252 0x95, and some
        // writers use the Symbol font's 0xB7. Left alone, a bullet becomes an
        // unreadable control character and every achievement line reads as the
        // start of a new job instead of a point under the one above.
        $bullets = ["\x7F", "\x95", "\x95\x20"];
        $text = str_replace($bullets, "\u{2022}", $text);

        if (!mb_check_encoding($text, 'UTF-8')) {
            $converted = @mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
            $text = is_string($converted) ? $converted : '';
        }

        // Remaining C0 controls carry no text. Tabs, newlines, and the heading
        // mark added by the layout pass are the exceptions.
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1E]/u', '', $text) ?? $text;
    }
}
