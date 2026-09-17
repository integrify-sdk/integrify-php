<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\Enum\BankCode;
use PHPUnit\Framework\TestCase;

/**
 * Bank kodlarının lüğəti.
 */
final class BankCodeTest extends TestCase
{
    public function testAllTheCodesFromThePythonPackageArePresent(): void
    {
        $this->assertCount(102, BankCode::all());
    }

    public function testAKnownCodeIsDescribed(): void
    {
        $this->assertSame('Təsdiq edildi', BankCode::describe('000'));
        $this->assertSame('İmtina, kifayət qədər vəsait yoxdur', BankCode::describe('116'));
    }

    public function testNumericLookingCodesAreStillFoundByString(): void
    {
        // PHP ədədə bənzəyən massiv açarlarını `int`-ə çevirir, yəni `'100'` açarı
        // massivdə `int` 100 kimi saxlanılır. Axtarış işləyir, çünki PHP `$a['100']`
        // yazılışında açarı geri çevirir — bu testin mövzusu məhz həmin çevirmədir.
        $this->assertSame('İmtina (ümumi, şərh yoxdur)', BankCode::describe('100'));
        $this->assertSame('İmtina, kifayət qədər vəsait yoxdur', BankCode::describe('116'));
    }

    public function testNonNumericCodesAreFound(): void
    {
        $this->assertSame('Təsdiqləndi, ICC oflayn rejimində', BankCode::describe('OY1'));
        $this->assertSame('İmtina, ICC oflayn rejimi səbəbindən', BankCode::describe('1Q1'));
    }

    public function testAnUnknownCodeIsNotInvented(): void
    {
        // Bank sabah yeni kod qaytara bilər — `null` "tanınmır" deməkdir, exception yox.
        $this->assertNull(BankCode::describe('997'));
        $this->assertNull(BankCode::describe('ZZZ'));
    }

    public function testAnAbsentCodeIsNull(): void
    {
        // EPoint uğurlu olmayan cavabda `code`-u ya buraxır, ya boş sətir qaytarır.
        $this->assertNull(BankCode::describe(null));
        $this->assertNull(BankCode::describe(''));
    }

    public function testCodesAreAllStringsUnlikeTheArrayKeys(): void
    {
        $codes = BankCode::codes();

        $this->assertCount(102, $codes);

        foreach ($codes as $code) {
            $this->assertIsString($code);
        }

        // `array_keys(all())` bunu verə bilmir: orada `100` `int`-dir və strict
        // müqayisə uğursuz olur. `codes()` məhz buna görə var.
        $this->assertTrue(in_array('100', $codes, true));
        $this->assertFalse(in_array('100', array_keys(BankCode::all()), true));
    }
}
