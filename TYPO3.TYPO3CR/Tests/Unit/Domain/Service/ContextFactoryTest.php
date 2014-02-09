<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

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
 * Testcase for the ContextFactory
 *
 */
class ContextFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createMergesDefaultPropertiesBeforeSettingAnInstanceByIdentifier() {
		$contextFactory = new \TYPO3\TYPO3CR\Domain\Service\ContextFactory();

		$mockContentDimensionRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
		$mockContentDimensionRepository->expects($this->any())->method('findAll')->will($this->returnValue(array()));
		$this->inject($contextFactory, 'contentDimensionRepository', $mockContentDimensionRepository);

		$context1 = $contextFactory->create(array());
		$context2 = $contextFactory->create(array('workspaceName' => 'live'));

		$this->assertSame($context1, $context2, 'Contexts should be re-used');
	}

}
