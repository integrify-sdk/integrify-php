---
title: Kapital Bank · Digər obyektlər
---

# Digər obyektlər

## PmoResultCode

PMO (Payment Multiplexing Operator) nəticə kodları və onların izahı.

Kodlar bankdan gəlir, ona görə siyahı tam deyil — bank sabah yeni kod qaytara bilər.
`describe()` tanımadığı kod üçün `null` qaytarır.

Bu, Python kitabxanasından fərqlənən şüurlu seçimdir. Orada validator
`PMO_RESULT_CODES[v]` yazır, yəni:

1. `pmo_result_code` field-inin özü izah mətni ilə əvəz olunur və orijinal kod itir;
2. tanınmayan kod `KeyError` atır — yəni bank yeni bir kod qaytaran gün cavabın
   parse-i tamamilə sınır, halbuki ödəniş uğurlu ola bilər.

Burada xam kod saxlanılır, izah isə ayrıca metoddur.

_Field-i yoxdur._
