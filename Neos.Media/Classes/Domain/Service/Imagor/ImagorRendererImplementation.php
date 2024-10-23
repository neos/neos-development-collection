<?php

namespace Neos\Media\Domain\Service\Imagor;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\Adjustment\AbstractImageAdjustment;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Thumbnail;
use Psr\Log\LoggerInterface;

class ImagorRendererImplementation extends AbstractFusionObject
{
    // ################# Fusion API #################

    /**
     * Asset
     *
     * @return AssetInterface | null
     */
    public function getAsset()
    {
        return $this->fusionValue('asset');
    }

    /**
     * MaximumWidth
     *
     * @return integer | null
     */
    public function getMaximumWidth(): ?int
    {
        return $this->fusionValue('maximumWidth');
    }

    /**
     * MaximumHeight
     *
     * @return integer | null
     */
    public function getMaximumHeight(): ?int
    {
        return $this->fusionValue('maximumHeight');
    }

    /**
     * AllowCropping
     *
     * @return boolean
     */
    public function getAllowCropping()
    {
        return $this->fusionValue('allowCropping');
    }

    /**
     * AllowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling()
    {
        return $this->fusionValue('allowUpScaling');
    }

    /**
     * Quality
     *
     * @return integer
     */
    public function getQuality(): int
    {
        return $this->fusionValue('quality');
    }

    /**
     * Async
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->fusionValue('format');
    }

    /**
     * Preset
     *
     * @return string
     */
    public function getPreset(): string
    {
        return $this->fusionValue('preset');
    }

    // ################# IMPLEMENTATION #################

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

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

    public function evaluate(): string
    {
        $asset = $this->getAsset();
        if ($asset === null) {
            return '';
        }
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
            return '';
        }
        $normalizedImageUrl = $this->normalizeUrl(strval($imageUrl));
        if (str_ends_with($normalizedImageUrl, '.svg')) {
            // cropping and such is not supported for SVGs neither in Imagor nor in Vips etc
            // trying to process SVGs with Imagor leads to black boxes
            return $normalizedImageUrl;
        }
        try {
            return $this->buildImageUrl($asset, $originalImage, $normalizedImageUrl);
        } catch (\Throwable $t) {
            // We catch exceptions and errors here since we had an incident due to a null value in the DB.
            // The resulting TypeError lead to an 500 error page. We do not want that.
            // TODO - logging!
            return '';
        }
    }

    public function allowsCallOfMethod(string $methodName): bool
    {
        return true;
    }

    /**
     * @param string $sourceImage
     * @return string
     */
    private function normalizeUrl(string $sourceImage): string
    {
        if ($this->environment->getContext()->isDevelopment()) {
            return str_ireplace('http://localhost:8081', $this->imagorSourceBaseUrl, $sourceImage);
        }
        return $sourceImage;
    }

    private function buildImageUrl(AssetInterface $image, ImageInterface $originalImage, string $sourceUrl): string
    {
        $imagorBuilder = $this->asImagorPathBuilder($image, $originalImage);
        return $this->imagorProxyBaseUrl . "/" . $imagorBuilder->build($sourceUrl);
    }

    private function asImagorPathBuilder(AssetInterface $image, ImageInterface $originalImage): ImagorPathBuilder
    {
        $allowUpScaling = $this->getAllowUpScaling();
        $allowCropping = $this->getAllowCropping();
        $quality = $this->getQuality();
        $format = $this->getFormat();
        // TODO: preset = NULL

        $result = (new ImagorPathBuilder())
            ->secret($this->imagorSecret)
            ->signerType($this->imagorSignerType)
            ->signerTruncate($this->imagorSignerTruncate)
            // (at time of writing) The following line increased the cache expiration in the HTTP response header to 7d.
            // The actual time given is ignored (unfortunately) if it exceeds the Imagor service settings:
            // -imagor-cache-header-ttl (defaults to 7d) and -imagor-cache-header-swr (defaults to 1d).
            ->addFilter('expire', (time() + 31_536_000) * 1000) // TTL is 1y
            ->addFilter('quality', $quality);
        if (!$allowCropping) {
            $result->fitIn();
        }
        if (!empty($format)) {
            $result->addFilter('format', $format);
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
            $allowUpScaling === false &&
            ($result->getResizeWidth() > $originalWidth || $result->getResizeHeight() > $originalHeight)
        ) {
            $result->resize(0, 0);
        }
        $this->limitToMaximalSize($image, $result);

        return $result;
    }

    private function limitToMaximalSize(AssetInterface $image, ImagorPathBuilder $result): void
    {
        $originalWidth = $result->getResizeWidth() !== 0 ? $result->getResizeWidth() : $image->getWidth();
        $originalHeight = $result->getResizeHeight() !== 0 ? $result->getResizeHeight() : $image->getHeight();

        if ($this->isTooWide($originalWidth) || $this->isTooHigh($originalHeight)) {
            $width = $originalWidth;
            $height = $originalHeight;
            // TODO: what if $allowCropping
            if ($this->isTooWide($width)) {
                // here the limit cannot be null but Psalm does not realise it, hence the default value
                $width = $this->getMaximumWidth() ?? 0;
                $height = ($width / $originalHeight) * $originalHeight;
                // by setting the height to 0 we keep the aspect ration
                $result->resize($width, 0);
            }
            // too high since we did not limit width OR
            // too high although we limited the width
            if ($this->isTooHigh($height)) {
                $result->resize(0, $height);
            }
        }
    }

    private function isTooHigh(int $height): bool
    {
        $maximumHeight = $this->getMaximumHeight();
        return $maximumHeight !== null && $maximumHeight !== 0 && $height > $maximumHeight;
    }

    private function isTooWide(int $width): bool
    {
        $maximumWidth = $this->getMaximumWidth();
        return $maximumWidth !== null && $maximumWidth !== 0 && $width > $maximumWidth;
    }
}
