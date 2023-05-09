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
use PHPUnit\Framework\TestCase;

/**
 * Test cases for content dimension identifiers
 */
class ContentDimensionIdentifierTest extends TestCase
{
    public function testInitializationThrowsExceptionForEmptyValue()
    {
        $this->expectException(Dimension\Exception\ContentDimensionIdIsInvalid::class);
        new Dimension\ContentDimensionId('');
    }
}
