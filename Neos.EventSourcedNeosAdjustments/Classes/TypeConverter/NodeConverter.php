<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Fusion\Core\Cache\ContentCache;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context.
 * {@see ContentCache::serializeContext()}
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class NodeConverter extends AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @var array
     */
    protected $sourceTypes = [NodeInterface::class];

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    public function convertFrom($source, $targetType = null, array $subProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return $this->nodeAddressFactory->createFromNode($source)->serializeForUri();
    }
}
