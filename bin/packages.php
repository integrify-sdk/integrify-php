<?php

declare(strict_types=1);

/**
 * `packages/` altındakı paketləri aşkar edən **yeganə mənbə**.
 *
 * CI workflow-larındakı matris, mirror adları və kök `composer.json`-un autoload
 * xəritəsi buradan gəlir — yeni inteqrasiya əlavə edəndə siyahını beş yerdə
 * yeniləmək lazım olmasın deyə.
 *
 * Konvensiya (hər üçü uyğun olmalıdır):
 *
 *     packages/lsim/  ->  integrify/lsim  ->  integrify-sdk/integrify-php-lsim
 *
 * Mirror adını dəyişmək, və ya paketi publish-dən kənarda saxlamaq üçün paketin
 * `composer.json`-una:
 *
 *     "extra": {"integrify": {"mirror": "başqa-ad", "publish": false}}
 *
 * Usage:
 *     php bin/packages.php            paketləri cədvəl kimi göstərir
 *     php bin/packages.php --json     GitHub Actions matrisi üçün JSON
 *     php bin/packages.php --check    kök autoload xəritəsi sinxrondurmu (CI)
 *     php bin/packages.php --sync     kök autoload xəritəsini yenidən yazır
 *
 * Exit: 0 = qaydasındadır, 1 = problem var (hər biri ayrıca sətirdə)
 */

const VENDOR = 'integrify';
const MIRROR_PREFIX = 'integrify-php-';

$root = dirname(__DIR__);
$mode = $argv[1] ?? '--table';

if (!in_array($mode, ['--table', '--json', '--check', '--sync'], true)) {
    fwrite(STDERR, "Unknown option {$mode}. Use --table, --json, --check or --sync.\n");
    exit(1);
}

/**
 * @return array<string, mixed>
 */
function manifest(string $path): array
{
    $raw = file_get_contents($path);

    if ($raw === false) {
        fwrite(STDERR, "cannot read {$path}\n");
        exit(1);
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        fwrite(STDERR, "{$path} is not valid JSON: {$error->getMessage()}\n");
        exit(1);
    }

    return $data;
}

/**
 * Manifestin `autoload`/`autoload-dev` psr-4 bölməsi.
 *
 * @param  array<string, mixed>  $manifest
 * @return array<string, string>
 */
function psr4(array $manifest, string $section): array
{
    $autoload = $manifest[$section] ?? null;

    if (!is_array($autoload)) {
        return [];
    }

    $psr4 = $autoload['psr-4'] ?? null;

    if (!is_array($psr4)) {
        return [];
    }

    $out = [];

    foreach ($psr4 as $namespace => $directory) {
        if (is_string($namespace) && is_string($directory)) {
            $out[$namespace] = $directory;
        }
    }

    return $out;
}

/**
 * Paketin `extra.integrify` bölməsi.
 *
 * @param  array<string, mixed>  $manifest
 * @return array<string, mixed>
 */
function settings(array $manifest): array
{
    $extra = $manifest['extra'] ?? null;

    if (!is_array($extra)) {
        return [];
    }

    $own = $extra[VENDOR] ?? null;

    if (!is_array($own)) {
        return [];
    }

    $out = [];

    foreach ($own as $key => $value) {
        if (is_string($key)) {
            $out[$key] = $value;
        }
    }

    return $out;
}

// ---------------------------------------------------------------------------
// Aşkarlama
// ---------------------------------------------------------------------------

$errors = [];
/** @var list<array{directory: string, package: string, mirror: string, publish: bool}> $packages */
$packages = [];
/** @var array<string, string> $autoload */
$autoload = [];
/** @var array<string, string> $autoloadDev */
$autoloadDev = [];

$directories = glob($root . '/packages/*', GLOB_ONLYDIR) ?: [];
sort($directories);

