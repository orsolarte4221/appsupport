<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\UseCase\DailyLoadReport\DailyLoadReportHandler;
use App\Application\UseCase\DailyLoadReport\DailyLoadReportQuery;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class WorkerController extends AbstractController
{
    public function __construct(
        private readonly DailyLoadReportHandler $reportHandler,
    ) {
    }

    #[Route('/report/daily-load', name: 'api_report_daily_load', methods: ['GET'])]
    public function dailyLoadReport(Request $request): JsonResponse
    {
        $dateParam = $request->query->get('date');
        $tz = new DateTimeZone('America/Bogota');

        if (is_string($dateParam) && $dateParam !== '') {
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $dateParam, $tz);
            if (!$dt) {
                return $this->json(['error' => 'Invalid date format. Expected YYYY-MM-DD'], 400);
            }
        } else {
            $dt = new DateTimeImmutable('now', $tz);
        }

        $rows = $this->reportHandler->handle(new DailyLoadReportQuery($dt));

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'worker' => $row->worker,
                'total' => $row->total,
            ];
        }

        return $this->json($out);
    }
}