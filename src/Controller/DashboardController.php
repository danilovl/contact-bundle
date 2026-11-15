<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Controller;

use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[Route(path: '%danilovl_contact.dashboard.path%', name: 'danilovl_contact_dashboard_')]
readonly class DashboardController
{
    public function __construct(
        private ContactConfig $config,
        private Environment $twig
    ) {}

    #[Route(path: '', name: 'index', methods: [Request::METHOD_GET])]
    public function index(): Response
    {
        $context = [
            'apiPrefix' => $this->config->apiPrefix,
            'title' => $this->config->dashboardTitle
        ];

        $content = $this->twig->render('@Contact/dashboard/index.html.twig', $context);

        return new Response($content);
    }
}
