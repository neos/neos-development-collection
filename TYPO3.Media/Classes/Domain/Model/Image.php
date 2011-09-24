<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "Media".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An image
 *
 * @entity
 */
class Image implements \TYPO3\Media\Domain\Model\ImageInterface {

	/**
	 * The image repository is injected so that the image can persist itself when new ImageVariant is added
	 *
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 * @inject
	 */
	protected $imageRepository;

	/**
	 * @var string
	 * @validate StringLength(maximum = 255)
	 */
	protected $title;

	/**
	 * @var \TYPO3\FLOW3\Resource\Resource
	 * @ManyToOne
	 * @validate NotEmpty
	 */
	protected $resource;

	/**
	 * @var integer
	 * @validate NotEmpty
	 */
	protected $width;

	/**
	 * @var integer
	 * @validate NotEmpty
	 */
	protected $height;

	/**
	 * one of PHPs IMAGETYPE_* constants
	 *
	 * @var integer
	 * @validate NotEmpty
	 */
	protected $type;

	/**
	 * @fixme this should be a collection, but that is currently not serialized by Doctrine
	 * @var array
	 */
	protected $imageVariants = array();

	/**
	 * @param \TYPO3\FLOW3\Resource\Resource $resource
	 */
	public function __construct(\TYPO3\FLOW3\Resource\Resource $resource) {
		$this->resource = $resource;
		$this->initialize();
	}

	/**
	 * Calculates image width, height and type from the image resource
	 *
	 * @return void
	 */
	protected function initialize() {
		$imageSize = getimagesize('resource://' . $this->resource->getResourcePointer()->getHash());
		$this->width = (integer)$imageSize[0];
		$this->height = (integer)$imageSize[1];
		$this->type = (integer)$imageSize[2];
	}

	/**
	 * Sets the image resource and (re-)initializes the image
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource
	 * @return void
	 */
	public function setResource(\TYPO3\FLOW3\Resource\Resource $resource) {
		$this->resource = $resource;
		$this->initialize();
	}

	/**
	 * Sets the title of this image (optional)
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * The title of this image
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Resource of the original image file
	 *
	 * @return \TYPO3\FLOW3\Resource\Resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Width of the image in pixels
	 *
	 * @return integer
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Height of the image in pixels
	 *
	 * @return integer
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * One of PHPs IMAGETYPE_* constants that reflects the image type
	 *
	 * @see http://php.net/manual/image.constants.php
	 * @return integer
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * File extension of the image without leading dot.
	 * @see http://www.php.net/manual/function.image-type-to-extension.php
	 *
	 * @return string
	 */
	public function getFileExtension() {
		return image_type_to_extension($this->type, FALSE);
	}

	/**
	 * Returns a thumbnail of this image.
	 *
	 * If maximum width/height is not specified or exceed the original images size,
	 * width/height of the original image is used
	 *
	 * Note: The image variant that will be created is intentionally not added to the imageVariants collection of this image
	 * If you want to create a persisted image variant, use createImageVariant() instead.
	 *
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 * @see \TYPO3\Media\Domain\Service\ImageService::transformImage()
	 */
	public function getThumbnail($maximumWidth = NULL, $maximumHeight = NULL) {
		$processingInstructions = array(
			array(
				'command' => 'thumbnail',
				'options' => array(
					'size' => array(
						'width' => $maximumWidth ?: $this->width,
						'height' => $maximumHeight ?: $this->height
					),
				),
			),
		);
		return new ImageVariant($this, $processingInstructions);
	}

	/**
	 * Set all variants of this image.
	 *
	 * @param array $imageVariants
	 * @return void
	 */
	public function setImageVariants(array $imageVariants) {
		$this->imageVariants = $imageVariants;
	}

	/**
	 * Return all variants of this image.
	 *
	 * @return array
	 */
	public function getImageVariants() {
		return $this->imageVariants;
	}

	/**
	 * Create a variant of this image using the given processing instructions.
	 *
	 * The variant is attached to the image for later (re-)use.
	 *
	 * @param array $processingInstructions
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 */
	public function createImageVariant(array $processingInstructions) {
		$imageVariant = new ImageVariant($this, $processingInstructions);
		// FIXME we currently need a unique hash because $this->imageVariants has to be an array in order to be serialized by Doctrine
		$uniqueHash = sha1($this->resource->getResourcePointer()->getHash() . '|' . serialize($processingInstructions));
		$this->imageVariants[$uniqueHash] = $imageVariant;
		$this->imageRepository->update($this);
		return $imageVariant;
	}

	/**
	 * Remove the given variant from this image.
	 *
	 * @param \TYPO3\Media\Domain\Model\ImageVariant $imageVariant
	 * @return void
	 */
	public function removeImageVariant(\TYPO3\Media\Domain\Model\ImageVariant $imageVariant) {
		// FIXME we currently need a unique hash because $this->imageVariants has to be an array in order to be serialized by Doctrine
		$uniqueHash = sha1($this->resource->getResourcePointer()->getHash() . '|' . serialize($imageVariant->getProcessingInstructions()));
		if (isset($this->imageVariants[$uniqueHash])) {
			unset($this->imageVariants[$uniqueHash]);
		}
	}

}

?>