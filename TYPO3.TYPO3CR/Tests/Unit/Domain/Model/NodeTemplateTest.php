<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Test case for NodeTemplate
 */
class NodeTemplateTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function setNameWithValidNameUpdatesName()
    {
        $nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $nodeTemplate->setName('valid-node-name');

        $this->assertEquals('valid-node-name', $nodeTemplate->getName());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setNameWithInvalidNameThrowsException()
    {
        $nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $nodeTemplate->setName(',?/invalid-node-name');
    }
}
