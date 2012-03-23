<?php
namespace TYPO3\TYPO3\Tests\Unit\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the File TypoScript Object
 *
 */
class FileTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function renderReturnsFileContent() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
		file_put_contents('vfs://Foo/Bar.txt', 'expected content');

		$file = new \TYPO3\TYPO3\TypoScript\File();
		$file->setPathAndFilename('vfs://Foo/Bar.txt');
		$this->assertEquals('expected content', $file->render());
	}

	/**
	 * @test
	 */
	public function renderReturnsErrorMessageIfFileDoesNotExist() {
		$file = new \TYPO3\TYPO3\TypoScript\File();
		$file->setPathAndFilename('thisdoesnotexist');
		$this->assertEquals('WARNING: File "' . $file->getPathAndFilename() . '" not found.', $file->render());
	}

}
?>