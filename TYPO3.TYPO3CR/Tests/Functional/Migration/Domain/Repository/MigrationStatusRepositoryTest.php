<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Migration\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus;
use TYPO3\TYPO3CR\Migration\Domain\Repository\MigrationStatusRepository;

/**
 */
class MigrationStatusRepositoryTest extends FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var MigrationStatusRepository
	 */
	protected $repository;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->repository = $this->objectManager->get('TYPO3\TYPO3CR\Migration\Domain\Repository\MigrationStatusRepository');
	}

	/**
	 * @test
	 */
	public function findAllReturnsResultsInAscendingVersionOrder() {
		$this->repository->add(new MigrationStatus('zyx', 'direction', new \DateTime()));
		$this->repository->add(new MigrationStatus('abc', 'direction', new \DateTime()));
		$this->repository->add(new MigrationStatus('mnk', 'direction', new \DateTime()));

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$expectedVersionOrder = array('abc', 'mnk', 'zyx');

		/** @var MigrationStatus $status */
		$i = 0;
		foreach ($this->repository->findAll() as $status) {
			$this->assertEquals($expectedVersionOrder[$i], $status->getVersion());
			$i++;
		}
	}

}
