<?php

namespace Compucie\Database;

use DateTime;
use Exception;
use Throwable;

function makeErrorLogging(string $methodName, Throwable $e): void
{
    error_log(sprintf(
        $methodName . ' error: %s in %s:%d%sTrace:%s',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        PHP_EOL,
        $e->getTraceAsString()
    ));
}

function safeDateTime(?string $value): DateTime
{
    if ($value === null || $value === '') {
        return new DateTime();
    }

    try {
        return new DateTime($value);
    } catch (Exception) {
        return new DateTime();
    }
}