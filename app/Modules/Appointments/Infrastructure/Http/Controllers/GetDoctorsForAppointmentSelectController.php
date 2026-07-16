<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Modules\Users\Aplication\DTOs\GetUsersByStatusAndRoleDTO;
use App\Modules\Users\Aplication\UseCases\GetUsersByRoleAndStatusUseCase;
use App\Modules\Users\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final readonly class GetDoctorsForAppointmentSelectController
{
    public function __construct(
        private GetUsersByRoleAndStatusUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $doctors = $this->useCase->execute(GetUsersByStatusAndRoleDTO::create(
            status: 'active',
            role: 'doctor',
        ));

        $admins = $this->useCase->execute(GetUsersByStatusAndRoleDTO::create(
            status: 'active',
            role: 'admin',
        ));

        $mergedDoctors = [];

        foreach (array_merge($doctors, $admins) as $doctor) {
            $mergedDoctors[(string) $doctor->id()->value] = $doctor;
        }

        return response()->json([
            'data' => UserResource::collection(array_values($mergedDoctors)),
        ], 200);
    }
}