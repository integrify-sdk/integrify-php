<?php

declare(strict_types=1);

/**
 * `docs/az/` altındakı **API referansını** mənbədən yenidən qurur.
 *
 * Python tərəfdə bu işi `mkdocstrings` görür: `::: integrify.ecustoms.schemas.request`
 * yazırsan, o da docstring-ləri və pydantic field-lərini oxuyub səhifəni qurur.
 * **PHP üçün mkdocstrings handler-i yoxdur** (rəsmi siyahı: C, Crystal, GitHub
 * Actions, Python, MATLAB, TypeScript, VBA və shell), ona görə bu skript onun
 * yerini tutur.
 *
 * Hazır PHP alətləri (phpDocumentor, Doctum) də uyğun gəlmir: onlar
 * `public ?string $trackingNo` yazacaq, lakin bu property-nin **məftildə**
 * `trackinG_NO` adlandığını bilmirlər. Halbuki bu paketlərdə oxucu üçün ən vacib
 * fakt məhz odur. Həmin məlumat `#[Field]` atributundadır və yalnız refleksiya ilə
 * görünür — `Mapper::describe()` da eyni şeyi edir.
 *
 * Usage:
 *     php bin/docs.php            api referansını və mkdocs naviqasiyasını yazır
 *     php bin/docs.php --check    yazılmış fayllar mənbə ilə sinxrondurmu (CI)
 *
 * Exit: 0 = qaydasındadır, 1 = fərq var (və ya yazıla bilmədi)
 */

use Integrify\Dto\Attribute\Field;

$root = dirname(__DIR__);
$mode = $argv[1] ?? '--write';

if (!in_array($mode, ['--write', '--check'], true)) {
    fwrite(STDERR, "Unknown option {$mode}. Use --write or --check.\n");
    exit(1);
}

/** Sənədlərin yazıldığı yer — Python repolarındakı `docs/az/` ilə eyni forma. */
const DOCS_DIR = 'docs/az/docs';

/** Naviqasiyanın yazıldığı fayl. */
const MKDOCS_FILE = 'docs/az/mkdocs.yml';

/** Naviqasiya bu iki sətrin arasına yazılır. */
const NAV_BEGIN = '# >>> bin/docs.php';
const NAV_END = '# <<< bin/docs.php';

/** Hər paketin səhifə başlıqları — bu sıra ilə naviqasiyaya düşür. */
const SECTIONS = [
    'client' => 'Klient',
    'config' => 'Konfiqurasiya',
    'request' => 'Sorğu DTO-ları',
    'response' => 'Cavab DTO-ları',
    'enums' => 'Enum-lar',
    'errors' => 'Xətalar',
    'other' => 'Digər obyektlər',
];

// ------------------------------------------------------------------------------------------- //
//  Autoload                                                                                    //
// ------------------------------------------------------------------------------------------- //

/**
 * Refleksiya üçün autoloader.
 *
 * `vendor/` varsa ondan istifadə olunur; yoxdursa PSR-4 xəritəsi birbaşa
 * `packages/*` qovluqlarından qurulur. Beləliklə sənədləri `composer install`
 * etmədən də yenidən qurmaq olar.
 */
