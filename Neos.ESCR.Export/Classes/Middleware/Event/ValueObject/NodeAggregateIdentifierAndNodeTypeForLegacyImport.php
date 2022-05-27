<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodeAggregateIdentifierAndNodeTypeForLegacyImport
{

    public function __construct(
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeTypeName $nodeTypeName
    ) {}
}
