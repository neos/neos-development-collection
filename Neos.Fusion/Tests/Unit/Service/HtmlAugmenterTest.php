<?php
namespace Neos\Fusion\Tests\Unit\Service;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Service\HtmlAugmenter;
use Neos\Neos\Exception;

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

    public function setUp(): void
    {
        $this->htmlAugmenter = new HtmlAugmenter();
    }

    /**
     * @test
     */
    public function addAttributesDoesNotAlterHtmlIfAttributesArrayIsEmpty()
    {
        $html = '<p>This is some html</p><p>Without a unique root element</p>';
        self::assertSame($html, $this->htmlAugmenter->addAttributes($html, []));
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
        /** @noinspection PhpUndefinedClassInspection */
        $mockObject = new \ClassWithToStringMethod();

        return [
            // object values with __toString method
            [
                'html' => '',
                'attributes' => ['object' => $mockObject],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div object="casted value"></div>'
            ],

            // empty source
            [
                'html' => '',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class"></div>',
            ],
            [
                'html' => '   	' . chr(10) . '  ',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class">   	' . chr(10) . '  </div>',
            ],

            // plaintext source
            [
                'html' => 'Plain Text without html',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="some-class">Plain Text without html</div>',
            ],

            // root element detection
            [
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p class="new-class">Simple HTML with unique root element</p>',
            ],
            [
                'html' => '<p>Simple HTML without</p><p> unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class"><p>Simple HTML without</p><p> unique root element</p></div>',
            ],
            [
                'html' => 'Plain text and simple HTML without<p> unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<div class="new-class">Plain text and simple HTML without<p> unique root element</p></div>',
            ],
            [
                'html' => '   <p>Simple HTML with unique root element in whitespace</p>   ',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => 'fallback-tag',
                'exclusiveAttributes' => null,
                'expectedResult' => '   <p class="some-class">Simple HTML with unique root element in whitespace</p>   ',
            ],
            [
                'html' => '<p class="some-class">Simple HTML without</p><p> unique root element</p>',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => 'fallback-tag',
                'exclusiveAttributes' => null,
                'expectedResult' => '<fallback-tag class="some-class"><p class="some-class">Simple HTML without</p><p> unique root element</p></fallback-tag>',
            ],
            [
                'html' => '<script>console.log("Script tag with unique root element");</script>',
                'attributes' => ['type' => 'new-type'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<script type="new-type">console.log("Script tag with unique root element");</script>',
            ],

            // attribute handling
            [
                'html' => '<root class="some-class">merging attributes</root>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root class="new-class some-class">merging attributes</root>',
            ],
            [
                'html' => '<root class="some-class">similar attribute value</root>',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root class="some-class">similar attribute value</root>',
            ],
            [
                'html' => '<root data-foo="">empty attribute value</root>',
                'attributes' => ['data-bar' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-bar data-foo="">empty attribute value</root>',
            ],
            [
                'html' => '<root data-foo="">empty attribute value, overridden</root>',
                'attributes' => ['data-foo' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-foo="">empty attribute value, overridden</root>',
            ],
            [
                'html' => '<root data-foo>omitted attribute value</root>',
                'attributes' => ['data-bar' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-bar data-foo>omitted attribute value</root>',
            ],
            [
                'html' => '<root data-foo>omitted attribute value, overridden</root>',
                'attributes' => ['data-foo' => ''],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root data-foo="">omitted attribute value, overridden</root>',
            ],

            // attribute encoding
            [
                'html' => '<p data-foo="&">invalid characters are encoded</p>',
                'attributes' => ['data-bar' => '<&"'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar="&lt;&amp;&quot;" data-foo="&amp;">invalid characters are encoded</p>',
            ],
            [
                'html' => '<p data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
                'attributes' => ['data-bar' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
            ],
            [
                // the following test only records the current behavior, I'm not sure whether it is intended
                'html' => '<p data-foo="&ouml;&auml;&uuml;&szlig;">valid characters are decoded</p>',
                'attributes' => ['data-bar' => 'öäüß'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar="öäüß" data-foo="öäüß">valid characters are decoded</p>',
            ],
            [
                'html' => '<p data-foo="öäüß🦆">valid characters are untouched</p>',
                'attributes' => ['data-bar' => 'öäüß🦆'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-bar="öäüß🦆" data-foo="öäüß🦆">valid characters are untouched</p>',
            ],

            // exclusive attributes
            [
                'html' => '<p data-foo="foo">exclusive attributes force new root element</p>',
                'attributes' => ['data-foo' => 'bar'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['data-foo'],
                'expectedResult' => '<div data-foo="bar"><p data-foo="foo">exclusive attributes force new root element</p></div>',
            ],
            [
                'html' => '<p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p>',
                'attributes' => ['dAtA-fOO' => 'bar'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['data-foo'],
                'expectedResult' => '<div dAtA-fOO="bar"><p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p></div>',
            ],
            [
                'html' => '<div some-attribute>no attribute value is required to make an attribute exclusive</div>',
                'attributes' => ['some-attribute' => 'value'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['some-attribute'],
                'expectedResult' => '<div some-attribute="value"><div some-attribute>no attribute value is required to make an attribute exclusive</div></div>',
            ],
            // Escaping possible preg_replace placeholders in attributes
            [
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => ['data-label' => 'Cost $0.00'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<p data-label="Cost $0.00">Simple HTML with unique root element</p>',
            ]
        ];
    }

    public function invalidAttributesDataProvider()
    {
        return [
            // invalid attributes
            [
                'html' => '',
                'attributes' => ['data-foo' => []],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root>array value ignored</root>',
            ],
            [
                'html' => '',
                'attributes' => ['data-foo' => (object)[]],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'expectedResult' => '<root>array value ignored</root>',
            ],
        ];
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
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @param string $html
     * @param array $attributes
     * @param string $fallbackTagName
     * @param array $exclusiveAttributes
     * @test
     * @dataProvider invalidAttributesDataProvider
     */
    public function invalidAttributesTests($html, array $attributes, $fallbackTagName, $exclusiveAttributes)
    {
        $this->expectException(Exception::class);
        $this->addAttributesTests($html, $attributes, $fallbackTagName, $exclusiveAttributes, null);
    }
}
