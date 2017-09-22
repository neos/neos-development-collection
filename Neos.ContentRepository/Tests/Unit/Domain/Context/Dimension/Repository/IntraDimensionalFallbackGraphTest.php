<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Context\Dimension\Repository;

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
 * Test cases for the intra dimensional fallback graph
 */
class IntraDimensionalFallbackGraphTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createDimensionRegistersDimension()
    {
        /** @var Dimension\Repository\IntraDimensionalFallbackGraph $graph */
        $graph = $this->getAccessibleMock(Dimension\Repository\IntraDimensionalFallbackGraph::class, ['dummy']);
        $dimension = $graph->_call('createDimension', 'test');

        $this->assertSame($dimension, $graph->getDimension('test'));
    }
}
