<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Repositories\TreatmentsRepositoryInterface;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Illuminate\Database\Eloquent\Builder;

final class EloquentTreatmentRepository implements TreatmentsRepositoryInterface
{
    public function save(TreatmentEntity $treatment): void
    {
        $data = [
            'id' => $treatment->Id()->value,
            'name' => $treatment->Name()->value,
            'description' => $treatment->Description()->value,
            'time' => $treatment->Time()->value,
        ];

        TreatmentModel::query()->updateOrCreate(['id' => $treatment->Id()->value], $data);
    }

    public function findById(TreatmentId $id): ?TreatmentEntity
    {
        $model = TreatmentModel::query()->find($id->value);

        return $model ? $this->mapToDomain($model) : null;
    }

    public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array
    {
        $query = TreatmentModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($treatmentName) {
            $query->where('name', 'like', '%'.$treatmentName->value.'%');
        }

        $results = $query->orderBy('name')->get();

        return $results->map(fn ($model) => $this->mapToDomain($model))->toArray();
    }

    public function delete(TreatmentId $id): void
    {
        TreatmentModel::destroy($id->value);
    }

    public function findPage(PageRequest $request, ?string $search): Page
    {
        $query = TreatmentModel::query()
            ->when($this->likePattern($search), fn (Builder $query, string $pattern) => $query->where(
                fn (Builder $query) => $query
                    ->whereRaw($this->withoutAccents('name').' ilike ?', [$pattern])
                    ->orWhereRaw($this->withoutAccents('description').' ilike ?', [$pattern])
                    ->orWhereRaw('cast(time as text) ilike ?', [$pattern])
            ));

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('name')
            ->orderBy('id')
            ->offset($request->offsetFor($total))
            ->limit($request->size)
            ->get()
            ->map(fn (TreatmentModel $treatment): TreatmentEntity => $this->mapToDomain($treatment))
            ->all();

        return Page::create($items, $total, $request);
    }

    public function stats(): array
    {
        $stats = TreatmentModel::query()
            ->selectRaw('count(*) as total, count(time) as with_duration, avg(time) as average')
            ->first();

        return [
            'total' => (int) $stats->total,
            'with_duration' => (int) $stats->with_duration,
            'average_minutes' => $stats->average === null ? null : (int) round((float) $stats->average),
        ];
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

        return '%'.addcslashes($unaccented, '\\%_').'%';
    }

    /**
     * Spec 028 (CA4, D16): a column compared without its accents, so "extraccion" finds
     * "Extracción". The ñ stays a letter of its own.
     */
    private function withoutAccents(string $column): string
    {
        return "translate({$column}, '".self::ACCENTED."', '".self::UNACCENTED."')";
    }

    private function mapToDomain(object $model): TreatmentEntity
    {
        return TreatmentEntity::fromPrimitives(
            (string) $model->id,
            (string) $model->name,
            (string) $model->time,
            (string) $model->description,
            (string) $model->created_at,
            (string) $model->updated_at
        );
    }
}
