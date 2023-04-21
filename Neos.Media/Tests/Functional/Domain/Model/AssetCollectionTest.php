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

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Tests\Functional\AbstractTest;

class AssetCollectionTest extends AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    public function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->assetCollectionRepository = $this->objectManager->get(AssetCollectionRepository::class);
    }

    /**
     * @test
     */
    public function parentChildrenRelation(): void
    {
        $child1 = new AssetCollection('child1');
        $child2 = new AssetCollection('child2');
        $parent = new AssetCollection('parent');

        $child1->setParent($parent);
        $child2->setParent($parent);

        $this->assetCollectionRepository->add($parent);
        $this->assetCollectionRepository->add($child1);
        $this->assetCollectionRepository->add($child2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedChild = $this->assetCollectionRepository->findOneByTitle('child1');
        $persistedParent = $persistedChild->getParent();

        self::assertEquals('parent', $persistedParent->getTitle());
        self::assertNull($persistedParent->getParent());

        $children = $this->assetCollectionRepository->findByParent($parent);
        self::assertEquals(2, $children->count());
        self::assertEquals('child1', $children->offsetGet(0)->getTitle());
        self::assertEquals('child2', $children->offsetGet(1)->getTitle());
    }

    /**
     * @test
     */
    public function circularParentChildrenRelationThrowsErrorWhenSettingParent(): void
    {
        $child = new AssetCollection('child');
        $childOfChild = new AssetCollection('childOfChild');
        $parent = new AssetCollection('parent');

        $child->setParent($parent);
        $childOfChild->setParent($child);

        $this->expectException(\InvalidArgumentException::class);
        $parent->setParent($childOfChild);
    }

    /**
     * @test
     */
    public function unsettingTheParentRemovesChildFromParent(): void
    {
        $child = new AssetCollection('child');
        $parent = new AssetCollection('parent');

        $child->setParent($parent);

        $this->assetCollectionRepository->add($parent);
        $this->assetCollectionRepository->add($child);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedChild = $this->assetCollectionRepository->findOneByTitle('child');
        $persistedChild->setParent(null);

        $this->assetCollectionRepository->update($persistedChild);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedChild = $this->assetCollectionRepository->findOneByTitle('child');
        $persistedParent = $this->assetCollectionRepository->findOneByTitle('parent');

        $children = $this->assetCollectionRepository->findByParent($persistedParent);

        self::assertNull($persistedChild->getParent());
        self::assertEquals(0, count($children));
    }

    /**
     * @test
     */
    public function deletingTheParentDeletesTheChild(): void
    {
        $child = new AssetCollection('child');
        $parent = new AssetCollection('parent');

        $child->setParent($parent);

        $this->assetCollectionRepository->add($parent);
        $this->assetCollectionRepository->add($child);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedParent = $this->assetCollectionRepository->findOneByTitle('parent');
        $this->assetCollectionRepository->remove($persistedParent);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $persistedChild = $this->assetCollectionRepository->findOneByTitle('child');
        $persistedParent = $this->assetCollectionRepository->findOneByTitle('parent');

        self::assertNull($persistedChild);
        self::assertNull($persistedParent);
    }
}
