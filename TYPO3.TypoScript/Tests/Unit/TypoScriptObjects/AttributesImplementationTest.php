<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Reflection\ObjectAccess;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\AttributesImplementation;

/**
 * Testcase for the TypoScript Attributes object
 */
class AttributesImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime
     */
    protected $mockTsRuntime;

    public function setUp()
    {
        parent::setUp();
        $this->mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    public function attributeExamples()
    {
        return array(
            'null' => array(null, ''),
            'empty array' => array(array(), ''),
            'boolean values' => array(array('booleanTrueAttribute' => true, 'booleanFalseAttribute' => false), ' booleanTrueAttribute'),
            'empty string value' => array(array('emptyStringAttribute' => ''), ' emptyStringAttribute'),
            'null value' => array(array('nullAttribute' => null), ''),
            'simple array' => array(array('attributeName1' => 'attributeValue1'), ' attributeName1="attributeValue1"'),
            'encoding' => array(array('spec<ial' => 'chara>cters'), ' spec&lt;ial="chara&gt;cters"'),
            'array attributes' => array(array('class' => array('icon', null, 'icon-neos', '')), ' class="icon icon-neos"'),
            'empty attribute value without allowEmpty' => array(array('emptyStringAttribute' => '', '__meta' => array('allowEmpty' => false)), ' emptyStringAttribute=""'),
        );
    }

    /**
     * @test
     * @dataProvider attributeExamples
     */
    public function evaluateTests($properties, $expectedOutput)
    {
        $path = 'attributes/test';
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) use ($path, $properties) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            return ObjectAccess::getPropertyPath($properties, str_replace('/', '.', $relativePath));
        }));

        $typoScriptObjectName = 'TYPO3.TypoScript:Attributes';
        $renderer = new AttributesImplementation($this->mockTsRuntime, $path, $typoScriptObjectName);
        if ($properties !== null) {
            foreach ($properties as $name => $value) {
                ObjectAccess::setProperty($renderer, $name, $value);
            }
        }

        $result = $renderer->evaluate();
        $this->assertEquals($expectedOutput, $result);
    }
}
