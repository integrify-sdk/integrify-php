<?php

declare(strict_types=1);

namespace Integrify\EPoint\Enum;

/**
 * Bankın 3 simvollu cavab kodları və onların izahı.
 *
 * Kodlar bankdan gəlir, EPoint-dən deyil, ona görə siyahı tam deyil — bank sabah
 * yeni kod qaytara bilər. `describe()` tanımadığı kod üçün `null` qaytarır; cavab
 * DTO-ları isə **xam kodu** saxlayır (`PaymentResult::$code`), izahı ayrıca verir.
 *
 * Bu, Python kitabxanasından fərqlənən şüurlu seçimdir: orada validator `code`
 * field-inin özünü izah mətni ilə əvəz edir, yəni tanınan kodun orijinal dəyəri
 * itir və `'000'` ilə müqayisə etmək mümkün olmur. Burada ikisi də əlçatandır.
 *
 * Mətnlər Python kitabxanasından **eynilə** götürülüb ki, iki SDK istifadəçiyə aynı
 * mesajı göstərsin. Bir qismində orfoqrafik problemlər var (`vaasaittün`,
 * `dolsazlıqdan`, `oqurlarnı kard`); onları burada düzəltmək iki kitabxananı
 * ayırardı, ona görə toxunulmayıb.
 */
