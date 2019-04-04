<?php
namespace Neos\Media\Browser\Domain;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;

/**
 *
 */
class ImageMapper
{
    /**
     * @var array
     */
    private $mappingResult = [];

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * ImageMapper constructor.
     *
     * @param ImageInterface $image
     * @param ResourceManager $resourceManager
     * @param PersistenceManagerInterface $persistenceManager
     */
    public function __construct(ImageInterface $image, ResourceManager $resourceManager, PersistenceManagerInterface $persistenceManager)
    {
        $mappingResult = $this->mapImage($image, $resourceManager, $persistenceManager);
        if ($image instanceof ImageVariant) {
            $mappingResult = array_merge($mappingResult, $this->mapVariant($image));
        }

        $this->mappingResult = $mappingResult;
    }

    /**
     * @return array
     */
    public function getMappingResult(): array
    {
        return $this->mappingResult;
    }

    /**
     * @param ImageInterface $image
     * @param ResourceManager $resourceManager
     * @param PersistenceManagerInterface $persistenceManager
     * @return array
     */
    private function mapImage(ImageInterface $image, ResourceManager $resourceManager, PersistenceManagerInterface $persistenceManager): array
    {
        $previeUri = $resourceManager->getPublicPersistentResourceUri($image->getResource());

        return [
            'previewUri' => $previeUri,
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'persistenceIdentifier' => $persistenceManager->getIdentifierByObject($image)
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
                $variantInformation['hasCrop'] = true;
                $variantInformation['cropInformation'] = [
                    'width' => $adjustment->getWidth(),
                    'height' => $adjustment->getHeight(),
                    'x' => $adjustment->getX(),
                    'y' => $adjustment->getY(),
                ];
            }
        }

        return $variantInformation;
    }
}
