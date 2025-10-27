<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Worker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Worker>
 */
class WorkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Worker::class);
    }

    /**
     * @return array<Worker>
     */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}