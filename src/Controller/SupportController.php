<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Support;
use App\Repository\SupportRepository;
use App\Service\SupportAssigner;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/supports')]
class SupportController extends AbstractController
{
    public function __construct(
        private readonly SupportAssigner $assigner,
        private readonly SupportRepository $supportRepository,
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

        $support = new Support();
        $support->setDescription($description);
        $support->setComplexity($complexity);

        $this->assigner->assignAndPersist($support);

        $tz = new DateTimeZone('America/Bogota');
        $assignedAt = $support->getAssignedAt()?->setTimezone($tz)->format('Y-m-d\TH:i:s');

        return $this->json([
            'id' => $support->getId(),
            'description' => $support->getDescription(),
            'complexity' => $support->getComplexity(),
            'assignedAt' => $assignedAt,
            'worker' => $support->getWorker()?->getName(),
        ], 201);
    }

    #[Route('', name: 'api_supports_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $supports = $this->supportRepository->findAllOrdered();

        $tz = new DateTimeZone('America/Bogota');
        $out = [];
        foreach ($supports as $s) {
            $out[] = [
                'id' => $s->getId(),
                'description' => $s->getDescription(),
                'complexity' => $s->getComplexity(),
                'assignedAt' => $s->getAssignedAt()?->setTimezone($tz)->format('Y-m-d\TH:i:s'),
                'worker' => $s->getWorker()?->getName(),
            ];
        }

        return $this->json($out);
    }
}