<?php
namespace TYPO3\Media\Domain\Model\Adjustment;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\ImageVariant;

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
     * @ORM\Column(nullable = FALSE)
     */
    protected $position;

    /**
     * @var \TYPO3\Media\Domain\Model\ImageVariant
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
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param integer $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
