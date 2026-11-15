<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_contact_photo')]
#[ORM\Entity]
class ContactPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 64)]
    private string $mimeType;

    #[ORM\Column(name: 'data', type: Types::TEXT)]
    private string $data;

    #[ORM\Column(name: 'hash', type: Types::STRING, length: 32)]
    private string $hash;

    public function __construct(string $mimeType, string $data)
    {
        $this->mimeType = $mimeType;
        $this->data = $data;
        $this->hash = md5($data);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
