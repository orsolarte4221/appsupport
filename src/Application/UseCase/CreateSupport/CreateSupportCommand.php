<?php
declare(strict_types=1);

namespace App\Application\UseCase\CreateSupport;

final class CreateSupportCommand
{
    public function __construct(
        public readonly string $description,
        public readonly int $complexity
    ) {
    }
}