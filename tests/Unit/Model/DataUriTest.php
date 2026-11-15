<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\Model;

use Danilovl\ContactBundle\Model\DataUri;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DataUriTest extends TestCase
{
    #[DataProvider('provideParseCases')]
    public function testParse(string $value, ?string $mimeType, ?string $base64): void
    {
        $dataUri = DataUri::parse($value);

        $this->assertSame($mimeType, $dataUri?->mimeType);
        $this->assertSame($base64, $dataUri?->base64);
    }

    #[DataProvider('provideGetBinaryCases')]
    public function testGetBinary(string $value, string $expected): void
    {
        $dataUri = DataUri::parse($value);

        $this->assertNotNull($dataUri);
        $this->assertSame($expected, $dataUri->getBinary());
    }

    public function testVCardType(): void
    {
        $dataUri = DataUri::parse('data:image/png;base64,aGVsbG8=');

        $this->assertSame('PNG', $dataUri?->getVCardType());
    }

    /**
     * @return Generator<string, array{string, ?string, ?string}>
     */
    public static function provideParseCases(): Generator
    {
        yield 'image' => ['data:image/PNG;base64,aGVsbG8=', 'image/png', 'aGVsbG8='];
        yield 'whitespace in base64' => ["data:image/jpeg;base64,aGVs\n bG8=", 'image/jpeg', 'aGVsbG8='];
        yield 'not image' => ['data:text/html;base64,PGgxPg==', null, null];
        yield 'not base64' => ['data:image/png;utf8,<svg>', null, null];
        yield 'plain url' => ['https://example.com/photo.png', null, null];
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function provideGetBinaryCases(): Generator
    {
        yield 'valid' => ['data:image/png;base64,aGVsbG8=', 'hello'];
        yield 'broken base64' => ['data:image/png;base64,a', ''];
    }
}
