<?php
namespace Neos\EventSourcedContentRepository\Tests\Unit\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\EventSourcedContentRepository\Domain\Context\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension values
 */
class ContentDimensionValueTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\EventSourcedContentRepository\Domain\Context\Dimension\Exception\InvalidContentDimensionValueException
     */
    public function initializationThrowsExceptionForEmptyValue()
    {
        $subject = new Dimension\ContentDimensionValue('');
    }

    /**
     * @test
     */
    public function specializationDepthDefaultsToZero()
    {
        $subject = new Dimension\ContentDimensionValue('value');

        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $subject->getSpecializationDepth()
        );
    }
}
