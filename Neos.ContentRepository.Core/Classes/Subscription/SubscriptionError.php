<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @api part of the subscription status
 */
final class SubscriptionError
{
    public function __construct(
        public readonly string $errorMessage,
        public readonly SubscriptionStatus $previousStatus,
        public readonly string|null $errorTrace = null,
    ) {
    }

    public static function fromPreviousStatusAndException(SubscriptionStatus $previousStatus, \Throwable $error): self
    {
        $messageLines = [];
        $level = 0;
        $exception = $error;
        do {
            $level++;
            if ($level >= 8) {
                $messageLines[] = '...Recursion';
                break;
            }

            $exceptionFqn = $exception::class;

            $messageLines[] = <<<MESSAGE
                Class: {$exceptionFqn}
                Message: {$exception->getMessage()}
                Code: {$exception->getCode()}
                File: {$exception->getFile()}
                Line: {$exception->getLine()}

                Trace: {$exception->getTraceAsString()}
                MESSAGE;
        } while ($exception = $exception->getPrevious());

        return new self(
            $error->getMessage(),
            $previousStatus,
            join("\n\n", $messageLines)
        );
    }
}
