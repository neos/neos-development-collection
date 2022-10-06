<?php
namespace Neos\Fusion\Tests\Unit\Core\Parser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Cache\ParserCache;
use Neos\Fusion;
use Neos\Flow\Tests\UnitTestCase;

class ParserTest extends UnitTestCase
{
    protected Parser $parser;

    public function setUp(): void
    {
        $this->parser = new Parser();
        $this->injectParserCacheMockIntoParser($this->parser);
    }

    private function injectParserCacheMockIntoParser(Parser $parser): void
    {
        $parserCache = $this->getMockBuilder(ParserCache::class)->getMock();
        $parserCache->method('cacheForFusionFile')->will(self::returnCallback(fn ($_, $getValue) => $getValue()));
        $parserCache->method('cacheForDsl')->will(self::returnCallback(fn ($_, $_2, $getValue) => $getValue()));
        $this->inject($parser, 'parserCache', $parserCache);
    }

    public function pathBlockTest(): array
    {
        return [
            [
                <<<'Fusion'
                a {
                }
                Fusion,
                []
            ],
            [
                <<<'Fusion'
                a = ""
                a {
                }
                Fusion,
                ['a' => '']
            ],
            [
                <<<'Fusion'
                a {
                    b = "c"
                }
                Fusion,
                ['a' => ['b' => 'c']]
            ],
            [
                <<<'Fusion'
                a {
                    b {
                        c {
                            d = "e"
                        }
                    }
                }
                Fusion,
                ['a' => ['b' => ['c' => ['d' => 'e']]]]
            ],
            [
                <<<'Fusion'
                a {
                    b {
                        c {
                        }
                    }
                }
                Fusion,
                []
            ],
            [
                <<<'Fusion'
                a = ""
                a {
                }
                Fusion,
                ['a' => '']
            ],
        ];
    }

    public function unexpectedBlocksWork(): array
    {
        // test of normal objects is already done with the fixtures
        return [
            [
                <<<'Fusion'
                a = "string" {
                    unuse = "full"
                }
                Fusion
            ],
            [
                <<<'Fusion'
                a = ${eel stuff} {
                    unuse = "full"
                }
                Fusion,
            ],
            [
                <<<'Fusion'
                a = -123132 {
                    unuse = "full"
                }
                Fusion,
            ],
            [
                <<<'Fusion'
                a > {
                    use = "full"
                }
                Fusion,
            ],
        ];
    }

    public function unsetPathOrSetToNull()
    {
        yield 'overwrite with boolean value' => [
            <<<'Fusion'
            a.b = "hello"
            a = true
            Fusion,
            ['a' => ['b' => 'hello', '__value' => true, '__eelExpression' => null, '__objectType' => null]]
        ];
        yield 'unset value at global level' => [
            <<<'Fusion'
            a >
            Fusion,
            ['a' => ['__stopInheritanceChain' => true]]
        ];

        yield 'set value to null at global level' => [
            <<<'Fusion'
            a = null
            Fusion,
            ['a' => null]
        ];

        yield 'set value to null at global level with previous content' => [
            <<<'Fusion'
            a.b = "hello"
            a = null
            Fusion,
            [
                'a' => [
                    'b' => 'hello',
                    '__value' => null,
                    '__eelExpression' => null,
                    '__objectType' => null
                ]
            ]
        ];

        yield 'set value to null at nested level with previous content' => [
            <<<'Fusion'
            a.b.c = "hello"
            a.b = null
            Fusion,
            [
                'a' => [
                    'b' => [
                        'c' => 'hello',
                        '__value' => null,
                        '__eelExpression' => null,
                        '__objectType' => null
                    ]
                ]
            ]
        ];
    }

    public function simplePathToArray(): \Generator
    {
        yield 'simple string "opened" with meta' => [
            <<<'Fusion'
            foo = "bar"
            foo.@baz = 1
            Fusion,
            [
                'foo' => [
                    '__value' => 'bar',
                    '__eelExpression' => null,
                    '__objectType' => null,
                    '__meta' => [
                        'baz' => 1
                    ]
                ]
            ]
        ];

        yield 'simple string "opened" with nested path' => [
            <<<'Fusion'
            foo = "bar"
            foo.baz = 1
            Fusion,
            [
                'foo' => [
                    '__value' => 'bar',
                    '__eelExpression' => null,
                    '__objectType' => null,
                    'baz' => 1
                ]
            ]
        ];

        yield 'null path with nested' => [
            <<<'Fusion'
            foo = null
            foo.bar = 1
            Fusion,
            ['foo' => ['bar' => 1]]
        ];
    }

