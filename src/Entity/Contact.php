<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Entity;

use DateTimeImmutable;
use Danilovl\ContactBundle\Model\{
    ContactValueKind,
    DataUri,
    UidGenerator
};
use Danilovl\ContactBundle\Repository\ContactRepository;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

#[ORM\Table(name: 'danilovl_contact')]
#[ORM\Index(name: 'idx_danilovl_contact_owner', columns: ['owner_identifier'])]
#[ORM\UniqueConstraint(name: 'uniq_danilovl_contact_owner_uid', columns: ['owner_identifier', 'uid'])]
#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'owner_identifier', type: Types::STRING, length: 180)]
    private string $ownerIdentifier;

    #[ORM\Column(name: 'uid', type: Types::STRING, length: 255, nullable: true)]
    private ?string $uid = null;

    #[ORM\Column(name: 'revision', type: Types::INTEGER, options: ['default' => 1])]
    private int $revision = 1;

    #[ORM\Column(name: 'prefix', type: Types::STRING, length: 32, nullable: true)]
    private ?string $prefix = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 128, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'middle_name', type: Types::STRING, length: 128, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 128, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'suffix', type: Types::STRING, length: 32, nullable: true)]
    private ?string $suffix = null;

    #[ORM\Column(name: 'nickname', type: Types::STRING, length: 128, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(name: 'company', type: Types::STRING, length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(name: 'department', type: Types::STRING, length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'job_title', type: Types::STRING, length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(name: 'birthday', type: Types::STRING, length: 10, nullable: true)]
    private ?string $birthday = null;

    #[ORM\Column(name: 'gender', type: Types::STRING, length: 1, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(name: 'pronouns', type: Types::STRING, length: 64, nullable: true)]
    private ?string $pronouns = null;

    #[ORM\Column(name: 'language', type: Types::STRING, length: 35, nullable: true)]
    private ?string $language = null;

    #[ORM\Column(name: 'timezone', type: Types::STRING, length: 64, nullable: true)]
    private ?string $timezone = null;

    /**
     * @deprecated moved to ContactPhoto, filled only before danilovl:contact:upgrade
     */
    #[ORM\Column(name: 'photo', type: Types::TEXT, nullable: true)]
    private ?string $legacyPhoto = null;

    #[ORM\Column(name: 'photo_hash', type: Types::STRING, length: 32, nullable: true)]
    private ?string $photoHash = null;

    #[ORM\OneToOne(targetEntity: ContactPhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'photo_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ContactPhoto $photoFile = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'favorite', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $favorite = false;

    /**
     * @deprecated moved to ContactTag, filled only before danilovl:contact:upgrade
     * @var list<string>|null
     */
    #[ORM\Column(name: 'tags', type: Types::JSON, nullable: true)]
    private ?array $legacyTags = null;

    /**
     * @var Collection<int, ContactTag>
     */
    #[ORM\ManyToMany(targetEntity: ContactTag::class, inversedBy: 'contacts')]
    #[ORM\JoinTable(name: 'danilovl_contact_tag_link')]
    private Collection $tagEntities;

    /**
     * @var list<array{key: string, value: string}>|null
     */
    #[ORM\Column(name: 'custom_fields', type: Types::JSON, nullable: true)]
    private ?array $customFields = null;

    /**
     * @var list<array{company: string|null, jobTitle: string|null, startDate: string|null, endDate: string|null}>|null
     */
    #[ORM\Column(name: 'work_history', type: Types::JSON, nullable: true)]
    private ?array $workHistory = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, ContactValue>
     */
    #[ORM\OneToMany(targetEntity: ContactValue::class, mappedBy: 'contact', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    private Collection $values;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(targetEntity: ContactAddress::class, mappedBy: 'contact', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    private Collection $addresses;

    /**
     * @var Collection<int, ContactDate>
     */
    #[ORM\OneToMany(targetEntity: ContactDate::class, mappedBy: 'contact', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    private Collection $dates;

    public function __construct(string $ownerIdentifier, ?string $uid = null)
    {
        $now = new DateTimeImmutable;

        $this->ownerIdentifier = $ownerIdentifier;
        $this->uid = $uid ?? UidGenerator::generate();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->values = new ArrayCollection;
        $this->addresses = new ArrayCollection;
        $this->dates = new ArrayCollection;
        $this->tagEntities = new ArrayCollection;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
        $this->revision++;
    }

    public function isPersisted(): bool
    {
        return isset($this->id);
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

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getPronouns(): ?string
    {
        return $this->pronouns;
    }

    public function setPronouns(?string $pronouns): void
    {
        $this->pronouns = $pronouns;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function setSuffix(?string $suffix): void
    {
        $this->suffix = $suffix;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getPhoto(): ?string
    {
        if ($this->photoFile !== null) {
            return DataUri::create($this->photoFile->getMimeType(), $this->photoFile->getData());
        }

        return $this->legacyPhoto;
    }

    public function hasPhoto(): bool
    {
        return $this->photoHash !== null || $this->legacyPhoto !== null;
    }

    public function getPhotoHash(): ?string
    {
        if ($this->photoHash !== null) {
            return $this->photoHash;
        }

        if ($this->legacyPhoto === null) {
            return null;
        }

        return md5($this->legacyPhoto);
    }

    public function setPhoto(?string $photo): void
    {
        $this->legacyPhoto = null;
        $dataUri = $photo === null ? null : DataUri::parse($photo);

        if ($dataUri === null) {
            $this->photoFile = null;
            $this->photoHash = null;

            return;
        }

        $hash = md5($dataUri->base64);
        if ($this->photoFile !== null && $this->photoFile->getHash() === $hash) {
            return;
        }

        $this->photoFile = new ContactPhoto($dataUri->mimeType, $dataUri->base64);
        $this->photoHash = $hash;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function isFavorite(): bool
    {
        return $this->favorite;
    }

    public function setFavorite(bool $favorite): void
    {
        $this->favorite = $favorite;
    }

    /**
     * @return list<string>
     */
    public function getLegacyTags(): array
    {
        return $this->legacyTags ?? [];
    }

    public function clearLegacyTags(): void
    {
        $this->legacyTags = null;
    }

    public function hasLegacyPhoto(): bool
    {
        return $this->legacyPhoto !== null;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        $names = [];
        foreach ($this->tagEntities as $tag) {
            $names[] = $tag->getName();
        }

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * @return Collection<int, ContactTag>
     */
    public function getTagEntities(): Collection
    {
        return $this->tagEntities;
    }

    /**
     * @param list<ContactTag> $tags
     */
    public function setTagEntities(array $tags): void
    {
        $this->tagEntities->clear();

        foreach ($tags as $tag) {
            $this->tagEntities->add($tag);
        }
    }

    public function addTagEntity(ContactTag $tag): void
    {
        if (!$this->tagEntities->contains($tag)) {
            $this->tagEntities->add($tag);
        }
    }

    public function removeTagEntity(ContactTag $tag): void
    {
        $this->tagEntities->removeElement($tag);
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    public function getCustomFields(): array
    {
        return $this->customFields ?? [];
    }

    /**
     * @param list<array{key: string, value: string}> $customFields
     */
    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields === [] ? null : $customFields;
    }

    /**
     * @return list<array{company: string|null, jobTitle: string|null, startDate: string|null, endDate: string|null}>
     */
    public function getWorkHistory(): array
    {
        return $this->workHistory ?? [];
    }

    /**
     * @param list<array{company: string|null, jobTitle: string|null, startDate: string|null, endDate: string|null}> $workHistory
     */
    public function setWorkHistory(array $workHistory): void
    {
        $this->workHistory = $workHistory === [] ? null : $workHistory;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, ContactValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    /**
     * @return list<ContactValue>
     */
    public function getValuesByKind(ContactValueKind $kind): array
    {
        $result = [];
        foreach ($this->values as $value) {
            if ($value->getKind() === $kind) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function addValue(ContactValue $value): void
    {
        $value->setContact($this);
        $this->values->add($value);
    }

    public function removeValue(ContactValue $value): void
    {
        $this->values->removeElement($value);
    }

    public function clearValues(): void
    {
        $this->values->clear();
    }

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(ContactAddress $address): void
    {
        $address->setContact($this);
        $this->addresses->add($address);
    }

    public function clearAddresses(): void
    {
        $this->addresses->clear();
    }

    /**
     * @return Collection<int, ContactDate>
     */
    public function getDates(): Collection
    {
        return $this->dates;
    }

    public function addDate(ContactDate $date): void
    {
        $date->setContact($this);
        $this->dates->add($date);
    }

    public function clearDates(): void
    {
        $this->dates->clear();
    }
}
