<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Service\VCard;

use Danilovl\ContactBundle\Service\VCard\VCardText;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VCardTextTest extends TestCase
{
    #[DataProvider('provideEscapeCases')]
    public function testEscape(?string $value, string $expected): void
    {
        $result = VCardText::escape($value);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideUnescapeCases')]
    public function testUnescape(string $value, string $expected): void
    {
        $result = VCardText::unescape($value);

        $this->assertSame($expected, $result);
    }

    public function testRoundTrip(): void
    {
        $original = "Tom, Jerry; \\ end\nnext line";

        $escaped = VCardText::escape($original);
        $result = VCardText::unescape($escaped);

        $this->assertSame($original, $result);
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideSplitUnescapedCases')]
    public function testSplitUnescaped(string $value, array $expected): void
    {
        $result = VCardText::splitUnescaped($value, ';');

        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideFoldCases')]
    public function testFold(int $length, int $linesCount): void
    {
        $line = str_repeat('a', $length);

        $folded = VCardText::fold($line);

        $lines = explode("\r\n", $folded);
        $unfolded = str_replace("\r\n ", '', $folded);
        $this->assertCount($linesCount, $lines);
        $this->assertSame($line, $unfolded);
    }

    /**
     * @return Generator<string, array{?string, string}>
     */
    public static function provideEscapeCases(): Generator
    {
        yield 'null' => [null, ''];
        yield 'special characters' => ["a,b;c\\d\ne\r\nf", 'a\,b\;c\\\\d\ne\nf'];
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function provideUnescapeCases(): Generator
    {
        yield 'special characters' => ['a\,b\;c\\\\d\ne\Nf', "a,b;c\\d\ne\nf"];
        yield 'trailing backslash' => ['abc\\', 'abc\\'];
    }

    /**
     * @return Generator<string, array{string, list<string>}>
     */
    public static function provideSplitUnescapedCases(): Generator
    {
        yield 'escaped separator' => ['a;b\;c;;d', ['a', 'b\;c', '', 'd']];
        yield 'no separator' => ['abc', ['abc']];
    }

    /**
     * @return Generator<string, array{int, int}>
     */
    public static function provideFoldCases(): Generator
    {
        yield 'at limit' => [75, 1];
        yield 'over limit' => [76, 2];
        yield 'long' => [200, 3];
    }
}
