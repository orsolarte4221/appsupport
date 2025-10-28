<?php
declare(strict_types=1);

namespace App\Application\UseCase\DailyLoadReport;

use App\Domain\Port\SupportRepository;
use App\Domain\Port\WorkerRepository;

final class DailyLoadReportHandler
{
    public function __construct(
        private WorkerRepository $workers,
        private SupportRepository $supports
    ) {
    }

    /**
     * @return list<DailyLoadReportRow>
     */
    public function handle(DailyLoadReportQuery $query): array
    {
        $rows = [];
        foreach ($this->workers->findAllAlphabetical() as $w) {
            $id = $w->id();
            if ($id === null) {
                continue;
            }

            $rows[] = new DailyLoadReportRow(
                worker: $w->name(),
                total: $this->supports->dailyLoadForWorker($query->date, $id)
            );
        }

        return $rows;
    }
}