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
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\Error\TargetNotFoundError;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Media\Domain\Model\Tag;

/**
 * This converter transforms to \Neos\Media\Domain\Model\Tag objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class TagConverter extends PersistentObjectConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = Tag::class;

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert an object from $source to an \Neos\Media\Domain\Model\Tag implementation
     *
     * @param mixed $source
     * @param string $targetType must implement 'Neos\Media\Domain\Model\Tag'
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return Tag|TargetNotFoundError The converted Tag, a Validation Error or NULL
     * @throws UnknownObjectException
     * @throws InvalidTargetException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $object = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);

        if ($object instanceof Tag) {
            // Persist tag immediately after it is converted, since otherwise the following happens:
            // If an asset and a collection to which the asset belongs are both tagged with the same tag, during the import two different tags with the same identity are created. This leads to an error when persisting the image.
            $this->persistenceManager->isNewObject($object) ? $this->persistenceManager->add($object) : $this->persistenceManager->update($object);
        }

        return $object;
    }
}
