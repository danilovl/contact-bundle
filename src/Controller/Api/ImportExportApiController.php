<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Controller\Api;

use DateTimeImmutable;
use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use Danilovl\ContactBundle\Service\{
    ContactImporter,
    ContactManager
};
use Danilovl\ContactBundle\Service\VCard\VCardWriter;
use Symfony\Component\HttpFoundation\{
    HeaderUtils,
    JsonResponse,
    Request,
    Response
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '%danilovl_contact.api.prefix%', name: 'danilovl_contact_api_')]
readonly class ImportExportApiController
{
    public function __construct(
        private ContactManager $contactManager,
        private ContactImporter $contactImporter,
        private VCardWriter $vCardWriter,
        private ContactConfig $config
    ) {}

    #[Route(path: '/export', name: 'export', methods: [Request::METHOD_GET])]
    public function export(): Response
    {
        $contacts = $this->contactManager->listFull();
        $content = $this->vCardWriter->writeAll($contacts);

        $date = new DateTimeImmutable;
        $fileName = 'contacts-' . $date->format('Y-m-d') . '.vcf';
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/vcard; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route(path: '/import', name: 'import', methods: [Request::METHOD_POST])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('File "file" is required.');
        }

        $fileSize = $file->getSize();
        if ($fileSize > $this->config->importMaxFileSize) {
            throw new BadRequestHttpException('File is too large.');
        }

        $content = $file->getContent();
        $result = $this->contactImporter->import($content);

        return new JsonResponse($result);
    }
}
