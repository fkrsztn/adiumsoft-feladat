<?php

declare(strict_types=1);

namespace ADS\TakeHome\Exceptions;

use RuntimeException;

/**
 * Hibás bemenet (hiányzó vagy érvénytelen paraméter).
 *
 * Az üzenete a felhasználónak szól, ezért magyarul, belső részletek nélkül
 * fogalmazzuk meg. HTTP 400-as válasz lesz belőle.
 */
class ValidationException extends RuntimeException
{
}
