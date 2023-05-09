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

namespace Neos\ContentRepository\Core\Tests\Unit\Dimension;

use Neos\ContentRepository\Core\Dimension;
use Neos\ContentRepository\Core\Dimension\Exception\ContentDimensionValueIsInvalid;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for content dimension values
 */
class ContentDimensionValueTest extends TestCase
{
    public function testInitializationThrowsExceptionForEmptyValue()
    {
        $this->expectException(ContentDimensionValueIsInvalid::class);
        new Dimension\ContentDimensionValue('');
    }

    public function testSpecializationDepthDefaultsToZero()
    {
        $subject = new Dimension\ContentDimensionValue('value');

        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $subject->specializationDepth
        );
    }
}
