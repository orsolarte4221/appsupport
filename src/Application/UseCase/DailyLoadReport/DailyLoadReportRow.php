<?php
declare(strict_types=1);

namespace App\Application\UseCase\DailyLoadReport;

final class DailyLoadReportRow
{
    public function __construct(
        public readonly string $worker,
        public readonly int $total
    ) {
    }
}