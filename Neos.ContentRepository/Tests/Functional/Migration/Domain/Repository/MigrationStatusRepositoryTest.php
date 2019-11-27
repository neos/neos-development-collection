<?php
namespace Neos\ContentRepository\Tests\Functional\Migration\Domain\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Migration\Domain\Model\MigrationStatus;
use Neos\ContentRepository\Migration\Domain\Repository\MigrationStatusRepository;

/**
 */
class MigrationStatusRepositoryTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var MigrationStatusRepository
     */
    protected $repository;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->objectManager->get(MigrationStatusRepository::class);
    }

    /**
     * @test
     */
    public function findAllReturnsResultsInAscendingVersionOrder()
    {
        $this->repository->add(new MigrationStatus('zyx', MigrationStatus::DIRECTION_DOWN, new \DateTime()));
        $this->repository->add(new MigrationStatus('abc', MigrationStatus::DIRECTION_UP, new \DateTime()));
        $this->repository->add(new MigrationStatus('mnk', MigrationStatus::DIRECTION_DOWN, new \DateTime()));

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $expectedVersionOrder = ['abc', 'mnk', 'zyx'];

        /** @var MigrationStatus $status */
        $i = 0;
        foreach ($this->repository->findAll() as $status) {
            self::assertEquals($expectedVersionOrder[$i], $status->getVersion());
            $i++;
        }
    }
}
