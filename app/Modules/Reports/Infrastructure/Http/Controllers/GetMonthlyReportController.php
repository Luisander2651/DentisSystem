<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Reports\Application\DTOs\MonthlyReportDTO;
use App\Modules\Reports\Application\UseCases\GetMonthlyReportUseCase;
use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Infrastructure\Http\Requests\MonthlyReportRequest;
use App\Modules\Reports\Infrastructure\Http\Resources\MonthlyReportResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 019 (CA1–CA7, CA9): GET /api/v1/reports/monthly?month=AAAA-MM.
 */
final readonly class GetMonthlyReportController
{
    public function __construct(
        private GetMonthlyReportUseCase $useCase,
    ) {}

    public function __invoke(MonthlyReportRequest $request): JsonResponse
    {
        try {
            $report = $this->useCase->execute(MonthlyReportDTO::create($request->month()));

            return response()->json(['data' => new MonthlyReportResource($report)], 200);
        } catch (ReportMonthException) {
            // A20: the month stopped being valid after the request was checked (the clock moved on).
            return response()->json(['message' => MonthlyReportRequest::MESSAGE, 'errors' => ['month' => [MonthlyReportRequest::MESSAGE]]], 422);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
