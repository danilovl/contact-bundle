<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Repository;

use Danilovl\ContactBundle\Entity\ContactTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<ContactTag>
 */
class ContactTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactTag::class);
    }

    /**
     * @param list<string> $keys
     * @return list<ContactTag>
     */
    public function findByOwnerAndKeys(string $ownerIdentifier, array $keys): array
    {
        if (count($keys) === 0) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('tag')
            ->where('tag.ownerIdentifier = :ownerIdentifier')
            ->andWhere('tag.nameKey IN (:keys)')
            ->setParameter('ownerIdentifier', $ownerIdentifier)
            ->setParameter('keys', $keys);

        /** @var list<ContactTag> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @return list<ContactTag>
     */
    public function findByOwner(?string $ownerIdentifier): array
    {
        $queryBuilder = $this->createQueryBuilder('tag')
            ->orderBy('tag.name', SortDirection::Ascending);

        if ($ownerIdentifier !== null) {
            $queryBuilder
                ->where('tag.ownerIdentifier = :ownerIdentifier')
                ->setParameter('ownerIdentifier', $ownerIdentifier);
        }

        /** @var list<ContactTag> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function findOneByOwner(int $id, ?string $ownerIdentifier): ?ContactTag
    {
        $queryBuilder = $this->createQueryBuilder('tag')
            ->where('tag.id = :id')
            ->setParameter('id', $id);

        if ($ownerIdentifier !== null) {
            $queryBuilder
                ->andWhere('tag.ownerIdentifier = :ownerIdentifier')
                ->setParameter('ownerIdentifier', $ownerIdentifier);
        }

        /** @var ContactTag|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }
}
