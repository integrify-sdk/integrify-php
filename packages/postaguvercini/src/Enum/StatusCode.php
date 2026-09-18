<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Enum;

/**
 * Servisin `StatusCode` dəyərləri.
 *
 * **Posta Güvercini uğursuz sorğuya da HTTP 200 qaytarır** — nəticə body-dəki
 * `StatusCode` field-indədir. `200` uğur, qalanı xətadır.
 *
 * Cavab DTO-larında bu enum **tip kimi istifadə olunmur**: servis sabah yeni kod
 * əlavə etsə, validasiya sınmamalıdır. DTO xam `int` saxlayır, bu enum isə
 * `status()` metodu vasitəsilə əlçatandır.
 *
 * Python sxemində 26 elan var, lakin dördü təkrarlanan dəyərlərdir
 * (`ALPHANUMBERIC_QUERY_*` `CREDIT_INQUIRY_*` ilə eyni 7020–7050 kodlarını paylaşır).
 * Python təkrarı səssizcə alias-a çevirir; PHP-də isə təkrarlanan dəyər **fatal
 * error**-dur, ona görə burada 22 fərqli case var və alias-lar şərhdə qeyd olunub.
 */
enum StatusCode: int
{
    case Ok = 200;

    case BadRequest = 400;

    case ServerError = 500;

    case RequestErrorDbError = 1010;

    case RequestErrorDbConnectionFailed = 1020;

    case RequestErrorHistoryRecord = 1030;

    case RequestErrorNoRecordFound = 1040;

    case RequestErrorUnknownMethod = 1050;

    case EmptyReceiverList = 1060;

    case InvalidSendDateFormat = 1063;

    case InvalidExpireDateFormat = 1064;

    case MaxNumberOfRecipients = 1070;

    case DifferentSizeRecipientAndMessage = 1080;

    case MessageEmpty = 1090;

    case StatusQueryDbError = 2020;

    case StatusInquiryHistoryRecordError = 2030;

    case StatusInquiryNoRecordFoundError = 2040;

    case StatusInquiryDbMethodError = 2050;

    /** Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_DB_ERROR` adı ilə elan olunub (alias). */
    case CreditInquiryDbError = 7020;

    /** Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_HISTORY_RECORD_ERROR` adı ilə elan olunub (alias). */
    case CreditInquiryHistoryRecordError = 7030;

    /** Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_NO_RECORD_FOUND_ERROR` adı ilə elan olunub (alias). */
    case CreditInquiryNoRecordFoundError = 7040;

    /** Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_DB_METHOD_ERROR` adı ilə elan olunub (alias). */
    case CreditInquiryDbMethodError = 7050;

    /** Yalnız `200` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this === self::Ok;
    }
}
