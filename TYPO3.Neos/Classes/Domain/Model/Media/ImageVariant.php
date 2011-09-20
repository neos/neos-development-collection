<?php
namespace TYPO3\TYPO3\Domain\Model\Media;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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
 * Domain Model of an Image variant
 *
 * @valueobject
 * @scope prototype
 */
class ImageVariant {

	/**
	 * @var string
	 */
	protected $image;

	/**
	 * @var array
	 */
	protected $processingInstructions;

	/**
	 * @param \TYPO3\TYPO3\Domain\Model\Media\Image $image
	 * @param array $processingInstructions
	 */
	public function __construct($image, array $processingInstructions) {
		if (is_object($image)) {
			$persistenceManager = \TYPO3\FLOW3\Core\Bootstrap::$staticObjectManager->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface'); // HACK!!!
			$this->image = $persistenceManager->getIdentifierByObject($image);
		} else {
			$this->image = $image;
		}
		$this->processingInstructions = $processingInstructions;
	}

	public function getImage() {
		return $this->image;
	}

	public function getResource() {
		$persistenceManager = \TYPO3\FLOW3\Core\Bootstrap::$staticObjectManager->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface'); // HACK!!!
		$image = $persistenceManager->getObjectByIdentifier($this->image, '\TYPO3\TYPO3\Domain\Model\Media\Image');
		return $image->getResource();
	}

	public function getProcessingInstructions() {
		return $this->processingInstructions;
	}
}
?>