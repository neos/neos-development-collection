<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Controller\Exception\NodeCreationException;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TYPO3 ContentModule controller
 *
 * @FLOW3\Scope("singleton")
 */
class ContentController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @FLOW3\Inject
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
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 *
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function uploadImageAction(\TYPO3\Media\Domain\Model\Image $image) {
		$this->imageRepository->add($image);
		return $this->imageWithMetadataAction($image);
	}

	/**
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function imageWithMetadataAction(\TYPO3\Media\Domain\Model\Image $image) {
		$thumbnail = $image->getThumbnail(500, 500);

		return json_encode(array(
			'imageUuid' => $this->persistenceManager->getIdentifierByObject($image),
			'previewImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnail->getResource()),
			'originalSize' => array('width' => $image->getWidth(), 'height' => $image->getHeight()),
			'previewSize' => array('width' => $thumbnail->getWidth(), 'height' => $thumbnail->getHeight())
		));
	}

	/**
	 * Output a grouped list of possible (new) content elements to select from
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $position either "above", "below" or "inside"
	 * @return string
	 * @FLOW3\SkipCsrfProtection
	 */
	public function newAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode, $position) {
		$allContentTypes = $this->contentTypeManager->getSubContentTypes('TYPO3.TYPO3:ContentObject');
		$contentTypeGroups = $this->settings['contentTypeGroups'];
		$groupedContentTypes = array();
		foreach ($contentTypeGroups as $contentTypeGroup) {
			$groupedContentTypes[] = array(
				'label' => $contentTypeGroup,
				'contentTypes' => array()
			);
		}
		foreach ($allContentTypes as $groupKey => $contentType) {
			if (!$contentType->hasGroup()) {
				continue;
			}
			if (!in_array($contentType->getGroup(), $contentTypeGroups)) {
				$contentTypeGroups[] = $contentType->getGroup();
				$groupKey = array_search($contentType->getGroup(), $contentTypeGroups);
				$groupedContentTypes[$groupKey] = array (
					'label' => $contentType->getGroup(),
					'contentTypes' => array()
				);
			} else {
				$groupKey = array_search($contentType->getGroup(), $contentTypeGroups);
			}
			$groupedContentTypes[$groupKey]['contentTypes'][] = $contentType;
		}
		$this->view->assign('groupedContentTypes', $groupedContentTypes);
		$this->view->assign('contentTypes', $this->contentTypeManager->getSubContentTypes('TYPO3.TYPO3:ContentObject'));
		$this->view->assign('referenceNode', $referenceNode);
		$this->view->assign('position', $position);
	}

	/**
	 * This action currently returns the JS configuration we need for the backend.
	 * That's still quite unclean, but it works for now.
	 *
	 * @return string
	 * @FLOW3\SkipCsrfProtection
	 */
	public function javascriptConfigurationAction() {
		$this->response->setHeader('Content-Type', 'text/javascript');
		$this->response->setContent('window.T3Configuration = {}; window.T3Configuration.Schema = ' . json_encode($this->contentTypeManager->getFullConfiguration()) . '; window.T3Configuration.UserInterface = ' . json_encode($this->settings['userInterface']));
		return '';
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $position either "above", "below" or "inside"
	 * @param string $type
	 * @throws \TYPO3\TYPO3\Controller\Exception\NodeCreationException
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @throws \TYPO3\TYPO3\Exception
	 */
	protected function populateNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$contentType = $this->contentTypeManager->getContentType($node->getContentType());

			// Set default values
		foreach ($contentType->getProperties() as $propertyName => $propertyConfiguration) {
			if (isset($propertyConfiguration['default'])) {
				$node->setProperty($propertyName, $propertyConfiguration['default']);
			}
		}

			// Populate structure
		if ($contentType->hasStructure()) {
			foreach ($contentType->getStructure() as $nodeName => $nodeConfiguration) {
				if (!isset($nodeConfiguration['type'])) {
					throw new \TYPO3\TYPO3\Exception('Type for node in structure has to be configured', 1316881909);
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