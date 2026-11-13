<?php
// Produce a secret-free, self-contained Laravel runtime artifact.
$source = realpath($argv[1] ?? dirname(__DIR__));
$output = $argv[2] ?? '';
$commit = $argv[3] ?? '';
if (!$source || !str_ends_with($output, '.tar.gz') || !preg_match('/^[a-f0-9]{40}$/', $commit)) {
    throw new RuntimeException('Usage: php deploy/package-release.php SOURCE OUTPUT.tar.gz COMMIT_SHA');
}
foreach (['vendor/autoload.php', 'public/build/manifest.json', 'deploy/production.env.example'] as $required) {
    if (!is_file($source.'/'.$required)) throw new RuntimeException('Missing runtime input: '.$required);
}
if (is_dir($source.'/vendor/phpunit')) throw new RuntimeException('Development dependencies found; run composer install --no-dev first.');
$tarPath = substr($output, 0, -3);
if (file_exists($tarPath) || file_exists($output)) throw new RuntimeException('Artifact exists; use a new output path.');
if (!is_dir(dirname($output))) mkdir(dirname($output), 0700, true);
$archive = new PharData($tarPath);
$excluded = ['.git', '.github', '.tooling', 'node_modules', 'storage', 'tests', 'docs', 'scripts', 'resources/images/source'];
$filter = new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), function ($entry) use ($source, $excluded) {
    $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($source) + 1));
    foreach ($excluded as $prefix) if ($relative === $prefix || str_starts_with($relative, $prefix.'/')) return false;
    if (str_starts_with($entry->getFilename(), '.env') || preg_match('/\.(key|pem|pfx|p12|sql|sqlite\d*|log)$/i', $entry->getFilename())) return false;
    if (in_array($relative, ['auth.json','public/hot','public/release.json'])) return false;
    if (str_starts_with($relative, 'bootstrap/cache/') && $entry->isFile()) return false;
    return true;
});
$count = 0;
foreach (new RecursiveIteratorIterator($filter) as $entry) {
    if (!$entry->isFile()) continue;
    $relative = str_replace('\\','/',substr($entry->getPathname(), strlen($source) + 1));
    $resolved = realpath($entry->getPathname());
    if (!$resolved || !str_starts_with(str_replace('\\','/',$resolved),str_replace('\\','/',$source).'/')) throw new RuntimeException('File points outside source tree.');
    $archive->addFile($entry->getPathname(), $relative); $count++;
}
$archive->addFromString('public/release.json',json_encode(['commit'=>$commit,'built_at'=>gmdate('c')],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
$archive->compress(Phar::GZ);
echo json_encode(['files'=>$count+1,'bytes'=>filesize($output),'sha256'=>hash_file('sha256',$output),'commit'=>$commit],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
