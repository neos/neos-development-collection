<?php
namespace TYPO3\Neos\TYPO3CR\Transformations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Convert serialized (old resource management) ImageVariants to new ImageVariants.
 */
class ImageVariantTransformation extends \TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\TypeConverter\ProcessingInstructionsConverter
	 */
	protected $processingInstructionsConverter;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeData $node) {
		return TRUE;
	}

	/**
	 * Change the property on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node) {
		foreach ($node->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
			if (isset($propertyConfiguration['type']) && $propertyConfiguration['type'] === 'TYPO3\Media\Domain\Model\ImageInterface') {
				$adjustments = array();
				$oldVariantConfiguration = $node->getProperty($propertyName);
				if (is_array($oldVariantConfiguration)) {
					foreach ($oldVariantConfiguration as $variantPropertyName => $property) {
						switch (substr($variantPropertyName, 3)) {
							case 'originalImage':
								/**
								 * @var $originalAsset \TYPO3\Media\Domain\Model\Image
								 */
								$originalAsset = $this->assetRepository->findByIdentifier($this->persistenceManager->getIdentifierByObject($property));
								break;
							case 'processingInstructions':
								$adjustments = $this->processingInstructionsConverter->convertFrom($property, 'array');
								break;
						}
					}
					if (isset($originalAsset)) {
						$newImageVariant = new \TYPO3\Media\Domain\Model\ImageVariant($originalAsset);
						foreach ($adjustments as $adjustment) {
							$newImageVariant->addAdjustment($adjustment);
						}
						$originalAsset->addVariant($newImageVariant);
						$this->assetRepository->update($originalAsset);
						$node->setProperty($propertyName, $this->persistenceManager->getIdentifierByObject($newImageVariant));
					} else {
						$node->setProperty($propertyName, NULL);
					}

				}
			}
		}
	}
}
