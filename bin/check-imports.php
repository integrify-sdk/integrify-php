<?php

declare(strict_types=1);

/**
 * Hər `use` ifadəsinin paketin **öz** autoloader-i ilə həll olunduğunu yoxlayır.
 *
 * Monorepo hər şeyi bir kök `vendor/`-dan autoload edir, ona görə paket elan etmədiyi
 * asılılığın class-ını istifadə edə bilər və testlər yenə keçər. Publish olunandan
 * sonra həmin class istifadəçinin `vendor/`-unda olmur:
 *
 *     PHP Fatal error: Class "Integrify\Client" not found
 *
 * Bu skript yalnız paket **tək başına** qurulduqdan sonra mənalıdır — `vendor/`-u
 * yalnız onun öz `composer.json`-u doldurmuş olmalıdır. CI-dakı "Standalone install"
 * job-u məhz bunu edir.
 *
 * Usage:  cd packages/<name> && php ../../bin/check-imports.php
 * Exit:   0 = hər import həll olunur, 1 = elan olunmamış asılılıq var
 */

$package = getcwd();

if ($package === false || !is_dir($package . '/src')) {
    fwrite(STDERR, "Run this from a package directory (the one with src/ in it).\n");
    exit(1);
}

$autoload = getenv('INTEGRIFY_AUTOLOAD');

if (!is_string($autoload) || $autoload === '') {
    $autoload = $package . '/vendor/autoload.php';
}

if (!is_file($autoload)) {
    fwrite(STDERR, "No autoloader at {$autoload}. Run `composer install` in this package first.\n");
    exit(1);
}

require $autoload;

/** @var list<string> $missing */
$missing = [];
$checked = 0;

/** @var iterable<\SplFileInfo> $files */
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package . '/src'));

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $lines = file($file->getPathname());

    if ($lines === false) {
        continue;
    }

    foreach ($lines as $line) {
        // `use function` və `use const` class deyil; qruplu `use A\{B, C}` bu kod
        // bazasında istifadə olunmur (PSR-12 hər import üçün ayrıca sətir istəyir).
        if (preg_match('/^use\s+(?!function\s|const\s)([A-Za-z0-9_\\\\]+)\s*(?:as\s|;)/', $line, $match) !== 1) {
            continue;
        }

        $symbol = $match[1];
        ++$checked;

        try {
            $found = class_exists($symbol)
                || interface_exists($symbol)
                || trait_exists($symbol)
                || enum_exists($symbol);
            $reason = 'not found';
        } catch (\Throwable $error) {
            // Class tapılır, lakin onun özünün parent-i tapılmır: eyni problem,
            // sadəcə bir pillə dərində. `class_exists()` bunu fatal kimi atır.
            $found = false;
            $reason = $error->getMessage();
        }

        if (!$found) {
            $relative = str_replace($package . '/', '', $file->getPathname());
            $missing[] = sprintf('%s: %s (%s)', $relative, $symbol, $reason);
        }
    }
}

if ($missing !== []) {
    $missing = array_values(array_unique($missing));

    fwrite(STDERR, "These imports are NOT installable from this package alone:\n\n");

    foreach ($missing as $entry) {
        fwrite(STDERR, "  {$entry}\n");
    }

    fwrite(STDERR, "\nAdd the package that provides them to composer.json \"require\".\n");
    exit(1);
}

printf("OK: %d imports, all resolvable from this package alone.\n", $checked);
exit(0);
