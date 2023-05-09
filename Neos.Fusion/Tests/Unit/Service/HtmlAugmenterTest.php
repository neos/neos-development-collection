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
                'allowEmpty' => true,
                'expectedResult' => '<div object="casted value"></div>'
            ],

            // empty source
            [
                'html' => '',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<div class="new-class"></div>',
            ],
            [
                'html' => '   	' . chr(10) . '  ',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<div class="new-class">   	' . chr(10) . '  </div>',
            ],

            // plaintext source
            [
                'html' => 'Plain Text without html',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<div class="some-class">Plain Text without html</div>',
            ],

            // root element detection
            [
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p class="new-class">Simple HTML with unique root element</p>',
            ],
            [
                'html' => '<p>Simple HTML without</p><p> unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<div class="new-class"><p>Simple HTML without</p><p> unique root element</p></div>',
            ],
            [
                'html' => 'Plain text and simple HTML without<p> unique root element</p>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<div class="new-class">Plain text and simple HTML without<p> unique root element</p></div>',
            ],
            [
                'html' => '   <p>Simple HTML with unique root element in whitespace</p>   ',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => 'fallback-tag',
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '   <p class="some-class">Simple HTML with unique root element in whitespace</p>   ',
            ],
            [
                'html' => '<p class="some-class">Simple HTML without</p><p> unique root element</p>',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => 'fallback-tag',
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<fallback-tag class="some-class"><p class="some-class">Simple HTML without</p><p> unique root element</p></fallback-tag>',
            ],
            [
                'html' => '<script>console.log("Script tag with unique root element");</script>',
                'attributes' => ['type' => 'new-type'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<script type="new-type">console.log("Script tag with unique root element");</script>',
            ],

            // attribute handling
            [
                'html' => '<root class="some-class">merging attributes</root>',
                'attributes' => ['class' => 'new-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<root class="new-class some-class">merging attributes</root>',
            ],
            [
                'html' => '<root class="some-class">similar attribute value</root>',
                'attributes' => ['class' => 'some-class'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<root class="some-class">similar attribute value</root>',
            ],
            [
                'html' => '<root data-foo="">empty attribute value</root>',
                'attributes' => ['data-bar' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<root data-bar data-foo>empty attribute value</root>',
            ],
            [
                'html' => '<root data-foo="">empty attribute value, overridden</root>',
                'attributes' => ['data-foo' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<root data-foo="">empty attribute value, overridden</root>',
            ],
            [
                'html' => '<root data-foo>omitted attribute value</root>',
                'attributes' => ['data-bar' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<root data-bar data-foo>omitted attribute value</root>',
            ],
            [
                'html' => '<root data-foo>omitted attribute value, overridden</root>',
                'attributes' => ['data-foo' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<root data-foo>omitted attribute value, overridden</root>',
            ],

            // attribute encoding
            [
                'html' => '<p data-foo="&">invalid characters are encoded</p>',
                'attributes' => ['data-bar' => '<&"'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bar="&lt;&amp;&quot;" data-foo="&amp;">invalid characters are encoded</p>',
            ],
            [
                'html' => '<p data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
                'attributes' => ['data-bar' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bar data-foo="&quot;&gt;&amp;">encoded entities are preserved</p>',
            ],
            [
                // the following test only records the current behavior, I'm not sure whether it is intended
                'html' => '<p data-foo="&ouml;&auml;&uuml;&szlig;">valid characters are decoded</p>',
                'attributes' => ['data-bar' => 'öäüß'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bar="öäüß" data-foo="öäüß">valid characters are decoded</p>',
            ],
            [
                'html' => '<p data-foo="öäüß🦆">valid characters are untouched</p>',
                'attributes' => ['data-bar' => 'öäüß🦆'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bar="öäüß🦆" data-foo="öäüß🦆">valid characters are untouched</p>',
            ],

            // exclusive attributes
            [
                'html' => '<p data-foo="foo">exclusive attributes force new root element</p>',
                'attributes' => ['data-foo' => 'bar'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['data-foo'],
                'allowEmpty' => true,
                'expectedResult' => '<div data-foo="bar"><p data-foo="foo">exclusive attributes force new root element</p></div>',
            ],
            [
                'html' => '<p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p>',
                'attributes' => ['dAtA-fOO' => 'bar'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['data-foo'],
                'allowEmpty' => true,
                'expectedResult' => '<div dAtA-fOO="bar"><p DaTa-Foo="foo">exclusive attributes are checked case insensitive</p></div>',
            ],
            [
                'html' => '<div some-attribute>no attribute value is required to make an attribute exclusive</div>',
                'attributes' => ['some-attribute' => 'value'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => ['some-attribute'],
                'allowEmpty' => true,
                'expectedResult' => '<div some-attribute="value"><div some-attribute>no attribute value is required to make an attribute exclusive</div></div>',
            ],
            // Escaping possible preg_replace placeholders in attributes
            [
                'html' => '<p>Simple HTML with unique root element</p>',
                'attributes' => ['data-label' => 'Cost $0.00'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-label="Cost $0.00">Simple HTML with unique root element</p>',
            ],
            // Adding of empty string attributes
            [
                'html' => '<p>Empty attribute</p>',
                'attributes' => ['data-att' => ''],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-att>Empty attribute</p>',
            ],
            // Adding of empty string attributes
            [
                'html' => '<p>Empty attribute</p>',
                'attributes' => ['data-att' => ''],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<p data-att="">Empty attribute</p>',
            ],
            // Adding of boolean attributes
            [
                'html' => '<p>Boolean attribute</p>',
                'attributes' => ['data-bool' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bool>Boolean attribute</p>',
            ],
            [
                'html' => '<p>Boolean attribute</p>',
                'attributes' => ['data-bool' => true],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<p data-bool="">Boolean attribute</p>',
            ],
            [
                'html' => '<p>Boolean attribute</p>',
                'attributes' => ['data-bool' => false],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p>Boolean attribute</p>',
            ],
            [
                'html' => '<p>Boolean attribute</p>',
                'attributes' => ['data-bool' => false],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<p>Boolean attribute</p>',
            ],
            // Adding of null attributes
            [
                'html' => '<p>Null attribute</p>',
                'attributes' => ['data-null' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p>Null attribute</p>',
            ],
            [
                'html' => '<p>Null attribute</p>',
                'attributes' => ['data-null' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<p>Null attribute</p>',
            ],
            // Adding of Stringable attributes
            [
                'html' => '<p>Stringable attribute</p>',
                'attributes' => ['data-stringable' => new class {
                    public function __toString(): string
                    {
                        return 'toString';
                    }
                }],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-stringable="toString">Stringable attribute</p>',
            ],
            [
                'html' => '<p>Stringable attribute</p>',
                'attributes' => ['data-stringable' => new class {
                    public function __toString(): string
                    {
                        return 'toString';
                    }
                }],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => false,
                'expectedResult' => '<p data-stringable="toString">Stringable attribute</p>',
            ],
            // Adding of array attributes
            [
                'html' => '<p>Array attribute</p>',
                'attributes' => ['class' => ["Hello", "world", new class {
                    public function __toString(){
                        return "toString";
                    }
                }]],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p class="Hello world toString">Array attribute</p>',
            ],
            [
                'html' => '<p>Array attribute</p>',
                'attributes' => ['class' => []],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p class>Array attribute</p>',
            ],

            // https://github.com/neos/neos-development-collection/issues/3582
            'empty string rendered as empty attribute' => [
                'html' => '<p>text</p>',
                'attributes' => ['data-bla' => ''],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bla>text</p>',
            ],

            // https://github.com/neos/neos-development-collection/issues/4213
            'null should not remove existing attribute' => [
                'html' => '<p data-bla>text</p>',
                'attributes' => ['data-bla' => null],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bla>text</p>',
            ],

            'apply empty string on existing empty attribute' => [
                'html' => '<p data-bla>text</p>',
                'attributes' => ['data-bla' => ''],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bla>text</p>',
            ],

            'apply string on existing empty attribute' => [
                'html' => '<p data-bla>text</p>',
                'attributes' => ['data-bla' => 'foobar'],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p data-bla="foobar">text</p>',
            ],

            'false removes attribute' => [
                'html' => '<p data-bla>text</p>',
                'attributes' => ['data-bla' => false],
                'fallbackTagName' => null,
                'exclusiveAttributes' => null,
                'allowEmpty' => true,
                'expectedResult' => '<p>text</p>',
            ]
        ];
    }

    public function invalidAttributesDataProvider()
    {
        return [
            // invalid attributes
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
     * @param bool $allowEmpty
     * @param array $exclusiveAttributes
     * @test
     * @dataProvider addAttributesDataProvider
     */
    public function addAttributesTests($html, array $attributes, $fallbackTagName, $exclusiveAttributes, $allowEmpty, $expectedResult)
    {
        if ($fallbackTagName === null) {
            $fallbackTagName = 'div';
        }
        $actualResult = $this->htmlAugmenter->addAttributes($html, $attributes, $fallbackTagName, $exclusiveAttributes, $allowEmpty);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @param string $html
     * @param array $attributes
     * @param string $fallbackTagName
     * @param array $exclusiveAttributes
     * @param bool $allowEmpty
     * @test
     * @dataProvider invalidAttributesDataProvider
     */
    public function invalidAttributesTests($html, array $attributes, $fallbackTagName, $exclusiveAttributes, $allowEmpty)
    {
        $this->expectException(\Error::class);
        $this->addAttributesTests($html, $attributes, $fallbackTagName, $exclusiveAttributes, $allowEmpty, null);
    }
}
