<?php

declare(strict_types=1);

namespace ADS\TakeHome\Exceptions;

use RuntimeException;

/**
 * A kért erőforrás (pl. város, régió) nem létezik.
 *
 * Az üzenete a felhasználónak szól. HTTP 404-es válasz lesz belőle.
 */
class NotFoundException extends RuntimeException
{
}
