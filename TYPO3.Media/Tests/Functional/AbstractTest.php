<?php
namespace TYPO3\Media\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Abstract Functional Test template
 */
abstract class AbstractTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * Creates an Image object from a file using a mock resource (in order to avoid a database resource pointer entry)
	 * @param string $imagePathAndFilename
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	protected function getMockResourceByImagePath($imagePathAndFilename) {
		$imagePathAndFilename = \TYPO3\Flow\Utility\Files::getUnixStylePath($imagePathAndFilename);
		$hash = sha1_file($imagePathAndFilename);
		copy($imagePathAndFilename, 'resource://' . $hash);
		return $mockResource = $this->createMockResourceAndPointerFromHash($hash);
	}

	/**
	 * Creates a mock ResourcePointer and Resource from a given hash.
	 * Make sure that a file representation already exists, e.g. with
	 * file_put_content('resource://' . $hash) before
	 *
	 * @param string $hash
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	protected function createMockResourceAndPointerFromHash($hash) {
		$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);

		$mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array('getResourcePointer'));
		$mockResource->expects($this->any())
				->method('getResourcePointer')
				->will($this->returnValue($resourcePointer));
		return $mockResource;
	}

}
?>