<?php

declare(strict_types=1);

/*
 * Bütün sənəd saytını qurur: ictimai səhifələr + parol arxasındakı məxfi inteqrasiyalar.
 *
 * Nəticə (Netlify `publish` qovluğu — `docs/site`):
 *
 *     docs/site/                      ictimai sayt
 *     docs/site/private/<ad>/         məxfi inteqrasiya
 *
 * `/private/*` altındakı hər şeyi `netlify/edge-functions/private-docs.ts` qoruyur.
 *
 * Məxfi mənbə bu sıra ilə axtarılır (`docs/private.json`-dakı hər qeyd üçün):
 *
 *   1. `PRIVATE_DOCS_<AD>_PATH` — lokal checkout-un yolu.
 *   2. `PRIVATE_DOCS_TOKEN` — GitHub-dan gətirilir (yalnız oxu icazəsi olan token).
 *   3. Bu reponun yanındakı qovluq (`../<repo adı>`) — lokal development.
 *   4. Tapılmasa qeyd ATLANIR (fork PR-ləri, girişi olmayan töhfəçilər) — amma
 *      `PRIVATE_DOCS_REQUIRED=1` qoyulubsa, build dayanır.
 *
 * Sonuncu qayda vacibdir: `netlify.toml`-da production üçün `PRIVATE_DOCS_REQUIRED=1`
 * var, yəni token itsə sayt məxfi bölmələr olmadan səssizcə yayımlanmır.
 *
 * NİYƏ YAML PARSER YOXDUR. Python qarşılığı `mkdocs.yml`-ı oxuyub-yazır. Burada
 * konfiqurasiya MƏTN kimi işlənir: ictimai `mkdocs.yml`-ın `nav` bloku onsuz da
 * `bin/docs.php`-in markerləri ilə əhatələnib, məxfi reponun `nav`-ı isə faylın
 * sonundadır. İki blok mətn səviyyəsində birləşdirilir. Beləliklə nə PHP üçün YAML
 * asılılığı lazım gəlir, nə də `!!python/name:` teqlərini qoruyub saxlamaq problemi
 * yaranır — onlara heç toxunulmur.
 *
 * Usage:
 *     php bin/build-docs.php                 hamısı
 *     php bin/build-docs.php --public-only   məxfi inteqrasiyaları atla
 */

$root = dirname(__DIR__);

const REGISTRY = 'docs/private.json';
const PUBLIC_CONFIG = 'docs/az/mkdocs.yml';
const SITE = 'docs/site';
const CHECKOUTS = '.private';

/** `bin/docs.php` naviqasiyanı bu iki sətrin arasına yazır; biz də oranı əvəz edirik. */
const NAV_BEGIN = '# >>> bin/docs.php';
const NAV_END = '# <<< bin/docs.php';

// `$argv` yalnız `register_argc_argv` açıq olanda mövcuddur. CLI-da defolt açıqdır,
// amma PHP bunu zəmanət vermir, ona görə dəyər `$_SERVER`-dən oxunur və tiplənir.
$given = $_SERVER['argv'] ?? [];
$arguments = [];

foreach (is_array($given) ? array_slice($given, 1) : [] as $argument) {
    if (is_string($argument)) {
        $arguments[] = $argument;
    }
}

$publicOnly = in_array('--public-only', $arguments, true);

// ------------------------------------------------------------------------------------------- //
//  Köməkçilər                                                                                  //
// ------------------------------------------------------------------------------------------- //

function say(string $message): void
{
    echo '[docs] ' . $message . "\n";
}

/**
 * @return never
 */
function fail(string $message): void
{
    fwrite(STDERR, '[docs] ERROR: ' . $message . "\n");
    exit(1);
}

/**
 * @param list<string> $command
 * @param array<string, string>|null $env
 */
function run(array $command, ?string $cwd = null, ?array $env = null): void
{
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $descriptors, $pipes, $cwd, $env);

    if (!is_resource($process)) {
        fail('cannot start: ' . implode(' ', $command));
    }

    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status !== 0) {
        // Token `extraheader`-də ola bilər, ona görə əmrin özü çap olunmur.
        fail(sprintf("%s failed (%d):\n%s%s", $command[0], $status, (string) $out, (string) $err));
    }
}

