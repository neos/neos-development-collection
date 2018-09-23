<?php
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

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Neos\Domain\Context\Content\NodeAddress;
use Neos\Neos\Domain\Context\Content\NodeAddressFactory;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Security\Context;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use Neos\EventSourcedContentRepository\Domain\Factory\NodeFactory;
use Neos\EventSourcedContentRepository\Domain\Model\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Model\NodeType;
use Neos\EventSourcedContentRepository\Domain\Service\Context as TYPO3CRContext;
use Neos\EventSourcedContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\EventSourcedContentRepository\Domain\Service\NodeServiceInterface;
use Neos\EventSourcedContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Utility\NodePaths;
use Neos\EventSourcedContentRepository\Exception\NodeException;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context.
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class NewNodeSerializer extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array(\Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface::class);

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;


    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     *
     */
    public function convertFrom($source, $targetType = null, array $subProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        // TODO: Move Node Address to CR
        return $this->nodeAddressFactory->createFromNode($source)->serializeForUri();
    }

}
