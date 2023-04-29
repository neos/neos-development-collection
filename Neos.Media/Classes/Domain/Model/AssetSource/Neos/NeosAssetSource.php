<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\AssetSource\Neos;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepositoryInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\Exception\ThumbnailServiceException;
use Psr\Http\Message\UriInterface;

/**
 * Asset source for Neos native assets
 */
final class NeosAssetSource implements AssetSourceInterface
{
    /**
     * @var string
     */
    private $assetSourceIdentifier;

    /**
     * @var NeosAssetProxyRepository
     */
    private $assetProxyRepository;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\InjectConfiguration(path="asyncThumbnails")
     * @var bool
     */
    protected $asyncThumbnails;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var string
     */
    protected $iconPath;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     */
    public function __construct(string $assetSourceIdentifier, array $assetSourceOptions)
    {
        if (preg_match('/^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid asset source identifier "%s". The identifier must match /^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier), 1513329665);
        }
        $this->assetSourceIdentifier = $assetSourceIdentifier;

        foreach ($assetSourceOptions as $optionName => $optionValue) {
            switch ($optionName) {
                case 'asyncThumbnails':
                    // If the option value is empty, preserve the default value injected from the Neos:Media:asyncThumbnails setting:
                    if (!empty($optionValue)) {
                        if (!is_bool($optionValue)) {
                            throw new \InvalidArgumentException(sprintf('Asset source option "%s" specified for Neos asset source "%s" must be either true or false. Please check your settings.', $optionName, $assetSourceIdentifier), 1522927471);
                        }
                        $this->asyncThumbnails = $optionValue;
                    }
                    break;
                case 'icon':
                    $this->iconPath = $optionValue;
                    break;
                case 'description':
                    $this->description = $optionValue;
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown asset source option "%s" specified for Neos asset source "%s". Please check your settings.', $optionName, $assetSourceIdentifier), 1513327774);
            }
        }
    }

    /**
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     * @return AssetSourceInterface
     */
    public static function createFromConfiguration(string $assetSourceIdentifier, array $assetSourceOptions): AssetSourceInterface
    {
        return new static($assetSourceIdentifier, $assetSourceOptions);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->assetSourceIdentifier;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Neos';
    }

    /**
     * Returns the resource path to Assetsources icon
     *
     * @return string
     */
    public function getIconUri(): string
    {
        return $this->resourceManager->getPublicPackageResourceUriByPath($this->iconPath);
    }

    /**
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return AssetProxyRepositoryInterface
     */
    public function getAssetProxyRepository(): AssetProxyRepositoryInterface
    {
        if ($this->assetProxyRepository === null) {
            $this->assetProxyRepository = new NeosAssetProxyRepository($this);
        }

        return $this->assetProxyRepository;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Internal method used by NeosAssetProxy
     *
     * @param AssetInterface $asset
     * @return Uri|null
     * @throws AssetServiceException
     * @throws ThumbnailServiceException
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function getThumbnailUriForAsset(AssetInterface $asset): ?UriInterface
    {
        $actionRequest = ($this->asyncThumbnails ? $this->createActionRequest() : null);
        $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset('Neos.Media.Browser:Thumbnail', ($actionRequest !== null));
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $actionRequest);
        return isset($thumbnailData['src']) ? new Uri($thumbnailData['src']) : null;
    }

    /**
     * Internal method used by NeosAssetProxy
     *
     * @param AssetInterface $asset
     * @return Uri|null
     * @throws AssetServiceException
     * @throws ThumbnailServiceException
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function getPreviewUriForAsset(AssetInterface $asset): ?UriInterface
    {
        $actionRequest = ($this->asyncThumbnails ? $this->createActionRequest() : null);
        $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset('Neos.Media.Browser:Preview', ($actionRequest !== null));
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $actionRequest);
        return isset($thumbnailData['src']) ? new Uri($thumbnailData['src']) : null;
    }

    /**
     * @return ActionRequest|null
     */
    private function createActionRequest(): ?ActionRequest
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof HttpRequestHandlerInterface) {
            return ActionRequest::fromHttpRequest($requestHandler->getHttpRequest());
        }
        return null;
    }
}
