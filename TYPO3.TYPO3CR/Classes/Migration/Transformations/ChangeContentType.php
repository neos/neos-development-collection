<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Change the content type.
 */
class ChangeContentType extends AbstractTransformation {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * The new ContentType to use as a string
	 *
	 * @var string
	 */
	protected $newType;

	/**
	 * @param string $newType
	 */
	public function setNewType($newType) {
		$this->newType = $newType;
	}

	/**
	 * If the given node has the property this transformation should work on, this
	 * returns TRUE if the given ContentType is registered with the ContentTypeManager
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return $this->contentTypeManager->hasContentType($this->newType);
	}

	/**
	 * Change the ContentType on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$contentType = $this->contentTypeManager->getContentType($this->newType);
		$node->setContentType($contentType);
	}
}
?>