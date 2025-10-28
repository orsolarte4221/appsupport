<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\Support as DomainSupport;
use App\Domain\Model\Worker as DomainWorker;
use App\Domain\Port\SupportRepository as DomainSupportRepository;
use App\Entity\Support as OrmSupport;
use App\Repository\SupportRepository as OrmSupportRepository;
use App\Repository\WorkerRepository as OrmWorkerRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSupportRepository implements DomainSupportRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrmSupportRepository $supportRepo,
        private OrmWorkerRepository $workerRepo
    ) {
    }

    public function save(DomainSupport $support): void
    {
        // Insert-only for now (domain id is null)
        $entity = new OrmSupport();
        $entity->setDescription($support->description());
        $entity->setComplexity($support->complexity());

        $assignedAt = $support->assignedAt();
        if ($assignedAt instanceof DateTimeInterface) {
            // Persist as immutable datetime
            $entity->setAssignedAt(DateTimeImmutable::createFromInterface($assignedAt));
        } else {
            $entity->setAssignedAt(null);
        }

        $domainWorker = $support->worker();
        if ($domainWorker !== null) {
            $wid = $domainWorker->id();
            if ($wid === null) {
                throw new \LogicException('Domain worker must have an id to persist support.');
            }
            $workerEntity = $this->workerRepo->find($wid);
            if ($workerEntity === null) {
                throw new \RuntimeException("Worker #{$wid} not found while persisting support.");
            }
            $entity->setWorker($workerEntity);
        } else {
            $entity->setWorker(null);
        }

        $this->em->persist($entity);
        $this->em->flush();

        // Reflect generated id back into the domain aggregate
        if (method_exists($support, 'persistedAs')) {
            $support->persistedAs((int) $entity->getId());
        }
    }

    /**
     * @return list<DomainSupport>
     */
    public function findAllOrdered(): array
    {
        $entities = $this->supportRepo->findAllOrdered();
        $out = [];
        foreach ($entities as $e) {
            $out[] = $this->mapEntityToDomain($e);
        }
        return $out;
    }

    public function dailyLoadForWorker(DateTimeInterface $date, int $workerId): int
    {
        // Compute the Bogota-local day boundaries and convert to UTC instants for comparison.
        $bogota = new DateTimeZone('America/Bogota');

        // Normalize to YYYY-MM-DD using provided date but Bogota TZ
        $dayLocal = new DateTimeImmutable($date->format('Y-m-d') . ' 00:00:00', $bogota);
        $nextDayLocal = $dayLocal->modify('+1 day');

        // Convert those instants to UTC for DB comparison (assuming DB stores UTC)
        $utc = new DateTimeZone('UTC');
        $startUtc = $dayLocal->setTimezone($utc);
        $endUtc = $nextDayLocal->setTimezone($utc);

        $qb = $this->em->createQueryBuilder();
        $qb->select('COALESCE(SUM(s.complexity), 0)')
            ->from(OrmSupport::class, 's')
            ->where('s.worker = :wid')
            ->andWhere('s.assignedAt >= :start')
            ->andWhere('s.assignedAt < :end')
            ->setParameter('wid', $workerId)
            ->setParameter('start', $startUtc)
            ->setParameter('end', $endUtc);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function mapEntityToDomain(OrmSupport $e): DomainSupport
    {
        $workerEntity = $e->getWorker();
        $domainWorker = null;
        if ($workerEntity !== null) {
            $domainWorker = new DomainWorker(
                id: $workerEntity->getId(),
                name: $workerEntity->getName()
            );
        }

        $domain = new DomainSupport(
            id: $e->getId(),
            description: $e->getDescription(),
            complexity: $e->getComplexity(),
            assignedAt: $e->getAssignedAt(),
            worker: $domainWorker
        );

        return $domain;
    }
}