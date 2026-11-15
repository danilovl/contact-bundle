<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Model;

use Danilovl\ContactBundle\Model\PartialDate;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PartialDateTest extends TestCase
{
    #[DataProvider('provideNormalizeCases')]
    public function testNormalize(string $value, ?string $expected): void
    {
        $result = PartialDate::normalize($value);

        $this->assertSame($expected, $result);
    }

    /**
     * @return Generator<string, array{string, ?string}>
     */
    public static function provideNormalizeCases(): Generator
    {
        yield 'full date' => ['1990-05-17', '1990-05-17'];
        yield 'full date with time' => ['1990-05-17T10:20:30Z', '1990-05-17'];
        yield 'compact date' => ['19900517', '1990-05-17'];
        yield 'yearless with dashes' => ['--05-17', '--05-17'];
        yield 'yearless compact' => ['--0517', '--05-17'];
        yield 'leap day without year' => ['--02-29', '--02-29'];
        yield 'leap day in leap year' => ['2024-02-29', '2024-02-29'];
        yield 'leap day in common year' => ['2023-02-29', null];
        yield 'month out of range' => ['1990-13-01', null];
        yield 'day out of range' => ['1990-04-31', null];
        yield 'yearless day out of range' => ['--04-31', null];
        yield 'surrounding spaces' => ['  1990-05-17  ', '1990-05-17'];
        yield 'empty string' => ['', null];
        yield 'text' => ['yesterday', null];
        yield 'wrong separators' => ['17.05.1990', null];
    }
}
