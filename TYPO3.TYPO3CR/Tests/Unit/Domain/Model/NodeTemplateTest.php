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
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;

/**
 * Test case for NodeTemplate
 */
class NodeTemplateTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setNameWithValidNameUpdatesName()
    {
        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setName('valid-node-name');

        $this->assertEquals('valid-node-name', $nodeTemplate->getName());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setNameWithInvalidNameThrowsException()
    {
        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setName(',?/invalid-node-name');
    }
}
