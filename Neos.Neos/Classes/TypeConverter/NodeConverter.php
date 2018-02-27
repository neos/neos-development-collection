<?php
namespace Neos\Neos\TypeConverter;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * An Object Converter for nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeConverter extends \Neos\ContentRepository\TypeConverter\NodeConverter
{
    /**
     * @Flow\Inject
     * @var ContentGraph
     */
    protected $contentGraph;

    /**
     * @var integer
     */
    protected $priority = 3;

    protected function prepareContextProperties($workspaceName, PropertyMappingConfigurationInterface $configuration = null, array $dimensions = null)
    {
        $contextProperties = parent::prepareContextProperties($workspaceName, $configuration, $dimensions);
        $contextProperties['rootNodeIdentifier'] = $this->contentGraph->findRootNodeByType(new NodeTypeName('Neos.Neos:Sites'))->getNodeIdentifier();
        return $contextProperties;
    }
}
