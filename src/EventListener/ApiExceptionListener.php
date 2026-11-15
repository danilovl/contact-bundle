<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\EventListener;

use Danilovl\ContactBundle\Exception\{
    ContactNotFoundException,
    ContactValidationException
};
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\{
    JsonResponse,
    Response
};
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class ApiExceptionListener
{
    private const string ROUTE_PREFIX = 'danilovl_contact_api_';

    public function __invoke(ExceptionEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!is_string($route) || !str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ContactValidationException) {
            $data = [
                'error' => $exception->getMessage(),
                'errors' => $exception->getErrors()
            ];
            $response = new JsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY);
            $event->setResponse($response);

            return;
        }

        if ($exception instanceof ContactNotFoundException) {
            $data = ['error' => $exception->getMessage()];
            $response = new JsonResponse($data, Response::HTTP_NOT_FOUND);
            $event->setResponse($response);

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $data = ['error' => $exception->getMessage()];
            $statusCode = $exception->getStatusCode();
            $response = new JsonResponse($data, $statusCode);
            $event->setResponse($response);
        }
    }
}
