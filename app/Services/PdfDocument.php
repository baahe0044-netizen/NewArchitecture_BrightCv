<?php

declare(strict_types=1);

/**
 * The parts of a PDF file that text extraction needs: its objects, its pages,
 * and the fonts each page draws with.
 *
 * A PDF is a set of numbered objects, and from PDF 1.5 onwards most of them are
 * packed inside compressed object streams, so they cannot be found by reading
 * the file top to bottom. Fonts matter because a subset font in a Word export
 * addresses its glyphs by number: without the font's own ToUnicode table those
 * numbers decode to nonsense rather than letters.
 */
final class PdfDocument
{
    /** @var array<int, string> object number => the body between `obj` and `endobj` */
    private array $objects = [];

    private function __construct(private readonly string $raw)
    {
    }

    public static function open(string $raw): self
    {
        $document = new self($raw);
        $document->indexObjects();
        return $document;
    }

    /**
     * Each page's content and the fonts it uses, in page order.
     *
     * @return list<array{content: string, fonts: array<string, array>}>
     */
    public function pages(): array
    {
        $pages = [];
        foreach ($this->objects as $body) {
            if (!preg_match('#/Type\s*/Page[^s]#', $body)) {
                continue;
            }
            $content = $this->contentOf($body);
            if (trim($content) === '') {
                continue;
            }
            $pages[] = ['content' => $content, 'fonts' => $this->fontsOf($body)];
        }

        // A file whose pages cannot be walked still yields its text: fall back
        // to every stream that carries text operators, in file order.
        if ($pages === []) {
            foreach ($this->objects as $body) {
                $content = $this->streamOf($body);
                if ($content !== null && (str_contains($content, 'Tj') || str_contains($content, 'TJ'))) {
                    $pages[] = ['content' => $content, 'fonts' => []];
                }
            }
        }

        return $pages;
    }

    // ------------------------------------------------------------------
    // Objects
    // ------------------------------------------------------------------

    private function indexObjects(): void
    {
        if (preg_match_all('/(\d+)\s+\d+\s+obj\b(.*?)endobj/s', $this->raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $this->objects[(int) $match[1]] = $match[2];
            }
        }

        // Objects packed into an object stream are invisible to the scan above,
        // and in a modern Word export that is where the fonts live.
        foreach ($this->objects as $body) {
            if (!str_contains($body, '/ObjStm')) {
                continue;
            }
            $this->indexObjectStream($body);
        }
    }

    private function indexObjectStream(string $body): void
    {
        $data = $this->streamOf($body);
        if ($data === null) {
            return;
        }

        $count = (int) ($this->number($body, 'N') ?? 0);
        $first = (int) ($this->number($body, 'First') ?? 0);
        if ($count <= 0 || $first <= 0) {
            return;
        }

        $header = substr($data, 0, $first);
        if (!preg_match_all('/(\d+)\s+(\d+)/', $header, $pairs, PREG_SET_ORDER)) {
            return;
        }

        $entries = array_slice($pairs, 0, $count);
        foreach ($entries as $index => $pair) {
            $number = (int) $pair[1];
            $offset = $first + (int) $pair[2];
            $end = isset($entries[$index + 1])
                ? $first + (int) $entries[$index + 1][2]
                : strlen($data);
            // Objects already read from the file body win: they are the ones an
            // incremental update most recently rewrote.
            $this->objects[$number] ??= substr($data, $offset, max(0, $end - $offset));
        }
    }

    /** Follow `12 0 R` to the object it names. */
    private function resolve(string $value): ?string
    {
        if (preg_match('/^\s*(\d+)\s+\d+\s+R\s*$/', $value, $match)) {
            return $this->objects[(int) $match[1]] ?? null;
        }
        return $value;
    }

    private function number(string $body, string $key): ?float
    {
        return preg_match('#/' . $key . '\s+([\d.]+)#', $body, $match) ? (float) $match[1] : null;
    }

