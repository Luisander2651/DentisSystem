<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Reports\Application\DTOs\MonthlyReportDTO;
use App\Modules\Reports\Application\UseCases\ExportMonthlyReportUseCase;
use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Infrastructure\Http\Requests\MonthlyReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Spec 019 (CA8, CA9): GET /api/v1/reports/monthly/excel?month=AAAA-MM. The file is written for
 * this request and deleted once sent (D10).
 */
final readonly class DownloadMonthlyReportController
{
    private const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(
        private ExportMonthlyReportUseCase $useCase,
    ) {}

    public function __invoke(MonthlyReportRequest $request): BinaryFileResponse|JsonResponse
    {
        try {
            $file = $this->useCase->execute(MonthlyReportDTO::create($request->month()));

            return response()
                ->download($file['path'], $file['filename'], ['Content-Type' => self::CONTENT_TYPE])
                ->deleteFileAfterSend();
        } catch (ReportMonthException) {
            // A20: the month stopped being valid after the request was checked (the clock moved on).
            return response()->json(['message' => MonthlyReportRequest::MESSAGE, 'errors' => ['month' => [MonthlyReportRequest::MESSAGE]]], 422);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            // The class is enough to look for the error; its message may carry data of a patient.
            Log::error('report.export_failed', ['month' => $request->month(), 'exception' => $e::class]);

            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
