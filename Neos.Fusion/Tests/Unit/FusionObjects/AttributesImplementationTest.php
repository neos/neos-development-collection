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

    public function setUp(): void
    {
        parent::setUp();
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    public function attributeExamples()
    {
        return [
            'null' => [null, ''],
            'empty array' => [[], ''],
            'boolean values' => [['booleanTrueAttribute' => true, 'booleanFalseAttribute' => false], ' booleanTrueAttribute'],
            'empty string value' => [['emptyStringAttribute' => ''], ' emptyStringAttribute'],
            'null value' => [['nullAttribute' => null], ''],
            'simple array' => [['attributeName1' => 'attributeValue1'], ' attributeName1="attributeValue1"'],
            'encoding' => [['spec<ial' => 'chara>cters'], ' spec&lt;ial="chara&gt;cters"'],
            'array attributes' => [['class' => ['icon', null, 'icon-neos', '']], ' class="icon icon-neos"'],
            'empty attribute value without allowEmpty' => [['emptyStringAttribute' => '', '__meta' => ['allowEmpty' => false]], ' emptyStringAttribute=""'],
        ];
    }

    /**
     * @test
     * @dataProvider attributeExamples
     */
    public function evaluateTests($properties, $expectedOutput)
    {
        $path = 'attributes/test';
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) use ($path, $properties) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            return ObjectAccess::getPropertyPath($properties, str_replace('/', '.', $relativePath));
        }));

        $fusionObjectName = 'Neos.Fusion:Attributes';
        $renderer = new AttributesImplementation($this->mockRuntime, $path, $fusionObjectName);
        if ($properties !== null) {
            foreach ($properties as $name => $value) {
                ObjectAccess::setProperty($renderer, $name, $value);
            }
        }

        $result = $renderer->evaluate();
        self::assertEquals($expectedOutput, $result);
    }
}
