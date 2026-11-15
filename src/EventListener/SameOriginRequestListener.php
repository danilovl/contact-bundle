<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\{
    JsonResponse,
    Request,
    Response
};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final readonly class SameOriginRequestListener
{
    private const string ROUTE_PREFIX = 'danilovl_contact_api_';

    private const array ALLOWED_FETCH_SITES = ['same-origin', 'none'];

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->isMethodSafe()) {
            return;
        }

        $route = $request->attributes->get('_route');
        if (!is_string($route) || !str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $isSameOrigin = $this->isSameOrigin($request);
        if ($isSameOrigin) {
            return;
        }

        $response = new JsonResponse(
            data: ['error' => 'Cross-site request is not allowed'],
            status: Response::HTTP_FORBIDDEN
        );

        $event->setResponse($response);
    }

    private function isSameOrigin(Request $request): bool
    {
        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if ($fetchSite !== null) {
            $lowerFetchSite = mb_strtolower($fetchSite);

            return in_array($lowerFetchSite, self::ALLOWED_FETCH_SITES, true);
        }

        $origin = $request->headers->get('Origin');
        if ($origin === null) {
            return true;
        }

        $schemeAndHost = $request->getSchemeAndHttpHost();

        return $origin === $schemeAndHost;
    }
}
