---
title: Posta Güvercini · Enum-lar
---

# Enum-lar

## Endpoint

Posta Güvercini SMS API-sinin endpoint-ləri (`https://www.poctgoyercini.com`).

Host adına diqqət: şirkətin adı "Posta Güvercini"dir, domen isə `poctgoyercini` —
yazılış fərqi onlarındır, səhv deyil.

`Send_1_N` bir mətni çox nömrəyə, `Send_N_N` isə hər nömrəyə öz mətnini göndərir.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::SendSingle` | `/api_json/v1/Sms/Send_1_N` |  |
| `Endpoint::SendMultiple` | `/api_json/v1/Sms/Send_N_N` |  |
| `Endpoint::Status` | `/api_json/v1/Sms/Status` |  |
| `Endpoint::CreditBalance` | `/api_json/v1/Sms/CreditBalance` |  |

## Channel

SMS kanalı.

`Otp` birdəfəlik parollar üçündür və adətən daha sürətli çatdırılır; `Bulk` isə
kütləvi göndərişlər üçündür. Default `Otp`-dir — Python kitabxanasındakı kimi.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Channel::Otp` | `OTP` |  |
| `Channel::Bulk` | `BULK` |  |

## StatusCode

Servisin `StatusCode` dəyərləri.

**Posta Güvercini uğursuz sorğuya da HTTP 200 qaytarır** — nəticə body-dəki
`StatusCode` field-indədir. `200` uğur, qalanı xətadır.

Cavab DTO-larında bu enum **tip kimi istifadə olunmur**: servis sabah yeni kod
əlavə etsə, validasiya sınmamalıdır. DTO xam `int` saxlayır, bu enum isə
`status()` metodu vasitəsilə əlçatandır.

Python sxemində 26 elan var, lakin dördü təkrarlanan dəyərlərdir
(`ALPHANUMBERIC_QUERY_*` `CREDIT_INQUIRY_*` ilə eyni 7020–7050 kodlarını paylaşır).
Python təkrarı səssizcə alias-a çevirir; PHP-də isə təkrarlanan dəyər **fatal
error**-dur, ona görə burada 22 fərqli case var və alias-lar şərhdə qeyd olunub.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `StatusCode::Ok` | `200` |  |
| `StatusCode::BadRequest` | `400` |  |
| `StatusCode::ServerError` | `500` |  |
| `StatusCode::RequestErrorDbError` | `1010` |  |
| `StatusCode::RequestErrorDbConnectionFailed` | `1020` |  |
| `StatusCode::RequestErrorHistoryRecord` | `1030` |  |
| `StatusCode::RequestErrorNoRecordFound` | `1040` |  |
| `StatusCode::RequestErrorUnknownMethod` | `1050` |  |
| `StatusCode::EmptyReceiverList` | `1060` |  |
| `StatusCode::InvalidSendDateFormat` | `1063` |  |
| `StatusCode::InvalidExpireDateFormat` | `1064` |  |
| `StatusCode::MaxNumberOfRecipients` | `1070` |  |
| `StatusCode::DifferentSizeRecipientAndMessage` | `1080` |  |
| `StatusCode::MessageEmpty` | `1090` |  |
| `StatusCode::StatusQueryDbError` | `2020` |  |
| `StatusCode::StatusInquiryHistoryRecordError` | `2030` |  |
| `StatusCode::StatusInquiryNoRecordFoundError` | `2040` |  |
| `StatusCode::StatusInquiryDbMethodError` | `2050` |  |
| `StatusCode::CreditInquiryDbError` | `7020` | Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_DB_ERROR` adı ilə elan olunub (alias). |
| `StatusCode::CreditInquiryHistoryRecordError` | `7030` | Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_HISTORY_RECORD_ERROR` adı ilə elan olunub (alias). |
| `StatusCode::CreditInquiryNoRecordFoundError` | `7040` | Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_NO_RECORD_FOUND_ERROR` adı ilə elan olunub (alias). |
| `StatusCode::CreditInquiryDbMethodError` | `7050` | Python-da bu dəyər həm də `ALPHANUMBERIC_QUERY_DB_METHOD_ERROR` adı ilə elan olunub (alias). |
