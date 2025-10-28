<?php
declare(strict_types=1);

namespace App\Application\UseCase\CreateSupport;

use App\Domain\Model\Support;
use App\Domain\Port\SupportRepository;
use App\Domain\Port\Clock;
use App\Domain\Service\SupportAssignmentPolicy;

final class CreateSupportHandler
{
    public function __construct(
        private SupportRepository $supports,
        private SupportAssignmentPolicy $policy,
        private Clock $clock
    ) {
    }

    public function handle(CreateSupportCommand $cmd): CreateSupportResult
    {
        $support = new Support(
            id: null,
            description: $cmd->description,
            complexity: $cmd->complexity,
            assignedAt: null,
            worker: null
        );

        $worker = $this->policy->pickWorkerFor($support);
        $when = $this->clock->now();
        $support->assignTo($worker, $when);

        $this->supports->save($support);

        return new CreateSupportResult(
            id: $support->id(),
            description: $support->description(),
            complexity: $support->complexity(),
            assignedAtIso: $support->assignedAt()?->format('Y-m-d\TH:i:s'),
            workerName: $support->worker()?->name()
        );
    }
}