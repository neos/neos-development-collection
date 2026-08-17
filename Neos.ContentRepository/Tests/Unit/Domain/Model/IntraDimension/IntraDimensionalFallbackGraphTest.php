<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model\IntraDimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Model\IntraDimension\IntraDimensionalFallbackGraph;
use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for the intra dimensional fallback graph
 */
class IntraDimensionalFallbackGraphTest extends UnitTestCase
{
    #[Test]
    public function createDimensionRegistersDimension()
    {
        $graph = new IntraDimensionalFallbackGraph();
        $dimension = $graph->createDimension('test');

        self::assertSame($dimension, $graph->getDimension('test'));
    }
}
