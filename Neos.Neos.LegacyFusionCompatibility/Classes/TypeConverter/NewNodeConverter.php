<?php
declare(strict_types=1);
namespace Neos\Neos\LegacyFusionCompatibility\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context.
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class NewNodeConverter extends AbstractTypeConverter
{
    /**
     * @var array<int,string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = NodeInterface::class;

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @param string $source
     * @param string $targetType
     * @param array<string,string> $subProperties
     * @return ?NodeInterface
     */
    public function convertFrom(
        $source,
        $targetType = null,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $nodeAddress = $this->nodeAddressFactory->createFromUriString($source);

        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );

        return $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->nodeAggregateIdentifier);
    }
}
