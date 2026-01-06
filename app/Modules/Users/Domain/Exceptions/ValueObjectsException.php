<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use InvalidArgumentException;

// Esta clase sirve como "paraguas"
abstract class ValueObjectsException extends InvalidArgumentException 
{
}