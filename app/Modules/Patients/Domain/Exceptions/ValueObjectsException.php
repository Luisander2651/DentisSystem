<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions;

use InvalidArgumentException;

abstract class ValueObjectsException extends InvalidArgumentException 
{
}