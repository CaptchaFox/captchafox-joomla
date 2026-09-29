<?php

/**
 * Builds the installable plugin package.
 *
 * Packs the contents of plugin/ into dist/plg_captcha_captchafox-<version>.zip. The version is
 * read from the manifest, which is its single source. Files are added in sorted order with a fixed
 * timestamp, so the same sources produce the same archive.
 *
 * Usage: composer build   (or: php build/build.php)
 */

declare(strict_types=1);

const PACKAGE_NAME = 'plg_captcha_captchafox';
const FIXED_MTIME  = 1735689600; // 2025-01-01T00:00:00Z

$root      = dirname(__DIR__);
$pluginDir = $root . '/plugin';
$distDir   = $root . '/dist';
$manifest  = $pluginDir . '/captchafox.xml';

if (!extension_loaded('zip')) {
    fwrite(STDERR, "The PHP zip extension is required.\n");
    exit(1);
}

$xml = @simplexml_load_file($manifest);

if ($xml === false || trim((string) $xml->version) === '') {
    fwrite(STDERR, "Could not read the version from $manifest.\n");
    exit(1);
}

$version = trim((string) $xml->version);
$target  = sprintf('%s/%s-%s.zip', $distDir, PACKAGE_NAME, $version);

if (!is_dir($distDir) && !mkdir($distDir, 0775, true)) {
    fwrite(STDERR, "Could not create $distDir.\n");
    exit(1);
}

if (is_file($target)) {
    unlink($target);
}

$files    = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if ($file->isFile() && $file->getFilename() !== '.DS_Store') {
        $files[] = substr($file->getPathname(), strlen($pluginDir) + 1);
    }
}

sort($files, SORT_STRING);

$zip = new ZipArchive();

if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not create $target.\n");
    exit(1);
}

foreach ($files as $relative) {
    $zip->addFile($pluginDir . '/' . $relative, $relative);
    $zip->setMtimeName($relative, FIXED_MTIME);
}

$zip->close();

printf("Built %s (%d files)\n", substr($target, strlen($root) + 1), count($files));
