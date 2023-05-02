<?php
namespace Neos\ContentRepository\NodeAccess\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeAccess\Tests\Functional\AbstractNodeTest;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Functional test case which tests FlowQuery ParentsOperation
 */
class ParentsOperationTest extends AbstractNodeTest
{
    /**
     * @test
     */
    public function parentsFollowedByFirstMatchesInnermostNodeOnRootline()
    {
        $teaserText = $this->node->getNode('teaser/dummy42');

        $q = new FlowQuery([$teaserText]);
        $actual = iterator_to_array($q->parents('[someSpecialProperty]')->first());
        $expected = [$this->node->getNode('teaser')];

        self::assertTrue($expected === $actual);
    }
}
