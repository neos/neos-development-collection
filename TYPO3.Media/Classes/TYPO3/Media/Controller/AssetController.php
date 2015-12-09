<?php
namespace TYPO3\Media\Controller;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\Proxy as DoctrineProxy;
use Doctrine\ORM\EntityNotFoundException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Repository\AudioRepository;
use TYPO3\Media\Domain\Repository\DocumentRepository;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\Media\Domain\Repository\VideoRepository;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\TypeConverter\AssetInterfaceConverter;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    const TAG_GIVEN = 0;
    const TAG_ALL = 1;
    const TAG_NONE = 2;

    const COLLECTION_GIVEN = 0;
    const COLLECTION_ALL = 1;

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
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

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
    protected function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view)
    {
        $view->assignMultiple(array(
            'view' => $this->browserState->get('view'),
            'sort' => $this->browserState->get('sort'),
            'filter' => $this->browserState->get('filter'),
            'activeTag' => $this->browserState->get('activeTag'),
            'activeAssetCollection' => $this->browserState->get('activeAssetCollection')
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
     * @param integer $collectionMode
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function indexAction($view = null, $sort = null, $filter = null, $tagMode = self::TAG_GIVEN, Tag $tag = null, $searchTerm = null, $collectionMode = self::COLLECTION_GIVEN, AssetCollection $assetCollection = null)
    {
        if ($view !== null) {
            $this->browserState->set('view', $view);
            $this->view->assign('view', $view);
        }
        if ($sort !== null) {
            $this->browserState->set('sort', $sort);
            $this->view->assign('sort', $sort);
        }
        if ($filter !== null) {
            $this->browserState->set('filter', $filter);
            $this->view->assign('filter', $filter);
        }
        if ($tagMode === self::TAG_GIVEN && $tag !== null) {
            $this->browserState->set('activeTag', $tag);
            $this->view->assign('activeTag', $tag);
        } elseif ($tagMode === self::TAG_NONE || $tagMode === self::TAG_ALL) {
            $this->browserState->set('activeTag', null);
            $this->view->assign('activeTag', null);
        }
        $this->browserState->set('tagMode', $tagMode);

        if ($collectionMode === self::COLLECTION_GIVEN && $assetCollection !== null) {
            $this->browserState->set('activeAssetCollection', $assetCollection);
            $this->view->assign('activeAssetCollection', $assetCollection);
        } elseif ($collectionMode === self::COLLECTION_ALL) {
            $this->browserState->set('activeAssetCollection', null);
            $this->view->assign('activeAssetCollection', null);
        }
        $this->browserState->set('collectionMode', $collectionMode);
        try {
            /** @var AssetCollection $activeAssetCollection */
            $activeAssetCollection = $this->browserState->get('activeAssetCollection');
            if ($activeAssetCollection instanceof DoctrineProxy) {
                // To trigger a possible EntityNotFound have to load the entity
                $activeAssetCollection->__load();
            }
        } catch (EntityNotFoundException $exception) {
            // If a removed tasset collection is still in the browser state it can not be fetched
            $this->browserState->set('activeAssetCollection', null);
            $activeAssetCollection = null;
        }

        // Unset active tag if it isn't available in the active asset collection
        if ($this->browserState->get('activeTag') && $activeAssetCollection !== null && !$activeAssetCollection->getTags()->contains($this->browserState->get('activeTag'))) {
            $this->browserState->set('activeTag', null);
            $this->view->assign('activeTag', null);
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

        if (!$this->browserState->get('activeTag') && $this->browserState->get('tagMode') === self::TAG_GIVEN) {
            $this->browserState->set('tagMode', self::TAG_ALL);
        }

        $assetCollections = array();
        foreach ($this->assetCollectionRepository->findAll() as $assetCollection) {
            $assetCollections[] = array('object' => $assetCollection, 'count' => $this->assetRepository->countByAssetCollection($assetCollection));
        }

        $tags = array();
        foreach ($activeAssetCollection !== null ? $activeAssetCollection->getTags() : $this->tagRepository->findAll() as $tag) {
            $tags[] = array('object' => $tag, 'count' => $this->assetRepository->countByTag($tag, $activeAssetCollection));
        }

        if ($searchTerm !== null) {
            $assets = $this->assetRepository->findBySearchTermOrTags($searchTerm, array(), $activeAssetCollection);
            $this->view->assign('searchTerm', $searchTerm);
        } elseif ($this->browserState->get('tagMode') === self::TAG_NONE) {
            $assets = $this->assetRepository->findUntagged($activeAssetCollection);
        } elseif ($this->browserState->get('activeTag') !== null) {
            $assets = $this->assetRepository->findByTag($this->browserState->get('activeTag'), $activeAssetCollection);
        } else {
            $assets = $activeAssetCollection === null ? $this->assetRepository->findAll() : $this->assetRepository->findByAssetCollection($activeAssetCollection);
        }

        $allCollectionsCount = $this->assetRepository->countAll();
        $maximumFileUploadSize = $this->maximumFileUploadSize();
        $this->view->assignMultiple(array(
            'assets' => $assets,
            'tags' => $tags,
            'allCollectionsCount' => $allCollectionsCount,
            'allCount' => $activeAssetCollection ? $this->assetRepository->countByAssetCollection($activeAssetCollection) : $allCollectionsCount,
            'untaggedCount' => $this->assetRepository->countUntagged($activeAssetCollection),
            'tagMode' => $this->browserState->get('tagMode'),
            'assetCollections' => $assetCollections,
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
    public function newAction()
    {
        $maximumFileUploadSize = $this->maximumFileUploadSize();
        $this->view->assignMultiple(array(
            'tags' => $this->tagRepository->findAll(),
            'assetCollections' => $this->assetCollectionRepository->findAll(),
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
    public function editAction(Asset $asset)
    {
        $this->view->assignMultiple(array(
            'tags' => $asset->getAssetCollections()->count() > 0 ? $this->tagRepository->findByAssetCollections($asset->getAssetCollections()->toArray()) : $this->tagRepository->findAll(),
            'asset' => $asset,
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ));
    }

    /**
     * @return void
     */
    protected function initializeUpdateAction()
    {
        $assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
        $assetMappingConfiguration->allowProperties('title', 'resource', 'tags', 'assetCollections');
        $assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
    }

    /**
     * Update an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function updateAction(Asset $asset)
    {
        $this->assetRepository->update($asset);
        $this->addFlashMessage(sprintf('Asset "%s" has been updated.', htmlspecialchars($asset->getLabel())));
        $this->redirect('index');
    }

    /**
     * Initialization for createAction
     *
     * @return void
     */
    protected function initializeCreateAction()
    {
        $assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
        $assetMappingConfiguration->allowProperties('title', 'resource', 'tags', 'assetCollections');
        $assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        $assetMappingConfiguration->setTypeConverterOption(AssetInterfaceConverter::class, AssetInterfaceConverter::CONFIGURATION_ONE_PER_RESOURCE, true);
    }

    /**
     * Create a new asset
     *
     * @param Asset $asset
     * @return void
     */
    public function createAction(Asset $asset)
    {
        if ($this->persistenceManager->isNewObject($asset)) {
            $this->assetRepository->add($asset);
        }
        $this->addFlashMessage(sprintf('Asset "%s" has been added.', htmlspecialchars($asset->getLabel())));
        $this->redirect('index', null, null, array(), 0, 201);
    }

    /**
     * Initialization for uploadAction
     *
     * @return void
     */
    protected function initializeUploadAction()
    {
        $assetMappingConfiguration = $this->arguments->getArgument('asset')->getPropertyMappingConfiguration();
        $assetMappingConfiguration->allowProperties('title', 'resource');
        $assetMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        $assetMappingConfiguration->setTypeConverterOption(AssetInterfaceConverter::class, AssetInterfaceConverter::CONFIGURATION_ONE_PER_RESOURCE, true);
    }

    /**
     * Upload a new asset. No redirection and no response body, for use by plupload (or similar).
     *
     * @param Asset $asset
     * @return string
     */
    public function uploadAction(Asset $asset)
    {
        if (($tag = $this->browserState->get('activeTag')) !== null) {
            $asset->addTag($tag);
        }

        if ($this->persistenceManager->isNewObject($asset)) {
            $this->assetRepository->add($asset);
        } else {
            $this->assetRepository->update($asset);
        }

        if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addAsset($asset)) {
            $this->assetCollectionRepository->update($assetCollection);
        }

        $this->addFlashMessage(sprintf('Asset "%s" has been added.', htmlspecialchars($asset->getLabel())));
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
    public function tagAssetAction(Asset $asset, Tag $tag)
    {
        $success = false;
        if ($asset->addTag($tag)) {
            $this->assetRepository->update($asset);
            $success = true;
        }
        $this->view->assign('value', $success);
    }

    /**
     * Adds an asset to an asset collection
     *
     * @param Asset $asset
     * @param AssetCollection $assetCollection
     * @return boolean
     */
    public function addAssetToCollectionAction(Asset $asset, AssetCollection $assetCollection)
    {
        $success = false;
        if ($assetCollection->addAsset($asset)) {
            $this->assetCollectionRepository->update($assetCollection);
            $success = true;
        }
        $this->view->assign('value', $success);
    }

    /**
     * Delete an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function deleteAction(Asset $asset)
    {
        $this->assetRepository->remove($asset);
        $this->addFlashMessage(sprintf('Asset "%s" has been deleted.', htmlspecialchars($asset->getLabel())));
        $this->redirect('index');
    }

    /**
     * @param string $label
     * @return void
     * @Flow\Validate(argumentName="label", type="NotEmpty")
     * @Flow\Validate(argumentName="label", type="Label")
     */
    public function createTagAction($label)
    {
        $existingTag = $this->tagRepository->findOneByLabel($label);
        if ($existingTag !== null) {
            if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addTag($existingTag)) {
                $this->assetCollectionRepository->update($assetCollection);
                $this->addFlashMessage(sprintf('Tag "%s" already exists and was added to collection.', htmlspecialchars($label)));
            }
        } else {
            $tag = new Tag($label);
            $this->tagRepository->add($tag);
            if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addTag($tag)) {
                $this->assetCollectionRepository->update($assetCollection);
            }
            $this->addFlashMessage(sprintf('Tag "%s" has been created.', htmlspecialchars($label)));
        }
        $this->redirect('index');
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function editTagAction(Tag $tag)
    {
        $this->view->assignMultiple(array(
            'tag' => $tag,
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ));
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function updateTagAction(Tag $tag)
    {
        $this->tagRepository->update($tag);
        $this->addFlashMessage(sprintf('Tag "%s" has been updated.', htmlspecialchars($tag->getLabel())));
        $this->redirect('index');
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function deleteTagAction(Tag $tag)
    {
        $taggedAssets = $this->assetRepository->findByTag($tag);
        foreach ($taggedAssets as $asset) {
            $asset->removeTag($tag);
            $this->assetRepository->update($asset);
        }
        $this->tagRepository->remove($tag);
        $this->addFlashMessage(sprintf('Tag "%s" has been deleted.', htmlspecialchars($tag->getLabel())));
        $this->redirect('index');
    }

    /**
     * @param string $title
     * @return void
     * @Flow\Validate(argumentName="title", type="NotEmpty")
     * @Flow\Validate(argumentName="title", type="Label")
     */
    public function createAssetCollectionAction($title)
    {
        $this->assetCollectionRepository->add(new AssetCollection($title));
        $this->addFlashMessage(sprintf('Collection "%s" has been created.', htmlspecialchars($title)));
        $this->redirect('index');
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function editAssetCollectionAction(AssetCollection $assetCollection)
    {
        $this->view->assignMultiple(array(
            'assetCollection' => $assetCollection,
            'tags' => $this->tagRepository->findAll()
        ));
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function updateAssetCollectionAction(AssetCollection $assetCollection)
    {
        $this->assetCollectionRepository->update($assetCollection);
        $this->addFlashMessage(sprintf('Collection "%s" has been updated.', htmlspecialchars($assetCollection->getTitle())));
        $this->redirect('index');
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function deleteAssetCollectionAction(AssetCollection $assetCollection)
    {
        if ($this->browserState->get('activeAssetCollection') === $assetCollection) {
            $this->browserState->set('activeAssetCollection', null);
        }
        $this->assetCollectionRepository->remove($assetCollection);
        $this->addFlashMessage(sprintf('Collection "%s" has been deleted.', htmlspecialchars($assetCollection->getTitle())));
        $this->redirect('index');
    }

    /**
     * Returns the lowest configured maximum upload file size
     *
     * @return integer
     */
    protected function maximumFileUploadSize()
    {
        return min(Files::sizeStringToBytes(ini_get('post_max_size')), Files::sizeStringToBytes(ini_get('upload_max_filesize')));
    }
}
