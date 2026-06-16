<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GalleryImageResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Id()->value,
            'url' => $this->Url()->value,
            'description' => $this->Description()->value,
            'status' => $this->Status()->value,
            'created_at' => $this->CreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->UpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}