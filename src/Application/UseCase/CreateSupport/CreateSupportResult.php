<?php
declare(strict_types=1);

namespace App\Application\UseCase\CreateSupport;

final class CreateSupportResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $description,
        public readonly int $complexity,
        public readonly ?string $assignedAtIso,
        public readonly ?string $workerName
    ) {
    }
}