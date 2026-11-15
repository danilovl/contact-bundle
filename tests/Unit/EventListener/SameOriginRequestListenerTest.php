<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Tests\Unit\EventListener;

use Danilovl\ContactBundle\EventListener\SameOriginRequestListener;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SameOriginRequestListenerTest extends TestCase
{
    private const string ROUTE = 'danilovl_contact_api_contact_create';

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('provideHeadersCases')]
    public function testHeaders(array $server, bool $allowed): void
    {
        $event = $this->createEvent('POST', self::ROUTE, $server);

        $listener = new SameOriginRequestListener;
        $listener($event);

        $this->assertSame($allowed, $event->getResponse() === null);
    }

    public function testBlockedResponse(): void
    {
        $server = ['HTTP_ORIGIN' => 'https://evil.com'];
        $event = $this->createEvent('DELETE', self::ROUTE, $server);

        $listener = new SameOriginRequestListener;
        $listener($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertJson((string) $response->getContent());
    }

    #[DataProvider('provideIgnoredCases')]
    public function testIgnored(string $method, ?string $route, int $requestType): void
    {
        $server = ['HTTP_SEC_FETCH_SITE' => 'cross-site'];
        $event = $this->createEvent($method, $route, $server, $requestType);

        $listener = new SameOriginRequestListener;
        $listener($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @param array<string, string> $server
     */
    private function createEvent(
        string $method,
        ?string $route,
        array $server,
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): RequestEvent {
        $server['HTTPS'] = 'on';
        $server['HTTP_HOST'] = 'test.local';

        $request = Request::create('https://test.local/contact/api/contacts', $method, [], [], [], $server);
        if ($route !== null) {
            $request->attributes->set('_route', $route);
        }

        $kernel = $this->createStub(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }

    /**
     * @return Generator<string, array{array<string, string>, bool}>
     */
    public static function provideHeadersCases(): Generator
    {
        yield 'no headers' => [[], true];
        yield 'fetch same-origin' => [['HTTP_SEC_FETCH_SITE' => 'same-origin'], true];
        yield 'fetch none' => [['HTTP_SEC_FETCH_SITE' => 'none'], true];
        yield 'fetch cross-site' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], false];
        yield 'fetch same-site' => [['HTTP_SEC_FETCH_SITE' => 'same-site'], false];
        yield 'fetch wins over origin' => [
            ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'https://test.local'],
            false
        ];
        yield 'same origin header' => [['HTTP_ORIGIN' => 'https://test.local'], true];
        yield 'foreign origin header' => [['HTTP_ORIGIN' => 'https://evil.com'], false];
        yield 'other port origin header' => [['HTTP_ORIGIN' => 'https://test.local:8443'], false];
    }

    /**
     * @return Generator<string, array{string, ?string, int}>
     */
    public static function provideIgnoredCases(): Generator
    {
        yield 'safe method' => ['GET', self::ROUTE, HttpKernelInterface::MAIN_REQUEST];
        yield 'foreign route' => ['POST', 'app_other', HttpKernelInterface::MAIN_REQUEST];
        yield 'no route' => ['POST', null, HttpKernelInterface::MAIN_REQUEST];
        yield 'sub request' => ['POST', self::ROUTE, HttpKernelInterface::SUB_REQUEST];
    }
}
