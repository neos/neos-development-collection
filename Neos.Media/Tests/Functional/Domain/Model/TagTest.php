<?php
declare(strict_types=1);

namespace Neos\Media\Tests\Functional\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Tests\Functional\AbstractTest;

class TagTest extends AbstractTest
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
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->tagRepository = $this->objectManager->get(TagRepository::class);
    }

    /**
     * @test
     */
    public function parentChildrenRelation(): void
    {
        $child1 = new Tag('child1');
        $child2 = new Tag('child2');
        $parent = new Tag('parent');

        $child1->setParent($parent);
        $child2->setParent($parent);

        $this->tagRepository->add($parent);
        $this->tagRepository->add($child1);
        $this->tagRepository->add($child2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedChild = $this->tagRepository->findOneByLabel('child1');
        $persistedParent = $persistedChild->getParent();

        self::assertEquals('parent', $persistedParent->getLabel());
        self::assertNull($persistedParent->getParent());

        $children = $parent->getChildren();
        self::assertEquals(2, $children->count());
        self::assertEquals('child1', $children->offsetGet(0)->getLabel());
        self::assertEquals('child2', $children->offsetGet(1)->getLabel());
    }
}
