<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Database\Eloquent\Builder;

class EloquentPatientRepository implements PatientsRepositoryInterface
{
    public function findByEmailExcludingId(PatientEmail $email, ?PatientId $excludedId): ?Patient
    {
        $query = PatientModel::where('email', $email->value);

        if ($excludedId !== null) {
            $query->where('id', '!=', $excludedId->value);
        }

        $patient = $query->first();

        return $patient ? $this->mapToDomain($patient) : null;
    }

    public function findByPatientId(PatientId $patientId): ?Patient
    {
        $patient = PatientModel::find($patientId->value);

        return $patient ? $this->mapToDomain($patient) : null;
    }

    public function save(Patient $patient): void
    {
        $data = [
            'first_name' => $patient->Name()->firstName,
            'last_name' => $patient->Name()->lastName,
            'email' => $patient->Email()->value,
            'password' => $patient->PasswordHash()->value,
            'status' => $patient->Status()->value,
            'role' => $patient->Role()->value,
        ];

        PatientModel::updateOrCreate(['id' => $patient->Id()->value], $data);
    }

    public function delete(PatientId $patientId): void
    {
        PatientModel::destroy($patientId->value);
    }

    public function findByRoleAndStatus(?PatientStatus $status): array
    {
        $query = PatientModel::query();

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        $patients = $query->get();

        return $patients ? $patients->map(fn ($patient) => $this->mapToDomain($patient))->toArray() : [];
    }

    public function findPage(PageRequest $request, ?PatientStatus $status, ?string $search): Page
    {
        $query = PatientModel::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($this->likePattern($search), fn (Builder $query, string $pattern) => $query->where(
                fn (Builder $query) => $query
                    ->whereRaw($this->withoutAccents('first_name').' ilike ?', [$pattern])
                    ->orWhereRaw($this->withoutAccents('last_name').' ilike ?', [$pattern])
                    ->orWhereRaw($this->withoutAccents("first_name || ' ' || last_name").' ilike ?', [$pattern])
                    ->orWhereRaw($this->withoutAccents('email').' ilike ?', [$pattern])
            ));

        $total = (clone $query)->count();

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($request->offsetFor($total))
            ->limit($request->size)
            ->get()
            ->map(fn (PatientModel $patient): Patient => $this->mapToDomain($patient))
            ->all();

        return Page::create($items, $total, $request);
    }

    public function countByStatus(): array
    {
        $byStatus = PatientModel::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $active = (int) ($byStatus[PatientStatus::active()->value] ?? 0);
        $inactive = (int) ($byStatus[PatientStatus::inactive()->value] ?? 0);

        return ['total' => $active + $inactive, 'active' => $active, 'inactive' => $inactive];
    }

    private const ACCENTED = 'áéíóúüÁÉÍÓÚÜ';

    private const UNACCENTED = 'aeiouuAEIOUU';

    /**
     * The search as a pattern that matches its text literally anywhere, without its accents (D16):
     * the LIKE wildcards and the escape character it holds lose their meaning (TM3). No search, no
     * pattern.
     */
    private function likePattern(?string $search): ?string
    {
        $text = trim((string) $search);

        if ($text === '') {
            return null;
        }

        $unaccented = strtr($text, array_combine(mb_str_split(self::ACCENTED), mb_str_split(self::UNACCENTED)));

        return '%'.addcslashes($unaccented, '\%_').'%';
    }

    /**
     * Spec 028 (CA4, D16): a column compared without its accents, so "sofia" finds "Sofía". The ñ
     * stays a letter of its own.
     */
    private function withoutAccents(string $column): string
    {
        return "translate({$column}, '".self::ACCENTED."', '".self::UNACCENTED."')";
    }

    private function mapToDomain(object $model): Patient
    {
        // Mapear el modelo de Eloquent a la entidad de dominio
        return Patient::fromPrimitives(
            (string) $model->id,
            (string) $model->first_name,
            (string) $model->last_name,
            (string) $model->email,
            (string) $model->password,
            (string) $model->status,
            (string) $model->role,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString()
        );
    }
}
