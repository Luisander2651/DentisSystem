<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\UseCases\GetAgendaDoctorsUseCase;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final readonly class GetDoctorsForAppointmentSelectController
{
    public function __construct(
        private GetAgendaDoctorsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            return response()->json([
                'data' => UserResource::collection($this->useCase->execute()),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
