<?php
namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\Proxy as DoctrineProxy;
use Doctrine\ORM\EntityNotFoundException;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\InvalidArgumentValueException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Browser\Domain\Session\BrowserState;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetNotFoundExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepositoryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetTypeFilter;
use Neos\Media\Domain\Model\AssetSource\Neos\NeosAssetProxy;
use Neos\Media\Domain\Model\AssetSource\SupportsCollectionsInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsSortingInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsTaggingInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Domain\Service\UserService as DomainUserService;
use Neos\Neos\Service\UserService;
use Neos\Utility\Files;
use Neos\Utility\MediaTypes;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends ActionController
{
    use CreateContentContextTrait;
    use BackendUserTranslationTrait;

    const TAG_GIVEN = 0;
    const TAG_ALL = 1;
    const TAG_NONE = 2;

    const COLLECTION_GIVEN = 0;
    const COLLECTION_ALL = 1;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => JsonView::class
    ];

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject()
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject(lazy = false)
     * @var BrowserState
     */
    protected $browserState;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\InjectConfiguration(path="assetSources", package="Neos.Media")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @var AssetSourceInterface[]
     */
    protected $assetSources = [];

    /**
     * @return void
     */
    public function initializeObject()
    {
        $domain = $this->domainRepository->findOneByActiveRequest();

        // Set active asset collection to the current site's asset collection, if it has one, on the first view if a matching domain is found
        if ($domain !== null && !$this->browserState->get('activeAssetCollection') && $this->browserState->get('automaticAssetCollectionSelection') !== true && $domain->getSite()->getAssetCollection() !== null) {
            $this->browserState->set('activeAssetCollection', $domain->getSite()->getAssetCollection());
            $this->browserState->set('automaticAssetCollectionSelection', true);
        }

        foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
            if (is_array($assetSourceConfiguration)) {
                $this->assetSources[$assetSourceIdentifier] = new $assetSourceConfiguration['assetSource']($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions'] ?? []);
            }
        }
    }

    /**
     * Set common variables on the view
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        $view->assignMultiple([
            'view' => $this->browserState->get('view'),
            'sortBy' => $this->browserState->get('sortBy'),
            'sortDirection' => $this->browserState->get('sortDirection'),
            'filter' => $this->browserState->get('filter'),
            'activeTag' => $this->browserState->get('activeTag'),
            'activeAssetCollection' => $this->browserState->get('activeAssetCollection'),
            'assetSources' => $this->assetSources
        ]);
    }

    /**
     * List existing assets
     *
     * @param string $view
     * @param string $sortBy
     * @param string $sortDirection
     * @param string $filter
     * @param int $tagMode
     * @param Tag $tag
     * @param string $searchTerm
     * @param int $collectionMode
     * @param AssetCollection $assetCollection
     * @param string $assetSourceIdentifier
     * @return void
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function indexAction($view = null, $sortBy = null, $sortDirection = null, $filter = null, $tagMode = self::TAG_GIVEN, Tag $tag = null, $searchTerm = null, $collectionMode = self::COLLECTION_GIVEN, AssetCollection $assetCollection = null, $assetSourceIdentifier = null)
    {
        // First, apply all options given to indexAction() and save them in the BrowserState object.
        // Note that the order of these apply*() method calls plays a role, because they may depend on previous results:
        $this->applyActiveAssetSourceToBrowserState($assetSourceIdentifier);
        $this->applyAssetCollectionOptionsToBrowserState($collectionMode, $assetCollection);

        $activeAssetSource = $this->getAssetSourceFromBrowserState();
        $assetProxyRepository = $activeAssetSource->getAssetProxyRepository();
        $activeAssetCollection = $this->getActiveAssetCollectionFromBrowserState();

        $this->applyViewOptionsToBrowserState($view, $sortBy, $sortDirection, $filter);
        $this->applyTagToBrowserState($tagMode, $tag, $activeAssetCollection);

        // Second, apply the options from the browser state to the Asset Proxy Repository
        $this->applyAssetTypeFilterFromBrowserState($assetProxyRepository);
        $this->applySortingFromBrowserState($assetProxyRepository);
        $this->applyAssetCollectionFilterFromBrowserState($assetProxyRepository);

        $assetCollections = [];
        $tags = [];
        $assetProxies = [];

        $allCollectionsCount = 0;
        $allCount = 0;
        $searchResultCount = 0;
        $untaggedCount = 0;

        try {
            foreach ($this->assetCollectionRepository->findAll() as $assetCollection) {
                $assetCollections[] = ['object' => $assetCollection, 'count' => $this->assetRepository->countByAssetCollection($assetCollection)];
            }

            foreach ($activeAssetCollection !== null ? $activeAssetCollection->getTags() : $this->tagRepository->findAll() as $tag) {
                $tags[] = ['object' => $tag, 'count' => $this->assetRepository->countByTag($tag, $activeAssetCollection)];
            }

            if ($searchTerm !== null) {
                $assetProxies = $assetProxyRepository->findBySearchTerm($searchTerm);
                $this->view->assign('searchTerm', $searchTerm);
            } elseif ($this->browserState->get('tagMode') === self::TAG_NONE) {
                $assetProxies = $assetProxyRepository->findUntagged();
            } elseif ($this->browserState->get('activeTag') !== null) {
                $assetProxies = $assetProxyRepository->findByTag($this->browserState->get('activeTag'));
            } else {
                $assetProxies = $activeAssetCollection === null ? $assetProxyRepository->findAll() : $assetProxyRepository->findAll();
            }

            $allCollectionsCount = $assetProxyRepository->countAll();
            $allCount = ($activeAssetCollection ? $this->assetRepository->countByAssetCollection($activeAssetCollection) : $allCollectionsCount);
            $searchResultCount = isset($assetProxies) ? $assetProxies->count() : 0;
            $untaggedCount = ($assetProxyRepository instanceof SupportsTaggingInterface ? $assetProxyRepository->countUntagged() : 0);
        } catch (AssetSourceConnectionExceptionInterface $e) {
            $this->view->assign('connectionError', $e);
        }

        $this->view->assignMultiple([
            'tags' => $tags,
            'allCollectionsCount' => $allCollectionsCount,
            'allCount' => $allCount,
            'searchResultCount' => $searchResultCount,
            'untaggedCount' => $untaggedCount,
            'tagMode' => $this->browserState->get('tagMode'),
            'assetProxies' => $assetProxies,
            'assetCollections' => $assetCollections,
            'argumentNamespace' => $this->request->getArgumentNamespace(),
            'maximumFileUploadSize' => $this->getMaximumFileUploadSize(),
            'humanReadableMaximumFileUploadSize' => Files::bytesToSizeString($this->getMaximumFileUploadSize()),
            'activeAssetSource' => $activeAssetSource,
            'activeAssetSourceSupportsSorting' => ($assetProxyRepository instanceof SupportsSortingInterface)
        ]);
    }

    /**
     * New asset form
     *
     * @return void
     */
    public function newAction()
    {
        $maximumFileUploadSize = $this->getMaximumFileUploadSize();
        $this->view->assignMultiple([
            'tags' => $this->tagRepository->findAll(),
            'assetCollections' => $this->assetCollectionRepository->findAll(),
            'maximumFileUploadSize' => $maximumFileUploadSize,
            'humanReadableMaximumFileUploadSize' => Files::bytesToSizeString($maximumFileUploadSize)
        ]);
    }

    /**
     * @param Asset $asset
     * @return void
     */
    public function replaceAssetResourceAction(Asset $asset)
    {
        $maximumFileUploadSize = $this->getMaximumFileUploadSize();
        $this->view->assignMultiple([
            'asset' => $asset,
            'maximumFileUploadSize' => $maximumFileUploadSize,
            'redirectPackageEnabled' => $this->packageManager->isPackageAvailable('Neos.RedirectHandler'),
            'humanReadableMaximumFileUploadSize' => Files::bytesToSizeString($maximumFileUploadSize)
        ]);
    }

    /**
     * Show an asset
     *
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @return void
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function showAction(string $assetSourceIdentifier, string $assetProxyIdentifier)
    {
        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            throw new \RuntimeException('Given asset source is not configured.', 1509702178);
        }

        $assetProxyRepository = $this->assetSources[$assetSourceIdentifier]->getAssetProxyRepository();
        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);

            $this->view->assignMultiple([
                'assetProxy' => $assetProxy,
                'assetCollections' => $this->assetCollectionRepository->findAll()
            ]);
        } catch (AssetNotFoundExceptionInterface $e) {
            $this->throwStatus(404, 'Asset not found');
        } catch (AssetSourceConnectionExceptionInterface $e) {
            $this->view->assign('connectionError', $e);
        }
    }

    /**
     * Edit an asset
     *
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @return void
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function editAction(string $assetSourceIdentifier, string $assetProxyIdentifier)
    {
        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            throw new \RuntimeException('Given asset source is not configured.', 1509632166);
        }

        $assetSource =  $this->assetSources[$assetSourceIdentifier];
        $assetProxyRepository = $assetSource->getAssetProxyRepository();

        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);

            $tags = [];
            if ($assetProxyRepository instanceof SupportsTaggingInterface && $assetProxyRepository instanceof SupportsCollectionsInterface) {
                // TODO: For generic implementation (allowing other asset sources to provide asset collections), the following needs to be refactored:

                if ($assetProxy instanceof NeosAssetProxy) {
                    /** @var Asset $asset */
                    $asset = $assetProxy->getAsset();
                    $assetCollections = $asset->getAssetCollections();
                    $tags = $assetCollections->count() > 0 ? $this->tagRepository->findByAssetCollections($assetCollections->toArray()) : $this->tagRepository->findAll();
                } else {
                    $tags = [];
                }
            }

            $this->view->assignMultiple([
                'tags' => $tags,
                'assetProxy' => $assetProxy,
                'assetCollections' => $this->assetCollectionRepository->findAll(),
                'assetSource' => $assetSource
            ]);
        } catch (AssetNotFoundExceptionInterface $e) {
            $this->throwStatus(404, 'Asset not found');
        } catch (AssetSourceConnectionExceptionInterface $e) {
            $this->view->assign('connectionError', $e);
        }
    }

    /**
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
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
     * @throws StopActionException
     */
    public function updateAction(Asset $asset)
    {
        $this->assetRepository->update($asset);
        $this->addFlashMessage('assetHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($asset->getLabel())]);
        $this->redirect('index');
    }

    /**
     * Initialization for createAction
     *
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
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
     * @throws StopActionException
     */
    public function createAction(Asset $asset)
    {
        if ($this->persistenceManager->isNewObject($asset)) {
            $this->assetRepository->add($asset);
        }
        $this->addFlashMessage('assetHasBeenAdded', '', Message::SEVERITY_OK, [htmlspecialchars($asset->getLabel())]);
        $this->redirect('index', null, null, [], 0, 201);
    }

    /**
     * Initialization for uploadAction
     *
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
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
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
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

        $this->addFlashMessage('assetHasBeenAdded', '', Message::SEVERITY_OK, [htmlspecialchars($asset->getLabel())]);
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
     * @return void
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
     * @return void
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
                $this->addFlashMessage('tagAlreadyExistsAndAddedToCollection', '', Message::SEVERITY_OK, [htmlspecialchars($label)]);
            }
        } else {
            $tag = new Tag($label);
            $this->tagRepository->add($tag);
            if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addTag($tag)) {
                $this->assetCollectionRepository->update($assetCollection);
            }
            $this->addFlashMessage('tagHasBeenCreated', '', Message::SEVERITY_OK, [htmlspecialchars($label)]);
        }
        $this->redirect('index');
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function editTagAction(Tag $tag)
    {
        $this->view->assignMultiple([
            'tag' => $tag,
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ]);
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function updateTagAction(Tag $tag)
    {
        $this->tagRepository->update($tag);
        $this->addFlashMessage('tagHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($tag->getLabel())]);
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
        $this->addFlashMessage('tagHasBeenDeleted', '', Message::SEVERITY_OK, [htmlspecialchars($tag->getLabel())]);
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
        $this->addFlashMessage('collectionHasBeenCreated', '', Message::SEVERITY_OK, [htmlspecialchars($title)]);
        $this->redirect('index');
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function editAssetCollectionAction(AssetCollection $assetCollection)
    {
        $this->view->assignMultiple([
            'assetCollection' => $assetCollection,
            'tags' => $this->tagRepository->findAll()
        ]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function updateAssetCollectionAction(AssetCollection $assetCollection)
    {
        $this->assetCollectionRepository->update($assetCollection);
        $this->addFlashMessage('collectionHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($assetCollection->getTitle())]);
        $this->redirect('index');
    }

    /**
     * Delete an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function deleteAction(Asset $asset)
    {
        $usageReferences = $this->assetService->getUsageReferences($asset);
        if (count($usageReferences) > 0) {
            $this->addFlashMessage('deleteRelatedNodes', '', Message::SEVERITY_WARNING, [], 1412422767);
            $this->redirect('index');
        }

        $this->assetRepository->remove($asset);
        $this->addFlashMessage('assetHasBeenDeleted', '', Message::SEVERITY_OK, [$asset->getLabel()], 1412375050);
        $this->redirect('index');
    }

    /**
     * Update the resource on an asset.
     *
     * @param AssetInterface $asset
     * @param PersistentResource $resource
     * @param array $options
     * @throws InvalidArgumentValueException
     * @return void
     */
    public function updateAssetResourceAction(AssetInterface $asset, PersistentResource $resource, array $options = [])
    {
        $sourceMediaType = MediaTypes::parseMediaType($asset->getMediaType());
        $replacementMediaType = MediaTypes::parseMediaType($resource->getMediaType());

        // Prevent replacement of image, audio and video by a different mimetype because of possible rendering issues.
        if (in_array($sourceMediaType['type'], ['image', 'audio', 'video']) && $sourceMediaType['type'] !== $replacementMediaType['type']) {
            $this->addFlashMessage(
                'resourceCanOnlyBeReplacedBySimilarResource',
                '',
                Message::SEVERITY_WARNING,
                [$sourceMediaType['type'], $resource->getMediaType()],
                1462308179
            );
            $this->redirect('index');
        }

        try {
            $originalFilename = $asset->getLabel();
            $this->assetService->replaceAssetResource($asset, $resource, $options);
        } catch (\Exception $exception) {
            $this->addFlashMessage('couldNotReplaceAsset', '', Message::SEVERITY_OK, [], 1463472606);
            $this->forwardToReferringRequest();
            return;
        }

        $this->addFlashMessage('assetHasBeenReplaced', '', Message::SEVERITY_OK, [htmlspecialchars($originalFilename)]);
        $this->redirect('index');
    }

    /**
     * Get Related Nodes for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function relatedNodesAction(AssetInterface $asset)
    {
        $userWorkspace = $this->userService->getPersonalWorkspace();

        $usageReferences = $this->assetService->getUsageReferences($asset);
        $relatedNodes = [];
        $inaccessibleRelations = [];

        $existingSites = $this->siteRepository->findAll();

        foreach ($usageReferences as $usage) {
            $inaccessibleRelation = [
                'type' => get_class($usage),
                'usage' => $usage
            ];

            if (!$usage instanceof AssetUsageInNodeProperties) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            try {
                $nodeType = $this->nodeTypeManager->getNodeType($usage->getNodeTypeName());
            } catch (NodeTypeNotFoundException $e) {
                $nodeType = null;
            }
            /** @var Workspace $workspace */
            $workspace = $this->workspaceRepository->findByIdentifier($usage->getWorkspaceName());
            $accessible = $this->domainUserService->currentUserCanReadWorkspace($workspace);

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeIdentifier();
            $inaccessibleRelation['workspaceName'] = $usage->getWorkspaceName();
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $accessible;

            if (!$accessible) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $node = $this->getNodeFrom($usage);
            // this should actually never happen.
            if (!$node) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $flowQuery = new FlowQuery([$node]);
            $documentNode = $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
            // this should actually never happen, too.
            if (!$documentNode) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $site = $node->getContext()->getCurrentSite();
            foreach ($existingSites as $existingSite) {
                /** @var Site $existingSite **/
                $siteNodePath = '/sites/' . $existingSite->getNodeName();
                if ($siteNodePath === $node->getPAth() || strpos($node->getPath(), $siteNodePath . '/') === 0) {
                    $site = $existingSite;
                }
            }

            $relatedNodes[$site->getNodeName()]['site'] = $site;
            $relatedNodes[$site->getNodeName()]['nodes'][] = [
                'node' => $node,
                'documentNode' => $documentNode
            ];
        }

        $this->view->assignMultiple([
            'totalUsageCount' => count($usageReferences),
            'nodeUsageClass' => AssetUsageInNodeProperties::class,
            'asset' => $asset,
            'inaccessibleRelations' => $inaccessibleRelations,
            'relatedNodes' => $relatedNodes,
            'contentDimensions' => $this->contentDimensionPresetSource->getAllPresets(),
            'userWorkspace' => $userWorkspace
        ]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function deleteAssetCollectionAction(AssetCollection $assetCollection)
    {
        foreach ($this->siteRepository->findByAssetCollection($assetCollection) as $site) {
            $site->setAssetCollection(null);
            $this->siteRepository->update($site);
        }

        if ($this->browserState->get('activeAssetCollection') === $assetCollection) {
            $this->browserState->set('activeAssetCollection', null);
        }
        $this->assetCollectionRepository->remove($assetCollection);
        $this->addFlashMessage('collectionHasBeenDeleted', '', Message::SEVERITY_OK, [htmlspecialchars($assetCollection->getTitle())]);
        $this->redirect('index');
    }

    /**
     * This custom errorAction adds FlashMessages for validation results to give more information in the
     *
     * @return string
     */
    protected function errorAction()
    {
        foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
            foreach ($errors as $error) {
                $this->flashMessageContainer->addMessage($error);
            }
        }

        return parent::errorAction();
    }

    /**
     * Individual error FlashMessage that hides which action fails in production.
     *
     * @return Message|bool The flash message or false if no flash message should be set
     */
    protected function getErrorFlashMessage()
    {
        if ($this->arguments->getValidationResults()->hasErrors()) {
            return false;
        }
        $errorMessage = 'An error occurred';
        if ($this->objectManager->getContext()->isDevelopment()) {
            $errorMessage .= ' while trying to call %1$s->%2$s()';
        }

        return new Error($errorMessage, null, [get_class($this), $this->actionMethodName]);
    }

    /**
     * Add a translated flashMessage.
     *
     * @param string $messageBody The translation id for the message body.
     * @param string $messageTitle The translation id for the message title.
     * @param string $severity
     * @param array $messageArguments
     * @param integer $messageCode
     * @return void
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = Message::SEVERITY_OK, array $messageArguments = [], $messageCode = null)
    {
        if (is_string($messageBody)) {
            $messageBody = $this->translator->translateById($messageBody, $messageArguments, null, null, 'Main', 'Neos.Media.Browser') ?: $messageBody;
        }

        $messageTitle = $this->translator->translateById($messageTitle, $messageArguments, null, null, 'Main', 'Neos.Media.Browser');
        parent::addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
    }

    /**
     * Returns the lowest configured maximum upload file size
     *
     * @return integer
     * @throws \Neos\Utility\Exception\FilesException
     */
    private function getMaximumFileUploadSize()
    {
        return min(Files::sizeStringToBytes(ini_get('post_max_size')), Files::sizeStringToBytes(ini_get('upload_max_filesize')));
    }

    /**
     * @param AssetUsageInNodeProperties $assetUsage
     * @return NodeInterface
     */
    private function getNodeFrom(AssetUsageInNodeProperties $assetUsage)
    {
        $context = $this->_contextFactory->create(['workspaceName' => $assetUsage->getWorkspaceName(), 'dimensions' => $assetUsage->getDimensionValues(), 'invisibleContentShown' => true, 'removedContentShown' => true]);
        return $context->getNodeByIdentifier($assetUsage->getNodeIdentifier());
    }

    /**
     * @param string $view
     * @param string $sortBy
     * @param string $sortDirection
     * @param string $filter
     */
    private function applyViewOptionsToBrowserState(string $view = null, string $sortBy = null, string $sortDirection = null, string $filter = null): void
    {
        if (!empty($view)) {
            $this->browserState->set('view', $view);
        }
        if (!empty($sortBy)) {
            $this->browserState->set('sortBy', $sortBy);
        }
        if (!empty($sortDirection)) {
            $this->browserState->set('sortDirection', $sortDirection);
        }
        if (!empty($filter)) {
            $this->browserState->set('filter', $filter);
        }

        foreach (['view', 'sortBy', 'sortDirection', 'filter'] as $optionName) {
            $this->view->assign($optionName, $this->browserState->get($optionName));
        }
    }

    /**
     * @param $assetSourceIdentifier
     */
    private function applyActiveAssetSourceToBrowserState($assetSourceIdentifier)
    {
        if ($assetSourceIdentifier !== null && isset($this->assetSources[$assetSourceIdentifier])) {
            $this->browserState->setActiveAssetSourceIdentifier($assetSourceIdentifier);
        }
    }

    /**
     * @param int $tagMode
     * @param Tag $tag
     * @param AssetCollection|null $activeAssetCollection
     */
    private function applyTagToBrowserState(int $tagMode = null, Tag $tag = null, AssetCollection $activeAssetCollection = null): void
    {
        if ($tagMode === self::TAG_GIVEN && $tag !== null) {
            $this->browserState->set('activeTag', $tag);
            $this->view->assign('activeTag', $tag);
        } elseif ($tagMode === self::TAG_NONE || $tagMode === self::TAG_ALL) {
            $this->browserState->set('activeTag', null);
            $this->view->assign('activeTag', null);
        }
        $this->browserState->set('tagMode', $tagMode);

        // Unset active tag if it isn't available in the active asset collection
        if ($this->browserState->get('activeTag') && $activeAssetCollection !== null && !$activeAssetCollection->getTags()->contains($this->browserState->get('activeTag'))) {
            $this->browserState->set('activeTag', null);
            $this->view->assign('activeTag', null);
        }

        if (!$this->browserState->get('activeTag') && $this->browserState->get('tagMode') === self::TAG_GIVEN) {
            $this->browserState->set('tagMode', self::TAG_ALL);
        }
    }

    /**
     * @return AssetSourceInterface
     */
    private function getAssetSourceFromBrowserState(): AssetSourceInterface
    {
        $assetSourceIdentifier = $this->browserState->getActiveAssetSourceIdentifier();
        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            $assetSourceIdentifiers = array_keys($this->assetSources);
            $assetSourceIdentifier = reset($assetSourceIdentifiers);
        }
        return $this->assetSources[$assetSourceIdentifier];
    }

    /**
     * @param int $collectionMode
     * @param AssetCollection $assetCollection
     */
    private function applyAssetCollectionOptionsToBrowserState(int $collectionMode = null, AssetCollection $assetCollection = null): void
    {
        if ($collectionMode === self::COLLECTION_GIVEN && $assetCollection !== null) {
            $this->browserState->set('activeAssetCollection', $assetCollection);
            $this->view->assign('activeAssetCollection', $assetCollection);
        } elseif ($collectionMode === self::COLLECTION_ALL) {
            $this->browserState->set('activeAssetCollection', null);
            $this->view->assign('activeAssetCollection', null);
        }
        $this->browserState->set('collectionMode', $collectionMode);
    }

    /**
     * @return AssetCollection|null
     */
    private function getActiveAssetCollectionFromBrowserState(): ?AssetCollection
    {
        try {
            /** @var AssetCollection $activeAssetCollection */
            $activeAssetCollection = $this->browserState->get('activeAssetCollection');
            if ($activeAssetCollection instanceof DoctrineProxy) {
                // To trigger a possible EntityNotFound have to load the entity
                $activeAssetCollection->__load();
            }
        } catch (EntityNotFoundException $exception) {
            // If a removed asset collection is still in the browser state it can not be fetched
            $this->browserState->set('activeAssetCollection', null);
            $activeAssetCollection = null;
        }
        return $activeAssetCollection;
    }

    /**
     * @param AssetProxyRepositoryInterface $assetProxyRepository
     */
    private function applySortingFromBrowserState(AssetProxyRepositoryInterface $assetProxyRepository): void
    {
        if ($assetProxyRepository instanceof SupportsSortingInterface) {
            switch ($this->browserState->get('sortBy')) {
                case 'Name':
                    $assetProxyRepository->orderBy(['resource.filename' => $this->browserState->get('sortDirection')]);
                break;
                case 'Modified':
                default:
                    $assetProxyRepository->orderBy(['lastModified' => $this->browserState->get('sortDirection')]);
                break;
            }
        }
    }

    /**
     * @param AssetProxyRepositoryInterface $assetProxyRepository
     */
    private function applyAssetTypeFilterFromBrowserState(AssetProxyRepositoryInterface $assetProxyRepository): void
    {
        $assetProxyRepository->filterByType(new AssetTypeFilter($this->browserState->get('filter')));
    }

    /**
     * @param AssetProxyRepositoryInterface $assetProxyRepository
     */
    private function applyAssetCollectionFilterFromBrowserState(AssetProxyRepositoryInterface $assetProxyRepository): void
    {
        if ($assetProxyRepository instanceof SupportsCollectionsInterface) {
            $assetProxyRepository->filterByCollection($this->getActiveAssetCollectionFromBrowserState());
        }
    }
}
