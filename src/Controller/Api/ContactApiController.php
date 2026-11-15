<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Controller\Api;

use Danilovl\ContactBundle\Model\DataUri;
use Danilovl\ContactBundle\Service\{
    ContactDataFactory,
    ContactManager,
    ContactNameFormatter,
    ContactNormalizer
};
use Danilovl\ContactBundle\Service\VCard\VCardWriter;
use Symfony\Component\HttpFoundation\{
    HeaderUtils,
    JsonResponse,
    Request,
    Response
};
use Symfony\Component\HttpKernel\Exception\{
    BadRequestHttpException,
    NotFoundHttpException
};
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '%danilovl_contact.api.prefix%/contacts', name: 'danilovl_contact_api_contact_')]
readonly class ContactApiController
{
    public function __construct(
        private ContactManager $contactManager,
        private ContactNormalizer $contactNormalizer,
        private ContactDataFactory $contactDataFactory,
        private ContactNameFormatter $nameFormatter,
        private VCardWriter $vCardWriter
    ) {}

    #[Route(path: '', name: 'list', methods: [Request::METHOD_GET])]
    public function list(Request $request): JsonResponse
    {
        $tag = $request->query->get('tag');
        $tagFilter = is_string($tag) && $tag !== '' ? $tag : null;

        $contacts = $this->contactManager->list(null, $tagFilter);
        $items = array_map($this->contactNormalizer->normalizeSummary(...), $contacts);

        return new JsonResponse(['items' => $items]);
    }

    #[Route(path: '/duplicates', name: 'duplicates', methods: [Request::METHOD_GET])]
    public function duplicates(): JsonResponse
    {
        $groups = $this->contactManager->findDuplicates();
        $items = [];

        foreach ($groups as $group) {
            $items[] = array_map($this->contactNormalizer->normalizeSummary(...), $group);
        }

        return new JsonResponse(['groups' => $items]);
    }

    #[Route(path: '/merge', name: 'merge', methods: [Request::METHOD_POST])]
    public function merge(Request $request): JsonResponse
    {
        $payload = $this->getPayload($request);
        $primaryId = $payload['primaryId'] ?? null;
        $rawIds = $payload['ids'] ?? [];

        if (!is_int($primaryId) || !is_array($rawIds)) {
            throw new BadRequestHttpException('Fields "primaryId" and "ids" are required.');
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_int($rawId)) {
                $ids[] = $rawId;
            }
        }

        $contact = $this->contactManager->merge($primaryId, $ids);
        $data = $this->contactNormalizer->normalizeFull($contact);

        return new JsonResponse($data);
    }

    #[Route(path: '/{id}', name: 'get', requirements: ['id' => '\d+'], methods: [Request::METHOD_GET])]
    public function get(int $id): JsonResponse
    {
        $contact = $this->contactManager->get($id);
        $data = $this->contactNormalizer->normalizeFull($contact);

        return new JsonResponse($data);
    }

    #[Route(path: '', name: 'create', methods: [Request::METHOD_POST])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->getPayload($request);
        $contactData = $this->contactDataFactory->create($payload);
        $contact = $this->contactManager->create($contactData);
        $data = $this->contactNormalizer->normalizeFull($contact);

        return new JsonResponse($data, Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'update', requirements: ['id' => '\d+'], methods: [Request::METHOD_PUT])]
    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $this->getPayload($request);
        $contactData = $this->contactDataFactory->create($payload);
        $contact = $this->contactManager->update($id, $contactData);
        $data = $this->contactNormalizer->normalizeFull($contact);

        return new JsonResponse($data);
    }

    #[Route(path: '/{id}/favorite', name: 'favorite', requirements: ['id' => '\d+'], methods: [Request::METHOD_PATCH])]
    public function favorite(Request $request, int $id): JsonResponse
    {
        $payload = $this->getPayload($request);
        $favorite = ($payload['favorite'] ?? false) === true;
        $contact = $this->contactManager->setFavorite($id, $favorite);
        $data = $this->contactNormalizer->normalizeSummary($contact);

        return new JsonResponse($data);
    }

    #[Route(path: '/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: [Request::METHOD_DELETE])]
    public function delete(int $id): JsonResponse
    {
        $this->contactManager->delete($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/{id}/photo', name: 'photo', requirements: ['id' => '\d+'], methods: [Request::METHOD_GET])]
    public function photo(Request $request, int $id): Response
    {
        $contact = $this->contactManager->get($id);
        $photo = $contact->getPhoto();
        $dataUri = $photo === null ? null : DataUri::parse($photo);

        if ($dataUri === null) {
            throw new NotFoundHttpException('Photo not found.');
        }

        $etag = md5($dataUri->base64);
        $response = new Response;
        $response->setEtag($etag);
        $response->setPrivate();
        $response->setMaxAge(31_536_000);

        $isNotModified = $response->isNotModified($request);
        if ($isNotModified) {
            return $response;
        }

        $binary = $dataUri->getBinary();
        $response->setContent($binary);
        $response->headers->set('Content-Type', $dataUri->mimeType);

        return $response;
    }

    #[Route(path: '/{id}/vcard', name: 'vcard', requirements: ['id' => '\d+'], methods: [Request::METHOD_GET])]
    public function vCard(int $id): Response
    {
        $contact = $this->contactManager->get($id);
        $content = $this->vCardWriter->write($contact);

        $displayName = $this->nameFormatter->getDisplayName($contact);
        $fileName = $this->createFileName($displayName);

        return $this->createVCardResponse($content, $fileName);
    }

    /**
     * @return array<mixed>
     */
    private function getPayload(Request $request): array
    {
        $content = $request->getContent();
        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        return $payload;
    }

    private function createFileName(string $displayName): string
    {
        $fileName = preg_replace('~[^\p{L}\p{N}._-]+~u', '_', $displayName) ?? '';
        $fileName = mb_trim($fileName, '_');

        return ($fileName === '' ? 'contact' : $fileName) . '.vcf';
    }

    private function createVCardResponse(string $content, string $fileName): Response
    {
        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'contact.vcf');

        $response->headers->set('Content-Type', 'text/vcard; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
