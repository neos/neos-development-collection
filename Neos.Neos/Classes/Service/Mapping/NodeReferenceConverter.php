<?php
namespace Neos\Neos\Service\Mapping;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\ContentRepository\Domain\Model\NodeInterface;

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
    protected $sourceTypes = array(NodeInterface::class, 'array');

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
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string the target type
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_array($source)) {
            $result = [];
            /** @var NodeInterface $node */
            foreach ($source as $node) {
                $result[] = $node->getIdentifier();
            }
        } else {
            if ($source instanceof NodeInterface) {
                $result = $source->getIdentifier();
            } else {
                $result = '';
            }
        }

        return $result;
    }
}
