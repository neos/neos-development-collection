<?php
namespace Neos\Fusion\Tests\Unit\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Exception;
use Neos\Fusion\FusionObjects\JoinImplementation;

/**
 * Testcase for the Fusion Parser
 */
class ParserTest extends UnitTestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * Sets up this test case
     *
     */
    public function setUp(): void
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('isRegistered')->will(self::returnCallback([$this, 'objectManagerIsRegisteredCallback']));

        $this->parser = $this->getAccessibleMock(Parser::class, ['dummy']);
        $this->parser->_set('objectManager', $this->mockObjectManager);
    }

    /**
     * call back for mocking the object factory
     * @return object fixture objects ...
     */
    public function objectManagerCallback()
    {
        $arguments = array_merge(func_get_args(), [$this->mockObjectManager]);
        $objectName = array_shift($arguments);

        $class = new \ReflectionClass($objectName);
        return ($class->getConstructor() !== null) ? $class->newInstanceArgs($arguments) : $class->newInstance();
    }

    /**
     * Call back for mocking the object manager's isRegistered() method
     * @return boolean
     */
    public function objectManagerIsRegisteredCallback()
    {
        $arguments = array_merge(func_get_args(), [$this->mockObjectManager]);
        $objectName = array_shift($arguments);
        switch ($objectName) {
            case 'Neos\Fusion\Fixtures\Text':
            case 'Neos\Fusion\Fixtures\Page':
            case 'Neos\Fusion\Fixtures\ContentArray':
            case 'Neos\Fusion\Fixtures\ObjectWithArrayProperty':
            case 'Neos\Fusion\Processors\WrapProcessor':
            case 'Neos\Fusion\Processors\SubstringProcessor':
            case 'Neos\Fusion\Processors\MultiplyProcessor':
            case 'Neos\SomeOther\Namespace\MyWrapProcessor':
                return true;
            default:
                return false;
        }
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 01
     *
     * @test
     */
    public function parserCorrectlyParsesFixture01()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture01');

        $expectedParseTree = [
            'test' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Hello world!'
            ],
            'secondTest' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 23,
                'value-with-dash' => 42,
                'value:with:colon' => 59
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);

        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 01.');
    }

    /**
     * Checks if a leading slash in the namespace declaration throws an exception
     *
     * @test
     */
    public function parserThrowsFusionExceptionIfNamespaceDeclarationIsInvalid()
    {
        $this->expectException(Exception::class);
        $sourceCode = 'namespace: cms=\-notvalid-\Fusion\Fixtures';
        $this->parser->parse($sourceCode);
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 02
     *
     * @test
     */
    public function parserCorrectlyParsesFixture02()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture02');

        $expectedParseTree = [
            'myObject' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => "Sorry, we're closed -- double quotes like \" do not need to be escaped."
            ],
            'anotherObject' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'And I said: "Hooray" -- single quotes like \' do not need to be escaped'
            ],
            'kaspersObject' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'The end of this line is a backslash\\',
                'bar' => 'Here comes \ a backslash in the middle'
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 02.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 03
     *
     * @test
     */
    public function parserCorrectlyParsesFixture03()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture03');

        $expectedParseTree = [
            'object1' => [
                'mySubObject' => [
                    'mySubSubObject' => [
                        '__objectType' => 'Neos.Fusion:Text',
                        '__value' => null,
                        '__eelExpression' => null,
                        'value' => 'Espresso is a fine beverage.'
                    ]
                ]
            ],
            'object2' => [
                '__objectType' => 'Neos.Fusion:ObjectWithArrayProperty',
                '__value' => null,
                '__eelExpression' => null,
                'theArray' => [
                    'theKey' => 'theValue'
                ]
            ],
            'object3' => [
                '__objectType' => 'Neos.Fusion:ObjectWithArrayProperty',
                '__value' => null,
                '__eelExpression' => null,
                'theArray' => [
                    'theKey' => [
                        '__objectType' => 'Neos.Fusion:Text',
                        '__value' => null,
                        '__eelExpression' => null,
                        'value' => 'theValue'
                    ]
                ]
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 03.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 04
     *
     * @test
     */
    public function parserCorrectlyParsesFixture04()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture04');

        $expectedParseTree = [
            'myArrayObject' => [
                '__objectType' => 'Neos.Fusion:ContentArray',
                '__value' => null,
                '__eelExpression' => null,
                10 => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'Hello ',
                    '__meta' => [
                        'position' => 'after 10'
                    ]
                ],
                20 => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'world!'
                ],
                30 => [
                    '__objectType' => 'Neos.Fusion:ContentArray',
                    '__value' => null,
                    '__eelExpression' => null,
                    20 => [
                        '__objectType' => 'Neos.Fusion:ContentArray',
                        '__value' => null,
                        '__eelExpression' => null,
                        10 => [
                            '__objectType' => 'Neos.Fusion:Text',
                            '__value' => null,
                            '__eelExpression' => null,
                            'value' => 'Huh?'
                        ]
                    ]
                ]
            ],
            'anotherObject' => [
                'sub1' => [
                    'sub2' => [
                        'sub3' => [
                            '__objectType' => 'Neos.Fusion:ContentArray',
                            '__value' => null,
                            '__eelExpression' => null,
                            1 => [
                                '__objectType' => 'Neos.Fusion:Text',
                                '__value' => null,
                                '__eelExpression' => null,
                                'value' => 'Yawn'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 04.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 05
     *
     * @test
     */
    public function parserCorrectlyParsesFixture05()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture05');

        $expectedParseTree = [
            'firstObject' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Go outside. The graphics are AMAZING!'
            ],
            'firstObject2' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Go outside. The graphics are AMAZING!'
            ],
            'firstObject3' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Overridden value'
            ],
            'firstObject4' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Ugly syntax with no space works!'
            ],
            'secondObject' => [
                'subObject' => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => '27°C and a blue sky.'
                ]
            ],
            'thirdObject' => [
                'subObject' => [
                    'subSubObject' => [
                        'someMessage' => [
                            '__objectType' => 'Neos.Fusion:Text',
                            '__value' => null,
                            '__eelExpression' => null,
                            'value' => 'Fully or hard tail?',
                            'value2' => 'I don\'t know.'
                        ]
                    ],
                    'anotherSubSubObject' => [
                        'someMessage' => [
                            '__objectType' => 'Neos.Fusion:Text',
                            '__value' => null,
                            '__eelExpression' => null,
                            'value' => 'Hard',
                        ]
                    ]
                ]
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 05.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 07
     *
     * @test
     */
    public function parserCorrectlyParsesFixture07()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture07');

        $expectedParseTree = [
            'object1' => [
                '__stopInheritanceChain' => true
            ],
            'object3' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => [
                    '__stopInheritanceChain' => true
                ]
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 07.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 08
     *
     * @todo Implement lazy rendering support for variable substitutions
     * @test
     */
    public function parserCorrectlyParsesFixture08()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture08');

        $expectedParseTree = [
            'object1' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Hello world!',
                'foo' => 42
            ],
            'object2' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Hello world!',
                'foo' => 42
            ],
            'lib' => [
                'object3' => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'Another message'
                ],
                'object4' => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'Another message'
                ],
                'object5' => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'Another message'
                ],
                'object6' => [
                    '__objectType' => 'Neos.Fusion:Text',
                    '__value' => null,
                    '__eelExpression' => null,
                    'value' => 'Hello world!',
                    'foo' => 21
                ],
            ],
            'object7' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Hello world!'
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 08.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 10
     *
     * @test
     */
    public function parserCorrectlyParsesFixture10()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture10');

        $expectedParseTree = [

            'newObject1' => [
                '__objectType' => 'Neos.Fusion:Text',
                'value' => [
                    '__value' => 'Hello',
                    '__objectType' => null,
                    '__eelExpression' => null,
                    '__meta' => [
                        'process' => [
                            1 => [
                                '__eelExpression' => 'value + \' world\'',
                                '__value' => null,
                                '__objectType' => null,
                            ],
                            'other' => [
                                '__eelExpression' => 'value + \' world\'',
                                '__value' => null,
                                '__objectType' => null,
                            ],
                            'default' => [
                                'expression' => [
                                    '__eelExpression' => 'value + \' world\'',
                                    '__value' => null,
                                    '__objectType' => null,
                                ],
                                '__meta' => [
                                    'position' => 'start'
                                ]
                            ]
                        ]
                    ]
                ],
                '__value' => null,
                '__eelExpression' => null,
            ],
            'newObject2' => [
                '__objectType' => 'Neos.Fusion:Text',
                'value' => 'Hello',
                '__meta' => [
                    'process' => [
                        1 => [
                            '__eelExpression' => 'value + \' world\'',
                            '__value' => null,
                            '__objectType' => null,
                        ],
                    ]
                ],
                '__value' => null,
                '__eelExpression' => null,
            ],
            '__prototypes' => [
                'Neos.Fusion:Foo' => [
                    '__meta' => [
                        'process' => [
                            1 => [
                                '__eelExpression' => 'value + \' world\'',
                                '__value' => null,
                                '__objectType' => null,
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 10.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 13
     *
     * @test
     */
    public function parserCorrectlyParsesFixture13()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture13');

        $expectedParseTree = [
            'object1' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => chr(10) . '  Some text.' . chr(10)
            ],
            'object2' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => chr(10) . '  Some text.' . chr(10)
            ],
            'object3' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'The text might start' . chr(10) . '  at some line\' and' . chr(10) . '  end at some other line'
            ],
            'object4' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'The text might start' . chr(10) . '  at some line "and' . chr(10) . '  end at some other line'
            ],
            'object5' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'The text might start' . chr(10) . '  at "some" line and' . chr(10) . '  end at some other line'
            ],
            'object6' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'The text might start' . chr(10) . '  at \'some\' line and' . chr(10) . '  end at some other line'
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 13.');
    }

    /**
     * checks if the object tree returned by the Fusion parser reflects source code fixture 14
     *
     * @test
     */
    public function parserCorrectlyParsesFixture14()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture14');

        $expectedParseTree = [
            'object1' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Curly braces like this {} or {that} are ignored.'
            ],
            'object2' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Curly braces like this {} or {that} are ignored.'
            ],
            'object3' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Slashes // or hashes # or /* signs are not interpreted as comments.'
            ],
            'object4' => [
                '__objectType' => 'Neos.Fusion:Text',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Slashes // or hashes # or /* signs are not interpreted as comments.'
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 14.');
    }

    /**
     * @test
     */
    public function parserCorrectlyParsesFixture15()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture15');

        $expectedParseTree = [
            'foo' => [
                '__objectType' => 'Neos.Fusion:Bar',
                '__value' => null,
                '__eelExpression' => null,
                'prop' => 'myValue'
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 15.');
    }

    protected function getExpectedParseTreeForFixture16()
    {
        $expectedParseTree = [
            '__prototypes' => [
                'Neos.Foo:Bar' => [
                    'baz' => 'Hallo'
                ],
                'Neos.Foo:Bar2' => [
                    'baz' => 'Foo',
                    'test2' => 42
                ],
                'Foo.Bar:Baz' => [
                    '__prototypes' => [
                        'Foo.Bar:Baz2' => [
                            'test' => 'asdf'
                        ]
                    ]
                ],
                'Neos.Foo:Bar3' => [
                    '__prototypeObjectName' => 'Neos.Foo:Bar2',
                    '__prototypeChain' => [
                        'Neos.Foo:Bar2'
                    ]
                ]
            ],
            'test' => [
                '__prototypes' => [
                    'Neos.Foo:Bar' => [
                        'baz' => 'Hallo'
                    ]
                ],
            ],
            'foo' => [
                'bar' => [
                    '__prototypes' => [
                        'Neos.Foo:Bar2' => [
                            'baz' => 'Foo',
                            'test2' => 42,
                            'blah' => [
                                '__eelExpression' => 'my.expression()',
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'blah2' => [
                                '__eelExpression' => "my.expression('asdf')",
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'blah3' => [
                                '__eelExpression' => 'my.expression("as' . "    some stuff }    " . '" + "df")',
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'multiline2' => [
                                '__eelExpression' => "my.expression(    Foo.bar(\"foo\")  )",
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'multiline3' => [
                                '__eelExpression' => "    my.expression(      Bar.foo(\"bar\")    )  ",
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'multiline4' => [
                                '__eelExpression' => "my.expression(    \"bla\",    \"blubb\",    Test()  )",
                                '__value' => null,
                                '__objectType' => null
                            ],
                            'multiline5' => [
                                '__eelExpression' => "'col-sm-'+    String.split(q(node).parent().property('layout'), '-')[multiColumnIteration.index]",
                                '__value' => null,
                                '__objectType' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $expectedParseTree;
    }

    /**
     * @test
     */
    public function parserCorrectlyParsesFixture16()
    {
        $fixture = __DIR__ . '/Fixtures/ParserTestFusionFixture16.fusion';
        $sourceCode = file_get_contents($fixture, FILE_TEXT);

        $expectedParseTree = $this->getExpectedParseTreeForFixture16();

        $actualParseTree = $this->parser->parse($sourceCode, $fixture);
        self::assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 16');
    }

    /**
     * @test
     */
    public function parserThrowsExceptionOnFixture16b()
    {
        $this->expectException(Exception::class);
        $fixture = __DIR__ . '/Fixtures/ParserTestFusionFixture16b.fusion';
        $sourceCode = file_get_contents($fixture, FILE_TEXT);

        $this->parser->parse($sourceCode, $fixture);
    }

    /**
     * @test
     */
    public function parserCorrectlyParsesFixture17()
    {
        $fixture = __DIR__ . '/Fixtures/ParserTestFusionFixture17.fusion';
        $sourceCode = file_get_contents($fixture, FILE_TEXT);

        $expectedParseTree = $this->getExpectedParseTreeForFixture16();

        // Check that values were overridden by fixture #17:
        $expectedParseTree['__prototypes']['Neos.Foo:Bar2']['baz'] = 'New Value';

        $text = [
            '__objectType' => 'Neos.Fusion:Text',
            '__value' => null,
            '__eelExpression' => null
        ];

        // Make sure that the namespace declaration for "default" is also available when fixture #17b is parsed:
        $expectedParseTree['object'] = $text;
        // Test normal globbing
        $expectedParseTree['globbing1'] = $text;
        $expectedParseTree['globbing2'] = $text;
        // Test recursive globbing
        $expectedParseTree['recursiveGlobbing1'] = $text;
        $expectedParseTree['recursiveGlobbing2'] = $text;
        $expectedParseTree['recursiveGlobbingUpTheTree'] = $text;
        // Test globbing with dots
        $expectedParseTree['globbingWithDots1'] = $text;

        $actualParseTree = $this->parser->parse($sourceCode, $fixture);
        self::assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 17');
    }

    /**
     * Checks if namespace declarations are expanded correctly
     *
     * @test
     */
    public function parserCorrectlyParsesFixture18()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture18');

        $expectedParseTree = [
            'object1' => [
                '__objectType' => 'Neos.Neos:Text',
                '__value' => null,
                '__eelExpression' => null
            ],
            'object2' => [
                '__objectType' => 'Neos.Neos:Text',
                '__value' => null,
                '__eelExpression' => null
            ],
            'object3' => [
                '__objectType' => 'Neos.Schirmchen:Text',
                '__value' => null,
                '__eelExpression' => null
            ],
            'object4' => [
                '__objectType' => 'Neos.Future:Text',
                '__value' => null,
                '__eelExpression' => null
            ],
            '__prototypes' => [
                'Neos.Neos:Foo' => [
                    '__meta' => [
                        'class' => JoinImplementation::class
                    ]
                ],
                'Neos.Neos:Bar' => [
                    '__meta' => [
                        'class' => JoinImplementation::class
                    ]
                ],
                'Neos.Schirmchen:Baz' => [
                    '__meta' => [
                        'class' => JoinImplementation::class
                    ]
                ],
                'Neos.Future:Quux' => [
                    '__meta' => [
                        'class' => JoinImplementation::class
                    ]
                ]
            ]
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 18.');
    }

    /**
     * Checks if simple values (string, boolean, integer) are parsed correctly
     *
     * @test
     */
    public function parserCorrectlyParsesFixture19()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture19');

        $expectedParseTree = [
            'somepath' => [
                'stringValue' => 'A string value',
                'booleanValueFalse' => false,
                'booleanValueTrue' => true,
                'nullValue' => null,
                'integerValue' => 42
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 19.');
    }

    /**
     * Checks if path with an underscore is parsed correctly
     *
     * @test
     */
    public function parserCorrectlyParsesFixture20()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture20');

        $expectedParseTree = [
            'somepath' => [
                '_stringValue' => 'A string value',
                '_booleanValueFalse' => false,
                '_booleanValueTrue' => true,
                '_integerValue' => 42,
                'value_with_underscores_inBetween' => 42,
                'nested_value' => [
                    'is' => 21
                ]
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 20.');
    }

    /**
     * @test
     */
    public function parserDetectsDirectRecursions()
    {
        $this->expectException(Exception::class);
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture21');
        $this->parser->parse($sourceCode);
    }

    /**
     * @test
     */
    public function parserDetectsIndirectRecursions()
    {
        $this->expectException(Exception::class);
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture22');
        $this->parser->parse($sourceCode);
    }

    /**
     * Checks if identifiers starting with digits are parsed correctly
     *
     * @test
     */
    public function parserCorrectlyParsesFixture21()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture23');

        $expectedParseTree = [
            '__prototypes' => [
                '4Testing:Example' => [
                    'someValue' => true
                ]
            ],
            'somepath' => [
                '101Neos' => 'A string value',
            ],
        ];

        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 23.');
    }

    /**
     * Checks if really long strings are parsed correctly
     *
     * @test
     */
    public function parserCorrectlyLongStrings()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionFixtureLongString');
        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertArrayHasKey('longString', $actualParseTree);
    }

    /**
     * Checks if comments in comments are parsed correctly
     *
     * @test
     */
    public function parserCorrectlyParsesComments01()
    {
        $sourceCode = $this->readFusionFixture('ParserTestFusionComments01');
        $expected = []; // Fixture contains only comments, so expect empty parse tree
        $actualParseTree = $this->parser->parse($sourceCode);
        self::assertEquals($expected, $actualParseTree, 'The parse tree was not as expected after parsing fixture `ParserTestFusionComments01.fusion`');
    }

    /**
     * Checks if dsl value is handed over to the invokeAndParseDsl method
     *
     * @test
     */
    public function parserInvokesFusionDslParsingIfADslPatternIsDetected()
    {
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->setMethods(['invokeAndParseDsl'])->getMock();

        $sourceCode = $this->readFusionFixture('ParserTestFusionFixture24');

        $parser
            ->expects($this->exactly(2))
            ->method('invokeAndParseDsl')
            ->withConsecutive(
                ['dsl1', 'example value'],
                ['dsl2', 'another' . chr(10) . 'multiline' . chr(10) . 'value']
            );

        $parser->parse($sourceCode);
    }

    /**
     * Checks unclosed dsl-expressions are
     *
     * @test
     */
    public function parserThrowsFusionExceptionIfUnfinishedDslIsDetected()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1490714685);
        $this->parser->parse('dslValue1 = dsl1`unclosed dsl expression');
    }

    /**
     * @param string $fixtureName File name of the Fusion fixture to be read (without .fusion)
     * @return string The content of the fixture
     */
    protected function readFusionFixture($fixtureName)
    {
        return file_get_contents(__DIR__ . '/Fixtures/' . $fixtureName . '.fusion', FILE_TEXT);
    }
}
