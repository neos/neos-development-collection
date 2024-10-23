<?php

declare(strict_types=1);

namespace Neos\Media\Domain\Service\Imagor;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Adjustment\AbstractImageAdjustment;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;

/**
 * TODO
 *
 * @Flow\Scope("singleton")
 */
class ImagorService
{

    /**
     * @Flow\InjectConfiguration("imagor.sourceBaseUrl")
     * @var string
     */
    protected string $imagorSourceBaseUrl;

    /**
     * @Flow\InjectConfiguration("imagor.proxyBaseUrl")
     * @var string
     */
    protected string $imagorProxyBaseUrl;

    /**
     * @Flow\InjectConfiguration("imagor.secret")
     * @var string | null
     */
    protected ?string $imagorSecret;

    /**
     * @Flow\InjectConfiguration("imagor.signerType")
     * @var string | null
     */
    protected ?string $imagorSignerType;

    /**
     * @Flow\InjectConfiguration("imagor.signerTruncate")
     * @var int | null
     */
    protected ?int $imagorSignerTruncate;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    public function getThumbnailUriAndSize(AssetInterface $asset, ThumbnailConfiguration $configuration): array
    {
        // TODO: URL ermitteln -> später auch S3, sonst local file

        $originalImage = $asset;
        if ($originalImage instanceof Thumbnail) {
            $originalImage = $asset->getOriginalAsset();
        }
        // might happen that thumbnail -> imageVariant -> Image, that's why we do not do elseif but if (after thumbnail)
        if ($originalImage instanceof ImageVariant) {
            $originalImage = $asset->getOriginalAsset();
        }

        $imageUrl = $this->resourceManager->getPublicPersistentResourceUri($originalImage->getResource());
        if ($imageUrl === '' || $imageUrl === false) {
            return [];
            # return ImagorResult::empty();
        }

        $url = $this->buildImageUrl($asset, $configuration, $originalImage, $imageUrl);

        return [
            'src' => $url,
        ];
    }

    private function buildImageUrl(AssetInterface $image, ThumbnailConfiguration $configuration, ImageInterface $originalImage, string $sourceUrl): string
    {
        $imagorBuilder = $this->asImagorPathBuilder($image, $configuration, $originalImage);
        $result = $imagorBuilder->build($sourceUrl);
        return $this->imagorProxyBaseUrl . "/" . $result;
    }

    private function asImagorPathBuilder(AssetInterface $image, ThumbnailConfiguration $configuration, ImageInterface $originalImage): ImagorPathBuilder
    {
        $result = (new ImagorPathBuilder())
            ->secret($this->imagorSecret)
            ->signerType($this->imagorSignerType)
            ->signerTruncate($this->imagorSignerTruncate)
            // (at time of writing) The following line increased the cache expiration in the HTTP response header to 7d.
            // The actual time given is ignored (unfortunately) if it exceeds the Imagor service settings:
            // -imagor-cache-header-ttl (defaults to 7d) and -imagor-cache-header-swr (defaults to 1d).
            ->addFilter('expire', (time() + 31_536_000) * 1000); // TTL is 1y

        if ($configuration->getQuality()) {
            $result->addFilter('quality', $configuration->getQuality());
        }

        if (!$configuration->isCroppingAllowed()) {
            $result->fitIn();
        }
        if ($configuration->getFormat()) {
            $result->addFilter('format', $configuration->getFormat());
        }
        // !!! despite the types of the getters, width and height might be null !!!
        // The DB column is NULLABLE, see DimensionsTrait.php
        $originalWidth = $originalImage->getWidth();
        $originalHeight = $originalImage->getHeight();
        $adapter = new ImagorPathBuilderImageInterfaceAdapter($result, $originalWidth, $originalHeight);
        if ($image instanceof ImageVariant) {
            foreach ($image->getAdjustments() as $adjustment) {
                if ($adjustment instanceof AbstractImageAdjustment && $adjustment->canBeApplied($adapter)) {
                    $adjustment->applyToImage($adapter);
                }
            }
        }
        if (
            $configuration->isUpScalingAllowed() === false &&
            ($result->getResizeWidth() > $originalWidth || $result->getResizeHeight() > $originalHeight)
        ) {
            $result->resize(0, 0);
        }
        // TODO: IMPLEMENT LATER: $this->limitToMaximalSize($image, $result);

        return $result;
    }
}
