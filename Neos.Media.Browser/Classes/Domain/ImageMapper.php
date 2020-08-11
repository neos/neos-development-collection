<?php
namespace Neos\Media\Browser\Domain;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * The ImageMapper provides basic information about an image object as array of simple types.
 */
class ImageMapper
{
    /**
     * The image to be mapped.
     *
     * @var ImageInterface
     */
    private $image;

    /**
     * @var array
     */
    private $mappingResult = [];

    /**
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * @var PersistenceManagerInterface
     */
    private $persistenceManager;

    /**
     * @param ResourceManager $resourceManager
     */
    public function injectResourceManager(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @param PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * ImageMapper constructor.
     *
     * @param ImageInterface $image
     */
    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    public function initializeObject()
    {
        $mappingResult = $this->mapImage();
        if ($this->image instanceof ImageVariant) {
            $mappingResult = array_merge($mappingResult, $this->mapVariant($this->image));
        }

        $this->mappingResult = $mappingResult;
        $this->image = null;
    }

    /**
     * @return array
     */
    public function getMappingResult(): array
    {
        return $this->mappingResult;
    }

    /**
     * Map the image object attached to this mapper to basic properties.
     *
     * @return array
     */
    private function mapImage(): array
    {
        $previewUri = $this->resourceManager->getPublicPersistentResourceUri($this->image->getResource());

        return [
            'previewUri' => $previewUri,
            'width' => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
            'persistenceIdentifier' => $this->persistenceManager->getIdentifierByObject($this->image)
        ];
    }

    /**
     * @param ImageVariant $imageVariant
     * @return array
     */
    private function mapVariant(ImageVariant $imageVariant): array
    {
        $variantInformation = [
            'presetIdentifier' => $imageVariant->getPresetIdentifier(),
            'presetVariantName' => $imageVariant->getPresetVariantName(),
            'hasCrop' => false,
            'cropInformation' => []
        ];

        foreach ($imageVariant->getAdjustments() as $adjustment) {
            if ($adjustment instanceof CropImageAdjustment) {
                $variantInformation = array_merge($variantInformation, $this->mapCrop($imageVariant, $adjustment));
            }
        }

        return $variantInformation;
    }

    /**
     * Map crop information for user interface.
     *
     * @param ImageVariant $imageVariant
     * @param CropImageAdjustment $adjustment
     * @return array
     */
    private function mapCrop(ImageVariant $imageVariant, CropImageAdjustment $adjustment)
    {
        $variantInformation['hasCrop'] = true;
        $variantInformation['cropInformation'] = [
            'width' => $adjustment->getWidth(),
            'height' => $adjustment->getHeight(),
            'x' => $adjustment->getX(),
            'y' => $adjustment->getY(),
        ];

        $aspectRatio = $adjustment->getAspectRatio();
        if ($aspectRatio !== null) {
            [$x, $y, $width, $height] = CropImageAdjustment::calculateDimensionsByAspectRatio($imageVariant->getOriginalAsset()->getWidth(), $imageVariant->getOriginalAsset()->getHeight(), $aspectRatio);
            $variantInformation['cropInformation'] = [
                'width' => $width,
                'height' => $height,
                'x' => $x,
                'y' => $y,
            ];
        }

        return $variantInformation;
    }
}
