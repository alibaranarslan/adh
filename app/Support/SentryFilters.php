<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SentryFilters
{
    private static array $ignoredExceptions = [
        NotFoundHttpException::class,
        MethodNotAllowedHttpException::class,
        TokenMismatchException::class,
        ValidationException::class,
        AuthenticationException::class,
        ModelNotFoundException::class,
    ];

    public static function beforeSend(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        if ($exception !== null) {
            foreach (self::$ignoredExceptions as $ignored) {
                if ($exception instanceof $ignored) {
                    return null;
                }
            }
        }

        return $event;
    }
}
