<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case for NodeTemplate
 */
class NodeTemplateTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function setNameWithValidNameUpdatesName() {
		$nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
		$nodeTemplate->setName('valid-node-name');

		$this->assertEquals('valid-node-name', $nodeTemplate->getName());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setNameWithInvalidNameThrowsException() {
		$nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
		$nodeTemplate->setName(',?/invalid-node-name');
	}

}
