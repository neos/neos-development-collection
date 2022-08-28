<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Unit\Dimension;

use Neos\ContentRepository\Dimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimension values
 */
class ContentDimensionConstraintsTest extends UnitTestCase
{
    public function testCombinationWithValueIsAllowedWithWildcardAllowedAndNoSpecificRestrictionsInPlace()
    {
        $subject = new Dimension\ContentDimensionConstraints(true, []);
        $this->assertSame(
            true,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    public function testCombinationWithValueIsDisallowedWithWildcardAllowedAndSpecificValueDisallowed()
    {
        $subject = new Dimension\ContentDimensionConstraints(true, ['value' => false]);
        $this->assertSame(
            false,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    public function testCombinationWithValueIsDisallowedWithWildcardDisallowedAndNoSpecificRestrictionsInPlace()
    {
        $subject = new Dimension\ContentDimensionConstraints(false, []);
        $this->assertSame(
            false,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }

    public function testCombinationWithValueIsAllowedWithWildcardDisallowedAndSpecificValueAllowed()
    {
        $subject = new Dimension\ContentDimensionConstraints(false, ['value' => true]);
        $this->assertSame(
            true,
            $subject->allowsCombinationWith(new Dimension\ContentDimensionValue('value'))
        );
    }
}
