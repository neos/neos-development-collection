<?php
namespace TYPO3\Neos\Tests\Functional\Domain\Service\Fixtures;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Service\DefaultPrototypeGeneratorInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

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
