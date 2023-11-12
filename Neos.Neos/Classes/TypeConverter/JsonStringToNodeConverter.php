<?php

declare(strict_types=1);

namespace Neos\Neos\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context. {@see ContentCache::serializeContext()}
 *
 * Serialized implementation {@see NodeToJsonStringSerializer}
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class JsonStringToNodeConverter extends AbstractTypeConverter
{
    /**
     * @var array<int,string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = Node::class;

    /**
     * @var integer
     */
    protected $priority = 2;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param string $source
     * @param string $targetType
     * @param array<string,string> $subProperties
     * @return Node|null|\Neos\Error\Messages\Error
     */
    public function convertFrom(
        $source,
        $targetType = null,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        assert(is_string($source));

        try {
            $serializedNode = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new \Neos\Error\Messages\Error(sprintf('Cannot convert assumed json string %s to node. %s', $source, $e->getMessage()));
        }

        $contentRepositoryId = ContentRepositoryId::fromString($serializedNode['contentRepositoryId']);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($serializedNode['workspaceName']));
        if (!$workspace) {
            return new \Neos\Error\Messages\Error('Could not find workspace while trying to convert node from json string %s.', 1699782153, [$source]);
        }

        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $workspace->currentContentStreamId,
            DimensionSpacePoint::fromArray($serializedNode['dimensionSpacePoint']),
            $workspace->isPublicWorkspace()
                ? VisibilityConstraints::frontend()
                : VisibilityConstraints::withoutRestrictions()
        );

        return $subgraph->findNodeById(NodeAggregateId::fromString($serializedNode['nodeAggregateId']));
    }
}
