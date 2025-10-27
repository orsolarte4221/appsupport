<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Support;
use App\Entity\Worker;
use App\Repository\WorkerRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

class SupportAssigner
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkerRepository $workerRepository,
    ) {
    }

    /**
     * Assigns the given support to the worker with the lowest daily load for today
     * (America/Bogota timezone). Tie-breaker: alphabetical by worker name.
     * Persists and flushes the support.
     */
    public function assignAndPersist(Support $support): void
    {
        $todayBogota = new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));

        /** @var list<Worker> $workers */
        $workers = $this->workerRepository->findAllAlphabetical();

        $minWorker = null;
        $minLoad = PHP_INT_MAX;

        foreach ($workers as $worker) {
            $load = $worker->dailyLoad($todayBogota);
            if ($load < $minLoad) {
                $minLoad = $load;
                $minWorker = $worker;
            }
        }

        if ($minWorker === null) {
            throw new \RuntimeException('No workers available to assign this support.');
        }

        $support->setWorker($minWorker);
        $support->setAssignedAt($todayBogota);

        $this->em->persist($support);
        $this->em->flush();
    }
}