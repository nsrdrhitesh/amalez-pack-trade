<?php
// Rebuild local responsive WebP assets from the original generated image studies.
$root = dirname(__DIR__);
$source = $root.'/resources/images/source/';
$output = $root.'/public/images/';
function exportWebp(string $file, string $target, int $width, int $height, ?array $crop = null): void {
    $image = imagecreatefrompng($file);
    $crop ??= [0, 0, imagesx($image), imagesy($image)];
    $resized = imagecreatetruecolor($width, $height);
    imagecopyresampled($resized, $image, 0, 0, ...[$crop[0], $crop[1], $width, $height, $crop[2], $crop[3]]);
    if (!is_dir(dirname($target))) { mkdir(dirname($target), 0755, true); }
    imagewebp($resized, $target, 82);
    imagedestroy($image); imagedestroy($resized);
    echo basename($target).' '.round(filesize($target)/1024).' KB'.PHP_EOL;
}
exportWebp($source.'packaging.png', $output.'hero/packaging-1600.webp', 1536, 1024);
exportWebp($source.'packaging.png', $output.'hero/packaging-800.webp', 800, 533);
exportWebp($source.'packaging.png', $output.'products/pouches.webp', 800, 800, [550, 20, 820, 820]);
exportWebp($source.'packaging.png', $output.'products/spout.webp', 800, 800, [510, 490, 490, 490]);
exportWebp($source.'materials.png', $output.'products/labels.webp', 800, 800, [780, 300, 700, 700]);
exportWebp($source.'materials.png', $output.'products/rolls.webp', 800, 800, [0, 80, 900, 900]);
exportWebp($source.'materials.png', $output.'gallery/materials.webp', 1200, 800);
exportWebp($source.'packaging.png', $output.'gallery/application.webp', 1000, 800, [240, 0, 1280, 1024]);