    public function overridePaths(): \Generator
    {
        yield 'eel expression is overridden by simple type' => [
            <<<'Fusion'
            foo = ${'bar'}
            foo = "but"
            Fusion,
            ['foo' => [
                '__eelExpression' => null,
                '__value' => 'but',
                '__objectType' => null,
            ]]
        ];

        yield 'fusion object is overridden by simple type' => [
            <<<'Fusion'
            foo = Foo:Bar
            foo = "but"
            Fusion,
            ['foo' => [
                '__objectType' => null,
                '__value' => 'but',
                '__eelExpression' => null,
            ]]
        ];

        yield 'simple type is overridden by simple type' => [
            <<<'Fusion'
            foo = 'bar'
            foo = 'but'
            Fusion,
            ['foo' => 'but']
        ];

        yield 'simple type is overridden by eel' => [
            <<<'Fusion'
            foo = 'bar'
            foo = ${eel}
            Fusion,
            ['foo' => [
                '__eelExpression' => 'eel',
                '__value' => null,
                '__objectType' => null,
            ]]
        ];

        yield 'simple type is overridden by object' => [
            <<<'Fusion'
            foo = 'bar'
            foo = Foo:Bar
            Fusion,
            ['foo' => [
                '__objectType' => 'Foo:Bar',
                '__value' => null,
                '__eelExpression' => null,
            ]]
        ];

        yield 'fusion object or eel are overridden by simple type' => [
            <<<'Fusion'
            foo = Foo:Bar
            foo = ${eel}
            foo = 'but'
            Fusion,
            ['foo' => [
                '__objectType' => null,
                '__value' => 'but',
                '__eelExpression' => null,
            ]]
        ];

        yield 'fusion object is overridden by eel' => [
            <<<'Fusion'
            foo = Foo:Bar
            foo = ${eel}
            Fusion,
            ['foo' => [
                '__objectType' => null,
                '__value' => null,
                '__eelExpression' => 'eel',
            ]]
        ];

        yield 'eel is overridden by fusion object' => [
            <<<'Fusion'
            foo = ${eel}
            foo = Foo:Bar
            Fusion,
            ['foo' => [
                '__eelExpression' => null,
                '__value' => null,
                '__objectType' => 'Foo:Bar',
            ]]
        ];
    }

    public function commentsTest(): array
    {
        $obj = function (string $name): array {
            return ['__objectType' => $name, '__value' => null, '__eelExpression' => null];
        };
        return[
            ['/** doc comment */', []],
            [
                <<<'Fusion'
                a = 'b' // hallo ich bin ein comment
                b = -123.4 # hallo ich bin ein comment
                c = Neos.Fusion:Hi /* hallo ich bin ein comment */
                Fusion,
                ['a' => 'b', 'b' => -123.4, 'c' => $obj('Neos.Fusion:Hi')]
            ],
            [
                <<<'Fusion'
                a { // hello
                    b = 123
                }
                Fusion,
                ['a' => ['b' => 123]]
            ],
            [
                <<<'Fusion'
                a { /*
                    comment
                */
                b = 123
                }
                Fusion,
                ['a' => ['b' => 123]]
            ],
            [
                <<<'Fusion'
                // hallo ich bin ein comment
                # hallo ich bin ein comment
                /* hallo ich bin

                 ein comment */
                Fusion,
                []
            ],
            [
                <<<'Fusion'
                a = "" /*
                    multiline after
                */ // hello
                b = 123 /*
                    multiline wrap
                */
                Fusion,
                ['a' => "", 'b' => 123]
            ],
            ['/*fwefe*/ a = 123456', ['a' => 123456]],
            ['/** doc comment */ a = 123', ['a' => 123]],
        ];
    }

