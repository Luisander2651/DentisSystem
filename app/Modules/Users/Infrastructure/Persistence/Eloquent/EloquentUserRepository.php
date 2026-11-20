<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Builder;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?UserEntity
    {
        // Buscar un usuario por su ID
        $user = UserModel::find($id->value);

        if ($user === null) {
            return null;
        }

        return $user ? $this->mapToDomain($user) : null;
    }

    /**
     * @return UserEntity[]
     */
    public function findByRole(UserRoleId $role): array
    {
        $roleId = (string) $role->toDatabaseId(); // 1 para admin, 2 para asistente, 3 para doctor

        $users = UserModel::where('role_id', $roleId)->get();

        return $users ? $users->map(fn ($user) => $this->mapToDomain($user))->toArray() : [];
    }

    /**
     * @return UserEntity[]
     */
    public function findByRoleAndStatus(?UserStatus $status, ?UserRoleId $role): array
    {
        $query = UserModel::query();

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        if ($role !== null) {
            $query->where('role_id', $role->toDatabaseId());
        }

        $users = $query->get();

        return $users ? $users->map(fn ($user) => $this->mapToDomain($user))->toArray() : [];
    }

    // Búsqueda por Email excluyendo un ID específico o null (solo bsucar por email)
    public function findByEmailExcludingId(UserEmail $email, ?UserId $excludeId): ?UserEntity
    {
        $query = UserModel::where('email', $email->value);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value);
        }

        $user = $query->first();

        return $user ? $this->mapToDomain($user) : null;
    }

    public function save(UserEntity $user): void
    {
        // 1. Mapeo de datos para no repetir código
        $data = [
            'first_name' => $user->name()->firstName,
            'last_name' => $user->name()->lastName,
            'email' => $user->email()->value,
            'password' => $user->password()->value,
            'role_id' => $user->role()->toDatabaseId(),
            'status' => $user->status()->value,
        ];

        // 2. Persistencia
        UserModel::updateOrCreate(
            ['id' => $user->id()->value],
            $data
        );
    }

    public function delete(UserId $id): void
    {
        UserModel::destroy($id->value);
    }

    public function listActiveUsers(): array
    {
        $users = UserModel::where('status', 'active')->get();

        return $users ? $users->map(fn ($user) => $this->mapToDomain($user))->toArray() : [];
    }

    public function findPage(PageRequest $request, ?UserStatus $status, ?UserRoleId $role): Page
    {
        $query = UserModel::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($role, fn (Builder $query) => $query->where('role_id', $role->toDatabaseId()));

        $total = (clone $query)->count();

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($request->offsetFor($total))
            ->limit($request->size)
            ->get()
            ->map(fn (UserModel $user): UserEntity => $this->mapToDomain($user))
            ->all();

        return Page::create($items, $total, $request);
    }

    public function countByRole(): array
    {
        $byRole = UserModel::query()
            ->selectRaw('role_id, count(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $count = fn (UserRoleId $role): int => (int) ($byRole[$role->toDatabaseId()] ?? 0);
        $administrador = $count(UserRoleId::administrador());
        $asistente = $count(UserRoleId::asistente());
        $doctor = $count(UserRoleId::doctor());

        return [
            'total' => $administrador + $asistente + $doctor,
            'administrador' => $administrador,
            'asistente' => $asistente,
            'doctor' => $doctor,
        ];
    }

    /**
     * Centralizamos la creación de la entidad aquí
     */
    private function mapToDomain(object $user): UserEntity
    {
        return UserEntity::fromPrimitives(
            (string) $user->id,
            (string) $user->first_name,
            (string) $user->last_name,
            (string) $user->email,
            (string) $user->password,
            (string) $user->role_id,
            (string) $user->status,
            $user->created_at->toDateTimeString(),
            $user->updated_at->toDateTimeString()
        );
    }
}
