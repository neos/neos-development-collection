<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\Flow\Annotations as Flow;

/**
 * The discriminator to distinguish references
 *
 * References with a given name can never target the same node aggregate twice, as enforced by the constraint checks
 * @see NodeReferencesForName
 */
#[Flow\Proxy(false)]
final readonly class ReferenceDiscriminator implements \JsonSerializable
{
    private function __construct(
        public ReferenceName $referenceName,
        public NodeAggregateId $nodeAggregateId,
    ) {
    }

    public static function create(
        ReferenceName $referenceName,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            referenceName: $referenceName,
            nodeAggregateId: $nodeAggregateId,
        );
    }

    public static function fromReference(Reference $reference): self
    {
        return new self(
            referenceName: $reference->name,
            nodeAggregateId: $reference->node->aggregateId,
        );
    }

    public function equals(self $other): bool
    {
        return $this->referenceName->equals($other->referenceName)
            && $this->nodeAggregateId->equals($other->nodeAggregateId);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
