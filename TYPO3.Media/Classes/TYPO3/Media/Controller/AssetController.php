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
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Repository\AudioRepository;
use TYPO3\Media\Domain\Repository\DocumentRepository;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\Media\Domain\Repository\VideoRepository;
use TYPO3\Media\TypeConverter\AssetInterfaceConverter;

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
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => 'TYPO3\Fluid\View\TemplateView',
		'json' => 'TYPO3\Flow\Mvc\View\JsonView'
	);

	/**
	 * Set common variables on the view
	 *
	 * @param \TYPO3\Flow\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view) {
		$view->assignMultiple(array(
			'view' => $this->browserState->get('view'),
			'sort' => $this->browserState->get('sort'),
			'filter' => $this->browserState->get('filter'),
			'activeTag' => $this->browserState->get('activeTag')
		));
	}

	/**
	 * List existing assets
	 *
	 * @param string $view
	 * @param string $sort
	 * @param string $filter
	 * @param integer $tagMode
	 * @param Tag $tag
	 * @param string $searchTerm
	 * @return void
	 */
	public function indexAction($view = NULL, $sort = NULL, $filter = NULL, $tagMode = self::TAG_GIVEN, Tag $tag = NULL, $searchTerm = NULL) {
		if ($view !== NULL) {
			$this->browserState->set('view', $view);
			$this->view->assign('view', $view);
		}
		if ($sort !== NULL) {
			$this->browserState->set('sort', $sort);
			$this->view->assign('sort', $sort);
		}
		if ($filter !== NULL) {
			$this->browserState->set('filter', $filter);
			$this->view->assign('filter', $filter);
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

		if ($this->browserState->get('filter') !== 'All') {
			switch ($this->browserState->get('filter')) {
				case 'Image':
					$this->assetRepository = new ImageRepository();
					break;
				case 'Document':
					$this->assetRepository = new DocumentRepository();
					break;
				case 'Video':
					$this->assetRepository = new VideoRepository();
					break;
				case 'Audio':
					$this->assetRepository = new AudioRepository();
					break;
			}
		}
		if ($this->browserState->get('sort') !== 'Modified') {
			$this->assetRepository->setDefaultOrderings(array('resource.filename' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING));
		}

		if ($searchTerm !== NULL) {
			$assets = $this->assetRepository->findBySearchTermOrTags($searchTerm);
			$this->view->assign('searchTerm', $searchTerm);
		} elseif ($this->browserState->get('tagMode') === self::TAG_NONE) {
			$assets = $this->assetRepository->findUntagged();
		} elseif ($this->browserState->get('activeTag') !== NULL) {
			$assets = $this->assetRepository->findByTag($this->browserState->get('activeTag'));
		} else {
			$assets = $this->assetRepository->findAll();
		}

		$maximumFileUploadSize = $this->maximumFileUploadSize();
		$this->view->assignMultiple(array(
			'assets' => $assets,
			'tags' => $tags,
			'allCount' => $this->assetRepository->countAll(),
			'untaggedCount' => $this->assetRepository->countUntagged(),
			'tagMode' => $tagMode,
			'argumentNamespace' => $this->request->getArgumentNamespace(),
			'maximumFileUploadSize' => $maximumFileUploadSize,
			'humanReadableMaximumFileUploadSize' => Files::bytesToSizeString($maximumFileUploadSize)
		));
	}

	/**
	 * New asset form
	 *
	 * @return void
	 */
	public function newAction() {
		$maximumFileUploadSize = $this->maximumFileUploadSize();
		$this->view->assignMultiple(array(
			'tags' => $this->tagRepository->findAll(),
			'maximumFileUploadSize' => $maximumFileUploadSize,
			'humanReadableMaximumFileUploadSize' => Files::bytesToSizeString($maximumFileUploadSize)
		));
	}

	/**
	 * Edit an asset
	 *
	 * @param Asset $asset
	 * @return void
	 */
	public function editAction(Asset $asset) {
		$this->view->assignMultiple(array(
			'tags' => $this->tagRepository->findAll(),
			'asset' => $asset
		));
	}

	/**
	 * @return void
	 */
	protected function initializeUpdateAction() {
		$assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
		$assetMappingConfiguration->allowProperties('title', 'resource', 'tags');
		$assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	/**
	 * Update an asset
	 *
	 * @param Asset $asset
	 * @return void
	 */
	public function updateAction(Asset $asset) {
		$this->assetRepository->update($asset);
		$this->addFlashMessage(sprintf('Asset "%s" has been updated.', $asset->getLabel()));
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
		$assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		$assetMappingConfiguration->setTypeConverterOption(AssetInterfaceConverter::class, AssetInterfaceConverter::CONFIGURATION_ONE_PER_RESOURCE, TRUE);
	}

	/**
	 * Create a new asset
	 *
	 * @param Asset $asset
	 * @return void
	 */
	public function createAction(Asset $asset) {
		if ($this->persistenceManager->isNewObject($asset)) {
			$this->assetRepository->add($asset);
		}
		$this->addFlashMessage(sprintf('Asset "%s" has been added.', $asset->getLabel()));
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
		$assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		$assetMappingConfiguration->setTypeConverterOption(AssetInterfaceConverter::class, AssetInterfaceConverter::CONFIGURATION_ONE_PER_RESOURCE, TRUE);
	}

	/**
	 * Upload a new asset. No redirection and no response body, for use by plupload (or similar).
	 *
	 * @param Asset $asset
	 * @return string
	 */
	public function uploadAction(Asset $asset) {
		if (($tag = $this->browserState->get('activeTag')) !== NULL) {
			$asset->addTag($tag);
		}
		if ($this->persistenceManager->isNewObject($asset)) {
			$this->assetRepository->add($asset);
		} else {
			$this->assetRepository->update($asset);
		}
		$this->addFlashMessage(sprintf('Asset "%s" has been added.', $asset->getLabel()));
		$this->response->setStatus(201);
		return '';
	}

	/**
	 * Tags an asset with a tag.
	 *
	 * No redirection and no response body, no flash message, for use by plupload (or similar).
	 *
	 * @param Asset $asset
	 * @param Tag $tag
	 * @return boolean
	 */
	public function tagAssetAction(Asset $asset, Tag $tag) {
		$success = FALSE;
		if ($asset->addTag($tag)) {
			$this->assetRepository->update($asset);
			$success = TRUE;
		}
		$this->view->assign('value', $success);
	}

	/**
	 * Delete an asset
	 *
	 * @param Asset $asset
	 * @return void
	 */
	public function deleteAction(Asset $asset) {
		$this->assetRepository->remove($asset);
		$this->addFlashMessage(sprintf('Asset "%s" has been deleted.', $asset->getLabel()));
		$this->redirect('index');
	}

	/**
	 * @param string $label
	 * @return void
	 * @Flow\Validate(argumentName="label", type="NotEmpty")
	 * @Flow\Validate(argumentName="label", type="Label")
	 */
	public function createTagAction($label) {
		$tag = $this->tagRepository->findByLabel($label);
		if (count($tag) > 0) {
			$this->addFlashMessage(sprintf('Tag "%s" already exists.', $label), '', \TYPO3\Flow\Error\Message::SEVERITY_ERROR);
		} else {
			$tag = new Tag($label);
			$this->tagRepository->add($tag);
			$this->addFlashMessage(sprintf('Tag "%s" has been created.', $label));
		}

		$this->redirect('index', NULL, NULL, array('tag' => $tag));
	}

	/**
	 * @param Tag $tag
	 * @return void
	 */
	public function editTagAction(Tag $tag) {
		$this->view->assign('tag', $tag);
	}

	/**
	 * @param Tag $tag
	 * @return void
	 */
	public function updateTagAction(Tag $tag) {
		$this->tagRepository->update($tag);
		$this->addFlashMessage(sprintf('Tag "%s" has been updated.', $tag->getLabel()));
		$this->redirect('index');
	}

	/**
	 * @param Tag $tag
	 * @return void
	 */
	public function deleteTagAction(Tag $tag) {
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
	 * Returns the lowest configured maximum upload file size
	 *
	 * @return integer
	 */
	protected function maximumFileUploadSize() {
		return min(Files::sizeStringToBytes(ini_get('post_max_size')), Files::sizeStringToBytes(ini_get('upload_max_filesize')));
	}
}
