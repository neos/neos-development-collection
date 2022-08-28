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
 * Test cases for content dimension identifiers
 */
class ContentDimensionIdentifierTest extends UnitTestCase
{
    public function testInitializationThrowsExceptionForEmptyValue()
    {
        $this->expectException(Dimension\Exception\ContentDimensionIdentifierIsInvalid::class);
        new Dimension\ContentDimensionIdentifier('');
    }
}
