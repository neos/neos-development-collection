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
        $class = get_class(...);
        return new self(
            $error->getMessage(),
            $previousStatus,
            <<<TEXT
            Class: {$class($error)}
            File: {$error->getFile()}
            Line: {$error->getLine()}
            Code: {$error->getCode()}
            
            
            TEXT
            . $error->getTraceAsString()
            . ($error->getPrevious() ? (
                <<<TEXT
                
                
                Previous: {$error->getPrevious()->getMessage()}
                Class: {$class($error->getPrevious())}
                File: {$error->getPrevious()->getFile()}
                Line: {$error->getPrevious()->getLine()}
                Code: {$error->getPrevious()->getCode()}
                
                
                TEXT
                . $error->getPrevious()->getTraceAsString()
            ) : '')
        );
    }
}
