<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @internal
 */
final class Subscription
{
    public function __construct(
        public readonly SubscriptionId $id,
        public readonly SubscriptionGroup $group,
        public readonly RunMode $runMode,
        public readonly SubscriptionStatus $status,
        public readonly SequenceNumber $position,
        public readonly bool $locked = false,
        public readonly SubscriptionError|null $error = null,
        public readonly int $retryAttempt = 0,
        public readonly \DateTimeImmutable|null $lastSavedAt = null,
    ) {
    }

    public static function create(
        SubscriptionId $id,
        SubscriptionGroup $group,
        RunMode $runMode,
    ): self {
        return new self(
            $id,
            $group,
            $runMode,
            SubscriptionStatus::NEW,
            SequenceNumber::fromInteger(0),
        );
    }

    public function with(
        SubscriptionStatus $status = null,
        SequenceNumber $position = null,
        int $retryAttempt = null,
    ): self {
        return new self(
            $this->id,
            $this->group,
            $this->runMode,
            $status ?? $this->status,
            $position ?? $this->position,
            $this->locked,
            $this->error,
            $retryAttempt ?? $this->retryAttempt,
            $this->lastSavedAt,
        );
    }

    public function withError(\Throwable|string $throwableOrMessage): self
    {
        if ($throwableOrMessage instanceof \Throwable) {
            $error = SubscriptionError::fromThrowable($this->status, $throwableOrMessage);
        } else {
            $error = new SubscriptionError($throwableOrMessage, $this->status);
        }
        return new self(
            $this->id,
            $this->group,
            $this->runMode,
            SubscriptionStatus::ERROR,
            $this->position,
            $this->locked,
            $error,
            $this->retryAttempt,
            $this->lastSavedAt,
        );
    }
//
//    public function doRetry(): void
//    {
//        if ($this->error === null) {
//            throw new NoErrorToRetry();
//        }
//
//        $this->retryAttempt++;
//        $this->status = $this->error->previousStatus;
//        $this->error = null;
//    }
    public function withoutError(): self
    {
        return new self(
            $this->id,
            $this->group,
            $this->runMode,
            $this->status,
            $this->position,
            $this->locked,
            null,
            $this->retryAttempt,
            $this->lastSavedAt,
        );
    }
}
