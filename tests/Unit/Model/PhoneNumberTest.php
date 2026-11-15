<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Model;

use Danilovl\ContactBundle\Model\PhoneNumber;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    #[DataProvider('provideNormalizeCases')]
    public function testNormalize(string $value, ?string $countryCode, ?string $expected): void
    {
        $result = PhoneNumber::normalize($value, $countryCode);

        $this->assertSame($expected, $result);
    }

    /**
     * @return Generator<string, array{string, string|null, string|null}>
     */
    public static function provideNormalizeCases(): Generator
    {
        yield 'plus with separators' => ['+1 415 555 0132', null, '+14155550132'];
        yield 'extension dropped' => ['+1 (415) 555-0132 x12', null, '+14155550132'];
        yield 'double zero prefix' => ['0012025550100', null, '+12025550100'];
        yield 'long number without plus' => ['14155550132', null, '+14155550132'];
        yield 'short local kept' => ['555-1234', null, '5551234'];
        yield 'ten digits kept' => ['2025550100', null, '2025550100'];
        yield 'leading zero kept' => ['0601 234 567', null, '0601234567'];
        yield 'too short' => ['12', null, null];
        yield 'no digits' => ['call me', null, null];
        yield 'country code and leading zero' => ['0601 234 567', '420', '+420601234567'];
        yield 'country code and local' => ['555-1234', '420', '+4205551234'];
        yield 'country code ignored for plus' => ['+1 415 555 0132', '420', '+14155550132'];
        yield 'country code ignored for long number' => ['14155550132', '420', '+14155550132'];
        yield 'country code ignored for double zero' => ['0012025550100', '420', '+12025550100'];
    }
}
