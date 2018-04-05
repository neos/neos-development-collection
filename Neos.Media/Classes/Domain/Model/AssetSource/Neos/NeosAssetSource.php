<?php
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
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxyRepository;
use Neos\Media\Domain\Model\AssetSource\AssetSource;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\Exception\ThumbnailServiceException;
use Psr\Http\Message\UriInterface;

/**
 * Asset source for Neos native assets
 */
final class NeosAssetSource implements AssetSource
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
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     */
    public function __construct(string $assetSourceIdentifier, array $assetSourceOptions)
    {
        if (preg_match('/^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid asset source identifier "%s". The identifier must match /^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier), 1513329665386);
        }
        $this->assetSourceIdentifier = $assetSourceIdentifier;

        foreach ($assetSourceOptions as $optionName => $optionValue) {
            switch ($optionName) {
                case 'asyncThumbnails':
                    if (!is_bool($optionValue)) {
                        throw new \InvalidArgumentException(sprintf('Asset source option "%s" specified for Neos asset source "%s" must be either true or false. Please check your settings.', $optionName, $assetSourceIdentifier), 1522927471208);
                    }
                    $this->asyncThumbnails = (bool)$optionValue;
                break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown asset source option "%s" specified for Neos asset source "%s". Please check your settings.', $optionName, $assetSourceIdentifier), 1513327774584);
            }
        }
    }

    /**
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     * @return AssetSource
     */
    public static function createFromConfiguration(string $assetSourceIdentifier, array $assetSourceOptions): AssetSource
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
     * @return AssetProxyRepository
     */
    public function getAssetProxyRepository(): AssetProxyRepository
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
     * @throws ThumbnailServiceException
     * @throws AssetServiceException
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
     * @throws ThumbnailServiceException
     * @throws AssetServiceException
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
            return new ActionRequest($requestHandler->getHttpRequest());
        }
        return null;
    }
}
