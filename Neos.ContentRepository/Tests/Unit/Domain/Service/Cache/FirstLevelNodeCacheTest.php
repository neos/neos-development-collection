<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Service\Cache\FirstLevelNodeCache;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the FirstLeveNodeCache
 */
class FirstLevelNodeCacheTest extends UnitTestCase
{

    /**
     * @test
     */
    public function returnNullForCachedNullValue()
    {
        $nodeIdA = 'node-id-a';
        $nodeIdB = 'node-id-b';

        $mockCache = new FirstLevelNodeCache();

        $mockCache->setByIdentifier($nodeIdA, null);

        $valueForNodeIdA = $mockCache->getByIdentifier($nodeIdA);
        $valueForNodeIdB = $mockCache->getByIdentifier($nodeIdB);

        self::assertNull($valueForNodeIdA);
        self::assertFalse($valueForNodeIdB);
    }

    /**
     * @test
     */
    public function resetCacheWorksProperly()
    {
        $nodeIdA = 'node-id-a';

        $mockCache = new FirstLevelNodeCache();

        $mockCache->setByIdentifier($nodeIdA, null);
        self::assertNull($mockCache->getByIdentifier($nodeIdA));
        $mockCache->removeNodeFromIdentifierCache($nodeIdA);
        self::assertFalse($mockCache->getByIdentifier($nodeIdA));
    }
}
