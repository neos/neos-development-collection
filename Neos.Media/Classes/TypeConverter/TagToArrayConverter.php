<?php
namespace Neos\Media\TypeConverter;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Media\Domain\Model\Tag;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * This converter transforms Neos.Media Tag instances to arrays.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class TagToArrayConverter extends AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $sourceTypes = [Tag::class];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @param mixed $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        return ($source instanceof Tag);
    }


    /**
     * Return a list of sub-properties inside the source object.
     * The "key" is the sub-property name, and the "value" is the value of the sub-property.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * Return the type of a given sub-property inside the $targetType, in this case always "array"
     *
     * @param string $targetType is ignored
     * @param string $propertyName is ignored
     * @param PropertyMappingConfigurationInterface $configuration is ignored
     * @return string always "array"
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        return 'array';
    }

    /**
     * Convert an object from $source to an array
     *
     * @param Tag $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array The converted tag or NULL
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $identity = $this->persistenceManager->getIdentifierByObject($source);

        return [
            '__identity' => $identity,
            '__type' => Tag::class,
            'label' => $source->getLabel(),
        ];
    }
}
