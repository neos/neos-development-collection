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

use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * An Object Converter for content subgraphs
 *
 * @Flow\Scope("singleton")
 */
class ContentSubgraphToStringConverter extends AbstractTypeConverter
{
    /**
     * @var array<int,string>
     */
    protected $sourceTypes = [ContentSubgraphInterface::class];

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * @param ContentSubgraphInterface $source
     * @param string $targetType
     * @param array<string,mixed> $convertedChildProperties
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ): string {
        return json_encode($source, JSON_THROW_ON_ERROR);
    }
}
