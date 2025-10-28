<?php
declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Support;
use App\Domain\Model\Worker;
use App\Domain\Port\Clock;
use App\Domain\Port\SupportRepository;
use App\Domain\Port\WorkerRepository;

final class SupportAssignmentPolicy
{
    public function __construct(
        private WorkerRepository $workers,
        private SupportRepository $supports,
        private Clock $clock
    ) {
    }

    public function pickWorkerFor(Support $support): Worker
    {
        $today = $this->clock->now();

        $minLoad = PHP_INT_MAX;
        $chosen = null;

        foreach ($this->workers->findAllAlphabetical() as $worker) {
            $id = $worker->id();
            if ($id === null) {
                continue;
            }
            $load = $this->supports->dailyLoadForWorker($today, $id);
            if ($load < $minLoad) {
                $minLoad = $load;
                $chosen = $worker;
            }
        }

        if ($chosen === null) {
            throw new \RuntimeException('No workers available to assign this support.');
        }

        return $chosen;
    }
}