function rmtree(string $path): void
{
    if (is_link($path) || (is_file($path) && !is_dir($path))) {
        unlink($path);

        return;
    }

    if (!is_dir($path)) {
        return;
    }

    /** @var Iterator<SplFileInfo> $items */
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        // `.git` obyektləri read-only olur; silməzdən əvvəl icazəni açırıq.
        if ($item->isDir() && !$item->isLink()) {
            @rmdir($item->getPathname());
        } else {
            @chmod($item->getPathname(), 0o666);
            @unlink($item->getPathname());
        }
    }

    rmdir($path);
}

/**
 * @param list<string> $skip qovluq adları
 */
function copytree(string $from, string $to, array $skip = []): void
{
    if (!is_dir($to) && !mkdir($to, 0o777, true) && !is_dir($to)) {
        fail("cannot create {$to}");
    }

    /** @var Iterator<string, SplFileInfo> $items */
    $items = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $file): bool => !in_array($file->getFilename(), $skip, true),
        ),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($items as $item) {
        $target = $to . '/' . substr($item->getPathname(), strlen($from) + 1);

        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0o777, true) && !is_dir($target)) {
                fail("cannot create {$target}");
            }

            continue;
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0o777, true);
        }

        copy($item->getPathname(), $target);
    }
}

function read(string $path): string
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        fail("cannot read {$path}");
    }

    return $contents;
}

/**
 * Massivdən sətir oxuyur.
 *
 * `json_decode(..., true)` hər dəyəri `mixed` verir və `(string)` ilə çevirmək onu
 * yalnız gizlədir: massiv və ya obyekt gələndə xəta atılır. Burada sətir deyilsə boş
 * qaytarılır — sızma yoxlaması üçün bu düzgün davranışdır.
 */
function asText(mixed $value): string
{
    return is_string($value) ? $value : '';
}

function envKey(string $name): string
{
    return (string) preg_replace('/[^A-Z0-9]/', '_', strtoupper($name));
}

