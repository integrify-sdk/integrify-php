<?php

declare(strict_types=1);

namespace Integrify\Azericard;

use Integrify\Azericard\Exception\SignatureMismatch;
use Integrify\Exception\InvalidRequest;
use OpenSSLAsymmetricKey;
use SensitiveParameter;

/**
 * Azericard-ın **iki fərqli** imza alqoritmi.
 *
 * MPI (kart ödənişləri) RSA SHA-256 istifadə edir və nəticə `P_SIGN` field-inə düşür.
 * MT (pul köçürmələri) isə MD5 + paylaşılan açar istifadə edir və nəticə `Signature`
 * field-inə düşür. İkisinin açarı da fərqlidir — bax: [`AzericardConfig`](AzericardConfig.php).
 */
final class Signature
{
    /**
     * MPI payload-unun `P_SIGN` dəyəri.
     *
     * Alqoritm: hər field üçün `uzunluq + dəyər` birləşdirilir, nəticə RSA SHA-256 ilə
     * imzalanır və hex kimi yazılır. Məsələn `terminal = 'TERM0001'` → `8TERM0001`.
     *
     * Boş dəyər `-` ilə əvəz olunur. Diqqət: burada "boş" PHP-nin falsy anlayışı
     * deyil, Python-un `if val:` davranışıdır — `'0'` **sətri doludur**, `0` ədədi
     * isə boşdur. Fərq önəmlidir: `trtype` `'0'` (bloklama) ola bilər və onu `-` kimi
     * göndərmək imzanı sındırardı.
     *
     * @param list<string> $values İmzalanacaq dəyərlər, sıra ilə.
     *
     * @throws InvalidRequest İmzalama uğursuz olarsa.
     */
    public static function rsa(array $values, #[SensitiveParameter] OpenSSLAsymmetricKey $privateKey): string
    {
        $signature = '';

        // `openssl_sign()` nəticəni referens ilə yazır və tipi elan etmir; uğur
        // qaytarsa da, alınanın sətir olduğunu ayrıca təsdiqləyirik.
        if (!openssl_sign(self::macSource($values), $signature, $privateKey, OPENSSL_ALGO_SHA256)
            || !is_string($signature)) {
            throw new InvalidRequest(sprintf(
                'Could not sign the Azericard payload: %s.',
                openssl_error_string() ?: 'unknown OpenSSL error',
            ));
        }

        return bin2hex($signature);
    }

    /**
     * İmzalanan mətn — `uzunluq + dəyər` birləşməsi.
     *
     * Ayrıca metoddur ki, testlər imzanın **nəyin üzərində** hesablandığını yoxlaya
     * bilsin; imzanın özü hər açar üçün fərqlidir, mənbə isə deterministikdir.
     *
     * @param list<string> $values
     */
    public static function macSource(array $values): string
    {
        $source = '';

        foreach ($values as $value) {
            // Python-un `if val:` yoxlaması: boş sətir boşdur, `'0'` isə yox.
            $source .= $value === '' ? '-' : strlen($value) . $value;
        }

        return $source;
    }

    /**
     * MT payload-unun `Signature` dəyəri: `md5(dəyərlər + açar)`.
     *
     * @param list<string> $values İmzalanacaq dəyərlər, sıra ilə.
     * @param string $key Paylaşılan açar.
     */
    public static function md5(array $values, #[SensitiveParameter] string $key): string
    {
        return md5(implode('', $values) . $key);
    }

    /**
     * MT cavabının imzasını **sabit zamanda** yoxlayır.
     *
     * Python kitabxanası bunu `assert` ilə edir. `assert` `python -O` altında
     * tamamilə silinir, yəni optimizasiya ilə işləyən tətbiqdə imza yoxlaması
     * **səssizcə yox olur** və saxta cavab qəbul edilir. Burada yoxlama adi koddur
     * və uyğunsuzluq `SignatureMismatch` atır.
     *
     * Müqayisə `hash_equals()` ilədir: adi `===` ilk fərqli baytda dayanır və
     * müqayisənin müddəti düzgün prefiksin uzunluğunu açır.
     *
     * @param list<string> $values
     *
     * @throws SignatureMismatch
     */
    public static function verify(array $values, #[SensitiveParameter] string $key, string $signature): void
    {
        if (!hash_equals(self::md5($values, $key), $signature)) {
            throw new SignatureMismatch();
        }
    }
}
