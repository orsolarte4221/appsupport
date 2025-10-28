<?php
declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Support;
use DateTimeInterface;

interface SupportRepository
{
    public function save(Support $support): void;

    /**
     * @return list<Support>
     */
    public function findAllOrdered(): array;

    public function dailyLoadForWorker(DateTimeInterface $date, int $workerId): int;
}