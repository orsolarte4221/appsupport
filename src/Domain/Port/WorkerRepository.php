<?php
declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Worker;

interface WorkerRepository
{
    /**
     * @return list<Worker>
     */
    public function findAllAlphabetical(): array;

    public function findById(int $id): ?Worker;
}