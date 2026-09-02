<?php

declare(strict_types=1);

/**
 * Draws the BrightCV app icons used by the web manifest and iOS home screen.
 *
 * The icons are committed to the repository, so this only needs re-running when
 * the mark or the brand colour changes:
 *
 *     php scripts/generate-icons.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The gd extension is required to draw the icons.\n");
    exit(1);
}

const BRAND = [0x40, 0x52, 0xb5];
const OUTPUT_DIR = __DIR__ . '/../public/assets/icons';

/**
 * @param float $safe fraction of the canvas the mark occupies. Maskable icons
 *              get a smaller mark so a circular or squircle crop cannot clip it.
 */
function drawIcon(int $size, float $safe, bool $rounded): GdImage
{
    $image = imagecreatetruecolor($size, $size);
    imagesavealpha($image, true);
    imagealphablending($image, false);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagealphablending($image, true);

    $brand = imagecolorallocate($image, BRAND[0], BRAND[1], BRAND[2]);
    $white = imagecolorallocate($image, 255, 255, 255);

    if ($rounded) {
        $radius = (int) round($size * 0.22);
        imagefilledrectangle($image, $radius, 0, $size - $radius, $size, $brand);
        imagefilledrectangle($image, 0, $radius, $size, $size - $radius, $brand);
        foreach ([[$radius, $radius], [$size - $radius, $radius], [$radius, $size - $radius], [$size - $radius, $size - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $brand);
        }
    } else {
        imagefilledrectangle($image, 0, 0, $size, $size, $brand);
    }

    // A sheet of paper with a folded corner and three lines of writing: the
    // same mark as the header logo, drawn with primitives because gd has no
    // vector renderer.
    $markWidth = $size * $safe * 0.72;
    $markHeight = $size * $safe;
    $left = (int) round(($size - $markWidth) / 2);
    $top = (int) round(($size - $markHeight) / 2);
    $right = (int) round($left + $markWidth);
    $bottom = (int) round($top + $markHeight);
    $fold = (int) round($markWidth * 0.34);

    imagefilledpolygon($image, [
        $left, $top,
        $right - $fold, $top,
        $right, $top + $fold,
        $right, $bottom,
        $left, $bottom,
    ], $white);

    // The fold reads as a shadowed triangle in the top corner.
    $shade = imagecolorallocatealpha($image, BRAND[0], BRAND[1], BRAND[2], 75);
    imagefilledpolygon($image, [
        $right - $fold, $top,
        $right, $top + $fold,
        $right - $fold, $top + $fold,
    ], $shade);

    $lineHeight = max(2, (int) round($markHeight * 0.055));
    $lineLeft = (int) round($left + $markWidth * 0.17);
    $lineRight = (int) round($right - $markWidth * 0.17);
    $firstLine = (int) round($top + $markHeight * 0.46);
    $gap = (int) round($markHeight * 0.17);

    foreach ([0, 1, 2] as $index) {
        $y = $firstLine + $gap * $index;
        $end = $index === 2 ? (int) round($lineLeft + ($lineRight - $lineLeft) * 0.55) : $lineRight;
        imagefilledrectangle($image, $lineLeft, $y, $end, $y + $lineHeight, $brand);
    }

    return $image;
}

if (!is_dir(OUTPUT_DIR) && !mkdir(OUTPUT_DIR, 0755, true) && !is_dir(OUTPUT_DIR)) {
    fwrite(STDERR, "Could not create " . OUTPUT_DIR . "\n");
    exit(1);
}

$icons = [
    // name          size  safe  rounded
    ['icon-192.png',  192, 0.56, true],
    ['icon-512.png',  512, 0.56, true],
    // Android masks maskable icons to a circle or squircle, so the mark shrinks
    // into the safe zone and the brand colour bleeds to the edges.
    ['maskable-192.png', 192, 0.42, false],
    ['maskable-512.png', 512, 0.42, false],
    // iOS applies its own rounding and does not support transparency well.
    ['apple-touch-icon.png', 180, 0.56, false],
];

foreach ($icons as [$name, $size, $safe, $rounded]) {
    $image = drawIcon($size, $safe, $rounded);
    imagepng($image, OUTPUT_DIR . '/' . $name, 9);
    imagedestroy($image);
    printf("%-24s %dx%d\n", $name, $size, $size);
}

echo "Icons written to public/assets/icons.\n";
