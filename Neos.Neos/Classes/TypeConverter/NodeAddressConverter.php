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

namespace Neos\Neos\TypeConverter;

use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * An Object Converter for content queries which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeAddressConverter extends AbstractTypeConverter
{
    /**
     * @var array<int,string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = NodeAddress::class;

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    public function canConvertFrom($source, $targetType): bool
    {
        return \mb_substr_count($source, '__') === 2 || \mb_strpos($source, '@') !== false;
    }

    /**
     * @param string $source
     * @param string $targetType
     * @param array<int,string> $convertedChildProperties
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ): NodeAddress {
        if (\mb_substr_count($source, '__') === 2) {
            return $this->nodeAddressFactory->createFromUriString($source);
        }
        return $this->nodeAddressFactory->createFromContextPath($source);
    }
}
