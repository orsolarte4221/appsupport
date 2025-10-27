<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Support;

#[ORM\Entity(repositoryClass: \App\Repository\WorkerRepository::class)]
class Worker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    /**
     * @var Collection<int, Support>
     */
    #[ORM\OneToMany(mappedBy: 'worker', targetEntity: Support::class)]
    private Collection $supports;

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->supports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Support>
     */
    public function getSupports(): Collection
    {
        return $this->supports;
    }

    public function addSupport(Support $support): self
    {
        if (!$this->supports->contains($support)) {
            $this->supports->add($support);
            $support->setWorker($this);
        }

        return $this;
    }

    public function removeSupport(Support $support): self
    {
        if ($this->supports->removeElement($support)) {
            if ($support->getWorker() === $this) {
                $support->setWorker(null);
            }
        }

        return $this;
    }

    /**
     * Sum of complexities for supports assigned on the given date in America/Bogota timezone.
     */
    public function dailyLoad(DateTimeInterface $date): int
    {
        $tz = new DateTimeZone('America/Bogota');

        // Normalize input date to the local date string used for comparison
        $localDateString = (new DateTimeImmutable($date->format('Y-m-d'), $tz))->format('Y-m-d');

        $sum = 0;
        foreach ($this->supports as $support) {
            $assignedAt = $support->getAssignedAt();
            if ($assignedAt === null) {
                continue;
            }

            $assignedLocalDate = $assignedAt->setTimezone($tz)->format('Y-m-d');
            if ($assignedLocalDate === $localDateString) {
                $sum += $support->getComplexity();
            }
        }

        return $sum;
    }
}