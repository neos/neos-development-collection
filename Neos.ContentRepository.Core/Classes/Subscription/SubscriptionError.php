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
        return new self($error->getMessage(), $previousStatus, $error->getTraceAsString());
    }
}
