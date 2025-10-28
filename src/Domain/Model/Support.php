<?php
declare(strict_types=1);

namespace App\Domain\Model;

use DateTimeImmutable;

final class Support
{
    public function __construct(
        private ?int $id,
        private string $description,
        private int $complexity,
        private ?DateTimeImmutable $assignedAt,
        private ?Worker $worker
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function complexity(): int
    {
        return $this->complexity;
    }

    public function assignedAt(): ?DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function worker(): ?Worker
    {
        return $this->worker;
    }

    public function assignTo(Worker $worker, DateTimeImmutable $when): void
    {
        $this->worker = $worker;
        $this->assignedAt = $when;
    }

    public function persistedAs(int $id): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new \LogicException('Support already has a different id.');
        }
        $this->id = $id;
    }
}