    /** The value of a dictionary key, with balanced brackets kept intact. */
    private function entry(string $body, string $key): ?string
    {
        $at = strpos($body, '/' . $key);
        if ($at === false) {
            return null;
        }
        $cursor = $at + strlen($key) + 1;
        $length = strlen($body);
        while ($cursor < $length && ctype_space($body[$cursor])) {
            $cursor++;
        }
        if ($cursor >= $length) {
            return null;
        }

        $opens = ['<<' => '>>', '[' => ']'];
        foreach ($opens as $open => $close) {
            if (substr($body, $cursor, strlen($open)) === $open) {
                $depth = 0;
                for ($i = $cursor; $i < $length; $i++) {
                    if (substr($body, $i, strlen($open)) === $open) {
                        $depth++;
                        $i += strlen($open) - 1;
                    } elseif (substr($body, $i, strlen($close)) === $close) {
                        $depth--;
                        if ($depth === 0) {
                            return substr($body, $cursor, $i + strlen($close) - $cursor);
                        }
                        $i += strlen($close) - 1;
                    }
                }
                return substr($body, $cursor);
            }
        }

        $end = strcspn($body, "/>\r\n", $cursor);
        return trim(substr($body, $cursor, $end));
    }

    // ------------------------------------------------------------------
    // Streams
    // ------------------------------------------------------------------

    private function streamOf(string $body): ?string
    {
        if (!preg_match('/stream\r?\n?(.*?)\s*endstream/s', $body, $match)) {
            return null;
        }
        return PdfFilters::decode($this->filtersOf($body), $match[1]);
    }

    /** @return list<string> */
    private function filtersOf(string $body): array
    {
        $filter = $this->entry($body, 'Filter');
        if ($filter === null) {
            return [];
        }
        preg_match_all('#/(\w+)#', $filter, $names);
        return $names[1];
    }

    private function contentOf(string $pageBody): string
    {
        $contents = $this->entry($pageBody, 'Contents');
        if ($contents === null) {
            return '';
        }

        $parts = [];
        // /Contents may be one reference or an array of them.
        if (preg_match_all('/(\d+)\s+\d+\s+R/', $contents, $refs)) {
            foreach ($refs[1] as $number) {
                $body = $this->objects[(int) $number] ?? null;
                if ($body === null) {
                    continue;
                }
                $stream = $this->streamOf($body);
                if ($stream !== null) {
                    $parts[] = $stream;
                }
            }
        }

        return implode("\n", $parts);
    }

    // ------------------------------------------------------------------
    // Fonts
    // ------------------------------------------------------------------

    /**
     * The fonts a page can draw with, keyed by the name its content uses.
     *
     * @return array<string, array{bytes: int, map: array<int, string>, bold: bool, symbolic: bool}>
     */
    private function fontsOf(string $pageBody): array
    {
        $resources = $this->entry($pageBody, 'Resources');
        if ($resources === null) {
            return [];
        }
        $resolved = $this->resolve($resources);
        if ($resolved === null) {
            return [];
        }

        $fontDict = $this->entry($resolved, 'Font');
        if ($fontDict === null) {
            return [];
        }
        $fontDict = $this->resolve($fontDict) ?? $fontDict;

        $fonts = [];
        if (preg_match_all('#/([^\s/]+)\s+(\d+)\s+\d+\s+R#', $fontDict, $entries, PREG_SET_ORDER)) {
            foreach ($entries as $entry) {
                $body = $this->objects[(int) $entry[2]] ?? null;
                if ($body !== null) {
                    $fonts[$entry[1]] = $this->readFont($body);
                }
            }
        }
        return $fonts;
    }

    /**
     * @return array{bytes: int, map: array<int, string>, bold: bool, symbolic: bool}
     */
    private function readFont(string $body): array
    {
        $base = preg_match('#/BaseFont\s*/([^\s/>\]]+)#', $body, $match) ? $match[1] : '';
        $encoding = preg_match('#/Encoding\s*/([\w-]+)#', $body, $match) ? $match[1] : '';

        $font = [
            // A composite font addresses glyphs with two bytes.
            'bytes' => str_contains($body, '/Type0') || str_contains($encoding, 'Identity') ? 2 : 1,
            'map' => [],
            'bold' => (bool) preg_match('/bold|black|heavy|semib/i', $base),
            // A symbol font's codes are picture numbers, not letters.
            'symbolic' => (bool) preg_match('/symbol|wingding|webding|dingbat/i', $base),
        ];

        $toUnicode = $this->entry($body, 'ToUnicode');
        if ($toUnicode !== null) {
            $stream = $this->resolve($toUnicode);
            if ($stream !== null) {
                $cmap = $this->streamOf($stream);
                if ($cmap !== null) {
                    $font['map'] = PdfCmap::parse($cmap);
                }
            }
        }

        return $font;
    }
}
