<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\TagRepository;
use TYPO3\Media\Tests\Functional\AbstractTest;

/**
 * Testcase for an tag repository
 *
 */
class TagRepositoryTest extends AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->tagRepository = $this->objectManager->get(TagRepository::class);
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
        $this->assertInstanceOf(Tag::class, $this->tagRepository->findAll()->getFirst());
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
