<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeTemplate;

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

        self::assertEquals('valid-node-name', $nodeTemplate->getName());
    }

    /**
     * @test
     */
    public function setNameWithInvalidNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setName(',?/invalid-node-name');
    }
}
