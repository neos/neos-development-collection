<?php
namespace TYPO3\Neos\TypeConverter;

/*
 * This file is part of the TYPO3.Neos package.
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
use Neos\Flow\Utility\TypeHandling;

/**
 * Converts the given entity to a JSON representation containing the identity and object type
 */
class EntityToIdentityConverter extends AbstractTypeConverter
{
    /**
     * The source types this converter can convert.
     *
     * @var array<string>
     */
    protected $sourceTypes = array('object');

    /**
     * The target type this converter can convert to.
     *
     * @var string
     */
    protected $targetType = 'array';

    /**
     * The priority for this converter.
     *
     * @var integer
     */
    protected $priority = 0;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Check if the given object has an identity.
     *
     * @param object $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     */
    public function canConvertFrom($source, $targetType)
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($source);
        return ($identifier !== null);
    }


    /**
     * Converts the given source object to an array containing the type and identity.
     *
     * @param object $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        return [
            '__identity' => $this->persistenceManager->getIdentifierByObject($source),
            '__type' => TypeHandling::getTypeForValue($source)
        ];
    }
}
