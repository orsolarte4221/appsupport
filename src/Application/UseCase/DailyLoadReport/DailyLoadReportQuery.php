<?php
declare(strict_types=1);

namespace App\Application\UseCase\DailyLoadReport;

final class DailyLoadReportQuery
{
    public function __construct(public readonly \DateTimeImmutable $date)
    {
    }
}