function bootstrap(string $root): void
{
    $vendor = $root . '/vendor/autoload.php';

    if (is_file($vendor)) {
        require_once $vendor;

        return;
    }

    /** @var array<string, string> $prefixes */
    $prefixes = [];

    foreach (glob($root . '/packages/*/composer.json') ?: [] as $file) {
        $raw = file_get_contents($file);

        if ($raw === false) {
            continue;
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode($raw, true) ?? [];
        /** @var array<string, mixed> $autoload */
        $autoload = is_array($manifest['autoload'] ?? null) ? $manifest['autoload'] : [];
        /** @var array<string, mixed> $psr4 */
        $psr4 = is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

        foreach ($psr4 as $prefix => $path) {
            if (is_string($path)) {
                $prefixes[$prefix] = dirname($file) . '/' . rtrim($path, '/') . '/';
            }
        }
    }

    // Uzun prefiks əvvəl gəlməlidir: `Integrify\Lsim\` `Integrify\`-dan öncə.
    uksort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    spl_autoload_register(static function (string $class) use ($prefixes): void {
        foreach ($prefixes as $prefix => $dir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $path = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            if (is_file($path)) {
                require_once $path;

                return;
            }
        }
    });
}

// ------------------------------------------------------------------------------------------- //
//  Docblock                                                                                    //
// ------------------------------------------------------------------------------------------- //

/**
 * Docblock-u xülasə mətninə və teq-lərə ayırır.
 *
 * Tam PHPDoc parser deyil və olmamalıdır: bu repoda docblock-ların forması
 * konvensiyadır (`@param ad Təsvir`, `@throws Class Səbəb`), ona görə sadə
 * ayırıcı kifayətdir və asılılıq gətirmir.
 *
 * @return array{summary: string, tags: array<string, list<string>>}
 */
function docblock(string|false $raw): array
{
    if (!is_string($raw) || $raw === '') {
        return ['summary' => '', 'tags' => []];
    }

    $body = preg_replace('#^\s*/\*\*|\*/\s*$#', '', $raw) ?? '';
    $lines = [];

    foreach (explode("\n", $body) as $line) {
        $lines[] = preg_replace('/^\s*\*ic?\s?/', '', preg_replace('/^\s*\*\s?/', '', $line) ?? '') ?? '';
    }

    $summary = [];
    /** @var array<string, list<string>> $tags */
    $tags = [];
    $current = null;

    foreach ($lines as $line) {
        if (preg_match('/^@(\w+)\s*(.*)$/', trim($line), $match) === 1) {
            $current = $match[1];
            $tags[$current][] = $match[2];

            continue;
        }

        if ($current !== null) {
            // Teq-in davamı: əvvəlki sətrə qoşulur. `array_pop()` + `[]=` siyahını
            // siyahı olaraq saxlayır; indekslə yazmaq onu adi massivə çevirərdi.
            $previous = array_pop($tags[$current]) ?? '';
            $tags[$current][] = trim($previous . ' ' . trim($line));

            continue;
        }

        $summary[] = $line;
    }

    return ['summary' => trim(implode("\n", $summary)), 'tags' => $tags];
}

/**
 * Docblock-un yalnız **birinci abzasını** qaytarır — cədvəl xanası üçün.
 */
function firstParagraph(string $summary): string
{
    $paragraph = explode("\n\n", trim($summary))[0];

    return trim(str_replace("\n", ' ', $paragraph));
}

/**
 * `@param` teq-lərini `ad => təsvir` xəritəsinə çevirir.
 *
 * @param array<string, list<string>> $tags
 *
 * @return array<string, string>
 */
function paramDescriptions(array $tags): array
{
    $descriptions = [];

    foreach ($tags['param'] ?? [] as $line) {
        // `@param list<GoodsItem> $goodsList Bağlamadakı mallar.`
        if (preg_match('/\$(\w+)\s*(.*)$/s', $line, $match) === 1) {
            $descriptions[$match[1]] = trim($match[2]);
        }
    }

    return $descriptions;
}

/**
 * `@param` teq-lərindəki **tipləri** qaytarır (`list<GoodsItem>` kimi).
 *
 * @param array<string, list<string>> $tags
 *
 * @return array<string, string>
 */
function paramTypes(array $tags): array
{
    $types = [];

    foreach ($tags['param'] ?? [] as $line) {
        if (preg_match('/^(\S+)\s+\$(\w+)/', $line, $match) === 1) {
            $types[$match[2]] = $match[1];
        }
    }

    return $types;
}

// ------------------------------------------------------------------------------------------- //
//  Tiplər                                                                                      //
// ------------------------------------------------------------------------------------------- //

/**
 * Tipi qısa, oxunaqlı formada yazır: `Integrify\Lsim\Enum\Status` -> `Status`.
 */
function shortType(?ReflectionType $type, ?string $hint = null): string
{
    // Docblock tipi daha dəqiqdir (`list<GoodsItem>` vs `array`), varsa onu götürürük.
    if ($hint !== null && $hint !== '') {
        return preg_replace('/[\w\\\\]+\\\\(\w+)/', '$1', $hint) ?? $hint;
    }

    if ($type instanceof ReflectionNamedType) {
        $name = $type->getName();
        $short = str_contains($name, '\\') ? substr(strrchr($name, '\\') ?: $name, 1) : $name;

        return ($type->allowsNull() && $name !== 'null' && $name !== 'mixed' ? '?' : '') . $short;
    }

    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
        $glue = $type instanceof ReflectionUnionType ? '|' : '&';
        $parts = [];

        foreach ($type->getTypes() as $member) {
            $parts[] = shortType($member);
        }

        return implode($glue, $parts);
    }

    return 'mixed';
}

