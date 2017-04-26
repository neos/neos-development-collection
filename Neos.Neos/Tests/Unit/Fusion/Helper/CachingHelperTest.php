<?php
namespace Neos\Neos\Tests\Unit\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\Helper\CachingHelper;

/**
 * Tests the CachingHelper
 */
class CachingHelperTest extends UnitTestCase
{
    /**
     * Provides datasets for teseting the CachingHelper::nodeTypeTag method.
     *
     * @return array
     */
    public function nodeTypeTagDataProvider()
    {
        $nodeTypeName1 = 'Neos.Neos:Foo';
        $nodeTypeName2 = 'Neos.Neos:Bar';
        $nodeTypeName3 = 'Neos.Neos:Moo';

        $nodeTypeObject1 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject1->expects(self::any())->method('getName')->willReturn($nodeTypeName1);

        $nodeTypeObject2 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject2->expects(self::any())->method('getName')->willReturn($nodeTypeName2);

        $nodeTypeObject3 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject3->expects(self::any())->method('getName')->willReturn($nodeTypeName3);

        return [
            [$nodeTypeName1, ['NodeType_' . $nodeTypeName1]],
            [[$nodeTypeName1, $nodeTypeName2, $nodeTypeName3],
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [$nodeTypeObject1, ['NodeType_' . $nodeTypeName1]],
            [[$nodeTypeName1, $nodeTypeObject2, $nodeTypeObject3],
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [(new \ArrayObject([$nodeTypeObject1, $nodeTypeObject2, $nodeTypeObject3])),
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [(object)['stdClass' => 'will do nothing'], []]
        ];
    }

    /**
     * @test
     * @dataProvider nodeTypeTagDataProvider
     *
     * @param mixed $input
     * @param array $expectedResult
     */
    public function nodeTypeTagProvidesExpectedResult($input, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($input);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
