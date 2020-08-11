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
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * An abstract image adjustment
 *
 * @Flow\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 */
abstract class AbstractImageAdjustment extends AbstractAdjustment implements ImageAdjustmentInterface
{
    /**
     * Order in which the adjustment is applied to the ImageVariant
     *
     * @var integer
     * @ORM\Column(nullable = false)
     */
    protected $position;

    /**
     * @var ImageVariant
     * @ORM\ManyToOne(inversedBy="adjustments", cascade={"all"})
     */
    protected $imageVariant;

    /**
     * Sets the image variant this adjustment belongs to
     *
     * @param ImageVariant $imageVariant
     * @return void
     * @api
     */
    public function setImageVariant(ImageVariant $imageVariant)
    {
        $this->imageVariant = $imageVariant;
    }

    /**
     * @return integer
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param integer $position
     */
    public function setPosition(int $position)
    {
        $this->position = $position;
    }
}