/**
 * Mətni markdown cədvəlinin xanasına təhlükəsiz yazır.
 *
 * Xanadakı `|` xananı bölür, ona görə `?string|int` kimi tiplər escape olunmalıdır;
 * sətir sonu isə cərgəni bölür.
 */
function cell(string $text): string
{
    return trim(str_replace(['|', "\n"], ['\\|', ' '], $text));
}

/**
 * `#[Field]` qaydalarını insan üçün yazır.
 */
function constraints(?Field $field): string
{
    if ($field === null) {
        return '';
    }

    $rules = [];

    if ($field->minLength !== null || $field->maxLength !== null) {
        $rules[] = sprintf(
            'uzunluq %s–%s',
            $field->minLength ?? '0',
            $field->maxLength ?? '∞',
        );
    }

    if ($field->pattern !== null) {
        $rules[] = '`' . $field->pattern . '`';
    }

    if ($field->min !== null || $field->max !== null) {
        $rules[] = sprintf('dəyər %s–%s', $field->min ?? '-∞', $field->max ?? '∞');
    }

    if ($field->minItems !== null || $field->maxItems !== null) {
        $rules[] = sprintf('%s–%s element', $field->minItems ?? '0', $field->maxItems ?? '∞');
    }

    return implode(', ', $rules);
}

// ------------------------------------------------------------------------------------------- //
//  Class-ların tapılması                                                                       //
// ------------------------------------------------------------------------------------------- //

/**
 * Qovluqdakı class adlarını PSR-4 yolundan çıxarır.
 *
 * @return list<class-string>
 */
function classesIn(string $dir, string $prefix, string $namespace): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $classes = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    /** @var SplFileInfo $file */
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($prefix) + 1, -4);
        /** @var class-string $class */
        $class = $namespace . str_replace('/', '\\', $relative);

        if (class_exists($class) || enum_exists($class) || interface_exists($class)) {
            $classes[] = $class;
        }
    }

    sort($classes);

    return $classes;
}

// ------------------------------------------------------------------------------------------- //
//  Keçidlər                                                                                    //
// ------------------------------------------------------------------------------------------- //

/**
 * Docblock-dakı `[`Foo`](Foo.php)` keçidlərini sayta uyğunlaşdırır.
 *
 * Docblock-larda qonşu fayla nisbi keçid verilir — IDE-də işləyir, saytda isə yoxdur:
 * `Foo.php` sənəd faylı deyil. Sinif sənədlərdədirsə keçid onun başlığına yönəldilir,
 * deyilsə keçid tamamilə götürülür və yalnız mətn qalır. Sındırılmış keçid saxlamaq
 * `mkdocs --strict`-i dayandırır, və oxucunu heç yerə aparmır.
 *
 * @param array<string, string> $pages qısa sinif adı => səhifə açarı
 */
