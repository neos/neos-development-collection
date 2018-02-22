<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension identifiers
 */
class ContentDimensionIdentifierTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\ContentRepository\Domain\Context\Dimension\Exception\InvalidContentDimensionIdentifierException
     */
    public function initializationThrowsExceptionForEmptyValue()
    {
        $subject = new Dimension\ContentDimensionIdentifier('');
    }
}