foreach ($directories as $path) {
    $directory = basename($path);
    $file = $path . '/composer.json';

    if (!is_file($file)) {
        $errors[] = "packages/{$directory}: no composer.json";

        continue;
    }

    $manifest = manifest($file);
    $name = $manifest['name'] ?? null;

    if (!is_string($name)) {
        $errors[] = "packages/{$directory}/composer.json: no \"name\"";

        continue;
    }

    // Katalog adı, paket adı və mirror adı bir-birindən çıxarılır; uyğunsuzluq
    // split zamanı yanlış repo-ya push deməkdir.
    if ($name !== VENDOR . '/' . $directory) {
        $errors[] = sprintf(
            'packages/%s/composer.json: name is "%s", expected "%s/%s" to match the directory',
            $directory,
            $name,
            VENDOR,
            $directory,
        );
    }

    if (isset($manifest['version'])) {
        $errors[] = "packages/{$directory}/composer.json: remove \"version\" — Packagist reads the Git tag";
    }

    $own = settings($manifest);
    $mirror = $own['mirror'] ?? null;
    $publish = $own['publish'] ?? true;

    $packages[] = [
        'directory' => $directory,
        'package' => $name,
        'mirror' => is_string($mirror) ? $mirror : MIRROR_PREFIX . $directory,
        'publish' => $publish !== false,
    ];

    foreach (psr4($manifest, 'autoload') as $namespace => $relative) {
        $autoload[$namespace] = 'packages/' . $directory . '/' . ltrim($relative, './');
    }

    foreach (psr4($manifest, 'autoload-dev') as $namespace => $relative) {
        $autoloadDev[$namespace] = 'packages/' . $directory . '/' . ltrim($relative, './');
    }
}

if ($packages === []) {
    fwrite(STDERR, "No packages found under packages/.\n");
    exit(1);
}

ksort($autoload);
ksort($autoloadDev);

/**
 * Yolu müqayisə üçün normallaşdırır.
 *
 * Windows-da `\\` və `/` qarışıq gəlir, üstəlik hərf reyestri və qısa (8.3) yollar
 * fərqli ola bilər. Yol mövcuddursa `realpath()` bunların hamısını həll edir; yoxdursa
 * sadə sətir normallaşdırmasına düşürük.
 */
function normalise(string $path): string
{
    $real = realpath($path);

    return rtrim(str_replace('\\', '/', $real === false ? $path : $real), '/');
}

// ---------------------------------------------------------------------------
// Kök autoload xəritəsinin müqayisəsi
// ---------------------------------------------------------------------------

$rootFile = $root . '/composer.json';
$rootManifest = manifest($rootFile);

$currentAutoload = psr4($rootManifest, 'autoload');
$currentAutoloadDev = psr4($rootManifest, 'autoload-dev');
ksort($currentAutoload);
ksort($currentAutoloadDev);

$inSync = $currentAutoload === $autoload && $currentAutoloadDev === $autoloadDev;

// ---------------------------------------------------------------------------
// Generasiya olunmuş autoloader-in müqayisəsi
//
// `composer.json` düzgün ola bilər, lakin `vendor/composer/autoload_psr4.php`
// köhnə qala bilər — yeni paket əlavə edib `composer dump-autoload` çağırmadıqda
// məhz belə olur. Nəticə PHPUnit-də onlarla "Class not found" xətasıdır, halbuki
// heç bir konfiqurasiya səhv deyil.
// ---------------------------------------------------------------------------

/** @var list<string> $stale */
$stale = [];
$generatedFile = $root . '/vendor/composer/autoload_psr4.php';
$hasVendor = is_file($generatedFile);

if ($hasVendor) {
    /** @var mixed $generated */
    $generated = require $generatedFile;

    foreach ($autoload + $autoloadDev as $namespace => $relative) {
        $expected = normalise($root . '/' . rtrim($relative, '/'));
        $paths = is_array($generated) ? ($generated[$namespace] ?? null) : null;

        if (!is_array($paths)) {
            $stale[] = sprintf('%s is missing from the generated autoloader', $namespace);

            continue;
        }

        $found = false;

        foreach ($paths as $path) {
            if (is_string($path) && normalise($path) === $expected) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            $stale[] = sprintf('%s does not point at %s', $namespace, $relative);
        }
    }
}

