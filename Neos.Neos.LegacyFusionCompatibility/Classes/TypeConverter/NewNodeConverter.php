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

use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context.
 *
 * @Flow\Scope("singleton")
 * @deprecated
 * TODO: TRY TO FIX IMPLEMENTATION // GET RID OF IT
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
    protected $targetType = Node::class;

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @param string $source
     * @param string $targetType
     * @param array<string,string> $subProperties
     * @return ?Node
     */
    public function convertFrom(
        $source,
        $targetType = null,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        throw new \RuntimeException('TODO FIX ME');

    }
}
