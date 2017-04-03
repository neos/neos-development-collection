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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Service\ImageService;
use Neos\Media\Exception\ImageFileException;

/**
 * An image
 *
 * @Flow\Entity
 */
class Image extends Asset implements ImageInterface, VariantSupportInterface
{
    use DimensionsTrait;

    /**
     * @var Collection<\Neos\Media\Domain\Model\ImageVariant>
     * @ORM\OneToMany(orphanRemoval=true, cascade={"all"}, mappedBy="originalAsset")
     */
    protected $variants;

    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * Constructor
     *
     * @param PersistentResource $resource
     */
    public function __construct(PersistentResource $resource)
    {
        parent::__construct($resource);
        $this->variants = new ArrayCollection();
    }

    /**
     * @param integer $initializationCause
     * @throws ImageFileException
     * @return void
     */
    public function initializeObject($initializationCause)
    {
        // FIXME: This is a workaround for after the resource management changes that introduced the property.
        if ($this->variants === null) {
            $this->variants = new ArrayCollection();
        }
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->calculateDimensionsFromResource($this->resource);
        }
        parent::initializeObject($initializationCause);
    }

    /**
     * Calculates image width and height from the image resource.
     *
     * @throws ImageFileException
     * @return void
     */
    public function refresh()
    {
        $this->calculateDimensionsFromResource($this->resource);
        parent::refresh();
    }

    /**
     * Adds a variant of this image
     *
     * Note that you should try to re-use variants if you need to adjust them, rather than creating a new
     * variant for every change. Non-used variants will remain in the database and block resource disk space
     * until they are removed explicitly or the original image is deleted.
     *
     * @param ImageVariant $variant The new variant
     * @return void
     * @throws \InvalidArgumentException
     */
    public function addVariant(ImageVariant $variant)
    {
        if ($variant->getOriginalAsset() !== $this) {
            throw new \InvalidArgumentException('Could not add the given ImageVariant to the list of this Image\'s variants because the variant refers to a different original asset.', 1381416726);
        }
        $this->variants->add($variant);
    }

    /**
     * Returns all variants (if any) derived from this asset
     *
     * @return array
     * @api
     */
    public function getVariants()
    {
        return $this->variants->toArray();
    }

    /**
     * Calculates and sets the width and height of this Image asset based
     * on the given PersistentResource.
     *
     * @param PersistentResource $resource
     * @return void
     * @throws ImageFileException
     */
    protected function calculateDimensionsFromResource(PersistentResource $resource)
    {
        try {
            $imageSize = $this->imageService->getImageSize($resource);
        } catch (ImageFileException $imageFileException) {
            throw new ImageFileException(sprintf('Tried to refresh the dimensions and meta data of Image asset "%s" but the file of resource "%s" does not exist or is not a valid image.', $this->getTitle(), $resource->getSha1()), 1381141468, $imageFileException);
        }

        $this->width = is_int($imageSize['width']) ? $imageSize['width'] : null;
        $this->height = is_int($imageSize['height']) ? $imageSize['height'] : null;
    }
}