// ---------------------------------------------------------------------------
// Çıxış
// ---------------------------------------------------------------------------

if ($errors !== [] && $mode !== '--json') {
    foreach ($errors as $error) {
        fwrite(STDERR, "ERROR  {$error}\n");
    }

    fwrite(STDERR, sprintf("\n%d problem(s).\n", count($errors)));
    exit(1);
}

if ($mode === '--json') {
    if ($errors !== []) {
        foreach ($errors as $error) {
            fwrite(STDERR, "ERROR  {$error}\n");
        }

        exit(1);
    }

    $publishable = array_values(array_filter($packages, static fn (array $p): bool => $p['publish']));

    echo json_encode(
        array_map(
            static fn (array $p): array => [
                'directory' => $p['directory'],
                'package' => $p['package'],
                'mirror' => $p['mirror'],
            ],
            $publishable,
        ),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
    ), "\n";

    exit(0);
}

if ($mode === '--sync') {
    $raw = (string) file_get_contents($rootFile);
    /** @var array<string, mixed> $updated */
    $updated = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    $updated['autoload'] = ['psr-4' => $autoload];
    $updated['autoload-dev'] = ['psr-4' => $autoloadDev];

    file_put_contents(
        $rootFile,
        json_encode(
            $updated,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) . "\n",
    );

    echo $inSync
        ? "composer.json autoload was already in sync.\n"
        : "composer.json autoload updated. Run `composer dump-autoload`.\n";

    exit(0);
}

if ($mode === '--check') {
    if ($inSync && $stale === []) {
        printf(
            "OK: %d packages, root autoload is in sync%s.\n",
            count($packages),
            $hasVendor ? ' (composer.json and vendor/)' : ' (vendor/ not installed, skipped)',
        );
        exit(0);
    }

    if ($inSync) {
        fwrite(STDERR, "composer.json is correct, but the GENERATED autoloader is stale:\n\n");

        foreach ($stale as $entry) {
            fwrite(STDERR, "  {$entry}\n");
        }

        fwrite(STDERR, "\nRun `composer dump-autoload`.\n");
        exit(1);
    }

    fwrite(STDERR, "The root composer.json autoload map does not match packages/.\n\n");
    fwrite(STDERR, "Expected \"autoload\".\"psr-4\":\n");

    foreach ($autoload as $namespace => $directory) {
        $marker = ($currentAutoload[$namespace] ?? null) === $directory ? ' ' : '>';
        fwrite(STDERR, sprintf("  %s \"%s\": \"%s\"\n", $marker, addslashes($namespace), $directory));
    }

    foreach (array_keys($currentAutoload) as $namespace) {
        if (!isset($autoload[$namespace])) {
            fwrite(STDERR, sprintf("  - \"%s\" is listed but no package declares it\n", addslashes($namespace)));
        }
    }

    fwrite(STDERR, "\nRun `composer sync-packages` to fix it.\n");
    exit(1);
}

printf("%d package(s) under packages/:\n\n", count($packages));
printf("  %-12s  %-20s  %-28s  %s\n", 'DIRECTORY', 'PACKAGE', 'MIRROR', 'PUBLISHED');
printf("  %-12s  %-20s  %-28s  %s\n", str_repeat('-', 12), str_repeat('-', 20), str_repeat('-', 28), '---------');

foreach ($packages as $package) {
    printf(
        "  %-12s  %-20s  %-28s  %s\n",
        $package['directory'],
        $package['package'],
        $package['mirror'],
        $package['publish'] ? 'yes' : 'no',
    );
}

if (!$inSync) {
    printf("\nRoot autoload is OUT OF SYNC — run `composer sync-packages`.\n");
    exit(1);
}

if ($stale !== []) {
    printf("\ncomposer.json is correct, but vendor/ is stale — run `composer dump-autoload`.\n");
    exit(1);
}

printf("\nRoot autoload is in sync.\n");
exit(0);
