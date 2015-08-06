<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;


/**
 * Takes care of cleaning up ImageVariants.
 *
 * @Flow\Scope("singleton")
 */
class ImageVariantGarbageCollector {

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Removes unused ImageVariants after a Node property changes to a different ImageVariant.
	 * This is triggered via the nodePropertyChanged event.
	 *
	 * Note: This method it triggered by the "nodePropertyChanged" signal, @see \TYPO3\TYPO3CR\Domain\Model\Node::emitNodePropertyChanged()
	 *
	 * @param NodeInterface $node the affected node
	 * @param string $propertyName name of the property that has been changed/added
	 * @param mixed $oldValue the property value before it was changed or NULL if the property is new
	 * @param mixed $value the new property value
	 * @return void
	 */
	public function removeUnusedImageVariant(NodeInterface $node, $propertyName, $oldValue, $value) {
		if ($oldValue === $value || (!$oldValue instanceof ImageVariant)) {
			return;
		}
		$identifier = $this->persistenceManager->getIdentifierByObject($oldValue);
		$results = $this->nodeDataRepository->findNodesByRelatedEntities(array(ImageVariant::class => [$identifier]));

		// This case shouldn't happen as the query will usually find at least the node that triggered this call, still if there is no relation we can remove the ImageVariant.
		if ($results === []) {
			$this->assetRepository->remove($oldValue);
			return;
		}

		// If the result contains exactly the node that got a new ImageVariant assigned then we are safe to remove the asset here.
		if ($results === [$node->getNodeData()]) {
			$this->assetRepository->remove($oldValue);
		}
	}
}