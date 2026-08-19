<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when an assessor accepts a request another partner has already taken.
 * Carries the calm German message the losing assessor sees — never an error page.
 */
class RequestAlreadyAssignedException extends Exception
{
    public const MESSAGE = 'Dieser Auftrag wurde bereits von einem anderen Sachverständigen übernommen.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
