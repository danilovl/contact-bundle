<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_contact_date')]
#[ORM\Entity]
class ContactDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'dates')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Contact $contact;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 64, nullable: true)]
    private ?string $label;

    #[ORM\Column(name: 'date', type: Types::STRING, length: 10)]
    private string $date;

    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['default' => 0])]
    private int $position;

    public function __construct(string $date, ?string $label = null, int $position = 0)
    {
        $this->date = $date;
        $this->label = $label;
        $this->position = $position;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
