<?php

namespace Compucie\Database\Sale\Exceptions;

use Exception;

class WeekDoesNotExistException extends Exception
{
    public function __construct(?int $weekNr) {
        parent::__construct("'$weekNr' is not a valid week.");
    }
}