<?php
namespace Neos\Media\Browser\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Media\Browser\Domain\ImageMapper;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 *
 */
class ImageVariantController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    public function initializeUpdateAction()
    {
        $mappingConfiguration = $this->arguments->getArgument('imageVariant')->getPropertyMappingConfiguration();
        $mappingConfiguration->allowAllProperties();
        $mappingConfiguration->getConfigurationFor('adjustments')->allowAllProperties();
        $mappingConfiguration->getConfigurationFor('adjustments')->getConfigurationFor('*')->allowAllProperties();
        $mappingConfiguration->getConfigurationFor('adjustments')->getConfigurationFor(CropImageAdjustment::class)->allowAllProperties();
        $mappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
    }

    /**
     * @param ImageVariant $imageVariant
     */
    public function updateAction(ImageVariant $imageVariant)
    {
        $this->assetRepository->update($imageVariant);
        return json_encode((new ImageMapper($imageVariant))->getMappingResult());
    }
}
