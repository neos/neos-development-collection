<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Neos\Flow\Annotations as Flow;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Utility\TypeHandling;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Media\Domain\Service\ImageService;

/**
 * A user defined variant (working copy) of an original Image asset
 *
 * @Flow\Entity
 */
class ImageVariant extends Asset implements AssetVariantInterface, ImageInterface
{
    use DimensionsTrait;

    /**
     * @var ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @var Image
     * @ORM\ManyToOne(inversedBy="variants")
     * @ORM\JoinColumn(nullable=false)
     * @Flow\Validate(type="NotEmpty")
     */
    protected $originalAsset;

    /**
     * @var ArrayCollection<\Neos\Media\Domain\Model\Adjustment\AbstractImageAdjustment>
     * @ORM\OneToMany(mappedBy="imageVariant", cascade={"all"}, orphanRemoval=TRUE)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $adjustments;

    /**
     * @var string
     * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
     */
    protected $name = '';

    /**
     * Constructs a new Image Variant based on the given original
     *
     * @param Image $originalAsset The original Image asset this variant is derived from
     */
    public function __construct(Image $originalAsset)
    {
        $this->originalAsset = $originalAsset;

        $this->thumbnails = new ArrayCollection();
        $this->adjustments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->lastModified = new \DateTime();
    }

    /**
     * Initialize this image variant
     *
     * This method will generate the resource of this asset when this object has just been newly created.
     * We can't run renderResource() in the constructor since not all dependencies have been injected then. Generating
     * resources lazily in the getResource() method is not feasible either, because getters will be triggered
     * by the validation mechanism on flushing the persistence which will result in undefined behavior.
     *
     * We don't call refresh() here because we only want the resource to be rendered, not all other refresh actions
     * from parent classes being executed.
     *
     * @param integer $initializationCause
     * @return void
     */
    public function initializeObject($initializationCause)
    {
        parent::initializeObject($initializationCause);
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->renderResource();
        }
    }

    /**
     * Returns the original image this variant is based on
     *
     * @return Image
     */
    public function getOriginalAsset()
    {
        return $this->originalAsset;
    }

    /**
     * Returns the resource of this image variant
     *
     * @return PersistentResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Refreshes this image variant: according to the added adjustments, a new image is rendered and stored as this
     * image variant's resource.
     *
     * @return void
     * @see getResource()
     */
    public function refresh()
    {
        // Several refresh() calls might happen during one request. If that is the case, the Resource Manager can't know
        // that the first created resource object doesn't have to be persisted / published anymore. Thus we need to
        // delete the resource manually in order to avoid orphaned resource objects:
        if ($this->resource !== null) {
            $this->resourceManager->deleteResource($this->resource);
        }

        parent::refresh();
        $this->renderResource();
    }

    /**
     * File extension of the image without leading dot.
     * This will return the file extension of the original image as this should not be different in image variants
     *
     * @return string
     */
    public function getFileExtension()
    {
        return $this->originalAsset->getFileExtension();
    }

    /**
     * Returns the title of the original image
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->originalAsset->getTitle();
    }

    /**
     * Returns the caption of the original image
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->originalAsset->getCaption();
    }

    /**
     * Sets a name which can be used for identifying this variant
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setting the image resource on an ImageVariant is not allowed, this method will
     * throw a RuntimeException.
     *
     * @param PersistentResource $resource
     * @return void
     * @throws \RuntimeException
     */
    public function setResource(PersistentResource $resource)
    {
        throw new \RuntimeException('Setting the resource on an ImageVariant is not supported.', 1366627480);
    }

    /**
     * Setting the title on an ImageVariant is not allowed, this method will throw a
     * RuntimeException.
     *
     * @param string $title
     * @return void
     * @throws \RuntimeException
     */
    public function setTitle($title)
    {
        throw new \RuntimeException('Setting the title on an ImageVariant is not supported.', 1366627475);
    }

    /**
     * Add a single tag to this asset
     *
     * @param Tag $tag
     * @return void
     */
    public function addTag(Tag $tag)
    {
        throw new \RuntimeException('Adding a tag on an ImageVariant is not supported.', 1371237593);
    }

    /**
     * Set the tags assigned to this asset
     *
     * @param Collection $tags
     * @return void
     */
    public function setTags(Collection $tags)
    {
        throw new \RuntimeException('Settings tags on an ImageVariant is not supported.', 1371237597);
    }

    /**
     * Adding variants to variants is not supported.
     *
     * @param ImageVariant $variant
     * @return void
     */
    public function addVariant(ImageVariant $variant)
    {
        throw new \RuntimeException('Adding variants to an ImageVariant is not supported.', 1381419461);
    }

    /**
     * Retrieving variants from variants is not supported (no-operation)
     *
     * @return array
     */
    public function getVariants()
    {
        return array();
    }

    /**
     * Adds the given adjustment to the list of adjustments applied to this image variant.
     *
     * If an adjustment of the given type already exists, the existing one will be overridden by the new one.
     *
     * @param ImageAdjustmentInterface $adjustment The adjustment to apply
     * @return void
     */
    public function addAdjustment(ImageAdjustmentInterface $adjustment)
    {
        $this->applyAdjustment($adjustment);
        $this->refresh();
    }

    /**
     * Adds the given adjustments to the list of adjustments applied to this image variant.
     *
     * If an adjustment of one of the given types already exists, the existing one will be overridden by the new one.
     *
     * @param array<ImageAdjustmentInterface> $adjustments
     * @return void
     */
    public function addAdjustments(array $adjustments)
    {
        foreach ($adjustments as $adjustment) {
            $this->applyAdjustment($adjustment);
        }

        $this->refresh();
    }

    /**
     * Apply the given adjustment to the image variant.
     * If an adjustment of the given type already exists, the existing one will be overridden by the new one.
     *
     * @param ImageAdjustmentInterface $adjustment
     * @return void
     */
    protected function applyAdjustment(ImageAdjustmentInterface $adjustment)
    {
        $existingAdjustmentFound = false;
        $newAdjustmentClassName = TypeHandling::getTypeForValue($adjustment);

        foreach ($this->adjustments as $existingAdjustment) {
            if (TypeHandling::getTypeForValue($existingAdjustment) === $newAdjustmentClassName) {
                foreach (ObjectAccess::getGettableProperties($adjustment) as $propertyName => $propertyValue) {
                    ObjectAccess::setProperty($existingAdjustment, $propertyName, $propertyValue);
                }
                $existingAdjustmentFound = true;
            }
        }
        if (!$existingAdjustmentFound) {
            $this->adjustments->add($adjustment);
            $adjustment->setImageVariant($this);
            $this->adjustments = $this->adjustments->matching(new Criteria(null, array('position' => 'ASC')));
        }

        $this->lastModified = new \DateTime();
    }

    /**
     * @return Collection
     */
    public function getAdjustments()
    {
        return $this->adjustments;
    }

    /**
     * Tells the ImageService to render the resource of this ImageVariant according to the existing adjustments.
     *
     * @return void
     */
    protected function renderResource()
    {
        $processedImageInfo = $this->imageService->processImage($this->originalAsset->getResource(), $this->adjustments->toArray());
        $this->resource = $processedImageInfo['resource'];
        $this->width = $processedImageInfo['width'];
        $this->height = $processedImageInfo['height'];
        $this->persistenceManager->whiteListObject($this->resource);
    }
}
