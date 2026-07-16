<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreatmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_object($this->resource) && method_exists($this->resource, 'Id')) {
            return [
                'id' => $this->resource->Id()?->value,
                'name' => $this->resource->Name()->value,
                'description' => $this->resource->Description()->value,
                'time' => $this->resource->Time()->value,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'time' => $this->time,
        ];
    }
}