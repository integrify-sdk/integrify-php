<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Enum;

/**
 * PMO (Payment Multiplexing Operator) nəticə kodları və onların izahı.
 *
 * Kodlar bankdan gəlir, ona görə siyahı tam deyil — bank sabah yeni kod qaytara bilər.
 * `describe()` tanımadığı kod üçün `null` qaytarır.
 *
 * Bu, Python kitabxanasından fərqlənən şüurlu seçimdir. Orada validator
 * `PMO_RESULT_CODES[v]` yazır, yəni:
 *
 * 1. `pmo_result_code` field-inin özü izah mətni ilə əvəz olunur və orijinal kod itir;
 * 2. tanınmayan kod `KeyError` atır — yəni bank yeni bir kod qaytaran gün cavabın
 *    parse-i tamamilə sınır, halbuki ödəniş uğurlu ola bilər.
 *
 * Burada xam kod saxlanılır, izah isə ayrıca metoddur.
 */
final class PmoResultCode
{
    /**
     * Diqqət: PHP ədədə bənzəyən massiv açarlarını `int`-ə çevirir, ona görə bu
     * massivin açarları qarışıqdır. Axtarış bundan əziyyət çəkmir; sətir siyahısı
     * lazımsa `codes()` istifadə edin.
     *
     * @var array<array-key, string>
     */
    private const MESSAGES = [
        '0' => '-',
        '1' => 'Approved',
        '2' => 'Approved Partial',
        '3' => 'Approved Purchase Only',
        '4' => 'Postponed',
        '5' => 'Strong customer authentication required',
        '6' => 'Need Checker\'s confirmation',
        '7' => 'Telebank customer already exists',
        '8' => 'Should select virtual card product',
        '9' => 'Should select account number',
        '10' => 'Should change PVV',
        '11' => 'Confirm payment precheck',
        '12' => 'Select bill',
        '13' => 'Customer confirmation requested',
        '14' => 'Original transaction not found',
        '15' => 'Slip already received',
        '16' => 'Personal information input error',
        '17' => 'SMS/EMail dynamic password requested',
        '18' => 'DPA/CAP dynamic password requested',
        '19' => 'Prepaid code not found',
        '20' => 'Prepaid code not found',
        '21' => 'Corresponding account exhausted',
        '22' => 'Acquirer limit exceeded',
        '23' => 'Cutover in process',
        '24' => 'Dynamic PVV Expired',
        '25' => 'Weak PIN',
        '26' => 'External authentication required',
        '27' => 'Additional data required',
        '29' => 'Closed account',
        '30' => 'Blocked',
        '40' => 'Lost card',
        '41' => 'Stolen card',
        '49' => 'Ineligible vendor account',
        '50' => 'Unauthorized usage',
        '51' => 'Expired card',
        '52' => 'Invalid card',
        '53' => 'Invalid PIN',
        '54' => 'System error',
        '55' => 'Ineligible transaction',
        '56' => 'Ineligible account',
        '57' => 'Transaction not supported',
        '58' => 'Restricted card',
        '59' => 'Insufficient funds',
        '60' => 'Uses limit exceeded',
        '61' => 'Withdrawal limit would be exceeded',
        '62' => 'PIN tries limit was reached',
        '63' => 'Withdrawal limit already reached',
        '64' => 'Credit amount limit',
        '65' => 'No statement information',
        '66' => 'Statement not available',
        '67' => 'Invalid amount',
        '68' => 'External decline',
        '69' => 'No sharing',
        '71' => 'Contact card issuer',
        '72' => 'Destination not available',
        '73' => 'Routing error',
        '74' => 'Format error',
        '75' => 'External decline special condition',
        '80' => 'Bad CW',
        '81' => 'Bad CVV2',
        '82' => 'Invalid transaction',
        '83' => 'PIN tries limit was exceeded',
        '84' => 'Bad CAVV',
        '85' => 'Bad ARQC',
        '90' => 'Approve administrative card operation inside window',
        '91' => 'Approve administrative card operation outside of window',
        '92' => 'Approve administrative card operation',
        '93' => 'Should select card',
        '94' => 'Confirm Issuer Fee',
        '95' => 'Insufficient cash',
        '96' => 'Approved frictionless',
        '98' => 'Invalid merchant',
    ];

    /**
     * Kodun izahı, tanınırsa.
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
     * @return array<array-key, string>
     */
    public static function all(): array
    {
        return self::MESSAGES;
    }

    /**
     * Tanınan kodların siyahısı, hamısı sətir olmaqla.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(strval(...), array_keys(self::MESSAGES));
    }
}
