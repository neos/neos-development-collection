<?php
namespace TYPO3\Neos\Controller\Backend;

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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * The TYPO3 ContentModule controller; providing backend functionality for the Content Module.
 *
 * @Flow\Scope("singleton")
 */
class ContentController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * The pluginService
	 *
	 * @var \TYPO3\Neos\Service\PluginService
	 * @Flow\Inject
	 */
	protected $pluginService;

	/**
	 * Upload a new image, and return its metadata.
	 *
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function uploadImageAction(\TYPO3\Media\Domain\Model\Image $image) {
		$this->imageRepository->add($image);
		return $this->imageWithMetadataAction($image);
	}

	/**
	 * Fetch the metadata for a given image
	 *
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function imageWithMetadataAction(\TYPO3\Media\Domain\Model\Image $image) {
		$this->response->setHeader('Content-Type', 'application/json');
		$thumbnail = $image->getThumbnail(500, 500);

		return json_encode(array(
			'imageUuid' => $this->persistenceManager->getIdentifierByObject($image),
			'originalImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($image->getResource()),
			'previewImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnail->getResource()),
			'originalSize' => array('w' => $image->getWidth(), 'h' => $image->getHeight()),
			'previewSize' => array('w' => $thumbnail->getWidth(), 'h' => $thumbnail->getHeight())
		));
	}

	/**
	 * Fetch the configured views for the given master plugin
	 *
	 * @param NodeInterface $node
	 * @return string
	 */
	public function pluginViewsAction(NodeInterface $node) {
		$this->response->setHeader('Content-Type', 'application/json');

		$pluginViewDefinitions = $this->pluginService->getPluginViewDefinitionsByPluginNodeType($node->getNodeType());
		$views = array();
		/** @var $pluginViewDefinition \TYPO3\Neos\Domain\Model\PluginViewDefinition */
		foreach ($pluginViewDefinitions as $pluginViewDefinition) {
			$label = $pluginViewDefinition->getLabel();

			$views[$pluginViewDefinition->getName()] = array(
				'label' => $label
			);

			$pluginViewNode = $this->pluginService->getPluginViewNodeByMasterPlugin($node, $pluginViewDefinition->getName());
			if ($pluginViewNode === NULL) {
				continue;
			}
			$q = new FlowQuery(array($pluginViewNode));
			$page = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
			$uri = $this->uriBuilder
						->reset()
						->uriFor('show', array('node' => $page), 'Frontend\Node', 'TYPO3.Neos');
			$pageTitle = $page->getProperty('title');
			$views[$pluginViewDefinition->getName()] = array(
				'label' => sprintf('"%s"', $label, $pageTitle),
				'pageNode' => array(
					'title' => $pageTitle,
					'path' => $page->getPath(),
					'uri' => $uri
				)
			);
		}
		return json_encode((object) $views);
	}

	/**
	 * Fetch all master plugins that are available in the current
	 * workspace.
	 *
	 * @param NodeInterface $node
	 * @return string JSON encoded array of node path => label
	 */
	public function masterPluginsAction(NodeInterface $node) {
		$this->response->setHeader('Content-Type', 'application/json');

		$pluginNodes = $this->pluginService->getPluginNodesWithViewDefinitions($node->getContext());
		$masterPlugins = array();
		if (is_array($pluginNodes)) {
			/** @var $pluginNode NodeInterface */
			foreach ($pluginNodes as $pluginNode) {
				if ($pluginNode->isRemoved()) {
					continue;
				}
				$q = new FlowQuery(array($pluginNode));
				$page = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
				if ($page === NULL) {
					continue;
				}
				$masterPlugins[$pluginNode->getPath()] = sprintf('"%s" on page "%s"', $pluginNode->getNodeType()->getLabel(), $page->getProperty('title'));
			}
		}
		return json_encode((object) $masterPlugins);
	}
}
