<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Controller\Exception\NodeCreationException;

/**
 * The TYPO3 ContentModule controller
 *
 * @scope singleton
 */
class ContentController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \TYPO3\TYPO3\Domain\Repository\Media\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * Adds the uploaded image to the image repository and returns the
	 * identifier of the image object.
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 *
	 * @param \TYPO3\TYPO3\Domain\Model\Media\Image $image
	 * @return string
	 */
	public function uploadImageAction(\TYPO3\TYPO3\Domain\Model\Media\Image $image) {
		$this->imageRepository->add($image);
		return $this->persistenceManager->getIdentifierByObject($image);
	}

	/**
	 * @param \TYPO3\TYPO3\Domain\Model\Media\Image $image
	 */
	public function imageWithMetadataAction(\TYPO3\TYPO3\Domain\Model\Media\Image $image = NULL) {
		return json_encode(array(
			'resourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($image->getResource()),
			'originalSize' => array('width' => 800, 'height' => 600),
			'previewSize' => array('width' => 400, 'height' => 300)
		));
	}

	/**
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $position either "above", "below" or "inside"
	 * @return string
	 * @skipCsrfProtection
	 */
	public function newAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, $position) {
		$this->view->assign('contentTypes', $this->contentTypeManager->getSubContentTypes('TYPO3.TYPO3:ContentObject'));
		$this->view->assign('referenceNode', $referenceNode);
		$this->view->assign('position', $position);
	}

	/**
	 * This action currently returns the JS configuration we need for the backend.
	 * That's still quite unclean, but it works for now.
	 *
	 * @skipCsrfProtection
	 */
	public function javascriptConfigurationAction() {
		return 'window.T3Configuration = {}; window.T3Configuration.Schema = ' . json_encode($this->contentTypeManager->getFullConfiguration()) . '; window.T3Configuration.UserInterface = ' . json_encode($this->settings['userInterface']);
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $position either "above", "below" or "inside"
	 * @param string $type
	 * @return string
	 */
	public function createAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, $position, $type) {
		if (!in_array($position, array('above', 'below', 'inside'))) {
			throw new NodeCreationException(sprintf('Position "%s" given, but only "above, below, inside" supported', $position), 1313754773);
		}

		if ($position === 'inside') {
			$parentNode = $referenceNode;
		} else {
			$parentNode = $referenceNode->getParent();
		}

		// TODO: Write policy which only allows createAction for logged in users!
		// TODO: make it possible for the user to specify the node identifier
		$newNode = $parentNode->createNode(uniqid(), $type);
		if ($position === 'above') {
			$newNode->moveBefore($referenceNode);
		} elseif ($position === 'below') {
			$newNode->moveAfter($referenceNode);
		}

		$this->populateNode($newNode);

		$parentFolderNode = $this->findNextParentFolderNode($newNode);
			// TODO: write Page URI service; it must be easier to retrieve the URI for a node...
		$pageUri = $this->uriBuilder
				->reset()
				->uriFor('show', array('node' => $parentFolderNode), 'Frontend\Node', 'TYPO3.TYPO3');
		return '<a rel="typo3-created-new-content" href="' . $newNode->getContextPath() . '" data-page="' . $pageUri . '">Go to new content element</a>';
	}

	/**
	 * Populate a given node
	 *
	 * Inserts the defined structure for a content type.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @return void
	 */
	protected function populateNode(\TYPO3\TYPO3CR\Domain\Model\Node $node) {
		$contentType = $this->contentTypeManager->getContentType($node->getContentType());

			// Fill Placeholder
		foreach ($contentType->getProperties() as $propertyName => $propertyConfiguration) {
			if (isset($propertyConfiguration['placeholder'])) {
				$node->setProperty($propertyName, $propertyConfiguration['placeholder']);
			}
		}

		if ($contentType->hasStructure()) {
			foreach ($contentType->getStructure() as $nodeName => $nodeConfiguration) {
				if (!isset($nodeConfiguration['type'])) {
					throw new \Exception('TODO fix exception');
				}

				$node->createNode($nodeName, $nodeConfiguration['type']);

				// TODO: recurse into nested structure definition
			}
		}
	}

	// TODO: TEAR APART THE CONTENT CONTROLLER!!!

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected function findNextParentFolderNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		while ($node = $node->getParent()) {
			if ($node->getContentType() === 'TYPO3.TYPO3:Page') {
				// TODO: Support for other "Folder" types, which are not of type "Page"
				return $node;
			}
		}
		return NULL;
	}
}
?>