function rewriteLinks(string $content, array $pages, string $currentPage): string
{
    return preg_replace_callback(
        '/\[([^\]]*)\]\((\w+)\.php\)/',
        static function (array $match) use ($pages, $currentPage): string {
            $target = $pages[$match[2]] ?? null;

            if ($target === null) {
                return $match[1];
            }

            $anchor = '#' . strtolower($match[2]);

            // Hər səhifə eyni qovluqdadır, ona görə nisbi keçid sadəcə fayl adıdır.
            return sprintf('[%s](%s)', $match[1], $target === $currentPage
                ? $anchor
                : $target . '.md' . $anchor);
        },
        $content,
    ) ?? $content;
}

// ------------------------------------------------------------------------------------------- //
//  Səhifələr                                                                                   //
// ------------------------------------------------------------------------------------------- //

/**
 * DTO-nun property cədvəli.
 *
 * @param class-string $class
 */
function dtoSection(string $class): string
{
    $reflection = new ReflectionClass($class);
    $meta = docblock($reflection->getDocComment());
    $constructor = $reflection->getConstructor();

    $lines = ['## ' . $reflection->getShortName(), ''];

    if ($meta['summary'] !== '') {
        $lines[] = $meta['summary'];
        $lines[] = '';
    }

    if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
        $lines[] = '_Field-i yoxdur._';
        $lines[] = '';

        return implode("\n", $lines);
    }

    $descriptions = paramDescriptions(docblock($constructor->getDocComment())['tags']);
    $types = paramTypes(docblock($constructor->getDocComment())['tags']);

    $lines[] = '| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |';
    $lines[] = '| :--- | :--- | :--- | :---: | :--- | :--- |';

    foreach ($constructor->getParameters() as $parameter) {
        $attributes = $parameter->getAttributes(Field::class);
        $field = $attributes === [] ? null : $attributes[0]->newInstance();
        // `#[Field]` yoxdursa, və ya adı verilməyibsə, məftildəki ad property adıdır.
        $wire = $field === null || $field->name === null ? $parameter->getName() : $field->name;
        $required = $parameter->isDefaultValueAvailable() ? '' : '✅';

        $lines[] = sprintf(
            '| `%s` | `%s` | `%s` | %s | %s | %s |',
            $parameter->getName(),
            cell($wire),
            cell(shortType($parameter->getType(), $types[$parameter->getName()] ?? null)),
            $required,
            cell(constraints($field)),
            cell($descriptions[$parameter->getName()] ?? ''),
        );
    }

    $lines[] = '';

    // Property olmayan public metodlar (`isSuccessful()`, `status()` və s.) da sənədə düşür.
    $helpers = [];

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isConstructor() || $method->isStatic() || $method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $helpers[] = sprintf(
            '| `%s()` | `%s` | %s |',
            $method->getName(),
            cell(shortType($method->getReturnType())),
            cell(firstParagraph(docblock($method->getDocComment())['summary'])),
        );
    }

    if ($helpers !== []) {
        $lines[] = '| Metod | Qaytarır | Nə edir |';
        $lines[] = '| :--- | :--- | :--- |';
        $lines = [...$lines, ...$helpers, ''];
    }

    return implode("\n", $lines);
}

/**
 * Enum-un case cədvəli.
 *
 * @param class-string $class
 */
function enumSection(string $class): string
{
    if (!enum_exists($class)) {
        return '';
    }

    $reflection = new ReflectionEnum($class);
    $meta = docblock($reflection->getDocComment());

    $lines = ['## ' . $reflection->getShortName(), ''];

    if ($meta['summary'] !== '') {
        $lines[] = $meta['summary'];
        $lines[] = '';
    }

    $lines[] = '| Case | Dəyər | Təsvir |';
    $lines[] = '| :--- | :--- | :--- |';

    foreach ($reflection->getCases() as $case) {
        $value = $case instanceof ReflectionEnumBackedCase ? $case->getBackingValue() : $case->getName();

        $lines[] = sprintf(
            '| `%s::%s` | `%s` | %s |',
            $reflection->getShortName(),
            $case->getName(),
            cell(is_string($value) ? $value : (string) $value),
            cell(firstParagraph(docblock($case->getDocComment())['summary'])),
        );
    }

    $lines[] = '';

    return implode("\n", $lines);
}

