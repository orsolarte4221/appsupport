<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\UseCase\CreateSupport\CreateSupportCommand;
use App\Application\UseCase\CreateSupport\CreateSupportHandler;
use App\Application\UseCase\ListSupports\ListSupportsHandler;
use App\Application\UseCase\ListSupports\ListSupportsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/supports')]
class SupportController extends AbstractController
{
    public function __construct(
        private readonly CreateSupportHandler $createHandler,
        private readonly ListSupportsHandler $listHandler,
    ) {
    }

    #[Route('', name: 'api_supports_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?? [];

        $description = (string) ($data['description'] ?? '');
        $complexity = (int) ($data['complexity'] ?? 0);

        if ($description === '') {
            return $this->json(['error' => 'description is required'], 400);
        }

        $allowed = [10, 20, 30];
        if (!in_array($complexity, $allowed, true)) {
            return $this->json(['error' => 'complexity must be one of 10, 20, 30'], 400);
        }

        $result = $this->createHandler->handle(
            new CreateSupportCommand($description, $complexity)
        );

        return $this->json([
            'id' => $result->id,
            'description' => $result->description,
            'complexity' => $result->complexity,
            'assignedAt' => $result->assignedAtIso,
            'worker' => $result->workerName,
        ], 201);
    }

    #[Route('', name: 'api_supports_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->listHandler->handle(new ListSupportsQuery());

        $out = [];
        foreach ($items as $i) {
            $out[] = [
                'id' => $i->id,
                'description' => $i->description,
                'complexity' => $i->complexity,
                'assignedAt' => $i->assignedAtIso,
                'worker' => $i->workerName,
            ];
        }

        return $this->json($out);
    }
}