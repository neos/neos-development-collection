<?php

declare(strict_types=1);

namespace Neos\Media\Domain\Service\Imagor;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Adjustment\AbstractImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;

/**
 * @Flow\Scope("singleton")
 */
class ImagorService
{
    /**
     * @Flow\InjectConfiguration("imagor.secret")
     */
    protected ?string $imagorSecret;

    /**
     * @Flow\InjectConfiguration("imagor.signerType")
     */
    protected ?string $imagorSignerType;

    /**
     * @Flow\InjectConfiguration("imagor.signerTruncate")
     */
    protected ?int $imagorSignerTruncate;

    /**
     * @Flow\Inject
     */
    protected ResourceManager $resourceManager;

    public function getThumbnailUriAndSize(ImageInterface $asset, ThumbnailConfiguration $configuration): array
    {
        // TODO: URL ermitteln -> später auch S3, sonst local file -> damit direkt von der
        //  Festplatte gelesen werden kann

        // todo test with secret in config

        $originalImage = $asset;
        // todo test with thumbnail
        if ($originalImage instanceof Thumbnail) {
            // will return either Image (fine) or ImageVariant => need Image
            $originalImage = $asset->getOriginalAsset();
        }
        // might happen that thumbnail -> imageVariant -> Image, that's why we do not do elseif but if (after thumbnail)
        if ($originalImage instanceof ImageVariant) {
            $originalImage = $asset->getOriginalAsset();
        }

        $imageUrl = $this->resourceManager->getPublicPersistentResourceUri($originalImage->getResource());
        if ($imageUrl === '' || $imageUrl === false) {
            return [];
        }

        try {
            // todo with and height calculate
            return [
                'src' => $this->createImagorPathBuilder(
                    $asset,
                    $configuration,
                    $originalImage,
                    $imageUrl
                )->getSourceUrl(),
            ];
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @throws Exception
     */
    private function createImagorPathBuilder(
        ImageInterface $image,
        ThumbnailConfiguration $configuration,
        ImageInterface $originalImage,
        string $sourceImageUrl
    ): ImagorPathBuilder {
        $result = (new ImagorPathBuilder($sourceImageUrl))
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

        if (! $configuration->isCroppingAllowed()) {
            $result->fitIn();
        }
        if ($configuration->getFormat()) {
            $result->addFilter('format', $configuration->getFormat());
        }

        $originalWidth = $originalImage->getWidth() ?? 0;
        $originalHeight = $originalImage->getHeight() ?? 0;
        $adapter = new ImagorPathBuilderImageInterfaceAdapter($result, $originalWidth, $originalHeight);
        if ($image instanceof ImageVariant) {
            foreach ($image->getAdjustments() as $adjustment) {
                if ($adjustment instanceof AbstractImageAdjustment && $adjustment->canBeApplied($adapter)) {
                    $adjustment->applyToImage($adapter);
                }
            }
        }
        if (
            ! $configuration->isUpScalingAllowed() &&
            ($result->getResizeWidth() > $originalWidth || $result->getResizeHeight() > $originalHeight)
        ) {
            $result->resize(0, 0);
        }

        $this->limitToMaximalSize($configuration, $result, $originalWidth, $originalHeight);

        return $result;
    }

    private function limitToMaximalSize(
        ThumbnailConfiguration $configuration,
        ImagorPathBuilder $result,
        int $originalWidth,
        int $originalHeight,
    ): void {
        $actualWidth = $result->getResizeWidth() !== 0 ? $result->getResizeWidth() : $originalWidth;
        $actualHeight = $result->getResizeHeight() !== 0 ? $result->getResizeHeight() : $originalHeight;

        if ($this->isTooWide($configuration, $actualWidth) || $this->isTooHigh($configuration, $actualHeight)) {
            $width = $actualWidth;
            $height = $actualHeight;

            // TODO: what if $allowCropping
            if ($this->isTooWide($configuration, $width)) {
                // here the limit cannot be null but Psalm does not realise it, hence the default value
                $width = $configuration->getMaximumWidth() ?? 0;
                $height = intval(round(($width / $actualHeight) * $actualHeight));
                // by setting the height to 0 we keep the aspect ration
                $result->resize($width, 0);
            }
            // too high since we did not limit width OR
            // too high although we limited the width
            if ($this->isTooHigh($configuration, $height)) {
                $result->resize(0, $height);
            }
        }
    }

    private function isTooHigh(ThumbnailConfiguration $configuration, int $height): bool
    {
        $maximumHeight = $configuration->getMaximumHeight();

        return $maximumHeight !== null && $maximumHeight !== 0 && $height > $maximumHeight;
    }

    private function isTooWide(ThumbnailConfiguration $configuration, int $width): bool
    {
        $maximumWidth = $configuration->getMaximumWidth();

        return $maximumWidth !== null && $maximumWidth !== 0 && $width > $maximumWidth;
    }
}
