<?php

declare(strict_types=1);

/**
 * Undoes the compression a PDF stream was written with.
 *
 * Exporters differ: Word writes plain FlateDecode, ReportLab writes
 * [/ASCII85Decode /FlateDecode]. Anything image shaped is not page text and is
 * refused rather than guessed at.
 */
final class PdfFilters
{
    /**
     * @param list<string> $filters
     */
    public static function decode(array $filters, string $data): ?string
    {
        foreach ($filters as $filter) {
            $data = match ($filter) {
                'ASCII85Decode', 'A85' => self::ascii85($data),
                'ASCIIHexDecode', 'AHx' => self::asciiHex($data),
                'FlateDecode', 'Fl' => self::inflate($data),
                'RunLengthDecode', 'RL' => self::runLength($data),
                // A predictor only rearranges bytes that are already inflated.
                'Crypt' => $data,
                default => null,
            };
            if ($data === null) {
                return null;
            }
        }

        if ($filters === []) {
            // Some writers omit the filter entry for uncompressed content, and
            // a few compress anyway, so try both.
            return self::inflate($data) ?? $data;
        }

        return $data;
    }

    public static function inflate(string $data): ?string
    {
        if (!function_exists('gzinflate')) {
            return null;
        }
        foreach (['gzuncompress', 'gzinflate'] as $function) {
            $result = @$function($data);
            if (is_string($result) && $result !== '') {
                return $result;
            }
        }
        $result = @gzinflate(ltrim($data, "\r\n"));
        return is_string($result) && $result !== '' ? $result : null;
    }

    private static function asciiHex(string $data): string
    {
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', explode('>', $data, 2)[0]) ?? '';
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }
        return (string) hex2bin($hex);
    }

    private static function runLength(string $data): string
    {
        $out = '';
        $length = strlen($data);
        for ($i = 0; $i < $length;) {
            $marker = ord($data[$i++]);
            if ($marker === 128) {
                break;
            }
            if ($marker < 128) {
                $out .= substr($data, $i, $marker + 1);
                $i += $marker + 1;
                continue;
            }
            if ($i < $length) {
                $out .= str_repeat($data[$i++], 257 - $marker);
            }
        }
        return $out;
    }

    private static function ascii85(string $data): string
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
                $out .= self::group($group, 4);
                $group = [];
            }
        }

        if ($group !== []) {
            $kept = count($group) - 1;
            while (count($group) < 5) {
                $group[] = 84;
            }
            $out .= self::group($group, $kept);
        }

        return $out;
    }

    /** @param list<int> $group */
    private static function group(array $group, int $keep): string
    {
        $total = 0;
        foreach ($group as $value) {
            $total = $total * 85 + $value;
        }
        return substr(pack('N', $total), 0, max(0, $keep));
    }
}
