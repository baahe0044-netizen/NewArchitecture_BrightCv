<?php

declare(strict_types=1);

/**
 * Draws the LunettiStar CV app icons used by the web manifest and iOS home
 * screen, by compositing the real logo mark onto a plain background.
 *
 * The icons are committed to the repository, so this only needs re-running
 * when the mark or its source image (assets/brand/logo-icon.png) changes:
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

const SOURCE_LOGO = __DIR__ . '/../public/assets/brand/logo-icon.png';
const OUTPUT_DIR = __DIR__ . '/../public/assets/icons';
// White, not a brand colour: the mark's own navy is the icon's darkest
// value, so a coloured card would swallow it. White is also what the source
// artwork was drawn against.
const BACKGROUND = [0xff, 0xff, 0xff];

/**
 * @param float $safe fraction of the canvas the mark occupies. Maskable icons
 *              get a smaller mark so a circular or squircle crop cannot clip it.
 */
function drawIcon(GdImage $logo, int $size, float $safe, bool $rounded): GdImage
{
    $image = imagecreatetruecolor($size, $size);
    imagesavealpha($image, true);
    imagealphablending($image, false);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagealphablending($image, true);

    $bg = imagecolorallocate($image, BACKGROUND[0], BACKGROUND[1], BACKGROUND[2]);

    if ($rounded) {
        $radius = (int) round($size * 0.22);
        imagefilledrectangle($image, $radius, 0, $size - $radius, $size, $bg);
        imagefilledrectangle($image, 0, $radius, $size, $size - $radius, $bg);
        foreach ([[$radius, $radius], [$size - $radius, $radius], [$radius, $size - $radius], [$size - $radius, $size - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $bg);
        }
    } else {
        imagefilledrectangle($image, 0, 0, $size, $size, $bg);
    }

    // Scale the logo into the safe zone, preserving its aspect ratio, then
    // centre it on the canvas.
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);
    $box = $size * $safe;
    $scale = min($box / $logoWidth, $box / $logoHeight);
    $destWidth = (int) round($logoWidth * $scale);
    $destHeight = (int) round($logoHeight * $scale);
    $destX = (int) round(($size - $destWidth) / 2);
    $destY = (int) round(($size - $destHeight) / 2);

    imagecopyresampled($image, $logo, $destX, $destY, 0, 0, $destWidth, $destHeight, $logoWidth, $logoHeight);

    return $image;
}

$logo = @imagecreatefrompng(SOURCE_LOGO);
if (!$logo) {
    fwrite(STDERR, "Could not read source logo at " . SOURCE_LOGO . "\n");
    exit(1);
}
imagealphablending($logo, false);
imagesavealpha($logo, true);

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
    $image = drawIcon($logo, $size, $safe, $rounded);
    imagepng($image, OUTPUT_DIR . '/' . $name, 9);
    imagedestroy($image);
    printf("%-24s %dx%d\n", $name, $size, $size);
}

echo "Icons written to public/assets/icons.\n";
