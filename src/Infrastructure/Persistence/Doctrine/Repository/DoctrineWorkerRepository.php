<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\Worker as DomainWorker;
use App\Domain\Port\WorkerRepository as DomainWorkerRepository;
use App\Repository\WorkerRepository as OrmWorkerRepository;

final class DoctrineWorkerRepository implements DomainWorkerRepository
{
    public function __construct(private OrmWorkerRepository $repo)
    {
    }

    /**
     * @return list<DomainWorker>
     */
    public function findAllAlphabetical(): array
    {
        $entities = $this->repo->findAllAlphabetical();
        $out = [];
        foreach ($entities as $e) {
            $out[] = new DomainWorker(
                id: $e->getId(),
                name: $e->getName()
            );
        }
        return $out;
    }

    public function findById(int $id): ?DomainWorker
    {
        $e = $this->repo->find($id);
        if ($e === null) {
            return null;
        }

        return new DomainWorker(
            id: $e->getId(),
            name: $e->getName()
        );
    }
}