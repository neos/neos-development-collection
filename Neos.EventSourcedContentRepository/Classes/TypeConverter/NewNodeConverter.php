<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;

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
     * @var array
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = \Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface::class;

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
     * TODO: Dependency to Neos; get rid of this!
     *
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     *
     */
    public function convertFrom($source, $targetType = null, array $subProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $nodeAddress = $this->nodeAddressFactory->createFromUriString($source);

        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );

        return $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());
    }
}
