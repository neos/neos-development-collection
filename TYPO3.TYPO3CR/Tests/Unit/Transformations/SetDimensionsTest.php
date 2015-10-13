<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Transformations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\ContentDimension;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Migration\Transformations\SetDimensions;

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
        return array(
            // single dimension, single value
            array(
                array(
                    'language' => array('en')
                ),
                array(
                    array('language' => 'en')
                )
            ),
            // single dimension, two values
            array(
                array(
                    'system' => array('iOS', 'Android')
                ),
                array(
                    array('system' => 'iOS'),
                    array('system' => 'Android')
                )
            ),
            // two dimension, single values
            array(
                array(
                    'language' => array('lv'),
                    'system' => array('Neos')
                ),
                array(
                    array('language' => 'lv'),
                    array('system' => 'Neos')
                )
            ),
            // two dimension, multiple values
            array(
                array(
                    'language' => array('lv'),
                    'system' => array('Neos', 'Flow')
                ),
                array(
                    array('language' => 'lv'),
                    array('system' => 'Neos'),
                    array('system' => 'Flow')
                )
            ),
        );
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
            $configuredDimensionObjects = array();
            foreach ($configuredDimensions as $dimensionIdentifier => $dimensionDefault) {
                $configuredDimensionObjects[] = new ContentDimension($dimensionIdentifier, $dimensionDefault);
            }

            $mockContentDimensionRepository = $this->getMockBuilder(ContentDimensionRepository::class)->getMock();
            $mockContentDimensionRepository->expects($this->atLeastOnce())->method('findAll')->will($this->returnValue($configuredDimensionObjects));
            $this->inject($transformation, 'contentDimensionRepository', $mockContentDimensionRepository);
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
                    if (!($dimension instanceof \TYPO3\TYPO3CR\Domain\Model\NodeDimension)) {
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