/**
 * Klientin metodları.
 *
 * @param class-string $class
 */
function clientSection(string $class): string
{
    $reflection = new ReflectionClass($class);
    $meta = docblock($reflection->getDocComment());

    $lines = ['# ' . $reflection->getShortName(), ''];

    if ($meta['summary'] !== '') {
        $lines[] = $meta['summary'];
        $lines[] = '';
    }

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isConstructor() || $method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $doc = docblock($method->getDocComment());
        $descriptions = paramDescriptions($doc['tags']);
        $types = paramTypes($doc['tags']);

        $lines[] = '## `' . $method->getName() . '()`';
        $lines[] = '';

        if ($doc['summary'] !== '') {
            $lines[] = $doc['summary'];
            $lines[] = '';
        }

        $signature = [];

        foreach ($method->getParameters() as $parameter) {
            $signature[] = sprintf(
                '%s%s $%s',
                $parameter->isVariadic() ? '' : '',
                shortType($parameter->getType(), $types[$parameter->getName()] ?? null),
                ($parameter->isVariadic() ? '...' : '') . $parameter->getName(),
            );
        }

        $lines[] = '```php';
        $lines[] = sprintf(
            '%s(%s): %s',
            $method->getName(),
            implode(', ', $signature),
            shortType($method->getReturnType(), ($doc['tags']['return'][0] ?? null) !== null
                ? explode(' ', trim($doc['tags']['return'][0]))[0]
                : null),
        );
        $lines[] = '```';
        $lines[] = '';

        if ($method->getNumberOfParameters() > 0) {
            $lines[] = '| Parametr | Tip | Məcburi | Təsvir |';
            $lines[] = '| :--- | :--- | :---: | :--- |';

            foreach ($method->getParameters() as $parameter) {
                $lines[] = sprintf(
                    '| `$%s` | `%s` | %s | %s |',
                    ($parameter->isVariadic() ? '...' : '') . $parameter->getName(),
                    cell(shortType($parameter->getType(), $types[$parameter->getName()] ?? null)),
                    $parameter->isDefaultValueAvailable() || $parameter->isVariadic() ? '' : '✅',
                    cell($descriptions[$parameter->getName()] ?? ''),
                );
            }

            $lines[] = '';
        }

        $throws = [];

        foreach ($doc['tags']['throws'] ?? [] as $line) {
            $parts = explode(' ', trim($line), 2);
            $throws[] = sprintf('- `%s`%s', $parts[0], isset($parts[1]) ? ' — ' . $parts[1] : '');
        }

        if ($throws !== []) {
            $lines[] = '**Atır:**';
            $lines[] = '';
            $lines = [...$lines, ...$throws, ''];
        }
    }

    return implode("\n", $lines);
}

/**
 * Bir neçə class-ı tək səhifəyə yığır.
 *
 * @param list<class-string> $classes
 * @param callable(class-string): string $render
 */
function page(string $title, string $intro, array $classes, callable $render): ?string
{
    if ($classes === []) {
        return null;
    }

    $lines = ['# ' . $title, ''];

    if ($intro !== '') {
        $lines[] = $intro;
        $lines[] = '';
    }

    foreach ($classes as $class) {
        $lines[] = $render($class);
    }

    return rtrim(implode("\n", $lines)) . "\n";
}

// ------------------------------------------------------------------------------------------- //
//  İcra                                                                                        //
// ------------------------------------------------------------------------------------------- //

bootstrap($root);

$json = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/bin/packages.php') . ' --json');

if (!is_string($json)) {
    fwrite(STDERR, "cannot run bin/packages.php --json\n");
    exit(1);
}

/** @var list<array{directory: string, package: string, mirror: string}> $packages */
$packages = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

