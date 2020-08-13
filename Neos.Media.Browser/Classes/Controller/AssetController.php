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
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Browser\Domain\ImageMapper;
use Neos\Media\Browser\Domain\Session\BrowserState;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetNotFoundExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepositoryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionExceptionInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\AssetSource\Neos\NeosAssetProxy;
use Neos\Media\Domain\Model\AssetSource\SupportsCollectionsInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsSortingInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsTaggingInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\Dto\AssetConstraints;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Model\VariantSupportInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\Exception\FilesException;
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
    use AddFlashMessageTrait;

    protected const TAG_GIVEN = 0;
    protected const TAG_ALL = 1;
    protected const TAG_NONE = 2;

    protected const COLLECTION_GIVEN = 0;
    protected const COLLECTION_ALL = 1;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => JsonView::class
    ];

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
     * @var PackageManager
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
     * @var \Neos\Media\Domain\Service\AssetSourceService
     */
    protected $assetSourceService;

    /**
     * @var AssetSourceInterface[]
     */
    protected $assetSources = [];

    /**
     * @Flow\InjectConfiguration(path="imageProfiles", package="Neos.Media")
     * @var array
     */
    protected $imageProfilesConfiguration;

    /**
     * @var AssetConstraints
     */
    private $assetConstraints;

    /**
     * @return void
     */
    public function initializeObject(): void
    {
        $domain = $this->domainRepository->findOneByActiveRequest();

        // Set active asset collection to the current site's asset collection, if it has one, on the first view if a matching domain is found
        if ($domain !== null && !$this->browserState->get('activeAssetCollection') && $this->browserState->get('automaticAssetCollectionSelection') !== true && $domain->getSite()->getAssetCollection() !== null) {
            $this->browserState->set('activeAssetCollection', $domain->getSite()->getAssetCollection());
            $this->browserState->set('automaticAssetCollectionSelection', true);
        }

        $this->assetSources = $this->assetSourceService->getAssetSources();
    }

    /**
     * @throws NoSuchArgumentException
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();

        if ($this->request->hasArgument('constraints')) {
            $this->assetConstraints = AssetConstraints::fromArray($this->request->getArgument('constraints'));
        } else {
            $this->assetConstraints = AssetConstraints::create();
        }
        $this->assetSources = $this->assetConstraints->applyToAssetSources($this->assetSources);
    }

    /**
     * Set common variables on the view
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view): void
    {
        $view->assignMultiple([
            'view' => $this->browserState->get('view'),
            'sortBy' => $this->browserState->get('sortBy'),
            'sortDirection' => $this->browserState->get('sortDirection'),
            'filter' => (string)$this->assetConstraints->applyToAssetTypeFilter($this->browserState->get('filter')),
            'filterOptions' => $this->assetConstraints->getAllowedAssetTypeFilterOptions(),
            'activeTag' => $this->browserState->get('activeTag'),
            'activeAssetCollection' => $this->browserState->get('activeAssetCollection'),
            'assetSources' => $this->assetSources,
            'variantsTabFeatureEnabled' => $this->settings['features']['variantsTab']['enable'],
            'constraints' => $this->assetConstraints,
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
     * @throws FilesException
     */
    public function indexAction($view = null, $sortBy = null, $sortDirection = null, $filter = null, $tagMode = self::TAG_GIVEN, Tag $tag = null, $searchTerm = null, $collectionMode = self::COLLECTION_GIVEN, AssetCollection $assetCollection = null, $assetSourceIdentifier = null): void
    {
        $assetSourceIdentifier = $this->assetConstraints->applyToAssetSourceIdentifiers($assetSourceIdentifier);

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
            foreach ($this->assetCollectionRepository->findAll()->toArray() as $retrievedAssetCollection) {
                assert($retrievedAssetCollection instanceof AssetCollection);
                $assetCollections[] = ['object' => $retrievedAssetCollection, 'count' => $this->assetRepository->countByAssetCollection($retrievedAssetCollection)];
            }

            foreach ($activeAssetCollection !== null ? $activeAssetCollection->getTags() : $this->tagRepository->findAll() as $retrievedTag) {
                assert($retrievedTag instanceof Tag);
                $tags[] = ['object' => $retrievedTag, 'count' => $this->assetRepository->countByTag($retrievedTag, $activeAssetCollection)];
            }

            if ($searchTerm !== null) {
                $assetProxies = $assetProxyRepository->findBySearchTerm($searchTerm);
                $this->view->assign('searchTerm', $searchTerm);
            } elseif ($this->browserState->get('tagMode') === self::TAG_NONE) {
                $assetProxies = $assetProxyRepository->findUntagged();
            } elseif ($this->browserState->get('activeTag') !== null) {
                $assetProxies = $assetProxyRepository->findByTag($this->browserState->get('activeTag'));
            } else {
                $assetProxies = $assetProxyRepository->findAll();
            }

            $allCollectionsCount = $this->assetRepository->countAll();
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
            'activeAssetSourceSupportsSorting' => $assetProxyRepository instanceof SupportsSortingInterface
        ]);
    }

    /**
     * New asset form
     *
     * @return void
     */
    public function newAction(): void
    {
        try {
            $maximumFileUploadSize = $this->getMaximumFileUploadSize();
        } catch (FilesException $e) {
            $maximumFileUploadSize = null;
        }

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
    public function replaceAssetResourceAction(Asset $asset): void
    {
        try {
            $maximumFileUploadSize = $this->getMaximumFileUploadSize();
        } catch (FilesException $e) {
            $maximumFileUploadSize = null;
        }

        $this->view->assignMultiple([
            'asset' => $asset,
            'maximumFileUploadSize' => $maximumFileUploadSize,
            'createAssetRedirectsOptionEnabled' => $this->packageManager->isPackageAvailable('Neos.RedirectHandler') && $this->settings['features']['createAssetRedirectsOption']['enable'],
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
    public function showAction(string $assetSourceIdentifier, string $assetProxyIdentifier): void
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
        } catch (AssetNotFoundExceptionInterface | AssetSourceConnectionExceptionInterface $e) {
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
    public function editAction(string $assetSourceIdentifier, string $assetProxyIdentifier): void
    {
        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            throw new \RuntimeException('Given asset source is not configured.', 1509632166);
        }

        $assetSource = $this->assetSources[$assetSourceIdentifier];
        $assetProxyRepository = $assetSource->getAssetProxyRepository();

        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);

            $tags = [];
            $contentPreview = 'ContentDefault';
            if ($assetProxyRepository instanceof SupportsTaggingInterface && $assetProxyRepository instanceof SupportsCollectionsInterface) {
                // TODO: For generic implementation (allowing other asset sources to provide asset collections), the following needs to be refactored:

                if ($assetProxy instanceof NeosAssetProxy) {
                    /** @var Asset $asset */
                    $asset = $assetProxy->getAsset();
                    $assetCollections = $asset->getAssetCollections();
                    $tags = $assetCollections->count() > 0 ? $this->tagRepository->findByAssetCollections($assetCollections->toArray()) : $this->tagRepository->findAll();

                    switch ($asset->getFileExtension()) {
                        case 'pdf':
                            $contentPreview = 'ContentPdf';
                            break;
                    }
                }
            }

            $this->view->assignMultiple([
                'tags' => $tags,
                'assetProxy' => $assetProxy,
                'assetCollections' => $this->assetCollectionRepository->findAll(),
                'contentPreview' => $contentPreview,
                'assetSource' => $assetSource,
                'canShowVariants' => ($assetProxy instanceof NeosAssetProxy) && ($assetProxy->getAsset() instanceof VariantSupportInterface)
            ]);
        } catch (AssetNotFoundExceptionInterface | AssetSourceConnectionExceptionInterface $e) {
            $this->view->assign('connectionError', $e);
        }
    }

    /**
     * Display variants of an asset
     *
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @param string $overviewAction
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function variantsAction(string $assetSourceIdentifier, string $assetProxyIdentifier, string $overviewAction): void
    {
        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            throw new \RuntimeException('Given asset source is not configured.', 1509632166);
        }

        $assetSource = $this->assetSources[$assetSourceIdentifier];
        $assetProxyRepository = $assetSource->getAssetProxyRepository();

        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);
            $asset = $this->persistenceManager->getObjectByIdentifier($assetProxy->getLocalAssetIdentifier(), Asset::class);

            /** @var VariantSupportInterface $originalAsset */
            $originalAsset = ($asset instanceof AssetVariantInterface ? $asset->getOriginalAsset() : $asset);

            $variantInformation = array_map(static function (AssetVariantInterface $imageVariant) {
                return (new ImageMapper($imageVariant))->getMappingResult();
            }, $originalAsset->getVariants());

            $this->view->assignMultiple([
                'assetProxy' => $assetProxy,
                'asset' => $originalAsset,
                'assetSource' => $assetSource,
                'imageProfiles' => $this->imageProfilesConfiguration,
                'overviewAction' => $overviewAction,
                'originalInformation' => (new ImageMapper($asset))->getMappingResult(),
                'variantsInformation' => $variantInformation,
                'isSubRequest' => !$this->request->isMainRequest()
            ]);
        } catch (AssetNotFoundExceptionInterface | AssetSourceConnectionExceptionInterface $e) {
            $this->view->assign('connectionError', $e);
        }
    }

    /**
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeUpdateAction(): void
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
     * @throws IllegalObjectTypeException
     */
    public function updateAction(Asset $asset): void
    {
        $this->assetRepository->update($asset);
        $this->addFlashMessage('assetHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($asset->getLabel())]);
        $this->redirectToIndex();
    }

    /**
     * Initialization for createAction
     *
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeCreateAction(): void
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
     * @throws IllegalObjectTypeException
     */
    public function createAction(Asset $asset): void
    {
        if ($this->persistenceManager->isNewObject($asset)) {
            $this->assetRepository->add($asset);
        }
        $this->addFlashMessage('assetHasBeenAdded', '', Message::SEVERITY_OK, [htmlspecialchars($asset->getLabel())]);
        $this->redirectToIndex();
    }

    /**
     * Initialization for uploadAction
     *
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeUploadAction(): void
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
     * @throws IllegalObjectTypeException
     */
    public function uploadAction(Asset $asset): string
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
        $this->response->setStatusCode(201);
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
     * @throws IllegalObjectTypeException
     */
    public function tagAssetAction(Asset $asset, Tag $tag): void
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
     * @throws IllegalObjectTypeException
     */
    public function addAssetToCollectionAction(Asset $asset, AssetCollection $assetCollection): void
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
     * @throws IllegalObjectTypeException
     * @throws StopActionException
     * @throws AssetServiceException
     */
    public function deleteAction(Asset $asset): void
    {
        $usageReferences = $this->assetService->getUsageReferences($asset);
        if (count($usageReferences) > 0) {
            $this->addFlashMessage('deleteRelatedNodes', '', Message::SEVERITY_WARNING, [], 1412422767);
            $this->redirectToIndex();
        }

        $this->assetRepository->remove($asset);
        $this->addFlashMessage('assetHasBeenDeleted', '', Message::SEVERITY_OK, [$asset->getLabel()], 1412375050);
        $this->redirectToIndex();
    }

    /**
     * Update the resource on an asset.
     *
     * @param AssetInterface $asset
     * @param PersistentResource $resource
     * @param array $options
     * @return void
     * @throws StopActionException
     * @throws ForwardException
     */
    public function updateAssetResourceAction(AssetInterface $asset, PersistentResource $resource, array $options = []): void
    {
        $sourceMediaType = MediaTypes::parseMediaType($asset->getMediaType());
        $replacementMediaType = MediaTypes::parseMediaType($resource->getMediaType());

        // Prevent replacement of image, audio and video by a different mimetype because of possible rendering issues.
        if ($sourceMediaType['type'] !== $replacementMediaType['type'] && in_array($sourceMediaType['type'], ['image', 'audio', 'video'])) {
            $this->addFlashMessage(
                'resourceCanOnlyBeReplacedBySimilarResource',
                '',
                Message::SEVERITY_WARNING,
                [$sourceMediaType['type'], $resource->getMediaType()],
                1462308179
            );
            $this->redirectToIndex();
        }

        try {
            $this->assetService->replaceAssetResource($asset, $resource, $options);
        } catch (\Exception $exception) {
            $this->addFlashMessage('couldNotReplaceAsset', '', Message::SEVERITY_OK, [], 1463472606);
            $this->forwardToReferringRequest();
            return;
        }

        $assetLabel = (method_exists($asset, 'getLabel') ? $asset->getLabel() : $resource->getFilename());
        $this->addFlashMessage('assetHasBeenReplaced', '', Message::SEVERITY_OK, [htmlspecialchars($assetLabel)]);
        $this->redirectToIndex();
    }

    /**
     * Get Related Nodes for an asset (proxy action)
     *
     * @param AssetInterface $asset
     * @return void
     * @throws ForwardException
     */
    public function relatedNodesAction(AssetInterface $asset): void
    {
        $this->forwardWithConstraints('relatedNodes', 'Usage', ['asset' => $asset]);
    }

    /**
     * @param string $label
     * @return void
     * @Flow\Validate(argumentName="label", type="NotEmpty")
     * @Flow\Validate(argumentName="label", type="Label")
     * @throws ForwardException
     */
    public function createTagAction(string $label): void
    {
        $this->forwardWithConstraints('create', 'Tag', ['label' => $label]);
    }

    /**
     * @param Tag $tag
     * @return void
     * @throws ForwardException
     */
    public function editTagAction(Tag $tag): void
    {
        $this->forwardWithConstraints('edit', 'Tag', ['tag' => $tag]);
    }

    /**
     * @param Tag $tag
     * @return void
     * @throws ForwardException
     */
    public function updateTagAction(Tag $tag): void
    {
        $this->forwardWithConstraints('update', 'Tag', ['tag' => $tag]);
    }

    /**
     * @param Tag $tag
     * @return void
     * @throws ForwardException
     */
    public function deleteTagAction(Tag $tag): void
    {
        $this->forwardWithConstraints('delete', 'Tag', ['tag' => $tag]);
    }

    /**
     * @param string $title
     * @return void
     * @Flow\Validate(argumentName="title", type="NotEmpty")
     * @Flow\Validate(argumentName="title", type="Label")
     * @throws ForwardException
     */
    public function createAssetCollectionAction($title): void
    {
        $this->forwardWithConstraints('create', 'AssetCollection', ['title' => $title]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     * @throws ForwardException
     */
    public function editAssetCollectionAction(AssetCollection $assetCollection): void
    {
        $this->forwardWithConstraints('edit', 'AssetCollection', ['assetCollection' => $assetCollection]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     * @throws ForwardException
     */
    public function updateAssetCollectionAction(AssetCollection $assetCollection): void
    {
        $this->forwardWithConstraints('update', 'AssetCollection', ['assetCollection' => $assetCollection]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     * @throws ForwardException
     */
    public function deleteAssetCollectionAction(AssetCollection $assetCollection): void
    {
        $this->forwardWithConstraints('delete', 'AssetCollection', ['assetCollection' => $assetCollection]);
    }

    /**
     * This custom errorAction adds FlashMessages for validation results to give more information in the
     *
     * @return string
     */
    protected function errorAction(): string
    {
        foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
            foreach ($errors as $error) {
                $this->controllerContext->getFlashMessageContainer()->addMessage($error);
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
     * Returns the lowest configured maximum upload file size
     *
     * @return int
     * @throws FilesException
     */
    private function getMaximumFileUploadSize(): int
    {
        return min(Files::sizeStringToBytes(ini_get('post_max_size')), Files::sizeStringToBytes(ini_get('upload_max_filesize')));
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

        foreach (['view', 'sortBy', 'sortDirection'] as $optionName) {
            $this->view->assign($optionName, $this->browserState->get($optionName));
        }
        $this->view->assign('filter', (string)$this->assetConstraints->applyToAssetTypeFilter($this->browserState->get('filter')));
    }

    /**
     * @param $assetSourceIdentifier
     */
    private function applyActiveAssetSourceToBrowserState($assetSourceIdentifier): void
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
        if ($activeAssetCollection !== null && $this->browserState->get('activeTag') && !$activeAssetCollection->getTags()->contains($this->browserState->get('activeTag'))) {
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
        $assetProxyRepository->filterByType($this->assetConstraints->applyToAssetTypeFilter($this->browserState->get('filter')));
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

    /**
     * Custom redirect method that adds "constraints" arguments from the current request
     *
     * @param array $arguments
     * @throws StopActionException | NoSuchArgumentException
     */
    private function redirectToIndex(array $arguments = []): void
    {
        if (!isset($arguments['constraints']) && $this->request->hasArgument('constraints')) {
            $arguments['constraints'] = $this->request->getArgument('constraints');
        }
        $this->redirect('index', null, null, $arguments);
    }

    /**
     * Custom forward method that adds "constraints" arguments from the current request
     *
     * @param string $actionName
     * @param string $controllerName
     * @param array $arguments
     * @throws ForwardException | NoSuchArgumentException
     */
    private function forwardWithConstraints(string $actionName, string $controllerName, array $arguments = []): void
    {
        if (!isset($arguments['constraints']) && $this->request->hasArgument('constraints')) {
            $arguments['constraints'] = $this->request->getArgument('constraints');
        }
        $this->forward($actionName, $controllerName, null, $arguments);
    }
}
