<?php
namespace Neos\Neos\Tests\Functional\Domain\Service\Fixtures;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\DefaultPrototypeGeneratorInterface;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeType;

/**
 * A testable prototype generator
 *
 * @Flow\Scope("singleton")
 */
class TestablePrototypeGenerator implements DefaultPrototypeGeneratorInterface
{
    /**
     * @var int
     */
    protected $callCount = 0;

    /**
     * @return void
     */
    public function reset()
    {
        $this->callCount = 0;
    }

    /**
     * Generate nothing but just count the method call
     *
     * @return string
     */
    public function generate(NodeType $nodeType)
    {
        $this->callCount++;

        return '';
    }

    /**
     * @return int
     */
    public function getCallCount()
    {
        return $this->callCount;
    }
}
