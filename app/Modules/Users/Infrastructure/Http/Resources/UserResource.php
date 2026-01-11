<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this es la instancia de UserEntity que pasaremos
        return [
            'id'         => $this->id()->value,
            'first_name' => $this->name()->firstName,
            'last_name'  => $this->name()->lastName,
            'email'      => $this->email()->value,
            'role_id'    => $this->role()->value,
            'status'     => $this->status()->value,
        ];
    }
}