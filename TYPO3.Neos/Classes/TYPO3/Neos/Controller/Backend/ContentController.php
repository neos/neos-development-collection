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
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Image;
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
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

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
	 * Upload a new Asset, and return its metadata
	 *
	 * Depending on the $metadata argument it will return asset metadata for the AssetEditor
	 * or image metadata for the ImageEditor
	 *
	 * @param Asset $asset
	 * @param string $metadata Type of metadata to return ("Asset" or "Image")
	 * @return string
	 */
	public function uploadAssetAction(Asset $asset, $metadata) {
		$this->assetRepository->add($asset);

		$this->response->setHeader('Content-Type', 'application/json');

		switch ($metadata) {
			case 'Asset':
				$result = $this->getAssetProperties($asset);
				break;
			case 'Image':
				$result = $this->getImageProperties($asset);
				break;
			default:
				$this->response->setStatus(400);
				$result = array('error' => 'Invalid "metadata" type: ' . $metadata);
		}
		return json_encode($result);
	}

	/**
	 * Fetch the metadata for a given image
	 *
	 * @param Image $image
	 * @return string JSON encoded response
	 */
	public function imageWithMetadataAction(Image $image) {
		$this->response->setHeader('Content-Type', 'application/json');

		$imageProperties = $this->getImageProperties($image);
		return json_encode($imageProperties);
	}

	/**
	 * @param Image $image
	 * @return array
	 */
	protected function getImageProperties(Image $image) {
		$thumbnail = $image->getThumbnail(500, 500);
		$imageProperties = array(
			'imageUuid' => $this->persistenceManager->getIdentifierByObject($image),
			'originalImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($image->getResource()),
			'previewImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnail->getResource()),
			'originalSize' => array('w' => $image->getWidth(), 'h' => $image->getHeight()),
			'previewSize' => array('w' => $thumbnail->getWidth(), 'h' => $thumbnail->getHeight())
		);
		return $imageProperties;
	}

	/**
	 * @return void
	 */
	public function initializeAssetsWithMetadataAction() {
		$propertyMappingConfiguration = $this->arguments->getArgument('assets')->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->allowAllProperties();
	}

	/**
	 * Fetch the metadata for multiple assets
	 *
	 * @param array<TYPO3\Media\Domain\Model\Asset> $assets
	 * @return string JSON encoded response
	 */
	public function assetsWithMetadataAction(array $assets) {
		$this->response->setHeader('Content-Type', 'application/json');

		$result = array();
		foreach ($assets as $asset) {
			$result[] = $this->getAssetProperties($asset);
		}
		return json_encode($result);
	}

	/**
	 * @param Asset $asset
	 * @return array
	 */
	protected function getAssetProperties(Asset $asset) {
		$thumbnail = $this->getAssetThumbnailImage($asset, 16, 16);
		$assetProperties = array(
			'assetUuid' => $this->persistenceManager->getIdentifierByObject($asset),
			'filename' => $asset->getResource()->getFilename(),
			'previewImageResourceUri' => $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnail['src'],
			'previewSize' => array('w' => $thumbnail['width'], 'h' => $thumbnail['height'])
		);
		return $assetProperties;
	}

	/**
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return integer
	 */
	protected function getDocumentIconSize($maximumWidth, $maximumHeight) {
		$size = max($maximumWidth, $maximumHeight);
		if ($size <= 16) {
			return 16;
		} elseif ($size <= 32) {
			return 32;
		} elseif ($size <= 48) {
			return 48;
		} else {
			return 512;
		}
	}

	/**
	 * @param AssetInterface $asset
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return array
	 */
	protected function getAssetThumbnailImage(AssetInterface $asset, $maximumWidth, $maximumHeight) {
		$iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

		if (is_file('resource://TYPO3.Media/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
			$icon = sprintf('TYPO3.Media/Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
		} else {
			$icon =  sprintf('TYPO3.Media/Icons/%spx/_blank.png', $iconSize);
		}

		return array(
			'width' => $iconSize,
			'height' => $iconSize,
			'src' => $icon
		);
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
