<?php
namespace TYPO3\Media\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An image service that acts as abstraction for the Imagine library
 *
 * @Flow\Scope("singleton")
 */
class ImageService {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 * @Flow\Inject
	 */
	protected $resourceManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Media\Domain\Model\ImageInterface $image
	 * @param array $processingInstructions
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 */
	public function transformImage(\TYPO3\Media\Domain\Model\ImageInterface $image, array $processingInstructions) {
		$uniqueHash = sha1($image->getResource()->getResourcePointer()->getHash() . '|' . serialize($processingInstructions));
		if (!file_exists('resource://' . $uniqueHash)) {
			$imagine = $this->objectManager->get('Imagine\Image\ImagineInterface');
			$imagineImage = $imagine->open('resource://' . $image->getResource()->getResourcePointer()->getHash());
			$imagineImage = $this->applyProcessingInstructions($imagineImage, $processingInstructions);
			file_put_contents('resource://' . $uniqueHash, $imagineImage->get($image->getFileExtension(), $this->settings['image']['defaultOptions']));
		}
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($image->getResource()->getFilename());
		$resource->setResourcePointer(new \TYPO3\Flow\Resource\ResourcePointer($uniqueHash));

		return $resource;
	}

	/**
	 * @param \Imagine\Image\ImageInterface $image
	 * @param array $processingInstructions
	 * @return \Imagine\Image\ImageInterface
	 * @throws \InvalidArgumentException
	 */
	protected function applyProcessingInstructions(\Imagine\Image\ImageInterface $image, array $processingInstructions) {
		foreach ($processingInstructions as $processingInstruction) {
			$commandName = $processingInstruction['command'];
			$commandMethodName = sprintf('%sCommand', $commandName);
			if (!is_callable(array($this, $commandMethodName))) {
				throw new \InvalidArgumentException('Invalid command "' . $commandName . '"', 1316613563);
			}
			$image = call_user_func(array($this, $commandMethodName), $image, $processingInstruction['options']);
		}
		return $image;
	}

	/**
	 * @param \Imagine\Image\ImageInterface $image
	 * @param array $commandOptions array('size' => ('width' => 123, 'height => 456), 'mode' => 'outbound')
	 * @return \Imagine\Image\ImageInterface
	 */
	protected function thumbnailCommand(\Imagine\Image\ImageInterface $image, array $commandOptions) {
		if (!isset($commandOptions['size'])) {

		}
		$dimensions = $this->parseBox($commandOptions['size']);
		if (isset($commandOptions['mode']) && $commandOptions['mode'] === \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_OUTBOUND) {
			$mode = \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND;
		} else {
			$mode = \Imagine\Image\ManipulatorInterface::THUMBNAIL_INSET;
		}
		return $image->thumbnail($dimensions, $mode);
	}

	/**
	 * @param \Imagine\Image\ImageInterface $image
	 * @param array $commandOptions array('size' => ('width' => 123, 'height => 456))
	 * @return \Imagine\Image\ImageInterface
	 */
	protected function resizeCommand(\Imagine\Image\ImageInterface $image, array $commandOptions) {
		if (!isset($commandOptions['size'])) {

		}
		$dimensions = $this->parseBox($commandOptions['size']);
		return $image->resize($dimensions);
	}

	/**
	 * @param \Imagine\Image\ImageInterface $image
	 * @param array $commandOptions array('start' => array('x' => 123, 'y' => 456), 'size' => array('width' => 123, 'height => 456))
	 * @return \Imagine\Image\ImageInterface
	 */
	protected function cropCommand(\Imagine\Image\ImageInterface $image, array $commandOptions) {
		if (!isset($commandOptions['start'])) {

		}
		if (!isset($commandOptions['size'])) {

		}
		$startPoint = $this->parsePoint($commandOptions['start']);
		$dimensions = $this->parseBox($commandOptions['size']);
		return $image->crop($startPoint, $dimensions);
	}

	/**
	 * @param \Imagine\Image\ImageInterface $image
	 * @param array $commandOptions
	 * @return \Imagine\Image\ImageInterface
	 */
	protected function drawCommand(\Imagine\Image\ImageInterface $image, array $commandOptions) {
		$drawer = $image->draw();
		foreach($commandOptions as $drawCommandName => $drawCommandOptions) {
			if ($drawCommandName === 'ellipse') {
				$drawer = $this->drawEllipse($drawer, $drawCommandOptions);
			} elseif ($drawCommandName === 'text') {
				$drawer = $this->drawText($drawer, $drawCommandOptions);
			} else {
				throw new \InvalidArgumentException('Invalid draw command "' . $drawCommandName . '"', 1316613593);
			}
		}
		return $image;
	}

	/**
	 * @param \Imagine\Draw\DrawerInterface $drawer
	 * @param array $commandOptions
	 * @return \Imagine\Draw\DrawerInterface
	 */
	protected function drawEllipse(\Imagine\Draw\DrawerInterface $drawer, array $commandOptions) {
		$center = $this->parsePoint($commandOptions['center']);
		$size = $this->parseBox($commandOptions['size']);
		$color = $this->parseColor($commandOptions['color']);
		$fill = isset($commandOptions['fill']) ? (boolean)$commandOptions['fill'] : FALSE;
		return $drawer->ellipse($center, $size, $color, $fill);
	}

	/**
	 * @param \Imagine\Draw\DrawerInterface $drawer
	 * @param array $commandOptions
	 * @return \Imagine\Draw\DrawerInterface
	 */
	protected function drawText(\Imagine\Draw\DrawerInterface $drawer, array $commandOptions) {
		$string = $commandOptions['string'];
		$font = $this->parseFont($commandOptions['font']);
		$position = $this->parsePoint($commandOptions['position']);
		$angle = (integer)$commandOptions['angle'];
		return $drawer->text($string, $font, $position, $angle);
	}

	/**
	 * @param array $coordinates
	 * @return \Imagine\Image\Point
	 */
	protected function parsePoint($coordinates) {
		return $this->objectManager->get('Imagine\Image\Point', $coordinates['x'], $coordinates['y']);
	}

	/**
	 * @param array $dimensions
	 * @return \Imagine\Image\Box
	 */
	protected function parseBox($dimensions) {
		return $this->objectManager->get('Imagine\Image\Box', $dimensions['width'], $dimensions['height']);
	}

	/**
	 * @param array $color
	 * @return \Imagine\Image\Color
	 */
	protected function parseColor($color) {
		$alpha = isset($color['alpha']) ? (integer)$color['alpha'] : NULL;
		if ($alpha > 100) {
			$alpha = 100;
		}
		if ($alpha < 0) {
			$alpha = 0;
		}
		return $this->objectManager->get('Imagine\Image\Color', $color['color'], $alpha);
	}
	/**
	 * @param array $options
	 * @return \Imagine\Image\FontInterface
	 */
	protected function parseFont($options) {
		$file = $options['file'];
		$size = $options['size'];
		$color = $this->parseColor($options['color']);
		return $this->objectManager->get('Imagine\Image\FontInterface', $file, $size, $color);
	}
}
