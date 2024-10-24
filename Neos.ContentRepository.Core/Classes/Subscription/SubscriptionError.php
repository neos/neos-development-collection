<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @internal
 */
final class SubscriptionError
{
    public function __construct(
        public readonly string $errorMessage,
        public readonly SubscriptionStatus $previousStatus,
        public readonly string|null $errorTrace = null,
    ) {
    }

    public static function fromThrowable(SubscriptionStatus $status, \Throwable $error): self
    {
        return new self($error->getMessage(), $status, $error->getTraceAsString());
    }
}