    public function throwsWrongComments(): array
    {
        return[
            [
                <<<'Fusion'
                a /* we are comments
                and we are not every where  */ = 'b'
                Fusion
            ],
            ['a = /*fwefe*/ 123456'],
            ['a /*fwefe*/ = 123456'],
            [
                <<<'Fusion'
                a /*
                    comment
                */
                // hello
                {
                    b = ""
                }
                Fusion,
            ],
            [
                <<<'Fusion'
                a /*
                    comment
                */ {
                    b = ""
                }
                Fusion,
            ],
            ['a = // hallo ich bin ein comment 454545'],
            [<<<'Fusion'
            a = "" /* multiline after assignment which is without newlines connected to new statement
            */ b = 123
            Fusion],
            [<<<'Fusion'
            // comment out comment start: /*
                helloIWillBeInterpretedAsPath
            */
            Fusion]
        ];
    }

    public function prototypeDeclarationAndInheritance(): array
    {
        return [
            [
                'prototype(asf.Ds:1).123 = 123',
                ['__prototypes' => ['asf.Ds:1' => ['123' => 123]]]
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar2) {
                    baz = 'Foo'
                }
                Fusion,
                ['__prototypes' => ['Neos.Foo:Bar2' => ['baz' => 'Foo']]]
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar2).baz = 'Foo'
                Fusion,
                ['__prototypes' => ['Neos.Foo:Bar2' => ['baz' => 'Foo']]]
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar2) {
                }
                Fusion,
                []
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar2) {
                }
                prototype(Neos.Foo:Bar2).baz = 42
                Fusion,
                ['__prototypes' => ['Neos.Foo:Bar2' => ['baz' => 42]]]
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar2) {
                    bar = 1
                } hello = "w"
                Fusion,
                ['__prototypes' => ['Neos.Foo:Bar2' => ['bar' => 1]], 'hello' => "w"]
            ],
            [
                <<<'Fusion'
                prototype(Neos.Foo:Bar3) < prototype(Neos.Foo:Bar2) {
                    foo = ''
                }
                Fusion,
                ['__prototypes' => ['Neos.Foo:Bar3' => [
                    '__prototypeObjectName' => 'Neos.Foo:Bar2',
                    'foo' => '',
                    '__prototypeChain' => ['Neos.Foo:Bar2'],
                ]]]
            ],
        ];
    }

    /**
     * @test
     */
    public function typeConversionOnPhpArrayKeys()
    {
        // as a reminder how php arrays work ^^
        $asArrayKey = static function ($val) {
            return array_key_first([$val => 'something']);
        };

        self::assertSame(-123, $asArrayKey(-123), 'negative digits stay');
        self::assertSame(-123, $asArrayKey('-123'), 'negative string digits will be converted to int');

        // shut-up operator to suppress PHP 8.1 warning "Deprecated: Implicit conversion from float 123.456 to int loses precision"
        @self::assertSame(123, $asArrayKey(123.456), 'floats will be converted to int');
        self::assertSame(123, $asArrayKey('123'), 'small string digits will be converted to int');
        self::assertNotSame('123', $asArrayKey('123'), 'small string digits will be converted to int');
        self::assertSame('1321231232123322123322', $asArrayKey('1321231232123322123322'), 'big string digits as array key stay strings');
        self::assertSame(1.3212312321233222E+21, 1321231232123322123322, 'big digits lose information');

        self::assertSame('007', $asArrayKey('007'), 'leading zeros in string digit array keys stay strings');
        self::assertSame('123.456', $asArrayKey('123.456'), 'string float array keys stay strings');
    }

    public function problematicPathIdNames(): \Generator
    {
        yield 'float in quotes as path identifier' => [
            <<<'Fusion'
            "15.8" = 123
            Fusion,
            ['15.8' => 123]
        ];

        yield 'int in quotes as path identifier' => [
            <<<'Fusion'
            "15" = 123
            Fusion,
            [15 => 123]
        ];

        yield 'int as path identifier' => [
            <<<'Fusion'
            24 = 123
            Fusion,
            [24 => 123]
        ];

        yield 'null as path identifier' => [
            <<<'Fusion'
            null = 123
            Fusion,
            ['null' => 123]
        ];

        yield 'true as path identifier' => [
            <<<'Fusion'
            true = 123
            Fusion,
            ['true' => 123]
        ];

        yield 'negative int as path identifier' => [
            <<<'Fusion'
            -123 = 123
            Fusion,
            [-123 => 123]
        ];

        yield 'positive int quoted as path identifier' => [
            <<<'Fusion'
            "+123" = 123
            Fusion,
            ["+123" => 123]
        ];

        yield 'multiple zeros as path identifier' => [
            <<<'Fusion'
            000 = 123
            Fusion,
            ['000' => 123]
        ];

        yield 'single zeros as path identifier' => [
            <<<'Fusion'
            0 = 123
            Fusion,
            [0 => 123]
        ];

        yield 'long digit as path identifier' => [
            <<<'Fusion'
            1321231232123322123322 = 123
            Fusion,
            ['1321231232123322123322' => 123]
        ];

        yield 'quoted keys and unquoted keys are treated the same' => [
            <<<'Fusion'
            123456 = 123
            "123456" = false
            Fusion,
            [123456 => false]
        ];

        yield 'floats in quoted keys can be used to copy values' => [
            <<<'Fusion'
            "123.132" = 123
            123 = "check that the float is not casted to 123"
            abc < "123.132"
            Fusion,
            ["123.132" => 123, 123 => "check that the float is not casted to 123", "abc" => 123]
        ];
    }

    public function throwsOldNamespaceDeclaration(): array
    {
        return [
            [
                <<<'Fusion'
                namespace: Alias=Package.Name
                b = Alias:Stuff
                Fusion
            ],
            [
                <<<'Fusion'
                namespace:     Alias  =  Package.Name
                b = Alias:Stuff
                Fusion
            ]
        ];
    }

    public function throwsGeneralWrongSyntax(): \Generator
    {
        yield 'path declaration after opening' => [<<<'Fusion'
            a { b = "hello"
            }
            Fusion];

        yield 'empty block without newline' => ['a {}'];

        yield 'empty strings in path' => ['"" = 123'];

        yield 'empty strings in path 2' => ['\'\' = 123'];

        yield 'utf in path' => ['äüö-path = ""'];

        yield 'unclosed string in assignment' => ['path = "hello world'];

        yield 'unclosed char in assignment' => ['path = \'hello world'];

        yield 'comments inside paths' => ['path/*comment*/path123 = ""'];

        yield 'multiple dots chained' => ['path..path = ""'];

        yield 'leading dot path' => ['path.path. = ""'];

        yield 'path starting with dot' => ['.path = ""'];

        yield 'spaces in path name' => ['path 123.path. = ""'];

        yield 'spaces between dots' => ['path . path  .  path = ""'];

        yield 'spaces between objects dots' => ['a = Neos . Fusion:Hi . Stuff'];

        yield 'spaces between objects  colons' => ['a = Fusion  : Object'];

        yield 'single path without operation' => ['path'];

        yield 'single nested path without operation' => ['path.path."string"'];

        yield 'single line block declaration' => ['a { b = "" }'];

        yield 'unclosed eel' => ['a = ${ hello {}'];

        yield 'open left eel and eof file doesnt end in catastrophic backtracking' => ['a = ${abc abc abc abc abc abc abc abc abc abc abc ...'];

        yield 'open left eel runs into fusion' => [<<<'Fusion'
            a {
                b = ${hello open left eel
            }
            Fusion];

        yield 'block out of context' => ['{}'];

        yield 'block close out of context' => [<<<'Fusion'
            a = "hello"
            }
            Fusion];

        yield 'missing brace' => [<<<'Fusion'
            a = Neos.Fusion:Value {
              value = Neos.Fusion:Join {
                a = "hello"
            }
            Fusion];

        yield 'newline after assign operator' => [<<<'Fusion'
            baz =
            'Foo'
            Fusion];

        yield 'newline after path before assign' => [<<<'Fusion'
            baz
            = 'Foo'
            Fusion];

        yield 'newline after path and assign operator' => [<<<'Fusion'
            baz
            =
            'Foo'
            Fusion];

        yield 'path with opening block brace on newline' => [<<<'Fusion'
            a
            {
                b = ""
            }
            Fusion];

        yield 'prototype with opening block brace on newline' => [<<<'Fusion'
            prototype(a:b)
            {
                b = ""
            }
            Fusion];

        yield 'multiline path key' => [<<<'Fusion'
            "multiline
              does
            work  " = 1
            Fusion];
    }

    public function unexpectedCopyAssigment()
    {
        yield 'copying from undefined path 1' => ['a < b', ['a' => null]];

        yield 'copying from undefined path 2' => ['n.a < b', ['n' => ['a' => null]]];

        yield 'copying from undefined path 3' => ['p < n.a', ['p' => null]];

        return [
            [<<<'Fusion'
            b = "hui"
            a < b
            Fusion, ['b' => 'hui', 'a' => 'hui']],

            [<<<'Fusion'
            b = "hui"
            a < .b
            Fusion, ['b' => 'hui', 'a' => 'hui']],

            [<<<'Fusion'
            b = "hui"
            a < b
            b = "wont change a"
            Fusion, ['b' => 'wont change a', 'a' => 'hui']],

            [<<<'Fusion'
            n.b = "hui"
            n.a < .b
            Fusion, ['n' => ['b' => 'hui', 'a' => 'hui']]],

            [<<<'Fusion'
            n.m {
                b = "hui"
                a < .b
            }
            Fusion, ['n' => ['m' => ['b' => 'hui', 'a' => 'hui']]]],
        ];
    }

    public function unexpectedObjectPaths(): array
    {
        return [
            ['0 = ""', [0 => '']],
            ['125646531 = ""', [125646531 => '']],
            ['0.1 = ""', [0 => [1 => '']]],
            ['TRUE = ""', ['TRUE' => '']],
            ['FALSE = ""', ['FALSE' => '']],
            ['NULL = ""', ['NULL' => '']],
            [': = ""', [':' => '']],
            ['- = ""', ['-' => '']],
            ['_ = ""', ['_' => '']],
            ['class = ""', ['class' => '']], // nope, me thinking about class as a keyword...
            ['something: = ""', ['something:' => '']],
            ['namespace: = ""', ['namespace:' => '']],
            ['a.include: = ""', ['a' => ['include:' => '']]],
            ['-_-:so-:m33_24et---hing00: = ""', ['-_-:so-:m33_24et---hing00:' => '']],
            ['"a.b" = ""', ['a.b' => '']],
            ['"@context" = ""', ['@context' => '']],
            ['"a.b\\\\" = ""', ['a.b\\' => '']],
            ['"a.b\\c" = ""', ['a.bc' => '']],
            ['"a.b\\"c" = ""', ['a.b"c' => '']],
            ['\'a.b\' = ""', ['a.b' => '']],
            [<<<'FUSION'
            "quo\"tes.mix\'ed".bla.'he\\\'.llo\\' = 1
            FUSION, ['quo"tes.mix\'ed' => ['bla' => ['he\\\'.llo\\' => 1]]]],
        ];
    }

    public function metaObjectPaths(): array
    {
        return [
            ['a.@abc = 1', ['a' => ['__meta' => ['abc' => 1]]]],
        ];
    }

    public function nestedObjectPaths(): array
    {
        return [
            ['12f:o:o.ba:r.as.ba:z = 1', ['12f:o:o' => ['ba:r' => ['as' => ['ba:z' => 1]]]]],
            ['a.b.c = ""', ['a' => ['b' => ['c' => '']]]],
            ['0.60.hello = ""', [0 => [60 => ['hello' => '']]]],
            ['"a.b.c".132.hel-lo.what: = ""', ['a.b.c' => [132 => ['hel-lo' => ['what:' => '']]]]],
        ];
    }

    public function simpleValueAssign(): array
    {
        return [
            ['a="b"', ['a' => 'b']],
            ['a = "b"', ['a' => 'b']],
            ['   a   =  "b"     ', ['a' => 'b']],
            ['a =  "b"
                                ', ['a' => 'b']],
            ['

                    a =  "b"', ['a' => 'b']],
            ['

                     a =  "b"

                     ', ['a' => 'b']],
            ['a = 123', ['a' => 123]],
            ['a = -123', ['a' => -123]],
            ['a = 1.123', ['a' => 1.123]],
            ['a = -1.123', ['a' => -1.123]],
            ['a = FALSE', ['a' => false]],
            ['a = false', ['a' => false]],
            ['a = TRUE', ['a' => true]],
            ['a = true', ['a' => true]],
            ['a = NULL', ['a' => null]],
            ['a = null', ['a' => null]],
        ];
    }

    public function eelValueAssign(): \Generator
    {
        $eel = function (string $exp): array {
            return ['__eelExpression' => $exp, '__value' => null, '__objectType' => null];
        };
        yield [
            <<<'Fusion'
            a = ${}
            Fusion,
            ['a' => $eel('')]
        ];
        yield [
            <<<'Fusion'
            a = ${hello}
            Fusion,
            ['a' => $eel('hello')]
        ];
        yield [
            <<<'Fusion'
            a = ${"string with escapes \" and {]}][{"}
            Fusion,
            ['a' => $eel('"string with escapes \\" and {]}][{"')]
        ];
        yield [
            <<<'Fusion'
            a = ${"string with escaped backslash at end \\"}
            Fusion,
            ['a' => $eel('"string with escaped backslash at end \\\\"')]
        ];
        yield [
            <<<'Fusion'
            a = ${"eel
            multi
            line
            strips
            newlines"
            }
            Fusion,
            ['a' => $eel('"eelmultilinestripsnewlines"')]
        ];
        yield [
            <<<'Fusion'
            a = ${
                "strip
                newlines
                    keep
                space"
            }
            Fusion,
            ['a' => $eel('    "strip    newlines        keep    space"')]
        ];
        yield [
            <<<'Fusion'
            a = ${fun({
            })}
            Fusion,
            ['a' => $eel('fun({})')]
        ];
    }

    public function stringAndCharValueAssign(): array
    {
        return [
            [<<<'Fusion'
            a = 'The end of this line is one escaped backslash \\'
            Fusion, ['a' => 'The end of this line is one escaped backslash \\']],
            ['a = ""', ['a' => '']],
            ['a = \'\'', ['a' => '']],
            ['a = "a\"b"', ['a' => 'a"b']],
            [<<<'Fusion'
            a = "a line\nbreak"
            Fusion, ['a' => 'a line'. chr(10) . 'break']],
            ['a = \'a"b\'', ['a' => 'a"b']],
            ['a = \'a"b\'', ['a' => 'a"b']],
            ['a = \'a\nb\'', ['a' => 'anb']],
            [<<<'Fusion'
            a = "a\\b"
            Fusion, ['a' => 'a\\b']],
            [<<<'Fusion'
            a = 'a\\v'
            Fusion, ['a' => 'a\\v']],
            [<<<'Fusion'
            a = 'a\v'
            Fusion, ['a' => 'av']],
        ];
    }

    public function fusionObjectNameEdgeCases(): array
    {
        $obj = function (string $name): array {
            return ['__objectType' => $name, '__value' => null, '__eelExpression' => null];
        };
        return [
            ['a = Foo.null:Bar', ['a' => $obj('Foo.null:Bar')]],
            ['a = truefalse.101:Bar', ['a' => $obj('truefalse.101:Bar')]],
            ['a = 4Object.123:A456.123', ['a' => $obj('4Object.123:A456.123')]],
            ['a = 4Foo:Bar', ['a' => $obj('4Foo:Bar')]],
            ['a = 3Vendor:Name', ['a' => $obj('3Vendor:Name')]],
            ['a = V3ndor:Name', ['a' => $obj('V3ndor:Name')]],
            ['a = TRUE132.Vendor:Object', ['a' => $obj('TRUE132.Vendor:Object')]],
            ['a = include:Object', ['a' => $obj('include:Object')]],
            ['a = namespace:Object', ['a' => $obj('namespace:Object')]],
        ];
    }

    public function weirdFusionObjectNamesParsesBecauseTheOldParserDidntComplain(): array
    {
        return [
            ['a = ABC:123'],
            ['a = 123:123'],
            ['a = 1:FusionObject'],
            ['a = 45455464:FusionObject'],
            ['a = TRUE:FusionObject'],
            ['a = false:FusionObject'],
            ['a = Vendor:Hello..Name'],
            ['a = .:.'],
            ['a = Vendor:....fewf..fwe.1415'],
        ];
    }

    public function throwsFusionObjectNamesWithoutNamespace(): array
    {
        return [
            ['a = Value'],
            ['a = Foo.null.Bar'],
            ['a = 4Foo.Bar'],
            ['a = 123.123.123'],
            ['a = 1.FusionObject'],
            ['a = 45455464.FusionObject'],
            ['a = TRUE.FusionObject'],
            ['a = false.FusionObject'],
            ['a = .Hello.Name'],
            ['a = ....fewf..fwe.1415'],
            ['a = 1354.154.453'],
            ['a = Hello..Name']
        ];
    }

    public function weirdDslNames(): \Generator
    {
        yield 'true as dsl key' => [
            'foo = true`code`',
            'true',
            'code'
        ];

        yield 'null as dsl key' => [
            'foo = null`code`',
            'null',
            'code'
        ];

        yield 'only number as dsl key' => [
            'foo = 123456`code`',
            '123456',
            'code'
        ];

        yield 'number and string as dsl key' => [
            'foo = 1foo`code`',
            '1foo',
            'code'
        ];
    }

    public function dslWithBraceOnFirstLine(): \Generator
    {
        yield 'dsl with brace on end of first line' => [
            'foo = dsl1`code {`',
            'dsl1',
            'code {'
        ];
    }

    /**
     * @test
     * @dataProvider overridePaths
     * @dataProvider simplePathToArray
     * @dataProvider commentsTest
     * @dataProvider unsetPathOrSetToNull
     * @dataProvider problematicPathIdNames
     * @dataProvider metaObjectPaths
     * @dataProvider eelValueAssign
     * @dataProvider simpleValueAssign
     * @dataProvider unexpectedCopyAssigment
     * @dataProvider unexpectedObjectPaths
     * @dataProvider nestedObjectPaths
     * @dataProvider pathBlockTest
     * @dataProvider stringAndCharValueAssign
     * @dataProvider prototypeDeclarationAndInheritance
     * @dataProvider fusionObjectNameEdgeCases
     */
    public function itParsesToExpectedAst($fusion, $expectedAst): void
    {
        $parsedFusionAst = $this->parser->parseFrom(\Neos\Fusion\Core\FusionSourceCode::fromString($fusion));
        self::assertSame($expectedAst, $parsedFusionAst);
    }

    /**
     * @test
     * @dataProvider throwsOldNamespaceDeclaration
     * @dataProvider throwsWrongComments
     * @dataProvider throwsGeneralWrongSyntax
     * @dataProvider throwsFusionObjectNamesWithoutNamespace
     */
    public function itThrowsWhileParsing($fusion): void
    {
        self::expectException(Fusion\Exception::class);
        $this->parser->parseFrom(\Neos\Fusion\Core\FusionSourceCode::fromString($fusion));
    }

    /**
     * @test
     * @dataProvider weirdFusionObjectNamesParsesBecauseTheOldParserDidntComplain
     * @dataProvider unexpectedBlocksWork
     */
    public function itParsesWithoutError($fusion): void
    {
        $this->parser->parseFrom(\Neos\Fusion\Core\FusionSourceCode::fromString($fusion));
        self::assertTrue(true);
    }

    /**
     * @dataProvider weirdDslNames
     * @dataProvider dslWithBraceOnFirstLine
     * @test
     */
    public function dslIsRecognizedAndPassed($sourceCode, $expectedDslName, $expectedDslContent)
    {
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->onlyMethods(['handleDslTranspile'])->getMock();
        $this->injectParserCacheMockIntoParser($parser);

        $parser
            ->expects($this->exactly(1))
            ->method('handleDslTranspile')
            ->with($expectedDslName, $expectedDslContent);

        $parser->parseFrom(\Neos\Fusion\Core\FusionSourceCode::fromString($sourceCode));
    }
}
