<?php

declare(strict_types=1);

namespace Integrify\EPoint;

use Integrify\EPoint\Dto\Response\CallbackData;
use Integrify\EPoint\Exception\SignatureMismatch;
use Integrify\Exception\ValidationFailed;
use JsonException;

/**
 * EPoint callback-lərinin açılması və doğrulanması.
 *
 * `pay()` yalnız ödənişi **başladır**. Nəticə EPoint dashboard-ında qeyd etdiyiniz
 * URL-ə `POST` olunur, `application/x-www-form-urlencoded` body ilə:
 *
 * ```
 * data=eyJzdGF0dXMiOiAic3VjY2VzcyJ9&signature=EG7cnaJteYS6cVuR2aqDvpecQtk=
 * ```
 *
 * `data` base64-lənmiş JSON-dur, `signature` isə onun `EPOINT_PRIVATE_KEY` ilə
 * imzasıdır. Tipik istifadə (framework-dən asılı olmayaraq — xam body kifayətdir):
 *
 * ```php
 * $callback = new Callback(EPointConfig::fromEnvironment());
 *
 * try {
 *     $data = $callback->decode($request->getContent());
 * } catch (SignatureMismatch) {
 *     return response('', 403);
 * }
 *
 * if ($data->isSuccessful()) {
 *     $order = Order::find($data->orderId);
 *     // ...
 * }
 * ```
 */
final readonly class Callback
{
    public function __construct(
        private EPointConfig $config,
    ) {
    }

    /**
     * Xam callback body-sini açır və imzasını yoxlayır.
     *
     * @param string $body `data=...&signature=...` formatında xam body.
     *
     * @throws SignatureMismatch İmza uyğun gəlmirsə, və ya body gözlənilən
     *     formatda deyilsə. İkisi də "bu datanı EPoint göndərməmişdir" deməkdir,
     *     ona görə çağıran tərəf üçün fərqi yoxdur.
     * @throws ValidationFailed Decode olunmuş JSON DTO-ya uyğun gəlmirsə.
     */
    public function decode(string $body): CallbackData
    {
        ['data' => $data, 'signature' => $signature] = self::parse($body);

        if (!$this->config->verify($data, $signature)) {
            throw new SignatureMismatch();
        }

        return CallbackData::from(self::payload($data));
    }

    /**
     * Callback-in yalnız imzasını yoxlayır, datanı açmadan.
     *
     * Body-ni jurnala yazıb sonra işləmək (məs., queue-ya atmaq) istədiyiniz halda
     * faydalıdır: imza dərhal yoxlanılır, DTO-ya çevirmə isə sonraya qalır.
     */
    public function verify(string $body): bool
    {
        try {
            ['data' => $data, 'signature' => $signature] = self::parse($body);
        } catch (SignatureMismatch) {
            return false;
        }

        return $this->config->verify($data, $signature);
    }

    /**
     * Xam body-dən `data` və `signature`-ı çıxarır.
     *
     * @return array{data: string, signature: string}
     *
     * @throws SignatureMismatch
     */
    private static function parse(string $body): array
    {
        $fields = [];
        parse_str($body, $fields);

        $data = $fields['data'] ?? null;
        $signature = $fields['signature'] ?? null;

        // Boş dəyər də qəbuledilməzdir: `data=&signature=` üçün imza hesablanır və
        // uyğun gələ bilər, nəticədə tamam boş bir "ödəniş" DTO-su qaytarılardı.
        if (!is_string($data) || $data === '' || !is_string($signature) || $signature === '') {
            throw new SignatureMismatch('the body has no "data"/"signature" pair');
        }

        return ['data' => $data, 'signature' => $signature];
    }

    /**
     * Base64 + JSON açılışı.
     *
     * @return array<array-key, mixed>
     *
     * @throws SignatureMismatch
     */
    private static function payload(string $data): array
    {
        // `strict: true` olmadan base64_decode() naməlum simvolları səssizcə atır və
        // zibil datadan "keçərli" JSON çıxa bilər.
        $json = base64_decode($data, true);

        if ($json === false) {
            throw new SignatureMismatch('"data" is not valid base64');
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new SignatureMismatch(sprintf('"data" is not valid JSON (%s)', $error->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new SignatureMismatch('"data" does not decode to a JSON object');
        }

        return $decoded;
    }
}
