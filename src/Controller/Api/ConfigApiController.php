<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Controller\Api;

use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use Danilovl\ContactBundle\Service\OwnerResolver;
use Symfony\Component\HttpFoundation\{
    JsonResponse,
    Request
};
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '%danilovl_contact.api.prefix%', name: 'danilovl_contact_api_')]
readonly class ConfigApiController
{
    public function __construct(
        private ContactConfig $config,
        private OwnerResolver $ownerResolver
    ) {}

    #[Route(path: '/config', name: 'config', methods: [Request::METHOD_GET])]
    public function config(): JsonResponse
    {
        $owner = $this->ownerResolver->resolve();
        $isAdmin = $this->ownerResolver->isAdmin();

        return new JsonResponse([
            'owner' => $owner,
            'isAdmin' => $isAdmin,
            'accessMode' => $this->config->accessMode,
            'apiPrefix' => $this->config->apiPrefix,
            'dashboardPath' => $this->config->dashboardPath,
            'title' => $this->config->dashboardTitle,
            'importMaxFileSize' => $this->config->importMaxFileSize,
            'photoMaxSize' => $this->config->photoMaxSize
        ]);
    }
}