/** @var array<string, string> $files yol => məzmun */
$files = [];
/** @var list<string> $nav */
$nav = [];

foreach ($packages as $package) {
    $directory = $package['directory'];
    $src = $root . '/packages/' . $directory . '/src';

    if (!is_dir($src)) {
        continue;
    }

    // PSR-4 prefiksi paketin öz manifestindən gəlir — ad qaydası burada təkrarlanmır.
    $raw = file_get_contents($root . '/packages/' . $directory . '/composer.json');
    /** @var array<string, mixed> $manifest */
    $manifest = is_string($raw) ? json_decode($raw, true) : [];
    /** @var array<string, mixed> $autoload */
    $autoload = is_array($manifest['autoload'] ?? null) ? $manifest['autoload'] : [];
    /** @var array<string, string> $psr4 */
    $psr4 = is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];
    $namespace = (string) array_key_first($psr4);

    $classes = classesIn($src, $src, $namespace);

    $clients = array_values(array_filter(
        $classes,
        static fn (string $class): bool => str_ends_with($class, 'Client'),
    ));
    $configs = array_values(array_filter(
        $classes,
        static fn (string $class): bool => str_ends_with($class, 'Config'),
    ));
    $requests = array_values(array_filter(
        $classes,
        static fn (string $class): bool => str_contains($class, '\\Dto\\Request\\'),
    ));
    $responses = array_values(array_filter(
        $classes,
        static fn (string $class): bool => str_contains($class, '\\Dto\\Response\\'),
    ));
    $enums = array_values(array_filter(
        $classes,
        static fn (string $class): bool => enum_exists($class),
    ));
    $errors = array_values(array_filter(
        $classes,
        static fn (string $class): bool => str_contains($class, '\\Exception\\'),
    ));

    // Heç bir qrupa düşməyən qalanlar: `FormPost`, `Callback`, `Page` və s.
    $grouped = [...$clients, ...$configs, ...$requests, ...$responses, ...$enums, ...$errors];
    $others = array_values(array_filter(
        $classes,
        static fn (string $class): bool => !in_array($class, $grouped, true)
            && !(new ReflectionClass($class))->isInterface(),
    ));

    // `core` paketində DTO-lar yoxdur; onun səhifəsi yalnız README-dir.
    $pages = [
        'client' => $clients === [] ? null : implode("\n", array_map(clientSection(...), $clients)),
        'config' => page(
            'Konfiqurasiya',
            '`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt '
            . 'yaradılarkən oxunur — sorğu atılarkən yox.',
            $configs,
            dtoSection(...),
        ),
        'request' => page(
            'Sorğu DTO-ları',
            'Bu obyektlər servisə **göndərilir**. `Məftildəki ad` sütunu payload-da hansı '
            . 'açarın görünəcəyini göstərir — PHP tərəfdəki ad heç vaxt məftilə çıxmır.',
            $requests,
            dtoSection(...),
        ),
        'response' => page(
            'Cavab DTO-ları',
            'Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və '
            . 'enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab '
            . 'validasiyadan keçməkdə davam edir.',
            $responses,
            dtoSection(...),
        ),
        'enums' => page('Enum-lar', '', $enums, enumSection(...)),
        'errors' => page(
            'Xətalar',
            'Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.',
            $errors,
            dtoSection(...),
        ),
        'other' => page('Digər obyektlər', '', $others, dtoSection(...)),
    ];

    // Hansı sinfin hansı səhifədə olduğunu bilmədən docblock keçidlərini
    // düzəltmək mümkün deyil, ona görə əvvəlcə indeks qurulur.
    /** @var array<string, string> $pageOf */
    $pageOf = [];

    foreach ([
        'client' => $clients,
        'config' => $configs,
        'request' => $requests,
        'response' => $responses,
        'enums' => $enums,
        'errors' => $errors,
        'other' => $others,
    ] as $key => $bucket) {
        foreach ($bucket as $class) {
            $pageOf[(new ReflectionClass($class))->getShortName()] = $key;
        }
    }

    $base = DOCS_DIR . '/integrations/' . $directory;

    // Paketin giriş səhifəsi README-nin özüdür — `pymdownx.snippets` onu daxil edir,
    // yəni mətn iki yerdə saxlanılmır və köhnəlmir.
    $files[$base . '/index.md'] = sprintf(
        "---\ntitle: %s\n---\n\n--8<-- \"packages/%s/README.md\"\n",
        $package['package'],
        $directory,
    );

    // mkdocs naviqasiyası `docs_dir`-ə nisbətən yazılır, repo kökünə yox.
    $navBase = substr($base, strlen(DOCS_DIR) + 1);
    $children = ['      - ' . $navBase . '/index.md'];

    foreach (SECTIONS as $key => $title) {
        $content = $pages[$key] ?? null;

        if ($content === null || trim($content) === '') {
            continue;
        }

        $files[$base . '/api-reference/' . $key . '.md'] = rewriteLinks($content, $pageOf, $key);
        $children[] = sprintf('          - %s: %s/api-reference/%s.md', $title, $navBase, $key);
    }

    $nav[] = '  - ' . $package['package'] . ':';
    $nav[] = array_shift($children);

    if ($children !== []) {
        $nav[] = '      - API Referansı:';
        $nav = [...$nav, ...$children];
    }
}

