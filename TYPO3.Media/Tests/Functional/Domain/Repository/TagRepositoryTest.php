<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Media\Domain\Model\Tag;

/**
 * Testcase for an tag repository
 *
 */
class TagRepositoryTest extends \TYPO3\Media\Tests\Functional\AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Media\Domain\Repository\TagRepository
     */
    protected $tagRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->tagRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\TagRepository');
    }

    /**
     * @test
     */
    public function tagsCanBePersisted()
    {
        $tag = new Tag('foobar');

        $this->tagRepository->add($tag);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->assertCount(1, $this->tagRepository->findAll());
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\Tag', $this->tagRepository->findAll()->getFirst());
    }

    /**
     * @test
     */
    public function findBySearchTermReturnsFilteredResult()
    {
        $tag1 = new Tag('foobar');
        $tag2 = new Tag('foo bar');
        $tag3 = new Tag('bar foo bar');

        $this->tagRepository->add($tag1);
        $this->tagRepository->add($tag2);
        $this->tagRepository->add($tag3);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(3, $this->tagRepository->findBySearchTerm('foo'));
        $this->assertCount(2, $this->tagRepository->findBySearchTerm('foo bar'));
        $this->assertCount(1, $this->tagRepository->findBySearchTerm(' foo '));
    }
}
