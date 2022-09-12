<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service\Mapping;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;

/**
 * Convert a boolean to a JavaScript compatible string representation.
 *
 * @Flow\Scope("singleton")
 */
class NodeTypeStringConverter extends AbstractTypeConverter
{
    /**
     * The source types this converter can convert.
     *
     * @var array<string>
     * @api
     */
    protected $sourceTypes = [NodeType::class];

    /**
     * The target type this converter can convert to.
     *
     * @var string
     * @api
     */
    protected $targetType = 'string';

    /**
     * {@inheritdoc}
     *
     * @param mixed $source
     * @param string $targetType
     * @param array<mixed> $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @api
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        if ($source instanceof NodeType) {
            return $source->getName();
        }

        return '';
    }
}