final class BankCode
{
    /**
     * Diqqət: PHP ədədə bənzəyən massiv açarlarını **int-ə çevirir**, ona görə bu
     * massivin açarları qarışıqdır: `'000'` və `'OY1'` sətir qalır, `'100'` isə
     * `int` 100 olur. Axtarış bundan əziyyət çəkmir (`MESSAGES['100']` işləyir,
     * çünki PHP açarı geri çevirir), lakin `array_keys()` üzərində strict müqayisə
     * uğursuz olur — buna görə `all()` açarları sətrə normallaşdırır.
     *
     * @var array<array-key, string>
     */
    private const MESSAGES = [
        '000' => 'Təsdiq edildi',
        '100' => 'İmtina (ümumi, şərh yoxdur)',
        '101' => 'İmtina (kartın istifadə müddəti bitib)',
        '102' => 'İmtina (fırıldaqçılığdan şübhə)',
        '103' => 'İmtina (qəbul edən ekvayer bank ilə əlaqə saxlasın)',
        '104' => 'İmtina (məhdudlaşdırılmış kart)',
        '105' => 'İmtina (qəbul edən ekvayer bankın təhlükəsizlik şöbəsi ilə əlaqə saxlasın)',
        '106' => 'İmtina (icazə verilən PIN cəhtləri keçildi)',
        '107' => 'İmtina (kart emitentinə müraciət edin)',
        '108' => 'İmtina (kart emitentinin xüsusi şərtlərinə müraciət edin)',
        '109' => 'İmtina, etibarsız tacir',
        '110' => 'İmtina, etibarsız məbləğ',
        '111' => 'İmtina, etibarsız kart nömrəsi',
        '112' => 'İmtina, PIN tələb olunur',
        '113' => 'İmtina, qəbuledilməz məbləğ',
        '114' => 'İmtina, tələb edilən hesab növü yoxdur',
        '115' => 'İmtina, tələb edilən funksiya dəstəklənmir',
        '116' => 'İmtina, kifayət qədər vəsait yoxdur',
        '117' => 'İmtina, səhv PIN',
        '118' => 'İmtina, kart məlumatları yoxdur',
        '119' => 'İmtina, kart sahibinə əməliyyat üçün icazə verilmir',
        '120' => 'İmtina, terminal əməliyyatı üçün icazə verilmir',
        '121' => 'İmtina, təhlükəsizlik pozuntusu',
        '122' => 'İmtina, pul əksərliyi limiti keçib',
        '123' => 'İmtina, qərarın çıxarılması təzliliyinin limiti aşıb',
        '124' => 'İmtina, vaasaittün qəbul edilmədi',
        '125' => 'İmtina, etibarsiz kart',
        '126' => 'İmtina, PIN bloku səhvdir',
        '127' => 'İmtina, PIN uzunluğu xətası',
        '128' => 'İmtina, PIN sinxronizasiya xətası',
        '180' => 'İmtina, saxta kart, səhv istifadə',
        '181' => 'İmtina, saxta kart, səhv istifadə',
        '200' => 'Pick-up (ümumi səbəblər)',
        '201' => 'Pick-up (kartın istifadə müddəti bitdi)',
        '202' => 'Pick-up (dolsazlıqdan şübhələnir)',
        '203' => 'Pick-up, qəbul edən bank əməkdar ilə əlaqə saxlasın',
        '204' => 'Pick-up, məhdudiyyətlərinə əyər',
        '205' => 'Pick-up (qəbul edən əməkdar bankın təhlükəsizlik səbəbi ilə əlaqə saxlasın)',
        '206' => 'Pick-up (icazə verən PIN çoxları keçildi)',
        '207' => 'Pick-up, tixrili sərat',
        '208' => 'Pick-up, xüsusi körait',
        '209' => 'Pick-up, oqurlarnı kard',
        '210' => 'Pick-up, saxta kartdan şübhələnir',
        '300' => 'Status mesajı: fayl əməliyyatı uğurludu',
        '301' => 'Status mesajı: fayl əməliyyatı qəbuledicilər tərəfindən dəstəklənmir',
        '302' => 'Status mesajı: faylda qeyd, tapma mümkün deyil',
        '303' => 'Status mesajı: dublikat qeyd, köhnə qeyd dəyişdirildi',
        '304' => 'Status mesajı: fayl əməliyyatı uğursuz oldu',
        '305' => 'Status mesajı: fayl kilidlidir',
        '306' => 'Status mesajı: fayl əməliyyatı uğursuz redaktə xətası',
        '307' => 'Status mesajı: dublikat qeyd, yeni qeyd əlavə edildi',
        '308' => 'Status mesajı: fayl məlumat formatı xətası',
        '309' => 'Status mesajı: fayl qəbul edilmədi',
        '400' => 'Qəbul edilmədi (naməlum fayl)',
        '499' => 'Təsdiq edildi, orijinal mesajı balansda yoxdur',
        '500' => 'Status mesajı: tutulub, balans dəyişdi',
        '501' => 'Status mesajı: məbləğ tutulubdur, balansda, cəmi təmin edilib',
        '502' => 'Status mesajı: tutulubdur, balansda deyil',
        '503' => 'Status mesajı: tutulubdurmaq üçün cəmlər mövcud deyil',
        '504' => 'Status mesajı: tutulubdurmayib, cəmi təmin edilib',
        '600' => 'Qəbul edildi (haqqı əməliyyat izləmək mümkün deyil)',
        '601' => 'Status mesajı: administrativməhdudiyyat',
        '602' => 'Status mesajı: POS terminalı yəni istifadə olunmur',
        '603' => 'Status mesajı: əməliyyat nöqsü / PAN uyğunsuzluğu',
        '604' => 'Status mesajı: istinad fotosu yanlışdır',
        '605' => 'Status mesajı: sorğu olunan element (qədim) edilən',
        '606' => 'Status mesajı: təlb olunan icra bilməməz-tələb olunan sənədlər mövcud deyil',
        '680' => 'Siyahı hazır deyil',
        '681' => 'Siyahı hazır',
        '700' => 'Qəbul edildi (ədəbəyə toplamaq üçün)',
        '800' => 'Qəbul edildi (səbonis nazərt üçün)',
        '900' => 'Tövsiyələr nəzərə alındı, həc bir maliyyə öhdəliyi qəbul edilmədi',
        '901' => 'Tövsiyələr nəzərə alındı, məbləğ bərabər qəbul edildi',
        '902' => 'İmtina səbəbi ismarıc: etibar yənizən axıdılması',
        '903' => 'İmtina səbəbi ismarıc: əməliyyatdan daxil edilməsi',
        '904' => 'İmtina səbəbi ismarıc: əməliyyat səhv',
        '905' => 'İmtina səbəbi ismarıc: ekavter kommutator tərəfindən dəstəklənmir',
        '906' => 'İmtina səbəbi ismarıc: marqruta prosesində keçid işləmir',
        '907' => 'İmtina səbəbi ismarıc: azalma əməliyyatı üçün əməliyyat təyinat yerini tapa bilmir',
        '908' => 'İmtina səbəbi ismarıc: kart emitenti və ya təyinat əməliyyat təyinat yerini tapabilmir',
        '909' => 'İmtina səbəbi ismarıc: sistem nasazlığı',
        '910' => 'İmtina səbəbi ismarıc: kart emitenti səndin gözləmə',
        '911' => 'İmtina səbəbi ismarıc: kartın emitentinin tərzümə müddəti bitib',
        '912' => 'İmtina səbəbi ismarıc: kartın emitenti mümkün deyil',
        '913' => 'İmtina səbəbi: dublikat əməliyyat',
        '914' => 'İmtina səbəbi: transaksiyanı izləmək mümkün deyil',
        '915' => 'İmtina səbəbi: uyğunlaşdırma və ya nəzərt nöqtəsinin sonmma səhvi',
        '916' => 'İmtina səbəbi: Yalnış MAC',
        '917' => 'İmtina səbəbi: MAC açarının yalnış sinxronizasiyası',
        '918' => 'İmtina səbəbi: istifadə üçün bağlayıcı açarların mövcudsuzluğu',
        '919' => 'İmtina səbəbi: şifrələnmə açarı sinxronlaşması səhvi',
        '920' => 'İmtina səbəbi: proqram/donanım təhlükəsizliyi səhvi-bir daha sınayın',
        '921' => 'İmtina səbəbi: proqram/donanım təhlükəsizliyi səhvi-fəaliyyət yoxdur',
        '922' => 'İmtina səbəbi: ismaric nömrəsinin ardıcılılığının pozulması',
        '923' => 'Status ismarici: müraciət prosesdədir',
        '950' => 'İmtina səbəbi: iş razılaşmasının pozuntusu',
        'XXX' => 'Kod kartların vəziyyəti kodu və ya stop siyahısına daxil edilməsəbəbinin kodu ilə əvəz edilməlidir',
        'OY1' => 'Təsdiqləndi, ICC oflayn rejimində',
        'OY3' => 'Təsdiqləndi, ICC oflayn rejimində',
        '1Q1' => 'İmtina, ICC oflayn rejimi səbəbindən',
        '1Z1' => 'İmtina, ICC oflayn rejimi səbəbindən',
        '1Z3' => 'İmtina, ICC oflayn rejimi səbəbindən',
    ];

    /**
     * Kodun izahı, tanınırsa.
     *
     * @param string|null $code Bankın cavab kodu (`'000'`, `'116'`, `'OY1'`).
     */
    public static function describe(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::MESSAGES[$code] ?? null;
    }

    /**
     * Bütün tanınan kodlar.
     *
     * Açarların tipi qarışıqdır — `'000'` sətir, `100` isə `int`. Bunu "düzəltmək"
     * mümkün deyil: `$a[(string) 100]` yazılışında da PHP açarı `int`-ə çevirir,
     * yəni PHP massivində ədədə bənzəyən sətir açar **saxlanıla bilmir**. Kodların
     * sətir siyahısı lazımsa `codes()` istifadə edin.
     *
     * @return array<array-key, string>
     */
    public static function all(): array
    {
        return self::MESSAGES;
    }

    /**
     * Tanınan kodların siyahısı, hamısı sətir olmaqla.
     *
     * `array_keys(all())` bunu vermir: orada `100` `int` kimi gəlir və strict
     * müqayisə (`in_array($code, ..., true)`) uğursuz olur. Siyahıda isə açar
     * problemi yoxdur, çünki dəyərlər açar deyil.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(strval(...), array_keys(self::MESSAGES));
    }
}
