<?php
namespace TYPO3\Neos\Tests\Functional\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Tests\Functional\AbstractNodeTest;


/**
 * Make sure legacy sites.xml structures (1.0 or 1.1) can be imported
 */
class LegacySiteImportServiceTest extends AbstractNodeTest {

	protected $nodeContextPath = NULL;

	protected $fixtureFileName = 'Domain/Service/Fixtures/LegacySite.xml';

	/**
	 * @test
	 */
	public function legacySiteImportYieldsExpectedResult() {
		$this->assertSame('<h1>Planned for change.</h1>', $this->getNodeWithContextPath('/sites/neosdemotypo3org/teaser/node52697bdfee199')->getProperty('title'));
	}
}
