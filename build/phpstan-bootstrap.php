<?php

/**
 * PHPStan bootstrap: makes the Joomla classes of the installation in JOOMLA_ROOT known.
 */

declare(strict_types=1);

$root = rtrim((string) getenv('JOOMLA_ROOT'), '/');

if ($root === '' || !is_file($root . '/libraries/vendor/autoload.php')) {
    fwrite(STDERR, "Set JOOMLA_ROOT to a Joomla 5.4 or 6.x installation (or run build/phpstan.sh).\n");
    exit(1);
}

\define('_JEXEC', 1);
\define('JPATH_ROOT', $root);
\define('JDEBUG', false);

// Framework packages (Joomla\Http, Joomla\Registry, …).
require $root . '/libraries/vendor/autoload.php';

// CMS classes (Joomla\CMS\…).
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Joomla\\CMS\\';

    if (str_starts_with($class, $prefix)) {
        $file = $root . '/libraries/src/' . str_replace('\\', '/', substr($class, \strlen($prefix))) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});

\define('JVERSION', (new Joomla\CMS\Version())->getShortVersion());
