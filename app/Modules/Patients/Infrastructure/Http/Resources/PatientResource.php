<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->Id()->value,
            'first_name' => $this->resource->Name()->firstName,
            'last_name' => $this->resource->Name()->lastName,
            'email' => $this->resource->Email()->value,
            'status' => $this->resource->Status()->value,
        ];
    }
}