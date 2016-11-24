<?php
namespace TYPO3\Neos\Tests\Unit\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Exception;
use TYPO3\Neos\Service\HtmlAugmenter;

/**
 * Testcase for the HTML Augmenter
 *
 */
class HtmlAugmenterTest extends UnitTestCase
{
    /**
     * @var HtmlAugmenter
     */
    protected $htmlAugmenter;

    public function setUp()
    {
        $this->htmlAugmenter = new HtmlAugmenter();
    }

    /**
     * @test
     */
    public function addAttributesDoesNotAlterHtmlIfAttributesArrayIsEmpty()
    {
        $html = '<p>This is some html</p><p>Without a unique root element</p>';
        $this->assertSame($html, $this->htmlAugmenter->addAttributes($html, array()));
    }

    public function addAttributesDataProvider()
    {
        eval('
			class ClassWithToStringMethod {
				public function __toString() {
					return "casted value";
				}
			}
		');
        $mockObject = new \ClassWithToStringMethod();

        return array(
            // object values with __toString method
            array(
                'html' => '',
                'attributes' => array('object' => $mockObject),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div object="casted value"></div>'
            ),

            // empty source
            array(
                'html' => '',
                'attributes' => array('class' => 'new-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class"></div>',
            ),
            array(
                'html' => '   	' . chr(10) . '  ',
                'attributes' => array('class' => 'new-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class">   	' . chr(10) . '  </div>',
            ),

            // root element detection
            array(
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => array('class' => 'new-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p class="new-class">Simple HTML with unique root element</p>',
            ),
            array(
                'html' => '<p>Simple HTML without</p><p> unique root element</p>',
                'attributes' => array('class' => 'new-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class"><p>Simple HTML without</p><p> unique root element</p></div>',
            ),
            array(
                'html' => '<p class="some-class">Simple HTML without</p><p> unique root element</p>',
                'attributes' => array('class' => 'some-class'),
                'fallbackTagName' => 'fallback-tag',
                'exclusiveAttributes' => null,
                'expectedResult' => '<fallback-tag class="some-class"><p class="some-class">Simple HTML without</p><p> unique root element</p></fallback-tag>',
            ),

            // attribute handling
            array(
                'html' => '<root class="some-class">merging attributes</root>',
                'attributes' => array('class' => 'new-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root class="new-class some-class">merging attributes</root>',
            ),
            array(
                'html' => '<root class="some-class">similar attribute value</root>',
                'attributes' => array('class' => 'some-class'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root class="some-class">similar attribute value</root>',
            ),
            array(
                'html' => '<root data-foo="">empty attribute value</root>',
                'attributes' => array('data-bar' => null),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-bar data-foo="">empty attribute value</root>',
            ),
            array(
                'html' => '<root data-foo="">empty attribute value, overridden</root>',
                'attributes' => array('data-foo' => null),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-foo="">empty attribute value, overridden</root>',
            ),
            array(
                'html' => '<root data-foo>omitted attribute value</root>',
                'attributes' => array('data-bar' => null),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-bar data-foo>omitted attribute value</root>',
            ),
            array(
                'html' => '<root data-foo>omitted attribute value, overridden</root>',
                'attributes' => array('data-foo' => ''),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-foo="">omitted attribute value, overridden</root>',
            ),

            // attribute encoding
            array(
                'html' => '<p data-foo="&">invalid characters are encoded</p>',
                'attributes' => array('data-bar' => '<&"'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar="&lt;&amp;&quot;" data-foo="&amp;">invalid characters are encoded</p>',
            ),
            array(
                'html' => '<p data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
                'attributes' => array('data-bar' => null),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
            ),
            array(
                // the following test only records the current behavior, I'm not sure whether it is intended
                'html' => '<p data-foo="&ouml;&auml;&uuml;&szlig;">valid characters are decoded</p>',
                'attributes' => array('data-bar' => 'öäüß'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar="öäüß" data-foo="öäüß">valid characters are decoded</p>',
            ),

            // exclusive attributes
            array(
                'html' => '<p data-foo="foo">exclusive attributes force new root element</p>',
                'attributes' => array('data-foo' => 'bar'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => array('data-foo'),
                'expectedResult' => '<div data-foo="bar"><p data-foo="foo">exclusive attributes force new root element</p></div>',
            ),
            array(
                'html' => '<p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p>',
                'attributes' => array('dAtA-fOO' => 'bar'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => array('data-foo'),
                'expectedResult' => '<div dAtA-fOO="bar"><p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p></div>',
            ),
            array(
                'html' => '<div some-attribute>no attribute value is required to make an attribute exclusive</div>',
                'attributes' => array('some-attribute' => 'value'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => array('some-attribute'),
                'expectedResult' => '<div some-attribute="value"><div some-attribute>no attribute value is required to make an attribute exclusive</div></div>',
            ),
            // Escaping possible preg_replace placeholders in attributes
            array(
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => array('data-label' => 'Cost $0.00'),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-label="Cost $0.00">Simple HTML with unique root element</p>',
            )
        );
    }

    public function invalidAttributesDataProvider()
    {
        return array(
            // invalid attributes
            array(
                'html' => '',
                'attributes' => array('data-foo' => []),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root>array value ignored</root>',
            ),
            array(
                'html' => '',
                'attributes' => array('data-foo' => (object)[]),
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root>array value ignored</root>',
            ),
        );
    }

    /**
     * @param string $html
     * @param array $attributes
     * @param string $fallbackTagName
     * @param string $expectedResult
     * @param array $exclusiveAttributes
     * @test
     * @dataProvider addAttributesDataProvider
     */
    public function addAttributesTests($html, array $attributes, $fallbackTagName, $exclusiveAttributes, $expectedResult)
    {
        if ($fallbackTagName === null) {
            $fallbackTagName = 'div';
        }
        $actualResult = $this->htmlAugmenter->addAttributes($html, $attributes, $fallbackTagName, $exclusiveAttributes);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @param string $html
     * @param array $attributes
     * @param string $fallbackTagName
     * @param array $exclusiveAttributes
     * @test
     * @dataProvider invalidAttributesDataProvider
     * @expectedException \TYPO3\Neos\Exception
     */
    public function invalidAttributesTests($html, array $attributes, $fallbackTagName, $exclusiveAttributes)
    {
        $this->addAttributesTests($html, $attributes, $fallbackTagName, $exclusiveAttributes, null);
    }
}
