<?php
namespace Neos\ContentRepository\Tests\Unit\Transformations;

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
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;
use Neos\ContentRepository\Migration\Transformations\SetDimensions;

/**
 * Testcase for the SetDimensions transformation
 */
class SetDimensionsTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function setDimensionsInput()
    {
        return [
            // single dimension, single value
            [
                [
                    'language' => ['en']
                ],
                [
                    ['language' => 'en']
                ]
            ],
            // single dimension, two values
            [
                [
                    'system' => ['iOS', 'Android']
                ],
                [
                    ['system' => 'iOS'],
                    ['system' => 'Android']
                ]
            ],
            // two dimension, single values
            [
                [
                    'language' => ['lv'],
                    'system' => ['Neos']
                ],
                [
                    ['language' => 'lv'],
                    ['system' => 'Neos']
                ]
            ],
            // two dimension, multiple values
            [
                [
                    'language' => ['lv'],
                    'system' => ['Neos', 'Flow']
                ],
                [
                    ['language' => 'lv'],
                    ['system' => 'Neos'],
                    ['system' => 'Flow']
                ]
            ],
        ];
    }

    /**
     * @dataProvider setDimensionsInput
     * @test
     * @param array $setValues The values passed to the transformation
     * @param array $expectedValues The values that are expected to be set on the node
     * @param array $configuredDimensions Optional set of dimensions "configured in the system"
     */
    public function setDimensionsWorksAsExpected(array $setValues, array $expectedValues, array $configuredDimensions = null)
    {
        $transformation = new SetDimensions();

        $transformation->setAddDefaultDimensionValues($configuredDimensions !== null);
        $transformation->setDimensionValues($setValues);

        if ($configuredDimensions !== null) {
            $contentDimensions = [];
            foreach ($configuredDimensions as $dimensionIdentifier => $dimensionDefault) {
                $defaultValue = new Dimension\ContentDimensionValue($dimensionDefault);
                $contentDimensions[] = new Dimension\ContentDimension(
                    new Dimension\ContentDimensionIdentifier($dimensionIdentifier),
                    [$defaultValue],
                    $defaultValue
                );
            }

            $mockContentDimensionSource = $this->getMockBuilder(Dimension\ContentDimensionSourceInterface::class)->getMock();
            $mockContentDimensionSource->expects($this->atLeastOnce())->method('getContentDimensionsOrderedByPriority')->will($this->returnValue($contentDimensions));
            $this->inject($transformation, 'contentDimensionSource', $mockContentDimensionSource);
        }

        $expected = array(
            'count' => count($expectedValues),
            'dimensions' => $expectedValues
        );

        $mockNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->once())->method('setDimensions')->with($this->callback(function (array $dimensions) use ($expected) {
            if (count($dimensions) === $expected['count']) {
                $simplifiedDimensions = array();
                foreach ($dimensions as $dimension) {
                    if (!($dimension instanceof NodeDimension)) {
                        return false;
                    }
                    $simplifiedDimensions[] = array($dimension->getName() => $dimension->getValue());
                }
                if ($expected['dimensions'] === $simplifiedDimensions) {
                    return true;
                }
            }

            return false;
        }));

        $transformation->execute($mockNode);
    }

    /**
     * @test
     */
    public function setDimensionsFillsInDefaultDimensionsAndValues()
    {
        $dimensionsToBeSet = array(
            'language' => array('lv'),
            'system' => array('Neos')
        );

        $expectedDimensions = array(
            array('language' => 'lv'),
            array('system' => 'Neos'),
            array('country' => 'New Zealand')
        );

        $configuredDimensions = array(
            'language' => 'en',
            'system' => 'Symfony',
            'country' => 'New Zealand'
        );

        $this->setDimensionsWorksAsExpected($dimensionsToBeSet, $expectedDimensions, $configuredDimensions);
    }
}
