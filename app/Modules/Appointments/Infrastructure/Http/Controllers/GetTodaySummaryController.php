<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\GetTodaySummaryUseCase;
use App\Modules\Appointments\Infrastructure\Http\Resources\TodaySummaryResource;
use Illuminate\Http\JsonResponse;

/**
 * Spec 020 (CA1, CA2, CA3, A13): a refusal leaves through the render of AuthorizationException,
 * with its usual body; any other failure answers the generic 500.
 */
final readonly class GetTodaySummaryController
{
    public function __construct(
        private GetTodaySummaryUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $agenda = $this->useCase->execute();

            return response()->json(['data' => TodaySummaryResource::at($agenda, now()->toImmutable())], 200);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