// Yazılan bütün fayllar `docs/` köküne nisbətəndir; nav-da da elə görünür.
$navBlock = implode("\n", [
    NAV_BEGIN,
    'nav:',
    '  - Ana səhifə: index.md',
    ...$nav,
    NAV_END,
]);

$mkdocs = file_get_contents($root . '/' . MKDOCS_FILE);

if (!is_string($mkdocs)) {
    fwrite(STDERR, 'cannot read ' . MKDOCS_FILE . "\n");
    exit(1);
}

$pattern = '/' . preg_quote(NAV_BEGIN, '/') . '.*?' . preg_quote(NAV_END, '/') . '/s';

if (preg_match($pattern, $mkdocs) !== 1) {
    fwrite(STDERR, MKDOCS_FILE . ' has no "' . NAV_BEGIN . '" … "' . NAV_END . "\" block.\n");
    exit(1);
}

$files[MKDOCS_FILE] = preg_replace($pattern, $navBlock, $mkdocs) ?? $mkdocs;

// `docs/` altındakı köhnə generasiya olunmuş fayllar da silinməlidir — paket adı
// dəyişəndə arxada qalan səhifə naviqasiyada görünməsə də saytda qalırdı.
$stale = [];

foreach (glob($root . '/' . DOCS_DIR . '/integrations/*/{index.md,api-reference/*.md}', GLOB_BRACE) ?: [] as $path) {
    $relative = substr($path, strlen($root) + 1);

    if (!isset($files[$relative])) {
        $stale[] = $relative;
    }
}

$differences = [];

foreach ($files as $path => $content) {
    $full = $root . '/' . $path;
    $existing = is_file($full) ? file_get_contents($full) : null;

    if ($existing === $content) {
        continue;
    }

    $differences[] = $path;

    if ($mode === '--write') {
        if (!is_dir(dirname($full))) {
            mkdir(dirname($full), 0o777, true);
        }

        file_put_contents($full, $content);
    }
}

foreach ($stale as $path) {
    $differences[] = $path . ' (stale)';

    if ($mode === '--write') {
        unlink($root . '/' . $path);
    }
}

if ($mode === '--check') {
    if ($differences === []) {
        echo "OK: docs are in sync with the source.\n";
        exit(0);
    }

    fwrite(STDERR, "Docs are out of date. Run `composer docs`:\n");

    foreach ($differences as $path) {
        fwrite(STDERR, '  ' . $path . "\n");
    }

    exit(1);
}

printf(
    "%d page(s) written, %d changed, %d stale removed.\n",
    count($files),
    count($differences) - count($stale),
    count($stale),
);
