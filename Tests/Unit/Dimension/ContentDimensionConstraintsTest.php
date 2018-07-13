<?php
namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\Dimension;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension values
 */
class ContentDimensionConstraintsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function combinationWithValueIsAllowedWithWildcardAllowedAndNoSpecificRestrictionsInPlace()
    {
        $subject = new Dimension\ContentDimensionConstraints(true, []);
        $this->assertSame(
            true,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    /**
     * @test
     */
    public function combinationWithValueIsDisallowedWithWildcardAllowedAndSpecificValueDisallowed()
    {
        $subject = new Dimension\ContentDimensionConstraints(true, ['value' => false]);
        $this->assertSame(
            false,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    /**
     * @test
     */
    public function combinationWithValueIsDisallowedWithWildcardDisallowedAndNoSpecificRestrictionsInPlace()
    {
        $subject = new Dimension\ContentDimensionConstraints(false, []);
        $this->assertSame(
            false,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    /**
     * @test
     */
    public function combinationWithValueIsAllowedWithWildcardDisallowedAndSpecificValueAllowed()
    {
        $subject = new Dimension\ContentDimensionConstraints(false, ['value' => true]);
        $this->assertSame(
            true,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }
}
