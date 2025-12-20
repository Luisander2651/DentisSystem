<?php

namespace App\Domain\Patient\Attributes;

enum PatientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DECEASED = 'deceased';
}