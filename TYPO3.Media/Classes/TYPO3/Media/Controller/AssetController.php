<?php
namespace TYPO3\Media\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	const TAG_GIVEN = 0;
	const TAG_ALL = 1;
	const TAG_NONE = 2;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\TagRepository
	 */
	protected $tagRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var \TYPO3\Media\Domain\Session\BrowserState
	 */
	protected $browserState;

	/**
	 * Set common variables on the view
	 *
	 * @param \TYPO3\Flow\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view) {
		$view->assign('view', $this->browserState->get('view'));
		$view->assign('activeTag', $this->browserState->get('activeTag'));
	}

	/**
	 * List existing assets
	 *
	 * @param string $view
	 * @param integer $tagMode
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return void
	 */
	public function indexAction($view = NULL, $tagMode = self::TAG_GIVEN, \TYPO3\Media\Domain\Model\Tag $tag = NULL) {
		if ($view !== NULL) {
			$this->browserState->set('view', $view);
			$this->view->assign('view', $view);
		}
		if ($tagMode === self::TAG_GIVEN && $tag !== NULL) {
			$this->browserState->set('activeTag', $tag);
			$this->view->assign('activeTag', $tag);
		} elseif ($tagMode === self::TAG_NONE || $tagMode === self::TAG_ALL) {
			$this->browserState->set('activeTag', NULL);
			$this->view->assign('activeTag', NULL);
		}
		$this->view->assign('tagMode', $tagMode);
		$this->browserState->set('tagMode', $tagMode);

		$tags = array();
		foreach ($this->tagRepository->findAll() as $tag) {
			$tags[] = array('tag' => $tag, 'count' => $this->assetRepository->countByTag($tag));
		}

		if ($this->browserState->get('tagMode') === self::TAG_NONE) {
			$assets = $this->assetRepository->findUntagged();
		} elseif ($this->browserState->get('activeTag') !== NULL) {
			$assets = $this->assetRepository->findByTag($this->browserState->get('activeTag'));
		} else {
			$assets = $this->assetRepository->findAll();
		}

		$this->view->assignMultiple(array(
			'assets' => $assets,
			'tags' => $tags,
			'allCount' => $this->assetRepository->countAll(),
			'untaggedCount' => $this->assetRepository->countUntagged(),
			'tagMode' => $tagMode
		));
	}

	/**
	 * New asset form
	 *
	 * @return void
	 */
	public function newAction() {
		$this->view->assign('tags', $this->tagRepository->findAll());
	}

	/**
	 * Edit an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function editAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->view->assign('tags', $this->tagRepository->findAll());
		$this->view->assign('asset', $asset);
	}

	/**
	 * @return void
	 */
	protected function initializeUpdateAction() {
		$assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
		$assetMappingConfiguration->allowProperties('title', 'resource', 'tags');
		$assetMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	/**
	 * Update an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function updateAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->assetRepository->update($asset);
		$this->addFlashMessage('Asset has been updated.');
		$this->redirect('index');
	}

	/**
	 * Initialization for createAction
	 *
	 * @return void
	 */
	protected function initializeCreateAction() {
		$assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
		$assetMappingConfiguration->allowProperties('title', 'resource', 'tags');
		$assetMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	/**
	 * Create a new asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function createAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$asset = $this->transformAsset($asset);

		$this->assetRepository->add($asset);
		$this->addFlashMessage('Asset has been added.');
		$this->redirect('index', NULL, NULL, array(), 0, 201);
	}

	/**
	 * Initialization for uploadAction
	 *
	 * @return void
	 */
	protected function initializeUploadAction() {
		$assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
		$assetMappingConfiguration->allowProperties('title', 'resource');
		$assetMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	/**
	 * Upload a new asset. No redirection and no response body, for use by plupload (or similar).
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return string
	 */
	public function uploadAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$asset = $this->transformAsset($asset);

		if (($tag = $this->browserState->get('activeTag')) !== NULL) {
			$asset->addTag($tag);
		}
		$this->assetRepository->add($asset);
		$this->addFlashMessage('Asset has been added.');
		$this->response->setStatus(201);
		return '';
	}

	/**
	 * Tags an asset with a tag.
	 *
	 * No redirection and no response body, no flash message, for use by plupload (or similar).
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return boolean
	 */
	public function tagAssetAction(\TYPO3\Media\Domain\Model\Asset $asset, \TYPO3\Media\Domain\Model\Tag $tag) {
		if (!$asset->addTag($tag)) {
			return FALSE;
		}
		$this->assetRepository->update($asset);
		return TRUE;
	}

	/**
	 * Delete an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function deleteAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->resourceManager->deleteResource($asset->getResource());
		$this->assetRepository->remove($asset);
		$this->addFlashMessage('Asset has been deleted.');
		$this->redirect('index');
	}

	/**
	 * @param string $label
	 * @return void
	 * @Flow\Validate(argumentName="label", type="NotEmpty")
	 * @Flow\Validate(argumentName="label", type="Label")
	 */
	public function createTagAction($label) {
		$this->tagRepository->add(new \TYPO3\Media\Domain\Model\Tag($label));
		$this->addFlashMessage(sprintf('Tag "%s" has been created.', $label));
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return void
	 */
	public function deleteTagAction(\TYPO3\Media\Domain\Model\Tag $tag) {
		$taggedAssets = $this->assetRepository->findByTag($tag);
		foreach ($taggedAssets as $asset) {
			$asset->removeTag($tag);
			$this->assetRepository->update($asset);
		}
		$this->tagRepository->remove($tag);
		$this->addFlashMessage(sprintf('Tag "%s" has been deleted.', $tag->getLabel()));
		$this->redirect('index');
	}

	/**
	 *
	 *
	 * @param \TYPO3\Media\Domain\Model\AssetInterface $asset
	 * @return \TYPO3\Media\Domain\Model\AssetInterface
	 */
	protected function transformAsset(\TYPO3\Media\Domain\Model\AssetInterface $asset) {
		$title = $asset->getTitle();
		if ($title === '') {
			$title = $asset->getResource()->getFilename();
		}
		$caption = $asset->getCaption();
		$tags = $asset->getTags();

		list($contentType, $subType) = explode('/', $asset->getResource()->getMediaType());
		switch ($contentType) {
			case 'image':
				$asset = new \TYPO3\Media\Domain\Model\Image($asset->getResource());
			break;
			case 'audio':
				$asset = new \TYPO3\Media\Domain\Model\Audio($asset->getResource());
			break;
			case 'video':
				$asset = new \TYPO3\Media\Domain\Model\Video($asset->getResource());
			break;
			case 'text':
				$asset = new \TYPO3\Media\Domain\Model\Document($asset->getResource());
			break;
			case 'application':
				if ($subType === 'pdf') {
					$asset = new \TYPO3\Media\Domain\Model\Document($asset->getResource());
				}
			break;
		}
		$asset->setTitle($title);
		$asset->setCaption($caption);
		$asset->setTags($tags);

		return $asset;
	}

}
