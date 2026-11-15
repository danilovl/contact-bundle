<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Controller\Api;

use Danilovl\ContactBundle\Entity\ContactTag;
use Danilovl\ContactBundle\Service\ContactTagManager;
use Symfony\Component\HttpFoundation\{
    JsonResponse,
    Request,
    Response
};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '%danilovl_contact.api.prefix%/tags', name: 'danilovl_contact_api_tag_')]
readonly class TagApiController
{
    public function __construct(private ContactTagManager $tagManager) {}

    #[Route(path: '', name: 'list', methods: [Request::METHOD_GET])]
    public function list(): JsonResponse
    {
        $tags = $this->tagManager->list();
        $items = array_map($this->normalize(...), $tags);

        return new JsonResponse(['items' => $items]);
    }

    #[Route(path: '/{id}', name: 'rename', requirements: ['id' => '\d+'], methods: [Request::METHOD_PUT])]
    public function rename(Request $request, int $id): JsonResponse
    {
        $content = $request->getContent();
        $payload = json_decode($content, true);
        $name = is_array($payload) ? $payload['name'] ?? null : null;

        if (!is_string($name)) {
            throw new BadRequestHttpException('Field "name" is required.');
        }

        $tag = $this->tagManager->rename($id, $name);

        return new JsonResponse($this->normalize($tag));
    }

    #[Route(path: '/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: [Request::METHOD_DELETE])]
    public function delete(int $id): JsonResponse
    {
        $this->tagManager->delete($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: int, name: string, count: int}
     */
    private function normalize(ContactTag $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'count' => $tag->getContacts()->count()
        ];
    }
}
