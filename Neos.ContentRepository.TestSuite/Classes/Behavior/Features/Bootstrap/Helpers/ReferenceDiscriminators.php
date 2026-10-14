<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\Flow\Annotations as Flow;

/**
 * A list of reference discriminators
 *
 * @implements \IteratorAggregate<int,ReferenceDiscriminator>
 */
#[Flow\Proxy(false)]
final readonly class ReferenceDiscriminators implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @param array<int,ReferenceDiscriminator> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    public static function list(ReferenceDiscriminator ...$items): self
    {
        return new self(array_values($items));
    }

    public static function fromReferences(References $references): self
    {
        return new self(array_map(
            fn (Reference $reference): ReferenceDiscriminator => ReferenceDiscriminator::fromReference($reference),
            $references->references,
        ));
    }

    /**
     * @return array<int,ReferenceDiscriminator>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
