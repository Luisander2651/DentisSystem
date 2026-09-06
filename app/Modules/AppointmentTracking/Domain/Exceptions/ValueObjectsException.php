<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions;

use InvalidArgumentException;

abstract class ValueObjectsException extends InvalidArgumentException {}
