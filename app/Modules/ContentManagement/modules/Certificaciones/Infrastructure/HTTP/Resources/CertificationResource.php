<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CertificationResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Id()->value,
            'name' => $this->Name()->value,
            'description' => $this->Description()->value,
            'status' => $this->Status()->value,
            'date' => $this->Date()->value,
            'image_url' => $this->ImageUrl()->value,
            'created_at' => $this->CreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->UpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
