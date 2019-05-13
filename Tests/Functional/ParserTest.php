<?php
namespace Neos\Fusion\Afx\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Neos\Fusion\Afx\Parser\Parser;

class ParserTest extends TestCase
{

    /**
     * @test
     */
    public function shouldParseEmptyCode()
    {
        $parser = new Parser('');

        $this->assertEquals(
            [],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseBlankCode()
    {
        $parser = new Parser('    ');

        $this->assertEquals(
            [
                [
                    'type' => 'text',
                    'payload' => '    '
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleTag()
    {
        $parser = new Parser('<div></div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }



    /**
     * @test
     */
    public function shouldParseSingleSelfClosingTag()
    {
        $parser = new Parser('<div/>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleSelfClosingTagWithWhitespaces()
    {
        $parser = new Parser('<div   />');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleTagWithWhitespaces()
    {
        $parser = new Parser('<div   ></div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleSelfClosingTagWithSingleAttribute()
    {
        $parser = new Parser('<div prop="value"/>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'value',
                                    'identifier' => 'prop'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleSelfClosingTagWithMultipleAttributes()
    {
        $parser = new Parser('<div prop="value" anotherProp="Another Value"/>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'value',
                                    'identifier' => 'prop'
                                ]
                            ],
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'Another Value',
                                    'identifier' => 'anotherProp'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleSelfClosingTagWithMultipleAttributesWrappedByMultipleWhitespaces()
    {
        $parser = new Parser('<div   prop="value"    anotherProp="Another Value"  />');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'value',
                                    'identifier' => 'prop'
                                ]
                            ],
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'Another Value',
                                    'identifier' => 'anotherProp'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSpreads()
    {
        $parser = new Parser('<div {...item} />');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'spread',
                                'payload' => [
                                    'type' => 'expression',
                                    'payload' => 'item'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSpreadsAndPropsInOrder()
    {
        $parser = new Parser('<div foo="string" {...item} bar={expression} />');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'string',
                                    'identifier' => 'foo'
                                ]
                            ],
                            [
                                'type' => 'spread',
                                'payload' => [
                                    'type' => 'expression',
                                    'payload' => 'item',
                                ]
                            ],
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'expression',
                                    'payload' => 'expression',
                                    'identifier' => 'bar'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseListOfTags()
    {
        $parser = new Parser('<div></div><span></span><h1></h1>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'span',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'h1',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseListOfTagsAndTextsWithTextOutside()
    {
        $parser = new Parser('foo<div></div>bar');

        $this->assertEquals(
            [
                [
                    'type' => 'text',
                    'payload' => 'foo'
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'text',
                    'payload' => 'bar'
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseListOfTagsAndTextsWithTagsOutside()
    {
        $parser = new Parser('<div></div>foobar<span></span>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'text',
                    'payload' => 'foobar'
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'span',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseListOfTagsAndTextsWithWhitepaceOutside()
    {
        $parser = new Parser('    <div></div>    ');

        $this->assertEquals(
            [
                [
                    'type' => 'text',
                    'payload' => '    '
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'text',
                    'payload' => '    '
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function propsCanHaveDashesInTheirName()
    {
        $parser = new Parser('<div prop-1="value" prop-2="Another Value"/>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'value',
                                    'identifier' => 'prop-1'
                                ]
                            ],
                            [
                                'type' => 'prop',
                                'payload' => [
                                    'type' => 'string',
                                    'payload' => 'Another Value',
                                    'identifier' => 'prop-2'
                                ]
                            ]
                        ],
                        'children' => [],
                        'selfClosing' => true
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleTagWithSeparateClosingTag()
    {
        $parser = new Parser('<div></div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseSingleTagWithSeparateClosingTagAndOneChild()
    {
        $parser = new Parser('<div>Hello World!</div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [
                            [
                                'type' => 'text',
                                'payload' => 'Hello World!'
                            ]
                        ],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseNestedSelfClosingTag()
    {
        $parser = new Parser('<div><input/></div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'input',
                                    'attributes' => [],
                                    'children' => [],
                                    'selfClosing' => true
                                ]
                            ]
                        ],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseNestedTags()
    {
        $parser = new Parser('<article><header><div>Header</div></header><div>Content</div><footer><div>Footer</div></footer></article>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'article',
                        'attributes' => [],
                        'children' => [
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'header',
                                    'attributes' => [],
                                    'children' => [
                                        [
                                            'type' => 'node',
                                            'payload' => [
                                                'identifier' => 'div',
                                                'attributes' => [],
                                                'children' => [
                                                    [
                                                        'type' => 'text',
                                                        'payload' => 'Header'
                                                    ]
                                                ],
                                                'selfClosing' => false
                                            ]
                                        ]
                                    ],
                                    'selfClosing' => false
                                ]
                            ],
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'div',
                                    'attributes' => [],
                                    'children' => [
                                        [
                                            'type' => 'text',
                                            'payload' => 'Content'
                                        ]
                                    ],
                                    'selfClosing' => false
                                ]
                            ],
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'footer',
                                    'attributes' => [],
                                    'children' => [
                                        [
                                            'type' => 'node',
                                            'payload' => [
                                                'identifier' => 'div',
                                                'attributes' => [],
                                                'children' => [
                                                    [
                                                        'type' => 'text',
                                                        'payload' => 'Footer'
                                                    ]
                                                ],
                                                'selfClosing' => false
                                            ]
                                        ]
                                    ],
                                    'selfClosing' => false
                                ]
                            ]
                        ],
                        'selfClosing' => false
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldHandleWhitespace()
    {
        $parser = new Parser('   <div>
							<input/>
					<label>Some

					Text</label>
							     </div>   ');

        $this->assertEquals(
            [
                [
                    'type' => 'text',
                    'payload' => '   '
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [
                            [
                                'type' => 'text',
                                'payload' => '
							'
                            ],
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'input',
                                    'attributes' => [],
                                    'children' => [],
                                    'selfClosing' => true
                                ]
                            ],
                            [
                                'type' => 'text',
                                'payload' => '
					'
                            ],
                            [
                                'type' => 'node',
                                'payload' => [
                                    'identifier' => 'label',
                                    'attributes' => [],
                                    'children' => [
                                        [
                                            'type' => 'text',
                                            'payload' => 'Some

					Text'
                                        ]
                                    ],
                                    'selfClosing' => false
                                ]
                            ],
                            [
                                'type' => 'text',
                                'payload' => '
							     '
                            ]
                        ],
                        'selfClosing' => false
                    ]
                ],
                [
                    'type' => 'text',
                    'payload' => '   '
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedTag()
    {
        $parser = new Parser('<div');
        $parser->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedTagWithContent()
    {
        $parser = new Parser('<div>foo');
        $parser->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedStringAttribute()
    {
        $parser = new Parser('<div foo="bar />');
        $parser->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedAttributeExpression()
    {
        $parser = new Parser('<div foo={bar() />');
        $parser->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedContentExpression()
    {
        $parser = new Parser('<div>{bar()</div>');
        $parser->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Parser\Exception
     */
    public function shouldThrowExceptionForUnclosedSpreadExpression()
    {
        $parser = new Parser('<div {...bar() />');
        $parser->parse();
    }
}
