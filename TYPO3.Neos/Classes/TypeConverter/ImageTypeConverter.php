<?php
namespace TYPO3\TYPO3\TypeConverter;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An type converter for Image objects; which are uploaded using plupload
 *
 * @FLOW3\Scope("singleton")
 */
class ImageTypeConverter extends \TYPO3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Media\Domain\Model\Image';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * We only convert stuff being uploaded using plupload.
	 *
	 * @param mixed $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
	 * @api
	 */
	public function canConvertFrom($source, $targetType) {
		return (isset($source['type']) && $source['type'] === 'plupload');
	}

	/**
	 * Converts the given string or array to a ResourcePointer object.
	 *
	 * If the input format is an array, this method assumes the resource to be a
	 * fresh file upload and imports the temporary upload file through the
	 * resource manager.
	 *
	 * @param array $source The upload info (expected keys: error, name, tmp_name)
	 * @return object An object or an instance of TYPO3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$resource = $this->resourceManager->importUploadedResource($_FILES['file']);
		if ($resource === FALSE) {
			throw new \TYPO3\FLOW3\Property\Exception\TypeConverterException('Resource could not be converted.', 1316428994);
		}
		$image = new \TYPO3\Media\Domain\Model\Image($resource);
		$image->setTitle(''); // TODO: this should maybe be settable
		return $image;
	}
}

?>