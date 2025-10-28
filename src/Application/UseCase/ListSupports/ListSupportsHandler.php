<?php
declare(strict_types=1);

namespace App\Application\UseCase\ListSupports;

use App\Domain\Port\SupportRepository;

final class ListSupportsHandler
{
    public function __construct(private SupportRepository $supports)
    {
    }

    /**
     * @return list<ListSupportsItem>
     */
    public function handle(ListSupportsQuery $query): array
    {
        $items = [];
        foreach ($this->supports->findAllOrdered() as $s) {
            $items[] = new ListSupportsItem(
                id: (int) $s->id(),
                description: $s->description(),
                complexity: $s->complexity(),
                assignedAtIso: $s->assignedAt()?->format('Y-m-d\TH:i:s'),
                workerName: $s->worker()?->name()
            );
        }
        return $items;
    }
}