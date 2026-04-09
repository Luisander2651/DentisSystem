<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\AddressesModel;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class EloquentAddressRepository implements AddressesRepositoryInterface
{
    public function save(Address $address): void
    {
        $data = [
            'patient_id' => $address->PatientId()->value,
            'street' => $address->Street()->value,
            'city' => $address->City()->value,
            'state' => $address->State()->value,
            'postal_code' => $address->PostalCode()->value,
        ];

        AddressesModel::updateOrCreate(['id' => $address->Id()->value], $data);
    }

    public function findByPatientId(PatientId $patientId): ?Address
    {
       $pateintAdress = AddressesModel::find($patientId->value);

       return $pateintAdress ? $this->mapToAddressModel($pateintAdress) : null;
    }

    public function deleteByPatientId(PatientId $patientId): void
    {
        AddressesModel::destroy($patientId->value);
    }

    private function mapToAddressModel(object $model): Address
    {
        // Implement the logic to map the Address entity to an Eloquent model array
        return Address::fromPrimitives(
            (int) $model->id,
            (string) $model->patient_id,
            $model->street,
            $model->city,
            $model->state,
            $model->postal_code,
            $model->created_at->toDateTimeString(),
            $model->updated_at->toDateTimeString()
        );
    }
}