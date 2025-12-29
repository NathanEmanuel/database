<?php

namespace Compucie\Database\Member\Exceptions;

use Exception;

class CardNotRegisteredException extends Exception
{
    public function __construct()
    {
        parent::__construct("Card is not registered.");
    }
}