<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryBuilderInterface;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterFactoryInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * Builder to combine injected dependencies and ProjectionFActoryDependencies into a ContentGraphAdapterFactory
 * @internal
 */
class ContentGraphAdapterFactoryBuilder implements ContentGraphAdapterFactoryBuilderInterface
{
    public function __construct(private readonly DbalClientInterface $dbalClient)
    {
    }

    public function build(ContentRepositoryId $contentRepositoryId, NodeTypeManager $nodeTypeManager, PropertyConverter $propertyConverter): ContentGraphAdapterFactoryInterface
    {
        return new ContentGraphAdapterFactory($this->dbalClient->getConnection(), $contentRepositoryId, $nodeTypeManager, $propertyConverter);
    }
}
