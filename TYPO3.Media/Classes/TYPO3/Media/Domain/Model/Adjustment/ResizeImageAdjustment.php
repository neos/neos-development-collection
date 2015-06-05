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
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * An adjustment for resizing an image
 *
 * @Flow\Entity
 */
class ResizeImageAdjustment extends AbstractImageAdjustment {

	/**
	 * @var integer
	 */
	protected $position = 20;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $width;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $height;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $maximumWidth;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $maximumHeight;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $minimumWidth;

	/**
	 * @var integer
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $minimumHeight;

	/**
	 * One of the ImagineImageInterface::RATIOMODE_* constants
	 *
	 * @var string
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $ratioMode;

	/**
	 * @var boolean
	 * @ORM\Column(nullable = TRUE)
	 */
	protected $allowUpScaling;

	/**
	 * Sets maximumHeight
	 *
	 * @param integer $maximumHeight
	 * @return void
	 * @api
	 */
	public function setMaximumHeight($maximumHeight) {
		$this->maximumHeight = $maximumHeight;
	}

	/**
	 * Returns maximumHeight
	 *
	 * @return integer
	 * @api
	 */
	public function getMaximumHeight() {
		return $this->maximumHeight;
	}

	/**
	 * Sets maximumWidth
	 *
	 * @param integer $maximumWidth
	 * @return void
	 * @api
	 */
	public function setMaximumWidth($maximumWidth) {
		$this->maximumWidth = $maximumWidth;
	}

	/**
	 * Returns maximumWidth
	 *
	 * @return integer
	 * @api
	 */
	public function getMaximumWidth() {
		return $this->maximumWidth;
	}

	/**
	 * Sets height
	 *
	 * @param integer $height
	 * @return void
	 * @api
	 */
	public function setHeight($height) {
		$this->height = $height;
	}

	/**
	 * Returns height
	 *
	 * @return integer
	 * @api
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * Sets width
	 *
	 * @param integer $width
	 * @return void
	 * @api
	 */
	public function setWidth($width) {
		$this->width = $width;
	}

	/**
	 * Returns width
	 *
	 * @return integer
	 * @api
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Sets minimumHeight
	 *
	 * @param integer $minimumHeight
	 * @return void
	 * @api
	 */
	public function setMinimumHeight($minimumHeight) {
		$this->minimumHeight = $minimumHeight;
	}

	/**
	 * Returns minimumHeight
	 *
	 * @return integer
	 * @api
	 */
	public function getMinimumHeight() {
		return $this->minimumHeight;
	}

	/**
	 * Sets minimumWidth
	 *
	 * @param integer $minimumWidth
	 * @return void
	 * @api
	 */
	public function setMinimumWidth($minimumWidth) {
		$this->minimumWidth = $minimumWidth;
	}

	/**
	 * Returns minimumWidth
	 *
	 * @return integer
	 * @api
	 */
	public function getMinimumWidth() {
		return $this->minimumWidth;
	}

	/**
	 * Sets ratioMode
	 *
	 * @param integer $ratioMode One of the \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_* constants
	 * @return void
	 * @api
	 */
	public function setRatioMode($ratioMode) {
		$this->ratioMode = $ratioMode;
	}

	/**
	 * Returns ratioMode
	 *
	 * @return integer
	 * @api
	 */
	public function getRatioMode() {
		return $this->ratioMode;
	}

	/**
	 * Sets allowUpScaling
	 *
	 * @return boolean
	 */
	public function getAllowUpScaling() {
		return $this->allowUpScaling;
	}

	/**
	 * Returns allowUpScaling
	 *
	 * @param boolean $allowUpScaling
	 */
	public function setAllowUpScaling($allowUpScaling) {
		$this->allowUpScaling = $allowUpScaling;
	}

