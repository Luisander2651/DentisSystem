<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 025 (CA5, CA17, TM7): what the portal tells the patient about the follow-up of one of
 * their appointments: the recommendations and the prescriptions, nothing else.
 */
final class PortalPrescriptionsResource extends JsonResource
{
    /**
     * @return array{recommendations: ?string, prescriptions: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'recommendations' => $this->resource['recommendations'],
            'prescriptions' => PortalPrescriptionResource::collection($this->resource['prescriptions']),
        ];
    }
}
