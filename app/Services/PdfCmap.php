<?php

declare(strict_types=1);

/**
 * Reads a font's ToUnicode table.
 *
 * A subset font embedded by Word numbers its glyphs from one rather than using
 * character codes, so the bytes in the page content mean nothing on their own.
 * The ToUnicode table is the font's own statement of which character each glyph
 * number stands for, and it is the only reliable way to read such a page.
 */
final class PdfCmap
{
    /**
     * @return array<int, string> glyph code => the text it stands for
     */
    public static function parse(string $cmap): array
    {
        $map = [];

        // <code> <text>
        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                if (!preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]*)>/', $block, $pairs, PREG_SET_ORDER)) {
                    continue;
                }
                foreach ($pairs as $pair) {
                    $map[(int) hexdec($pair[1])] = self::text($pair[2]);
                }
            }
        }

        // <first> <last> <text of the first>, or <first> <last> [ <text> ... ]
        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                self::readRanges($block, $map);
            }
        }

        return $map;
    }

    /**
     * @param array<int, string> $map
     */
    private static function readRanges(string $block, array &$map): void
    {
        $pattern = '/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(\[(?:[^\]]*)\]|<[0-9A-Fa-f]*>)/s';
        if (!preg_match_all($pattern, $block, $ranges, PREG_SET_ORDER)) {
            return;
        }

        foreach ($ranges as $range) {
            $first = (int) hexdec($range[1]);
            $last = (int) hexdec($range[2]);
            if ($last < $first || $last - $first > 65535) {
                continue;
            }

            if (str_starts_with($range[3], '[')) {
                // One replacement per code in the range.
                preg_match_all('/<([0-9A-Fa-f]*)>/', $range[3], $items);
                foreach ($items[1] as $index => $hex) {
                    $map[$first + $index] = self::text($hex);
                }
                continue;
            }

            // One starting value, incremented across the range.
            $hex = trim($range[3], '<>');
            if ($hex === '') {
                continue;
            }
            $units = self::units($hex);
            if ($units === []) {
                continue;
            }
            $lastIndex = count($units) - 1;
            for ($code = $first; $code <= $last; $code++) {
                $shifted = $units;
                $shifted[$lastIndex] += $code - $first;
                $map[$code] = self::fromUnits($shifted);
            }
        }
    }

    /** Hex UTF-16BE to UTF-8. */
    private static function text(string $hex): string
    {
        return self::fromUnits(self::units($hex));
    }

    /** @return list<int> */
    private static function units(string $hex): array
    {
        if (strlen($hex) % 4 !== 0) {
            $hex = str_pad($hex, (int) (ceil(strlen($hex) / 4) * 4), '0', STR_PAD_LEFT);
        }
        $units = [];
        foreach (str_split($hex, 4) ?: [] as $chunk) {
            $units[] = (int) hexdec($chunk);
        }
        return $units;
    }

    /** @param list<int> $units */
    private static function fromUnits(array $units): string
    {
        $text = '';
        for ($i = 0, $total = count($units); $i < $total; $i++) {
            $unit = $units[$i];
            // A surrogate pair spans two units.
            if ($unit >= 0xD800 && $unit <= 0xDBFF && isset($units[$i + 1])) {
                $low = $units[++$i];
                $text .= self::character(0x10000 + (($unit - 0xD800) << 10) + ($low - 0xDC00));
                continue;
            }
            $text .= self::character($unit);
        }
        return $text;
    }

    private static function character(int $code): string
    {
        if ($code <= 0) {
            return '';
        }
        // Word writes list bullets from a symbol font, which land in the private
        // use area. Left as they are they read as stray accented letters.
        if ($code >= 0xF000 && $code <= 0xF0FF) {
            return in_array($code & 0xFF, [0x7F, 0x95, 0xA7, 0xB7, 0xD8, 0xFC], true) ? "\u{2022}" : '';
        }
        if ($code >= 0xE000 && $code <= 0xF8FF) {
            return '';
        }

        return mb_chr($code, 'UTF-8') ?: '';
    }
}
