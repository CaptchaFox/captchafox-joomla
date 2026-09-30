<?php

/**
 * Adds a release to the update server file updates/updates.xml.
 *
 * Usage: php build/add-update.php <version> <sha256>
 *
 * Called by the release workflow after the release ZIP has been published. An existing entry for the
 * same version is replaced, so the script can run again for a failed release.
 */

declare(strict_types=1);

const REPOSITORY  = 'https://github.com/CaptchaFox/captchafox-joomla';
const RAW_BASE    = 'https://raw.githubusercontent.com/CaptchaFox/captchafox-joomla/main';
const PACKAGE     = 'plg_captcha_captchafox';
const UPDATE_FILE = __DIR__ . '/../updates/updates.xml';

// Joomla 5.4 and 6.x (a regular expression, matched against the start of the Joomla version) and the
// PHP minimum; keep in line with the checks in plugin/script.php.
const TARGET_PLATFORM = '(5\.4|6\.)';
const PHP_MINIMUM     = '8.1';

[, $version, $sha256] = $argv + [null, '', ''];

if (!preg_match('/^\d+\.\d+\.\d+$/', (string) $version) || !preg_match('/^[0-9a-f]{64}$/', (string) $sha256)) {
    fwrite(STDERR, "Usage: php build/add-update.php <version> <sha256>\n");
    exit(1);
}

$document                     = new DOMDocument();
$document->preserveWhiteSpace = false;
$document->formatOutput       = true;

if (!$document->load(UPDATE_FILE)) {
    fwrite(STDERR, 'Could not read ' . UPDATE_FILE . "\n");
    exit(1);
}

$updates = $document->documentElement;

if ($updates === null || $updates->nodeName !== 'updates') {
    fwrite(STDERR, UPDATE_FILE . " has no <updates> root element.\n");
    exit(1);
}

// Replace an existing entry for this version.
foreach (iterator_to_array($updates->getElementsByTagName('update')) as $existing) {
    if ($existing->getElementsByTagName('version')->item(0)?->textContent === $version) {
        $updates->removeChild($existing);
    }
}

$tag   = 'v' . $version;
$entry = $document->createElement('update');

$add = static function (DOMElement $parent, string $name, string $value = '', array $attributes = []) use ($document): DOMElement {
    $element = $document->createElement($name);

    if ($value !== '') {
        $element->appendChild($document->createTextNode($value));
    }

    foreach ($attributes as $attribute => $attributeValue) {
        $element->setAttribute($attribute, $attributeValue);
    }

    $parent->appendChild($element);

    return $element;
};

$add($entry, 'name', 'Captcha - CaptchaFox');
$add($entry, 'description', 'CaptchaFox captcha plugin for Joomla');
$add($entry, 'element', 'captchafox');
$add($entry, 'type', 'plugin');
$add($entry, 'folder', 'captcha');
$add($entry, 'client', 'site');
$add($entry, 'version', $version);
$add($entry, 'infourl', REPOSITORY . '/releases/tag/' . $tag, ['title' => 'CaptchaFox for Joomla ' . $version]);
$downloads = $add($entry, 'downloads');
$add($downloads, 'downloadurl', REPOSITORY . '/releases/download/' . $tag . '/' . PACKAGE . '-' . $version . '.zip', ['type' => 'full', 'format' => 'zip']);
$add($entry, 'sha256', $sha256);
$add($entry, 'changelogurl', RAW_BASE . '/updates/changelog.xml');
$add($entry, 'maintainer', 'Scoria Labs GmbH');
$add($entry, 'maintainerurl', 'https://captchafox.com');
$add($entry, 'targetplatform', '', ['name' => 'joomla', 'version' => TARGET_PLATFORM]);
$add($entry, 'php_minimum', PHP_MINIMUM);

// Newest entry first.
$first = null;

foreach ($updates->childNodes as $child) {
    if ($child instanceof DOMElement) {
        $first = $child;
        break;
    }
}

$updates->insertBefore($entry, $first);

if ($document->save(UPDATE_FILE) === false) {
    fwrite(STDERR, 'Could not write ' . UPDATE_FILE . "\n");
    exit(1);
}

printf("Added version %s to updates/updates.xml\n", $version);
