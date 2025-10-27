<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Support;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Support>
 */
class SupportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Support::class);
    }

    /**
     * Returns supports with their worker ordered by assignedAt DESC then id ASC.
     *
     * @return list<Support>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.worker', 'w')->addSelect('w')
            ->orderBy('s.assignedAt', 'DESC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}