	/**
	 * Check if this Adjustment can or should be applied to its ImageVariant.
	 *
	 * @param ImagineImageInterface $image
	 * @return boolean
	 */
	public function canBeApplied(ImagineImageInterface $image) {
		$expectedDimensions = $this->calculateDimensions($image->getSize());
		if ($expectedDimensions->contains($image->getSize()) && $this->getAllowUpScaling() !== TRUE) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Applies this adjustment to the given Imagine Image object
	 *
	 * @param ImagineImageInterface $image
	 * @return ImagineImageInterface
	 * @internal Should never be used outside of the media package. Rely on the ImageService to apply your adjustments.
	 */
	public function applyToImage(ImagineImageInterface $image) {
		$ratioMode = $this->ratioMode ?: ImageInterface::RATIOMODE_INSET;
		return $this->resize($image, $this->calculateDimensions($image->getSize()), $ratioMode);
	}

	/**
	 * Calculates and returns the dimensions the image should have according all parameters set
	 * in this adjustment.
	 *
	 * @param BoxInterface $originalDimensions Dimensions of the unadjusted image
	 * @return Box
	 */
	protected function calculateDimensions(BoxInterface $originalDimensions) {
		$width = $originalDimensions->getWidth();
		$height = $originalDimensions->getHeight();

		if ($this->getAllowUpScaling()) {
			$this->width = $this->getMaximumWidth();
			$this->height = $this->getMaximumHeight();
		}

		// height and width are set explicitly:
		if ($this->width !== NULL && $this->height !== NULL) {
			$width = $this->width;
			$height = $this->height;

			// only width is set explicitly:
		} elseif ($this->width !== NULL) {
			$width = $this->width;
			$height = ($this->width / $originalDimensions->getWidth()) * $originalDimensions->getHeight();

			// only height is set explicitly:
		} elseif ($this->height !== NULL) {
			$width = ($this->height / $originalDimensions->getHeight()) * $originalDimensions->getWidth();
			$height = $this->height;
		}

		// makes sure that maximumWidth and maximumHeight is respected:
		if ($this->maximumWidth !== NULL && $this->maximumHeight !== NULL) {
			if ($width > $this->maximumWidth) {
				$width = $this->maximumWidth;
			}
			if ($height > $this->maximumHeight) {
				$height = $this->maximumHeight;
			}
			return new Box($width, $height);
		}

		// no matter if width has been adjusted previously, makes sure that maximumWidth is respected:
		if ($this->maximumWidth !== NULL) {
			if ($width > $this->maximumWidth) {
				$width = $this->maximumWidth;
				$height = ($width / $originalDimensions->getWidth()) * $originalDimensions->getHeight();
			}
		}

		// no matter if height has been adjusted previously, makes sure that maximumHeight is respected:
		if ($this->maximumHeight !== NULL) {
			if ($height > $this->maximumHeight) {
				$height = $this->maximumHeight;
				$width = ($height / $originalDimensions->getHeight()) * $originalDimensions->getWidth();
			}
		}

		return new Box($width, $height);
	}

	/**
	 * @param ImagineImageInterface $image
	 * @param BoxInterface $size
	 * @param string $mode
	 * @param string $filter
	 * @return \Imagine\Image\ManipulatorInterface
	 */
	protected function resize(ImagineImageInterface $image, BoxInterface $size, $mode = ImageInterface::RATIOMODE_INSET, $filter = ImagineImageInterface::FILTER_UNDEFINED) {
		if ($mode !== ImageInterface::RATIOMODE_INSET &&
			$mode !== ImageInterface::RATIOMODE_OUTBOUND
		) {
			throw new \InvalidArgumentException('Invalid mode specified');
		}

		$imageSize = $image->getSize();
		$ratios = array(
			$size->getWidth() / $imageSize->getWidth(),
			$size->getHeight() / $imageSize->getHeight()
		);
		if ($mode === ImageInterface::RATIOMODE_INSET) {
			$ratio = min($ratios);
		} else {
			$ratio = max($ratios);
		}

		$thumbnail = $image->copy();

		$thumbnail->usePalette($image->palette());
		$thumbnail->strip();
		if (!$imageSize->contains($size) && $this->getAllowUpScaling()) {
			$imageSize = $imageSize->scale($ratio);
		} elseif ($size->contains($imageSize) && !$this->getAllowUpScaling()) {
			return $thumbnail;
		}

		if ($mode === ImageInterface::RATIOMODE_OUTBOUND) {
			if (!$imageSize->contains($size)) {
				$size = new Box(
					min($imageSize->getWidth(), $size->getWidth()),
					min($imageSize->getHeight(), $size->getHeight())
				);
			} else {
				$imageSize = $thumbnail->getSize()->scale($ratio);
				$thumbnail->resize($imageSize, $filter);
			}
			$thumbnail->crop(new Point(
				max(0, round(($imageSize->getWidth() - $size->getWidth()) / 2)),
				max(0, round(($imageSize->getHeight() - $size->getHeight()) / 2))
			), $size);
		} else {
			if (!$imageSize->contains($size)) {
				$imageSize = $imageSize->scale($ratio);
				$thumbnail->resize($imageSize, $filter);
			} else {
				$imageSize = $thumbnail->getSize()->scale($ratio);
				$thumbnail->resize($imageSize, $filter);
			}
		}

		return $thumbnail;
	}

}
