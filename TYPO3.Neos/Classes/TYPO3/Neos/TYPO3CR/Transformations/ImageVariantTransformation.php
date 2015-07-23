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

use Doctrine\Common\Persistence\ObjectManager;
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
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related interface.
	 *
	 * @Flow\Inject
	 * @var ObjectManager
	 */
	protected $entityManager;

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
			if (isset($propertyConfiguration['type']) && ($propertyConfiguration['type'] === 'TYPO3\Media\Domain\Model\ImageInterface' || preg_match('/array\<.*\>/', $propertyConfiguration['type']))) {
				if (!isset($nodeProperties)) {
					$nodeRecordQuery = $this->entityManager->getConnection()->prepare('SELECT properties FROM typo3_typo3cr_domain_model_nodedata WHERE persistence_object_identifier=?');
					$nodeRecordQuery->execute([$this->persistenceManager->getIdentifierByObject($node)]);
					$nodeRecord = $nodeRecordQuery->fetch(\PDO::FETCH_ASSOC);
					$nodeProperties = unserialize($nodeRecord['properties']);
				}

				if (!isset($nodeProperties[$propertyName]) || empty($nodeProperties[$propertyName])) {
					continue;
				}

				if ($propertyConfiguration['type'] === 'TYPO3\Media\Domain\Model\ImageInterface') {
					$adjustments = array();
					$oldVariantConfiguration = $nodeProperties[$propertyName];
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
							$nodeProperties[$propertyName] = $this->persistenceManager->getIdentifierByObject($newImageVariant);
						} else {
							$nodeProperties[$propertyName] = NULL;
						}
					}
				} elseif (preg_match('/array\<.*\>/', $propertyConfiguration['type'])) {
					if (is_array($nodeProperties[$propertyName])) {
						$convertedValue = [];
						foreach ($nodeProperties[$propertyName] as $entryValue) {
							$convertedValue[] = $this->persistenceManager->getIdentifierByObject($entryValue);
						}
						$nodeProperties[$propertyName] = $convertedValue;
					}
				}

				$nodeUpdateQuery = $this->entityManager->getConnection()->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties=? WHERE persistence_object_identifier=?');
				$nodeUpdateQuery->execute([serialize($nodeProperties), $this->persistenceManager->getIdentifierByObject($node)]);
			}
		}
	}
}
