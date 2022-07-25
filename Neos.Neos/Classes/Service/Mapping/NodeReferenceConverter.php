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

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * Converter to convert node references to their identifiers
 *
 * @Flow\Scope("singleton")
 */
class NodeReferenceConverter extends AbstractTypeConverter
{
    /**
     * The source types this converter can convert.
     *
     * @var array<string>
     * @api
     */
    protected $sourceTypes = [NodeInterface::class, 'array'];

    /**
     * The target type this converter can convert to.
     *
     * @var string
     * @api
     */
    protected $targetType = 'string';

    /**
     * The priority for this converter.
     *
     * @var integer
     * @api
     */
    protected $priority = 0;

    /**
     * {@inheritdoc}
     *
     * @param NodeInterface|array<NodeInterface> $source
     * @param string $targetType
     * @param array<mixed> $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string|array<int,string> the target type
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        if (is_array($source)) {
            $result = [];
            foreach ($source as $node) {
                $result[] = (string)$node->getNodeAggregateIdentifier();
            }
        } else {
            $result = (string)$source->getNodeAggregateIdentifier();
        }

        return $result;
    }
}
