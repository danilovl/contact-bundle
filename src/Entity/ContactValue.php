<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use Danilovl\ContactBundle\Model\ContactValueKind;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_contact_value')]
#[ORM\Index(name: 'idx_danilovl_contact_value_value', columns: ['value'])]
#[ORM\Index(name: 'idx_danilovl_contact_value_normalized', columns: ['kind', 'normalized_value'])]
#[ORM\Entity]
class ContactValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'values')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Contact $contact;

    #[ORM\Column(name: 'kind', type: Types::STRING, length: 16, enumType: ContactValueKind::class)]
    private ContactValueKind $kind;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 64, nullable: true)]
    private ?string $label;

    #[ORM\Column(name: 'value', type: Types::STRING, length: 255)]
    private string $value;

    #[ORM\Column(name: 'normalized_value', type: Types::STRING, length: 255, nullable: true)]
    private ?string $normalizedValue;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: 'related_contact_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Contact $relatedContact;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $primary;

    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['default' => 0])]
    private int $position;

    public function __construct(
        ContactValueKind $kind,
        string $value,
        ?string $label = null,
        bool $primary = false,
        int $position = 0,
        ?string $normalizedValue = null,
        ?Contact $relatedContact = null
    ) {
        $this->kind = $kind;
        $this->value = $value;
        $this->label = $label;
        $this->primary = $primary;
        $this->position = $position;
        $this->normalizedValue = $normalizedValue;
        $this->relatedContact = $relatedContact;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function getKind(): ContactValueKind
    {
        return $this->kind;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getNormalizedValue(): ?string
    {
        return $this->normalizedValue;
    }

    public function setNormalizedValue(?string $normalizedValue): void
    {
        $this->normalizedValue = $normalizedValue;
    }

    public function getRelatedContact(): ?Contact
    {
        return $this->relatedContact;
    }

    public function setRelatedContact(?Contact $relatedContact): void
    {
        $this->relatedContact = $relatedContact;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
