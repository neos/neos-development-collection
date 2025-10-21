<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

/**
 * @implements \IteratorAggregate<ProjectionSubscriberFactory>
 * @internal only API for custom content repository integrations
 */
final class ContentRepositorySubscriberFactories implements \IteratorAggregate
{
    /**
     * @var array<ProjectionSubscriberFactory>
     */
    private array $subscriberFactories;

    private function __construct(ProjectionSubscriberFactory ...$subscriberFactories)
    {
        $this->subscriberFactories = $subscriberFactories;
    }

    /**
     * @param array<ProjectionSubscriberFactory> $subscriberFactories
     * @return self
     */
    public static function fromArray(array $subscriberFactories): self
    {
        return new self(...$subscriberFactories);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return $this->subscriberFactories === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->subscriberFactories;
    }
}
