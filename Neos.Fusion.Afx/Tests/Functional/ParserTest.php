<?php
namespace Neos\Fusion\Afx\Tests\Functional;

use Neos\Fusion\Afx\Parser\AfxParserException;
use Neos\Fusion\Afx\Parser\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    /**
     * @test
     */
    public function shouldParseEmptyCode(): void
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
    public function shouldParseBlankCode(): void
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
    public function shouldParseSingleTag(): void
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
    public function shouldParseSingleTagWithContent(): void
    {
        $parser = new Parser('<div>test</div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [
                            0 => [
                                'type' => 'text',
                                'payload' => 'test'
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
    public function shouldParseSingleTagWithZeroAsContent(): void
    {
        $parser = new Parser('<div>0</div>');

        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'children' => [
                            0 => [
                                'type' => 'text',
                                'payload' => '0'
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
    public function shouldParseSingleSelfClosingTag(): void
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
    public function shouldParseSingleSelfClosingTagWithWhitespaces(): void
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
    public function shouldParseSingleTagWithWhitespaces(): void
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
    public function shouldParseSingleSelfClosingTagWithSingleAttribute(): void
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
    public function shouldParseSingleSelfClosingTagWithMultipleAttributes(): void
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
    public function shouldParseSingleSelfClosingTagWithMultipleAttributesWrappedByMultipleWhitespaces(): void
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
    public function shouldParseSpreads(): void
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
    public function shouldParseSpreadsAndPropsInOrder(): void
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
    public function shouldParseListOfTags(): void
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
    public function shouldParseListOfTagsAndTextsWithTextOutside(): void
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
    public function shouldParseListOfTagsAndTextsWithTagsOutside(): void
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
    public function shouldParseListOfTagsAndTextsWithWhitepaceOutside(): void
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
    public function propsCanHaveDashesInTheirName(): void
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
    public function shouldParseSingleTagWithSeparateClosingTag(): void
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
    public function shouldParseSingleTagWithSeparateClosingTagAndOneChild(): void
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
    public function shouldParseNestedSelfClosingTag(): void
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
    public function shouldParseNestedTags(): void
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
    public function shouldHandleWhitespace(): void
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
     */
    public function shouldParseComments(): void
    {
        $parser = new Parser('<!-- lorem ipsum -->');
        $this->assertEquals(
            [
                [
                    'type' => 'comment',
                    'payload' => ' lorem ipsum '
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldIgnoreTagsAndExpressionsInComments(): void
    {
        $parser = new Parser('<!-- <foo>{bar}</foo> -->');
        $this->assertEquals(
            [
                [
                    'type' => 'comment',
                    'payload' => ' <foo>{bar}</foo> '
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseCommentsBeforeContent(): void
    {
        $parser = new Parser('<!--lorem ipsum--><div />');
        $this->assertEquals(
            [
                [
                    'type' => 'comment',
                    'payload' => 'lorem ipsum'
                ],
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'selfClosing' => true,
                        'children' => []
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseCommentsAfterContent(): void
    {
        $parser = new Parser('<div/><!--lorem ipsum-->');
        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'selfClosing' => true,
                        'children' => []
                    ]
                ],
                [
                    'type' => 'comment',
                    'payload' => 'lorem ipsum'
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldParseCommentsInsideContent(): void
    {
        $parser = new Parser('<div><!--lorem ipsum--></div>');
        $this->assertEquals(
            [
                [
                    'type' => 'node',
                    'payload' => [
                        'identifier' => 'div',
                        'attributes' => [],
                        'selfClosing' => false,
                        'children' => [
                            [
                                'type' => 'comment',
                                'payload' => 'lorem ipsum'
                            ]
                        ]
                    ]
                ]
            ],
            $parser->parse()
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedTag(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedTagWithContent(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div>foo');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedStringAttribute(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div foo="bar />');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedAttributeExpression(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div foo={bar() />');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedContentExpression(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div>{bar()</div>');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForUnclosedSpreadExpression(): void
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div {...bar() />');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForWronglyStartedComment()
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div><! foo --></div>');
        $parser->parse();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForCommentWithoutProperEnd()
    {
        $this->expectException(AfxParserException::class);
        $parser = new Parser('<div><!-- foo </div>');
        $parser->parse();
    }
}
