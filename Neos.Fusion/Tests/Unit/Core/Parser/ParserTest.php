<?php

namespace Neos\Fusion\Tests\Unit\Core\Parser;

use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\ParserException;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function additionalNewFusionSyntaxProposalAndIdeas()
    {
        yield 'prototype definition keyword' => [
            <<<'Fusion'
            prototype: Vendor:MyObject {
                value = 123
            }
            Fusion,
            ['__prototypes' => ['Vendor:MyObject' => ['value' => 123]]]
        ];
        yield 'prototype extension keyword' => [
            <<<'Fusion'
            prototype: Vendor:MyObject extends Neos.Fusion:Value {
                value = 123
            }
            Fusion,
            ['__prototypes' => ['Vendor:MyObject' => ['__prototypeObjectName' => 'Neos.Fusion:Value', 'value' => 123, '__prototypeChain' => ['Neos.Fusion:Value']]]]
        ];
        yield 'path prototype extension keyword' => [
            <<<'Fusion'
            prototype(Vendor:MyObject) extends prototype(Neos.Fusion:Value) {
                value = 123
            }
            Fusion,
            ['__prototypes' => ['Vendor:MyObject' => ['__prototypeObjectName' => 'Neos.Fusion:Value', 'value' => 123,  '__prototypeChain' => ['Neos.Fusion:Value']]]]
        ];
        yield 'empty prototype definition' => [
            <<<'Fusion'
            prototype: Vendor:MyObject {
            }
            Fusion,
            []
        ];
        yield 'unset keyword absolute path' => [
            <<<'Fusion'
            a = 'value'
            unset: a
            Fusion,
            ['a' => ['__stopInheritanceChain' => true]]
        ];
        yield 'unset keyword relative path' => [
            <<<'Fusion'
            b {
                a = 'value'
                unset: .a
            }
            Fusion,
            ['b' => ['a' => ['__stopInheritanceChain' => true]]]
        ];
    }

    public function pathBlockTest()
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
                a { b = ""
                }
                Fusion,
                ['a' => ['b' => ""]]
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

    public function unexpectedBlocksWork()
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

    public function commentsTest()
    {
        $obj = function (string $name): array {
            return ['__objectType' => $name, '__value' => null, '__eelExpression' => null];
        };
        return[
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
                a /* we are comments
                and we are every where  */ = 'b'
                Fusion,
                ['a' => 'b']
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
            ['a = /*fwefe*/ 123456', ['a' => 123456]],
            ['a /*fwefe*/ = 123456', ['a' => 123456]],
            ['/*fwefe*/ a = 123456', ['a' => 123456]],
        ];
    }

    public function throwsWrongComments()
    {
        return[
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

    public function prototypeDeclarationAndInheritance()
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


    public function throwsOldNamespaceDeclaration()
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

    public function throwsGeneralWrongSyntax()
    {
        return [
            ['fwefw/*fwef*/fewfwe1212 = ""'], // no comments inside everywhere
            ['"" = 123'], // no empty strings in path
            ['\'\' = 123'],
            ['äüöfwef = ""'], // no utf in path
            ['nomultidots..few = ""'], // no multiple dots chained
            ['.nostartingdot = ""'], // no path starting with dot
            ['32fe wfwe.f = ""'], // no spaces in path name
            ['pat . fewfw  .  fewf       .    = ""'], // no spaces between dots
            ['a = Neos . Fusion:Hi . Stuff'], // no spaces between objects dots
            ['a = Fusion  : Object'], // no spaces between objects  colons
            ['{}'], // block out of context
            ['a = ${ fwef fewf {}'], // unclosed eel
            [<<<'Fusion'
            a = "fwfe"
            }
            Fusion], // block out of context
            ['a { b = "" }'], // no end of line detected
            // will throw with old parser: An exception was thrown while Neos tried to render your page
            // missing brace:
            [<<<'Fusion'
            a = Neos.Fusion:Value {
              value = Neos.Fusion:Join {
                a = "wfef"
            }
            Fusion],
            [<<<'Fusion'
            baz =
            'Foo'
            Fusion],
            [<<<'Fusion'
            baz
            =
            'Foo'
            Fusion],
            [<<<'Fusion'
            baz
            = 'Foo'
            Fusion],
            [<<<'Fusion'
            asinglepathwithoutoperation
            Fusion],
            [<<<'Fusion'
            a.single-path."withoutoperation"
            Fusion],
            [<<<'Fusion'
            a = ${hello open left eel and eof file...
            Fusion],
            [<<<'Fusion'
            a = ${hello open left eel
            b {
            }
            Fusion],
            [<<<'Fusion'
            a
            {
                b = ""
            }
            Fusion],
            [<<<'Fusion'
            prototype(a)
            {
                b = ""
            }
            Fusion],
        ];
    }


    public function unexpectedCopyAssigment()
    {
        return [
            ['a < b', ['b' => [], 'a' => []]],
            ['n.a < b', ['b' => [], 'n' => ['a' => []]]],

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

    public function unexpectedObjectPaths()
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
            [<<<'Fusion'
            "multiline
              does
            work  " = 1
            Fusion, ["multiline\n  does\nwork  " => 1]],
        ];
    }

    public function metaObjectPaths()
    {
        return [
            ['a.@abc = 1', ['a' => ['__meta' => ['abc' => 1]]]],
            ['a.@override = 1', ['a' => ['__meta' => ['context' => 1]]]],
        ];
    }

    public function nestedObjectPaths()
    {
        return [
            ['12f:o:o.ba:r.as.ba:z = 1', ['12f:o:o' => ['ba:r' => ['as' => ['ba:z' => 1]]]]],
            ['a.b.c = ""', ['a' => ['b' => ['c' => '']]]],
            ['0.60.hello = ""', [0 => [60 => ['hello' => '']]]],
            ['"a.b.c".132.hel-lo.what: = ""', ['a.b.c' => [132 => ['hel-lo' => ['what:' => '']]]]],
        ];
    }

    public function simpleValueAssign()
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

    public function eelValueAssign()
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

    public function stringAndCharValueAssign()
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

    public function fusionObjectNameEdgeCases()
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

    public function weirdFusionObjectNamesParsesBecauseTheOldParserDidntComplain()
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

    public function throwsFusionObjectNamesWithoutNamespace()
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


    /**
     * @test
     * @dataProvider additionalNewFusionSyntaxProposalAndIdeas
     * @dataProvider commentsTest
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
        $parser = new Parser;
        $parsedFusionAst = $parser->parse($fusion);
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
        self::expectException(ParserException::class);

        $parser = new Parser;
        $parser->parse($fusion);
    }

    /**
     * @test
     * @dataProvider weirdFusionObjectNamesParsesBecauseTheOldParserDidntComplain
     * @dataProvider unexpectedBlocksWork
     */
    public function itParsesWithoutError($fusion): void
    {
        $parser = new Parser;
        $parser->parse($fusion);
        self::assertTrue(true);
    }
}
