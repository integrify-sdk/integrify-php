---
title: Posta Güvercini · Sorğu DTO-ları
---

# Sorğu DTO-ları

Bu obyektlər servisə **göndərilir**. `Məftildəki ad` sütunu payload-da hansı açarın görünəcəyini göstərir — PHP tərəfdəki ad heç vaxt məftilə çıxmır.

## CreditBalanceRequest

`checkBalance()` sorğusunun payload-u — yalnız hesabın məlumatları.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `username` | `Username` | `string` |  |  | Hesabın istifadəçi adı. |
| `password` | `Password` | `string` |  |  | Hesabın parolu. |

## SendMultipleRequest

`sendMessages()` sorğusunun payload-u — hər alıcıya öz mətni.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `messages` | `Messages` | `list<SmsMessage>` | ✅ |  | Nömrə–mətn cütləri. |
| `sendDate` | `SendDate` | `string\|null` |  |  | Göndərilmə vaxtı, `YYYYMMDD HH:MM`. `null` — indi. |
| `expireDate` | `ExpireDate` | `string\|null` |  |  | Etibarlılıq müddəti, `YYYYMMDD HH:MM`. |
| `channel` | `Channel` | `string` |  |  | Kanal: `OTP` və ya `BULK`. |
| `originator` | `Originator` | `string\|null` |  |  | Göndərən adı. |
| `username` | `Username` | `string` |  |  | Hesabın istifadəçi adı. |
| `password` | `Password` | `string` |  |  | Hesabın parolu. |

## SendSingleRequest

`sendSms()` sorğusunun payload-u — bir mətn, çox alıcı.

Bütün field adları məftildə **PascalCase**-dir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `message` | `Message` | `string` | ✅ |  | Göndəriləcək mətn. |
| `receivers` | `Receivers` | `list<string>` | ✅ |  | Alıcı nömrələri. |
| `sendDate` | `SendDate` | `string\|null` |  |  | Göndərilmə vaxtı, `YYYYMMDD HH:MM`. `null` — indi. |
| `expireDate` | `ExpireDate` | `string\|null` |  |  | Etibarlılıq müddəti, `YYYYMMDD HH:MM`. |
| `channel` | `Channel` | `string` |  |  | Kanal: `OTP` və ya `BULK`. |
| `originator` | `Originator` | `string\|null` |  |  | Göndərən adı. |
| `username` | `Username` | `string` |  |  | Hesabın istifadəçi adı. |
| `password` | `Password` | `string` |  |  | Hesabın parolu. |

## SmsMessage

`sendMessages()` üçün bir nömrə–mətn cütü.

`Send_N_N` endpoint-i hər alıcıya **öz** mətnini göndərir, ona görə nömrə və mətn
bir yerdə saxlanılır. Bu, iki ayrı siyahını `zip` etməkdən daha təhlükəsizdir:
uzunluqları fərqli olan iki siyahı səssizcə qısaldılır və bir mesaj heç kimə
getmir.

Məftildə field adları **PascalCase**-dir (`Receiver`, `Message`) — bütün API belədir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `receiver` | `Receiver` | `string` | ✅ |  | Nömrə: ölkə kodu + operator kodu + nömrə (`994501234567`). |
| `message` | `Message` | `string` | ✅ |  | Həmin nömrəyə gedən mətn. |

## StatusRequest

`getStatus()` sorğusunun payload-u.

Göndərmə sorğularından fərqli olaraq burada `SendDate`/`ExpireDate`/`Channel`
field-ləri **ümumiyyətlə yoxdur** — Python sxemi də onları elan etmir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `messageIds` | `MessageIds` | `list<string>` | ✅ |  | Vəziyyəti soruşulan mesaj IDləri. |
| `username` | `Username` | `string` |  |  | Hesabın istifadəçi adı. |
| `password` | `Password` | `string` |  |  | Hesabın parolu. |
