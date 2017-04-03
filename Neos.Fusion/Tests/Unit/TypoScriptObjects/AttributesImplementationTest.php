<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\AttributesImplementation;

/**
 * Testcase for the Fusion Attributes object
 */
class AttributesImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime
     */
    protected $mockRuntime;

    public function setUp()
    {
        parent::setUp();
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
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
        $this->mockRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) use ($path, $properties) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            return ObjectAccess::getPropertyPath($properties, str_replace('/', '.', $relativePath));
        }));

        $typoScriptObjectName = 'Neos.Fusion:Attributes';
        $renderer = new AttributesImplementation($this->mockRuntime, $path, $typoScriptObjectName);
        if ($properties !== null) {
            foreach ($properties as $name => $value) {
                ObjectAccess::setProperty($renderer, $name, $value);
            }
        }

        $result = $renderer->evaluate();
        $this->assertEquals($expectedOutput, $result);
    }
}
