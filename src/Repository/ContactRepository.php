<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Repository;

use Danilovl\ContactBundle\Entity\{
    Contact,
    ContactTag,
    ContactValue
};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return list<Contact>
     */
    public function findByOwnerWithValues(?string $ownerIdentifier, ?string $tag = null): array
    {
        $queryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->addSelect('contact_value')
            ->leftJoin('contact.values', 'contact_value')
            ->orderBy('contact.id', SortDirection::Ascending);

        if ($tag !== null) {
            $tagKey = ContactTag::createKey($tag);
            $queryBuilder
                ->innerJoin('contact.tagEntities', 'filter_tag', 'WITH', 'filter_tag.nameKey = :tagKey')
                ->setParameter('tagKey', $tagKey);
        }

        /** @var list<Contact> $result */
        $result = $queryBuilder->getQuery()->getResult();

        $tagQueryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->addSelect('contact_tag')
            ->leftJoin('contact.tagEntities', 'contact_tag');
        $tagQueryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @return list<ContactValue>
     */
    public function findRelationsTo(Contact $contact): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('contact_value')
            ->from(ContactValue::class, 'contact_value')
            ->where('contact_value.relatedContact = :contact')
            ->setParameter('contact', $contact);

        /** @var list<ContactValue> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function findOneByOwnerAndUid(string $uid, string $ownerIdentifier): ?Contact
    {
        $queryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->andWhere('contact.uid = :uid')
            ->setParameter('uid', $uid);

        /** @var Contact|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return list<Contact>
     */
    public function findByOwnerFull(?string $ownerIdentifier): array
    {
        $contacts = $this->findByOwnerWithValues($ownerIdentifier);

        $addressQueryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->addSelect('contact_address')
            ->leftJoin('contact.addresses', 'contact_address');
        $addressQueryBuilder->getQuery()->getResult();

        $dateQueryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->addSelect('contact_date')
            ->leftJoin('contact.dates', 'contact_date');
        $dateQueryBuilder->getQuery()->getResult();

        return $contacts;
    }

    public function findOneByOwner(int $id, ?string $ownerIdentifier): ?Contact
    {
        $queryBuilder = $this->createOwnerQueryBuilder($ownerIdentifier)
            ->andWhere('contact.id = :id')
            ->setParameter('id', $id);

        /** @var Contact|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }

    private function createOwnerQueryBuilder(?string $ownerIdentifier): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('contact');

        if ($ownerIdentifier === null) {
            return $queryBuilder;
        }

        return $queryBuilder
            ->where('contact.ownerIdentifier = :ownerIdentifier')
            ->setParameter('ownerIdentifier', $ownerIdentifier);
    }
}
