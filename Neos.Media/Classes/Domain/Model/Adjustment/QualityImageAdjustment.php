<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Adjustment;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Imagine\Image\ImageInterface;
use Neos\Flow\Annotations as Flow;

/**
 * An adjustment for quality of an image
 *
 * @Flow\Entity
 */
class QualityImageAdjustment extends AbstractImageAdjustment
{
    /**
     * @var integer
     */
    protected $position = 30;

    /**
     * @var integer
     * @ORM\Column(nullable = true)
     */
    protected $quality;

    /**
     * Returns quality
     *
     * @return integer
     */
    public function getQuality(): ?int
    {
        return $this->quality;
    }

    /**
     * Sets quality
     *
     * @param integer $quality
     * @return void
     */
    public function setQuality(int $quality = null): void
    {
        $this->quality = $quality;
    }

    /**
     * Applies this adjustment to the given Imagine Image object
     *
     * @param ImageInterface $image
     * @return ImageInterface
     */
    public function applyToImage(ImageInterface $image)
    {
        return $image;
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImageInterface $image
     * @return boolean
     */
    public function canBeApplied(ImageInterface $image)
    {
        return false;
    }
}