function env(string $name): ?string
{
    $value = getenv($name);

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/** `mkdocs build --strict`, həmişə konfiqurasiyanın öz qovluğundan. */
function mkdocs(string $config): void
{
    say('mkdocs build -f ' . $config);
    run(['mkdocs', 'build', '--strict', '-f', basename($config)], dirname($config));
}

// ------------------------------------------------------------------------------------------- //
//  Konfiqurasiya mətni                                                                         //
// ------------------------------------------------------------------------------------------- //

/**
 * Faylın sonundakı `nav:` blokunu olduğu kimi qaytarır.
 *
 * `nav:` mkdocs konfiqurasiyasında sonuncu açardır (hər iki repoda), ona görə sətrin
 * əvvəlindəki `nav:`-dan faylın sonuna qədər olan hissə tam nav-dır. Növbəti üst
 * səviyyə açar görünsə, orada dayanırıq — səhv birləşmədənsə, aydın xəta yaxşıdır.
 */
function navBlock(string $config, string $where): string
{
    $lines = explode("\n", $config);
    $collected = [];
    $inside = false;

    foreach ($lines as $line) {
        if (!$inside) {
            if (preg_match('/^nav:\s*$/', $line) === 1) {
                $inside = true;
            }

            continue;
        }

        // Üst səviyyə açar (girinti yoxdur, şərh deyil) — nav bitdi.
        if (preg_match('/^[A-Za-z_][\w-]*:/', $line) === 1) {
            break;
        }

        $collected[] = $line;
    }

    if (!$inside) {
        fail("{$where} has no `nav:` block");
    }

    return rtrim(implode("\n", $collected), "\n");
}

/**
 * Nav sətirlərindəki `.md` yollarını ictimai saytın MÜTLƏQ url-lərinə çevirir.
 *
 * Məxfi sayt ayrıca qurulur və ictimai səhifələr orada yoxdur — nisbi keçid 404
 * verərdi. `--strict` isə mövcud olmayan səhifəyə nisbi keçidi xəta sayır, ona görə
 * mütləq url həm də build-in keçməsi üçün lazımdır.
 *
 * `a/b.md` -> `/a/b/`, `a/index.md` -> `/a/`.
 *
 * ADSIZ QEYDLƏR. İctimai nav-da paketin giriş səhifəsi adsız yazılır
 * (`- integrations/epoint/index.md`), çünki `navigation.indexes` onu bölmənin ÖZÜ
 * edir və ad bölmə başlığından gəlir. Burada isə o, mütləq keçidə çevrilir — artıq
 * səhifə deyil, sadəcə link, və `navigation.indexes` linki bölməyə yığmır. Adsız
 * qalsa, yan paneldə fayl yolu görünərdi, ona görə əhatə edən bölmənin adı götürülür.
 */
function linkToPublic(string $nav): string
{
    $out = [];
    /** @var list<array{indent: int, label: string}> $sections */
    $sections = [];

    foreach (explode("\n", $nav) as $line) {
        $indent = strlen($line) - strlen(ltrim($line, ' '));

        // Bölmə başlığı: `- Ad:` (səhifə yolu yoxdur).
        if (preg_match('/^\s*-\s*(.+?):\s*$/', $line, $header) === 1) {
            while ($sections !== [] && end($sections)['indent'] >= $indent) {
                array_pop($sections);
            }

            $sections[] = ['indent' => $indent, 'label' => trim($header[1])];
            $out[] = $line;

            continue;
        }

        if (preg_match('/^(\s*-\s*)(?:([^:]+):\s*)?(\S+\.md)\s*$/', $line, $match) !== 1) {
            $out[] = $line;

            continue;
        }

        [, $dash, $label, $page] = $match;
        $url = '/' . preg_replace('/(?:^|(?<=\/))index\.md$|\.md$/', '', $page);
        $url = rtrim($url, '/') . '/';
        $url = $url === '//' ? '/' : $url;

        if (trim((string) $label) === '') {
            while ($sections !== [] && end($sections)['indent'] >= $indent) {
                array_pop($sections);
            }

            $label = $sections === [] ? basename(dirname($page)) : end($sections)['label'];
        }

        $out[] = $dash . trim((string) $label) . ': ' . $url;
    }

    return implode("\n", $out);
}

/** Bloku verilən qədər sağa sürüşdürür (boş sətirlərə toxunmadan). */
function indent(string $text, int $spaces): string
{
    $pad = str_repeat(' ', $spaces);
    $out = [];

    foreach (explode("\n", $text) as $line) {
        $out[] = trim($line) === '' ? $line : $pad . $line;
    }

    return implode("\n", $out);
}

/** Üst səviyyə skalyar açarı əvəz edir, yoxdursa əlavə edir. */
function setKey(string $config, string $key, string $value): string
{
    $line = $key . ': ' . $value;
    $pattern = '/^' . preg_quote($key, '/') . ':[^\n]*$/m';

    if (preg_match($pattern, $config) === 1) {
        return (string) preg_replace($pattern, $line, $config, 1);
    }

    return $line . "\n" . $config;
}

// ------------------------------------------------------------------------------------------- //
//  Məxfi mənbə                                                                                 //
// ------------------------------------------------------------------------------------------- //

/**
 * `ref`-i token ilə gətirir, tokeni nə diskə, nə də `argv`-ə yazmadan.
 *
 * Token `git config` dəyəri kimi MÜHİT DƏYİŞƏNİ ilə ötürülür: əmr sətrində getsəydi,
 * eyni maşındakı hər prosesin `ps` çıxışında görünərdi.
 */
function fetchFromGithub(string $repo, string $ref, string $token, string $dest): void
{
    $basic = base64_encode('x-access-token:' . $token);

    // Mühit `getenv()`-dən götürülür, `$_ENV`-dən yox. `$_ENV` `variables_order`
    // parametrindən asılıdır və çox vaxt BOŞ olur — onda git nə `PATH`, nə proxy
    // dəyişənlərini görər və fetch anlaşılmaz şəkildə uğursuz olardı.
    $env = getenv();
    $env['GIT_TERMINAL_PROMPT'] = '0';
    $env['GIT_CONFIG_COUNT'] = '1';
    $env['GIT_CONFIG_KEY_0'] = 'http.https://github.com/.extraheader';
    $env['GIT_CONFIG_VALUE_0'] = 'AUTHORIZATION: basic ' . $basic;

    mkdir($dest, 0o777, true);
    run(['git', 'init', '--quiet'], $dest, $env);
    run(['git', 'remote', 'add', 'origin', 'https://github.com/' . $repo . '.git'], $dest, $env);
    run(['git', 'fetch', '--quiet', '--depth', '1', 'origin', $ref], $dest, $env);
    run(['git', 'checkout', '--quiet', '--detach', 'FETCH_HEAD'], $dest, $env);
}

/**
 * @param array{repo: string, ref?: string, label?: string} $entry
 */
function resolveSource(string $root, string $name, array $entry): ?string
{
    $dest = $root . '/' . CHECKOUTS . '/' . $name;
    rmtree($dest);

    $explicit = env('PRIVATE_DOCS_' . envKey($name) . '_PATH');

    if ($explicit !== null) {
        $path = realpath($explicit);

        if ($path === false || !is_dir($path)) {
            fail("{$name}: PRIVATE_DOCS_" . envKey($name) . "_PATH is not a directory: {$explicit}");
        }

        say("{$name}: using local checkout {$path}");
        copytree($path, $dest, ['.git', 'vendor', 'node_modules', 'site', '.private']);

        return $dest;
    }

    $token = env('PRIVATE_DOCS_TOKEN');

    if ($token !== null) {
        $ref = $entry['ref'] ?? 'main';
        say("{$name}: fetching {$entry['repo']}@{$ref}");
        fetchFromGithub($entry['repo'], $ref, $token, $dest);

        return $dest;
    }

    $parts = explode('/', $entry['repo']);
    $sibling = dirname($root) . '/' . end($parts);

    if (is_dir($sibling)) {
        say("{$name}: using sibling checkout {$sibling}");
        copytree($sibling, $dest, ['.git', 'vendor', 'node_modules', 'site', '.private']);

        return $dest;
    }

    return null;
}

// ------------------------------------------------------------------------------------------- //
//  Məxfi build                                                                                 //
// ------------------------------------------------------------------------------------------- //

/**
 * Məxfi saytı qurub `docs/site/private/<ad>/` altına qoyur.
 *
 * Tema, rənglər və markdown genişlənmələri İCTİMAİ konfiqurasiyadan gəlir — məxfi
 * repodan yalnız səhifələr və `nav` götürülür. Beləliklə iki sayt eyni görünür və
 * məxfi reponun teması yenilənməsə də geridə qalmır.
 *
 * @param array{repo: string, ref?: string, label?: string} $entry
 *
 * @return array{out: string, pages: list<string>}
 */
function buildPrivate(string $root, string $name, array $entry, string $checkout, string $siteUrl): array
{
    $config = $checkout . '/docs/az/mkdocs.yml';

    if (!is_file($config)) {
        fail("{$name}: docs/az/mkdocs.yml not found in {$entry['repo']}");
    }

    // API referansı məxfi mənbədən YENİDƏN qurulur. Python tərəfdə bunu mkdocstrings
    // build zamanı edir; PHP-də generator ayrıca addımdır, ona görə burada çağırılır.
    // Repodakı commit-ə etibar etmək köhnəlmiş səhifə yayımlamaq demək olardı.
    say("{$name}: regenerating the API reference");
    run([PHP_BINARY, $root . '/bin/docs.php', '--package=' . $checkout]);

    $urlPath = 'private/' . $name . '/';
    $build = $checkout . '/_site';
    rmtree($build);

    // Səhifələr öz yol strukturunu saxlayır (`integrations/<ad>/...`), ona görə məxfi
    // nav-ı olduğu kimi köçürmək olur — yolları yenidən yazmağa ehtiyac yoxdur.
    copytree($checkout . '/docs/az/docs', $build . '/docs');

    $publicDocs = $root . '/docs/az/docs';

    if (is_dir($publicDocs . '/assets')) {
        copytree($publicDocs . '/assets', $build . '/docs/assets');
    }

    $merged = read($root . '/' . PUBLIC_CONFIG);
    $publicNav = linkToPublic(navBlock($merged, PUBLIC_CONFIG));
    $privateNav = navBlock(read($config), $name . ':docs/az/mkdocs.yml');

    $label = $entry['label'] ?? $name;
    $navText = implode("\n", [
        'nav:',
        $publicNav,
        '  - ' . $label . ':',
        indent($privateNav, 4),
    ]);

    $pattern = '/' . preg_quote(NAV_BEGIN, '/') . '.*?' . preg_quote(NAV_END, '/') . '/s';

    if (preg_match($pattern, $merged) !== 1) {
        fail(PUBLIC_CONFIG . ' has no generated nav block');
    }

    $merged = (string) preg_replace($pattern, $navText, $merged, 1);
    $merged = setKey($merged, 'site_url', $siteUrl . $urlPath);
    $merged = setKey($merged, 'docs_dir', 'docs');
    $merged = setKey($merged, 'site_dir', 'site');

    // `edit_uri` ictimai repoya baxır; məxfi səhifə üçün "Redaktə et" düyməsi ictimai
    // repoda mövcud olmayan fayla aparardı.
    $merged = (string) preg_replace('/^edit_uri:[^\n]*$/m', 'edit_uri: \'\'', $merged);

    file_put_contents($build . '/mkdocs.yml', $merged);
    mkdocs($build . '/mkdocs.yml');

    $out = $root . '/' . SITE . '/' . $urlPath;
    rmtree($out);
    copytree($build . '/site', $out);

    say("{$name} -> /{$urlPath}");

    return ['out' => $out, 'pages' => pagePaths($out)];
}

/**
 * Saytdakı hər səhifənin yolu (`a/b/`), `index.html` fayllarına görə.
 *
 * @return list<string>
 */
function pagePaths(string $site): array
{
    $pages = [];

    /** @var Iterator<SplFileInfo> $items */
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($site, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($items as $item) {
        if ($item->getFilename() !== 'index.html') {
            continue;
        }

        $directory = dirname($item->getPathname());
        $pages[] = $directory === rtrim($site, '/')
            ? ''
            : substr($directory, strlen(rtrim($site, '/')) + 1) . '/';
    }

    sort($pages);

    return $pages;
}

// ------------------------------------------------------------------------------------------- //
//  Sızma yoxlaması                                                                             //
// ------------------------------------------------------------------------------------------- //

/**
 * Məxfi məzmundan bir şey ictimai çıxışa düşübsə, build dayanır.
 *
 * Dörd fərqli yoldan sızma olur və hər biri ayrıca yoxlanılır:
 *
 *   1. `sitemap.xml` — axtarış motorlarına birbaşa ünvan verir.
 *   2. İctimai axtarış indeksi məxfi səhifənin ÜNVANINI saxlayır.
 *   3. İctimai axtarış indeksi məxfi səhifənin MƏTNİNİ saxlayır. İndeks parol
 *      tələb olunmadan yüklənir, yəni səhifə qorunsa da mətni açıq qala bilər —
 *      bu, ən sakit və ən təhlükəli sızma yoludur.
 *   4. İctimai HTML/JSON məxfi paketin namespace-ini yazır.
 *
 * @param list<array{name: string, out: string, pages: list<string>, checkout: string}> $builds
 */
function checkLeaks(string $root, array $builds): void
{
    $site = $root . '/' . SITE;
    $problems = [];

    $sitemap = is_file($site . '/sitemap.xml') ? read($site . '/sitemap.xml') : '';

    if (str_contains($sitemap, '/private/')) {
        $problems[] = 'public sitemap mentions /private/';
    }

    // İNDEKS XAM MƏTN KİMİ OXUNMUR. Material onu Python-un `json.dump`-ı ilə yazır,
    // yəni ASCII olmayan hərflər `\u011f` kimi qaçırılır: `sorğuda` xam faylda
    // `sor\u011fuda` olur və hərfi axtarış heç vaxt tapmır. Ona görə əvvəlcə decode
    // edilir, sonra müqayisə olunur.
    /** @var array<string, string> $publicPages ünvan => mətn */
    $publicPages = [];
    $locations = '';

    $indexPath = $site . '/search/search_index.json';

    if (is_file($indexPath)) {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode(read($indexPath), true) ?? [];
        /** @var list<array<string, mixed>> $documents */
        $documents = is_array($decoded['docs'] ?? null) ? $decoded['docs'] : [];

        foreach ($documents as $document) {
            $location = asText($document['location'] ?? null);
            $page = explode('#', $location)[0];
            $locations .= "\n" . $location;
            $publicPages[$page] = ($publicPages[$page] ?? '') . "\n" . asText($document['text'] ?? null);
        }
    }

    $private = array_map(static fn (array $b): string => rtrim($b['out'], '/'), $builds);
    $files = publicFiles($site, $private);

    foreach ($builds as $build) {
        foreach ($build['pages'] as $page) {
            $url = 'private/' . $build['name'] . '/' . $page;

            if (str_contains($locations, $url)) {
                $problems[] = 'public search index has private page ' . $url;
            }
        }

        foreach (privateTexts($build['out']) as $chunk) {
            $seen = 0;

            foreach ($publicPages as $text) {
                if (str_contains($text, $chunk)) {
                    $seen++;
                }
            }

            // BİR səhifə = sızma. BİR NEÇƏ səhifə = ortaq şablon mətni.
            //
            // `bin/docs.php` hər paketin `config.md`-sinə eyni giriş cümləsini yazır
            // ("`fromEnvironment()` adlandırılmış konstruktordur..."), ona görə həmin
            // abzas məxfi paketdə də, altı ictimai paketdə də var. Bu, sızma deyil —
            // eyni generatorun eyni sabitidir. Sızan mətn isə kiminsə bir ictimai
            // səhifəyə köçürdüyü abzasdır və yalnız bir yerdə görünür.
            if ($seen === 1) {
                $problems[] = sprintf(
                    'public search index repeats %s content: "%s..."',
                    $build['name'],
                    mb_substr(trim(strip_tags($chunk)), 0, 70),
                );

                break;
            }
        }

        foreach (privateNamespaces($build['checkout']) as $namespace) {
            foreach ($files as $file) {
                if (str_contains(read($file), $namespace)) {
                    $problems[] = sprintf(
                        '%s mentions private namespace %s',
                        substr($file, strlen($site) + 1),
                        $namespace,
                    );

                    break 2;
                }
            }
        }
    }

    if ($problems !== []) {
        $shown = array_slice($problems, 0, 20);

        if (count($problems) > 20) {
            $shown[] = sprintf('... and %d more', count($problems) - 20);
        }

        fail("private content leaked into the public site:\n  - " . implode("\n  - ", $shown));
    }

    say('leak check passed');
}

/**
 * Məxfi paketin namespace prefiksi — `Integrify\ECustoms` kimi.
 *
 * Sızma yoxlaması ÜÇÜN QISA SİNİF ADI YARAMIR. `ResponseCode` həm məxfi paketdə enum,
 * həm də Azericard-ın ictimai sənədlərində adi bir söz kimi keçir; qısa adla yoxlasaq,
 * hər build yalançı sızma ilə dayanar. Python qarşılığı da məhz buna görə yalnız tam
 * adlara (`pkg.module.Object`) baxır — burada onun qarşılığı namespace-dir.
 *
 * @return list<string>
 */
function privateNamespaces(string $checkout): array
{
    $manifest = $checkout . '/composer.json';

    if (!is_file($manifest)) {
        return [];
    }

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode(read($manifest), true) ?? [];
    /** @var array<string, mixed> $autoload */
    $autoload = is_array($decoded['autoload'] ?? null) ? $decoded['autoload'] : [];
    /** @var array<string, mixed> $psr4 */
    $psr4 = is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

    $out = [];

    foreach (array_keys($psr4) as $prefix) {
        $trimmed = trim((string) $prefix, '\\');

        if ($trimmed !== '') {
            // HTML-də tək, JSON-da qoşa tərs-slash ilə görünür.
            $out[] = $trimmed;
            $out[] = str_replace('\\', '\\\\', $trimmed);
        }
    }

    return array_values(array_unique($out));
}

/**
 * Məxfi saytın axtarış indeksindən ABZAS səviyyəsində mətn parçaları.
 *
 * Səhifə parol arxasında olsa belə, onun MƏTNİ ictimai indeksə düşə bilər — indeks
 * heç bir qorumadan yüklənir. Bu, ən sakit sızma yoludur: heç bir səhifə açılmır,
 * amma məzmun oxunur.
 *
 * NİYƏ ABZAS, BÜTÖV BÖLMƏ YOX. İndeksdə bir qeyd bütöv bölmədir (bir neçə abzas).
 * Sızma isə adətən bir abzasdır: mətn ictimai səhifəyə köçürüləndə bölmənin yalnız
 * bir hissəsi gedir və bütöv bölmə ilə müqayisə onu GÖRMÜR. Hər iki indeks eyni
 * Material renderindən çıxdığı üçün abzas parçası hərfi-hərfinə üst-üstə düşür.
 *
 * Qısa parçalar (başlıq, bir cümlə) təsadüfən rast gəlinə bilər, ona görə 60
 * simvoldan qısaları atılır.
 *
 * @return list<string>
 */
function privateTexts(string $site): array
{
    $path = $site . '/search/search_index.json';

    if (!is_file($path)) {
        return [];
    }

    /** @var array<string, mixed> $index */
    $index = json_decode(read($path), true) ?? [];
    /** @var list<array<string, mixed>> $documents */
    $documents = is_array($index['docs'] ?? null) ? $index['docs'] : [];

    $chunks = [];

    foreach ($documents as $document) {
        // Həm açılış, həm bağlanış teqi ilə bölünür. Yalnız `</p>` ilə bölsək,
        // abzasdan ƏVVƏLKİ cədvəl mətni ona yapışıq qalır və ictimai səhifədə
        // tək abzas görünəndə uyğunluq tapılmır.
        $pieces = preg_split('#</?p>#', asText($document['text'] ?? null)) ?: [];

        foreach ($pieces as $chunk) {
            $chunk = trim($chunk);

            if (mb_strlen($chunk) >= 60) {
                $chunks[] = $chunk;
            }
        }
    }

    return array_values(array_unique($chunks));
}

/**
 * İctimai (yəni məxfi qovluqlarda olmayan) mətn faylları.
 *
 * Əvvəl burada `static $cache` vardı. İki səbəbdən getdi: PHPStan statik dəyişənin
 * tipini çıxara bilmir, və daha pisi — keş ilk çağırışın `$builds`-inə bağlı qalır,
 * yəni ikinci sayt üçün çağırılsa səhv siyahı qaytarardı. İndi siyahı bir dəfə
 * `checkLeaks()`-də qurulub ötürülür.
 *
 * @param list<string> $private məxfi qovluqların yolları
 *
 * @return list<string>
 */
function publicFiles(string $site, array $private): array
{
    $files = [];

    /** @var Iterator<SplFileInfo> $items */
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($site, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($items as $item) {
        if (!$item->isFile() || !in_array($item->getExtension(), ['html', 'json', 'xml', 'txt'], true)) {
            continue;
        }

        $path = $item->getPathname();

        foreach ($private as $directory) {
            if (str_starts_with($path, $directory . '/')) {
                continue 2;
            }
        }

        $files[] = $path;
    }

    return $files;
}

// ------------------------------------------------------------------------------------------- //
//  İcra                                                                                        //
// ------------------------------------------------------------------------------------------- //

/** @var array{site_url: string, private: array<string, array{repo: string, ref?: string, label?: string}>} $registry */
$registry = json_decode(read($root . '/' . REGISTRY), true, 512, JSON_THROW_ON_ERROR);
$siteUrl = rtrim($registry['site_url'], '/') . '/';
$required = in_array(strtolower((string) env('PRIVATE_DOCS_REQUIRED')), ['1', 'true', 'yes', 'on'], true);

rmtree($root . '/' . SITE);

// İctimai sayt.
mkdocs($root . '/' . PUBLIC_CONFIG);
copytree($root . '/docs/az/site', $root . '/' . SITE);

/** @var list<array{name: string, out: string, pages: list<string>, checkout: string}> $builds */
$builds = [];

if (!$publicOnly) {
    foreach ($registry['private'] as $name => $entry) {
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $name) !== 1) {
            fail("private integration name '{$name}' must be lowercase [a-z0-9-]");
        }

        $checkout = resolveSource($root, $name, $entry);

        if ($checkout === null) {
            $message = "{$name}: no source (set PRIVATE_DOCS_TOKEN)";

            if ($required) {
                fail($message);
            }

            say($message . '; skipping');

            continue;
        }

        $built = buildPrivate($root, $name, $entry, $checkout, $siteUrl);
        $builds[] = [
            'name' => $name,
            'out' => $built['out'],
            'pages' => $built['pages'],
            'checkout' => $checkout,
        ];
    }
}

if ($builds !== []) {
    checkLeaks($root, $builds);
}

say('done -> ' . SITE);
