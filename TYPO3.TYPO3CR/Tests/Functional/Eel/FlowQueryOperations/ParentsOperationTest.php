<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Eel\FlowQueryOperations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Tests\Functional\AbstractNodeTest;

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

        $q = new FlowQuery(array($teaserText));
        $actual = iterator_to_array($q->parents('[someSpecialProperty]')->first());
        $expected = array($this->node->getNode('teaser'));

        $this->assertTrue($expected === $actual);
    }
}
