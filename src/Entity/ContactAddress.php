<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_contact_address')]
#[ORM\Entity]
class ContactAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Contact $contact;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 64, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(name: 'street', type: Types::STRING, length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(name: 'extended', type: Types::STRING, length: 255, nullable: true)]
    private ?string $extended = null;

    #[ORM\Column(name: 'city', type: Types::STRING, length: 128, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(name: 'region', type: Types::STRING, length: 128, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(name: 'postal_code', type: Types::STRING, length: 32, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(name: 'country', type: Types::STRING, length: 128, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getExtended(): ?string
    {
        return $this->extended;
    }

    public function setExtended(?string $extended): void
    {
        $this->extended = $extended;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
