<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Backend;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\ImageRepository;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Exception\ThumbnailServiceException;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Media\TypeConverter\ImageInterfaceArrayPresenter;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Service\PluginService;
use Neos\Neos\TypeConverter\EntityToIdentityConverter;

/**
 * The Neos ContentModule controller; providing backend functionality for the Content Module.
 *
 * @Flow\Scope("singleton")
 */
class ContentController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $imageRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * The pluginService
     *
     * @var PluginService
     * @Flow\Inject
     */
    protected $pluginService;

    /**
     * @Flow\Inject
     * @var ImageInterfaceArrayPresenter
     */
    protected $imageInterfaceArrayPresenter;

    /**
     * @Flow\Inject
     * @var EntityToIdentityConverter
     */
    protected $entityToIdentityConverter;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Initialize property mapping as the upload usually comes from the Inspector JavaScript
     * @throws NoSuchArgumentException
     */
    public function initializeUploadAssetAction(): void
    {
        $propertyMappingConfiguration = $this->arguments->getArgument('asset')
            ->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();
        $propertyMappingConfiguration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
            true
        );
        $propertyMappingConfiguration->setTypeConverterOption(
            AssetInterfaceConverter::class,
            AssetInterfaceConverter::CONFIGURATION_ONE_PER_RESOURCE,
            true
        );
        $propertyMappingConfiguration->allowCreationForSubProperty('resource');
    }

    /**
     * Upload a new image, and return its metadata.
     *
     * Depending on the $metadata argument it will return asset metadata for the AssetEditor
     * or image metadata for the ImageEditor
     *
     * Note: This action triggers the AssetUploaded signal that can be used to adjust the asset based on the
     * (site) node it was attached to.
     *
     * @param Asset $asset
     * @param string $metadata Type of metadata to return ("Asset" or "Image")
     * @param string $node The node the new asset should be assigned to
     * @param string $propertyName The node property name the new asset should be assigned to
     * @return string
     * @throws IllegalObjectTypeException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function uploadAssetAction(Asset $asset, string $metadata, string $node, string $propertyName)
    {
        $nodeAddressString = $node;
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($nodeAddressString);

        $node = $contentRepository->getContentGraph()
            ->getSubgraphByIdentifier(
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            )
            ->findNodeByNodeAggregateIdentifier($nodeAddress->nodeAggregateIdentifier);


        $this->response->setContentType('application/json');
        if ($metadata !== 'Asset' && $metadata !== 'Image') {
            $this->response->setStatusCode(400);
            $result = ['error' => 'Invalid "metadata" type: ' . $metadata];
        } else {
            if ($asset instanceof ImageInterface && $metadata === 'Image') {
                $result = $this->getImageInterfacePreviewData($asset);
            } else {
                $result = $this->getAssetProperties($asset);
            }
            if ($this->persistenceManager->isNewObject($asset)) {
                $this->assetRepository->add($asset);
            }
            $this->emitAssetUploaded($asset, $node, $propertyName);
        }
        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    /**
     * Configure property mapping for adding a new image variant.
     *
     * @return void
     * @throws NoSuchArgumentException
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
     */
    public function initializeCreateImageVariantAction()
    {
        $this->arguments->getArgument('asset')->getPropertyMappingConfiguration()
            ->allowOverrideTargetType()
            ->allowAllProperties()
            ->setTypeConverterOption(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true
            );
    }

    /**
     * Generate a new image variant from given data.
     *
     * @param ImageVariant $asset
     * @return string
     * @throws IllegalObjectTypeException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function createImageVariantAction(ImageVariant $asset)
    {
        if ($this->persistenceManager->isNewObject($asset)) {
            $this->assetRepository->add($asset);
        }

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        // This will not be sent as "application/json" as we need the JSON string and not the single variables.
        return json_encode($this->entityToIdentityConverter->convertFrom(
            $asset,
            'array',
            [],
            $propertyMappingConfiguration
        ), JSON_THROW_ON_ERROR);
    }

    /**
     * Fetch the metadata for a given image
     *
     * @param ImageInterface $image
     *
     * @return string JSON encoded response
     * @throws ThumbnailServiceException
     */
    public function imageWithMetadataAction(ImageInterface $image)
    {
        $this->response->setContentType('application/json');
        $imageProperties = $this->getImageInterfacePreviewData($image);

        return json_encode($imageProperties, JSON_THROW_ON_ERROR);
    }

    /**
     * Returns important meta data for the given object implementing ImageInterface.
     *
     * Will return an array with the following keys:
     *
     *   "originalImageResourceUri": Uri for the original resource
     *   "previewImageResourceUri": Uri for a preview image with reduced size
     *   "originalDimensions": Dimensions for the original image (width, height, aspectRatio)
     *   "previewDimensions": Dimensions for the preview image (width, height)
     *   "object": object properties like the __identity and __type of the object
     *
     * @param ImageInterface $image The image to retrieve meta data for
     * @return array<string,mixed>
     * @throws ThumbnailServiceException
     */
    protected function getImageInterfacePreviewData(ImageInterface $image)
    {
        // TODO: Now that we try to support all ImageInterface implementations we should use a strategy here
        // to get the image properties for custom implementations
        if ($image instanceof ImageVariant) {
            $imageProperties = $this->getImageVariantPreviewData($image);
        } elseif ($image instanceof Image) {
            $imageProperties = $this->getImagePreviewData($image);
        }

        $imageProperties['object'] = $this->imageInterfaceArrayPresenter->convertFrom($image, 'string');
        return $imageProperties;
    }

    /**
     * @param Image $image
     * @return array<string,mixed>
     * @throws ThumbnailServiceException
     */
    protected function getImagePreviewData(Image $image): array
    {
        $imageProperties = [
            'originalImageResourceUri' => $this->resourceManager->getPublicPersistentResourceUri($image->getResource()),
            'originalDimensions' => [
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'aspectRatio' => $image->getAspectRatio()
            ],
            'mediaType' => $image->getResource()->getMediaType()
        ];
        $thumbnail = $this->thumbnailService->getThumbnail(
            $image,
            $this->thumbnailService->getThumbnailConfigurationForPreset('Neos.Media.Browser:Preview')
        );
        if ($thumbnail !== null) {
            $imageProperties['previewImageResourceUri'] = $this->thumbnailService->getUriForThumbnail($thumbnail);
            $imageProperties['previewDimensions'] = [
                'width' => $thumbnail->getWidth(),
                'height' => $thumbnail->getHeight()
            ];
        }
        return $imageProperties;
    }

    /**
     * @param ImageVariant $imageVariant
     * @return array<string,mixed>
     * @throws ThumbnailServiceException
     */
    protected function getImageVariantPreviewData(ImageVariant $imageVariant)
    {
        $image = $imageVariant->getOriginalAsset();
        $imageProperties = $this->getImagePreviewData($image);
        return $imageProperties;
    }

    /**
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
     */
    protected function initializeAssetsWithMetadataAction()
    {
        $propertyMappingConfiguration = $this->arguments->getArgument('assets')
            ->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();
        $propertyMappingConfiguration->setTypeConverterOption(
            AssetInterfaceConverter::class,
            AssetInterfaceConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED,
            true
        );
        $propertyMappingConfiguration->forProperty('*')->setTypeConverterOption(
            AssetInterfaceConverter::class,
            AssetInterfaceConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED,
            true
        );
    }

    /**
     * Fetch the metadata for multiple assets
     *
     * @param array<\Neos\Media\Domain\Model\AssetInterface> $assets
     * @return string JSON encoded response
     * @throws ThumbnailServiceException
     */
    public function assetsWithMetadataAction(array $assets)
    {
        $this->response->setContentType('application/json');

        $result = [];
        foreach ($assets as $asset) {
            $result[] = $this->getAssetProperties($asset);
        }
        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string,mixed>
     * @throws ThumbnailServiceException
     */
    protected function getAssetProperties(AssetInterface $asset)
    {
        $assetProperties = [
            'assetUuid' => $this->persistenceManager->getIdentifierByObject($asset),
            'filename' => $asset->getResource()->getFilename()
        ];
        $thumbnail = $this->thumbnailService->getThumbnail(
            $asset,
            $this->thumbnailService->getThumbnailConfigurationForPreset('Neos.Media.Browser:Thumbnail')
        );
        if ($thumbnail !== null) {
            $assetProperties['previewImageResourceUri'] = $this->thumbnailService->getUriForThumbnail($thumbnail);
            $assetProperties['previewSize'] = ['w' => $thumbnail->getWidth(), 'h' => $thumbnail->getHeight()];
        }

        return $assetProperties;
    }

    /**
     * Fetch the configured views for the given master plugin
     *
     * @param string $identifier Specifies the node to look up
     * @param string $workspaceName Name of the workspace to use for querying the node
     * @param array<string,string> $dimensions Optional list of dimensions and their values which should be used
     *                          for querying the specified node
     * @return string
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function pluginViewsAction($identifier = null, $workspaceName = 'live', array $dimensions = [])
    {
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);

        $this->response->setContentType('application/json');

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspaceName));
        if (is_null($workspace)) {
            throw new \InvalidArgumentException('Could not resolve workspace "' . $workspaceName . '"', 1651848878);
        }
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            new ContentSubgraphIdentity(
                $contentRepositoryIdentifier,
                $workspace->getCurrentContentStreamIdentifier(),
                DimensionSpacePoint::fromArray($dimensions),
                VisibilityConstraints::withoutRestrictions()
            )
        );
        $node = $identifier
            ? $nodeAccessor->findByIdentifier(NodeAggregateIdentifier::fromString($identifier))
            : null;

        $views = [];
        if ($node instanceof Node) {
            $pluginViewDefinitions = $this->pluginService->getPluginViewDefinitionsByPluginNodeType(
                $node->nodeType
            );
            foreach ($pluginViewDefinitions as $pluginViewDefinition) {
                $label = $pluginViewDefinition->getLabel();

                $views[$pluginViewDefinition->getName()] = ['label' => $label];

                $pluginViewNode = $this->pluginService->getPluginViewNodeByMasterPlugin(
                    $node,
                    $pluginViewDefinition->getName()
                );
                if ($pluginViewNode === null) {
                    continue;
                }
                $documentNode = $this->findClosestDocumentNode($pluginViewNode);
                if ($documentNode === null) {
                    continue;
                }
                $contentRepository = $this->contentRepositoryRegistry->get(
                    $documentNode->subgraphIdentity->contentRepositoryIdentifier
                );
                $documentAddress = NodeAddressFactory::create($contentRepository)->createFromNode($documentNode);
                $uri = $this->uriBuilder
                    ->reset()
                    ->uriFor('show', ['node' => $documentAddress->serializeForUri()], 'Frontend\Node', 'Neos.Neos');
                $views[$pluginViewDefinition->getName()] = [
                    'label' => $label,
                    'pageNode' => [
                        'title' => $documentNode->getLabel(),
                        'uri' => $uri
                    ]
                ];
            }
        }
        return json_encode((object)$views, JSON_THROW_ON_ERROR);
    }

    /**
     * Fetch all master plugins that are available in the current
     * workspace.
     *
     * @param string $workspaceName Name of the workspace to use for querying the node
     * @param array<string,string> $dimensions Optional list of dimensions and their values
     *                          which should be used for querying the specified node
     * @return string JSON encoded array of node path => label
     * @throws \Neos\Eel\Exception
     */
    public function masterPluginsAction(string $workspaceName = 'live', array $dimensions = [])
    {
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;

        $this->response->setContentType('application/json');

        $pluginNodes = $this->pluginService->getPluginNodesWithViewDefinitions(
            WorkspaceName::fromString($workspaceName),
            DimensionSpacePoint::fromArray($dimensions),
            $contentRepositoryIdentifier
        );

        $masterPlugins = [];
        foreach ($pluginNodes as $pluginNode) {
            $documentNode = $this->findClosestDocumentNode($pluginNode);
            if ($documentNode === null) {
                continue;
            }
            $translationHelper = new TranslationHelper();
            $masterPlugins[(string)$pluginNode->nodeAggregateIdentifier] = $translationHelper->translate(
                'masterPlugins.nodeTypeOnPageLabel',
                null,
                [
                    'nodeTypeName' => $translationHelper->translate($pluginNode->nodeType->getLabel()),
                    'pageLabel' => $documentNode->getLabel()
                ],
                'Main',
                'Neos.Neos'
            );
        }

        return json_encode((object)$masterPlugins, JSON_THROW_ON_ERROR);
    }

    final protected function findClosestDocumentNode(Node $node): ?Node
    {
        while ($node instanceof Node) {
            if ($node->nodeType->isOfType('Neos.Neos:Document')) {
                return $node;
            }
            $node = $this->findParentNode($node);
        }

        return null;
    }

    protected function findParentNode(Node $node): ?Node
    {
        return $this->nodeAccessorManager->accessorFor(
            $node->subgraphIdentity
        )->findParentNode($node);
    }

    /**
     * Signals that a new asset has been uploaded through the Neos Backend
     *
     * @param Asset $asset The uploaded asset
     * @param Node|null $node The node the asset belongs to
     * @param string $propertyName The node property name the asset is assigned to
     * @return void
     * @Flow\Signal
     */
    protected function emitAssetUploaded(Asset $asset, ?Node $node, string $propertyName)
    {
    }
}
