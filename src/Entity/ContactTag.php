<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use Danilovl\ContactBundle\Repository\ContactTagRepository;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_contact_tag')]
#[ORM\UniqueConstraint(name: 'uniq_danilovl_contact_tag_owner_key', columns: ['owner_identifier', 'name_key'])]
#[ORM\Entity(repositoryClass: ContactTagRepository::class)]
class ContactTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'owner_identifier', type: Types::STRING, length: 180)]
    private string $ownerIdentifier;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 64)]
    private string $name;

    #[ORM\Column(name: 'name_key', type: Types::STRING, length: 64)]
    private string $nameKey;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, mappedBy: 'tagEntities')]
    private Collection $contacts;

    public function __construct(string $ownerIdentifier, string $name)
    {
        $this->ownerIdentifier = $ownerIdentifier;
        $this->contacts = new ArrayCollection;

        $this->rename($name);
    }

    public static function createKey(string $name): string
    {
        return mb_strtolower($name);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerIdentifier(): string
    {
        return $this->ownerIdentifier;
    }

    public function setOwnerIdentifier(string $ownerIdentifier): void
    {
        $this->ownerIdentifier = $ownerIdentifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameKey(): string
    {
        return $this->nameKey;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->nameKey = self::createKey($name);
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }
}
