<?php

// Dependency-free release check; never boots Laravel or reads environment secrets.
$root = dirname(__DIR__);
$assets = json_decode(file_get_contents($root.'/database/content/assets.json'), true, 512, JSON_THROW_ON_ERROR);
$hashes = [];
$bytes = 0;
foreach ($assets as $asset) {
    if (! preg_match('~^images/business/[a-z0-9-]+\.webp$~', $asset['file'])) {
        throw new RuntimeException('Invalid asset path.');
    }
    $file = $root.'/public/'.$asset['file'];
    $size = getimagesize($file);
    if (! $size || $size['mime'] !== 'image/webp' || $size[0] !== $asset['width'] || $size[1] !== $asset['height'] || filesize($file) !== $asset['bytes'] || ! hash_equals(hash_file('sha256', $file), $asset['sha256'])) {
        throw new RuntimeException('Invalid or changed image: '.$asset['id']);
    }
    if (isset($hashes[$asset['sha256']]) || ! str_contains($asset['alt'], 'Amalez PackTrade') || ! str_starts_with($asset['page'], '/') || $asset['bytes'] > 350000) {
        throw new RuntimeException('Duplicate, unlabelled or oversized asset: '.$asset['id']);
    }
    $hashes[$asset['sha256']] = true;
    $bytes += $asset['bytes'];
}
if (count($assets) < 200) {
    throw new RuntimeException('The reviewed library is incomplete.');
}
echo json_encode(['verified_assets'=>count($assets), 'unique_checksums'=>count($hashes), 'total_bytes'=>$bytes], JSON_PRETTY_PRINT).PHP_EOL;
