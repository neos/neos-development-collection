<?php
namespace TYPO3\TypoScript\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript Parser
 */
class ParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Core\Parser
	 */
	protected $parser;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$this->mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnCallback(array($this, 'objectManagerIsRegisteredCallback')));

		$parserClassName = $this->buildAccessibleProxy('TYPO3\TypoScript\Core\Parser');
		$this->parser = new $parserClassName();
		$this->parser->_set('objectManager', $this->mockObjectManager);
	}

	/**
	 * call back for mocking the object factory
	 * @return fixture objects ...
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function objectManagerCallback() {
		$arguments = array_merge(func_get_args(), array($this->mockObjectManager));
		$objectName = array_shift($arguments);

		$class = new \ReflectionClass($objectName);
		return ($class->getConstructor() !== NULL) ? $class->newInstanceArgs($arguments) : $class->newInstance();
	}

	/**
	 * Call back for mocking the object manager's isRegistered() method
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function objectManagerIsRegisteredCallback() {
		$arguments = array_merge(func_get_args(), array($this->mockObjectManager));
		$objectName = array_shift($arguments);
		switch ($objectName) {
			case 'TYPO3\TypoScript\Fixtures\Text' :
			case 'TYPO3\TypoScript\Fixtures\Page' :
			case 'TYPO3\TypoScript\Fixtures\ContentArray' :
			case 'TYPO3\TypoScript\Fixtures\ObjectWithArrayProperty' :
			case 'TYPO3\TypoScript\Processors\WrapProcessor' :
			case 'TYPO3\TypoScript\Processors\SubstringProcessor' :
			case 'TYPO3\TypoScript\Processors\MultiplyProcessor' :
			case 'TYPO3\SomeOther\Namespace\MyWrapProcessor' :
				return TRUE;
			default :
				return FALSE;
		}
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 01
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture01() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture01.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'test' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Hello world!'
			),
			'secondTest' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 23,
				'value-with-dash' => 42,
				'value:with:colon' => 59
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);

		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 01.');
	}

	/**
	 * Checks if a leading slash in the namespace declaration throws an exception
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function parserThrowsTypoScriptExceptionIfNamespaceDeclarationIsInvalid() {
		$sourceCode = "namespace: cms=\-notvalid-\TypoScript\Fixtures";
		$this->parser->parse($sourceCode);
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 02
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture02() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture02.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'myObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => "Sorry, we're closed -- double quotes like \" do not need to be escaped."
			),
			'anotherObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'And I said: "Hooray" -- single quotes like \' do not need to be escaped'
			),
			'kaspersObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'The end of this line is a backslash\\',
				'bar' => 'Here comes \ a backslash in the middle'
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 02.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 03
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture03() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture03.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object1' => array(
				'mySubObject' => array(
					'mySubSubObject' => array(
						'__objectType' => 'TYPO3.TypoScript:Text',
						'__value' => NULL,
						'__eelExpression' => NULL,
						'value' => 'Espresso is a fine beverage.'
					)
				)
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:ObjectWithArrayProperty',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'theArray' => array(
					'theKey' => 'theValue'
				)
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:ObjectWithArrayProperty',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'theArray' => array(
					'theKey' => array(
						'__objectType' => 'TYPO3.TypoScript:Text',
						'__value' => NULL,
						'__eelExpression' => NULL,
						'value' => 'theValue'
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 03.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 04
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture04() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture04.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'myArrayObject' => array(
				'__objectType' => 'TYPO3.TypoScript:ContentArray',
				'__value' => NULL,
				'__eelExpression' => NULL,
				10 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'Hello ',
					'__meta' => array(
						'position' => 'after 10'
					)
				),
				20 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'world!'
				),
				30 => array(
					'__objectType' => 'TYPO3.TypoScript:ContentArray',
					'__value' => NULL,
					'__eelExpression' => NULL,
					20 => array(
						'__objectType' => 'TYPO3.TypoScript:ContentArray',
						'__value' => NULL,
						'__eelExpression' => NULL,
						10 => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
							'__value' => NULL,
							'__eelExpression' => NULL,
							'value' => 'Huh?'
						)
					)
				)
			),
			'anotherObject' => array(
				'sub1' => array(
					'sub2' => array(
						'sub3' => array(
							'__objectType' => 'TYPO3.TypoScript:ContentArray',
							'__value' => NULL,
							'__eelExpression' => NULL,
							1 => array(
								'__objectType' => 'TYPO3.TypoScript:Text',
								'__value' => NULL,
								'__eelExpression' => NULL,
								'value' => 'Yawn'
							)
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 04.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 05
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture05() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture05.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'firstObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Go outside. The graphics are AMAZING!'
			),
			'firstObject2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Go outside. The graphics are AMAZING!'
			),
			'firstObject3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Overridden value'
			),
			'secondObject' => array(
				'subObject' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => '27°C and a blue sky.'
				)
			),
			'thirdObject' => array(
				'subObject' => array(
					'subSubObject' => array(
						'someMessage' => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
							'__value' => NULL,
							'__eelExpression' => NULL,
							'value' => 'Fully or hard tail?',
							'value2' => 'I don\'t know.'
						)
					),
					'anotherSubSubObject' => array(
						'someMessage' => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
							'__value' => NULL,
							'__eelExpression' => NULL,
							'value' => 'Hard',
						)
					)
				)
			),
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 05.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 07
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture07() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture07.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 07.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 08
	 *
	 * @todo Implement lazy rendering support for variable substitutions
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture08() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture08.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object1' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Hello world!',
				'foo' => 42
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Hello world!',
				'foo' => 42
			),
			'lib' => array(
				'object3' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'Another message'
				),
				'object4' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'Another message'
				),
				'object5' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'Another message'
				),
				'object6' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'__value' => NULL,
					'__eelExpression' => NULL,
					'value' => 'Hello world!',
					'foo' => 21
				),
			),
			'object7' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Hello world!'
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 08.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 10
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture10() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture10.ts2', FILE_TEXT);

		$expectedParseTree = array(

			'newObject1' => array(
				'__objectType' =>'TYPO3.TypoScript:Text',
				'value' => array(
					'__value' => 'Hello',
					'__objectType' => NULL,
					'__eelExpression' => NULL,
					'__meta' => array(
						'process' => array(
							1 => array(
								'__eelExpression' => 'value + \' world\'',
								'__value' => NULL,
								'__objectType' => NULL,
							),
							'other' => array(
								'__eelExpression' => 'value + \' world\'',
								'__value' => NULL,
								'__objectType' => NULL,
							),
							'default' => array(
								'expression' => array(
									'__eelExpression' => 'value + \' world\'',
									'__value' => NULL,
									'__objectType' => NULL,
								),
								'__meta' => array(
									'position' => 'start'
								)
							)
						)
					)
				),
				'__value' => NULL,
				'__eelExpression' => NULL,
			),
			'newObject2' => array(
				'__objectType' =>'TYPO3.TypoScript:Text',
				'value' => 'Hello',
				'__meta' => array(
					'process' => array(
						1 => array(
							'__eelExpression' => 'value + \' world\'',
							'__value' => NULL,
							'__objectType' => NULL,
						),
					)
				),
				'__value' => NULL,
				'__eelExpression' => NULL,
			),
			'__prototypes' => array(
				'TYPO3.TypoScript:Foo' => array(
					'__meta' => array(
						'process' => array(
							1 => array(
								'__eelExpression' => 'value + \' world\'',
								'__value' => NULL,
								'__objectType' => NULL,
							),
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 10.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 13
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture13() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture13.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object1' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => chr(10) . '	Some text.' . chr(10)
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => chr(10) . '	Some text.' . chr(10)
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'The text might start' . chr(10) . '	at some line\' and' . chr(10) . '	end at some other line'
			),
			'object4' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'The text might start' . chr(10) . '	at some line "and' . chr(10) . '	end at some other line'
			),
			'object5' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'The text might start' . chr(10) . '	at "some" line and' . chr(10) . '	end at some other line'
			),
			'object6' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'The text might start' . chr(10) . '	at \'some\' line and' . chr(10) . '	end at some other line'
			),
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 13.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 14
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture14() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture14.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object1' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Curly braces like this {} or {that} are ignored.'
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Curly braces like this {} or {that} are ignored.'
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Slashes // or hashes # or /* signs are not interpreted as comments.'
			),
			'object4' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'value' => 'Slashes // or hashes # or /* signs are not interpreted as comments.'
			),
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 14.');
	}

	/**
	 * @test
	 */
	public function parserCorrectlyParsesFixture15() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture15.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'foo' => array(
				'__objectType' => 'TYPO3.TypoScript:Bar',
				'__value' => NULL,
				'__eelExpression' => NULL,
				'prop' => 'myValue'
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 15.');
	}

	protected function getExpectedParseTreeForFixture16() {
		$expectedParseTree = array(
			'__prototypes' => array(
				'TYPO3.Foo:Bar' => array(
					'baz' => 'Hallo'
				),
				'TYPO3.Foo:Bar2' => array(
					'baz' => 'Foo',
					'test2' => 42
				),
				'Foo.Bar:Baz' => array(
					'__prototypes' => array(
						'Foo.Bar:Baz2' => array(
							'test' => 'asdf'
						)
					)
				),
				'TYPO3.Foo:Bar3' => array(
					'__prototypeObjectName' => 'TYPO3.Foo:Bar2',
					'__prototypeChain' => array(
						'TYPO3.Foo:Bar2'
					)
				)
			),
			'test' => array(
				'__prototypes' => array(
					'TYPO3.Foo:Bar' => array(
						'baz' => 'Hallo'
					)
				),
			),
			'foo' => array(
				'bar' => array(
					'__prototypes' => array(
						'TYPO3.Foo:Bar2' => array(
							'baz' => 'Foo',
							'test2' => 42,
							'blah' => array(
								'__eelExpression' => 'my.expression()',
								'__value' => NULL,
								'__objectType' => NULL
							),
							'blah2' => array(
								'__eelExpression' => "my.expression('asdf')",
								'__value' => NULL,
								'__objectType' => NULL
							),
							'blah3' => array(
								'__eelExpression' => 'my.expression("asdf")',
								'__value' => NULL,
								'__objectType' => NULL
							),
						)
					)
				)
			)
		);

		return $expectedParseTree;
	}

	/**
	 * @test
	 */
	public function parserCorrectlyParsesFixture16() {
		$fixture = __DIR__ . '/Fixtures/ParserTestTypoScriptFixture16.ts2';
		$sourceCode = file_get_contents($fixture, FILE_TEXT);

		$expectedParseTree = $this->getExpectedParseTreeForFixture16();

		$actualParseTree = $this->parser->parse($sourceCode, $fixture);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 16');
	}

	/**
	 * @test
	 */
	public function parserCorrectlyParsesFixture17() {
		$fixture = __DIR__ . '/Fixtures/ParserTestTypoScriptFixture17.ts2';
		$sourceCode = file_get_contents($fixture, FILE_TEXT);

		$expectedParseTree = $this->getExpectedParseTreeForFixture16();

		// Check that values were overridden by fixture #17:
		$expectedParseTree['__prototypes']['TYPO3.Foo:Bar2']['baz'] = 'New Value';

		// Set the default namespace to TYPO3.Neos - that's what Neos does as well in Domain\Service\TypoScriptService:
		$this->parser->setObjectTypeNamespace('default', 'TYPO3.Neos');

		// Make sure that the namespace declaration for "default" is also available when fixture #17b is parsed:
		$expectedParseTree['object'] = array(
			'__objectType' => 'TYPO3.Neos:Text',
			'__value' => NULL,
			'__eelExpression' => NULL
		);

		$actualParseTree = $this->parser->parse($sourceCode, $fixture);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 17');
	}

	/**
	 * Checks if namespace declarations are expanded correctly
	 *
	 * @test
	 */
	public function parserCorrectlyParsesFixture18() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture18.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'object1' => array(
				'__objectType' => 'TYPO3.Neos:Text',
				'__value' => NULL,
				'__eelExpression' => NULL
			),
			'object2' => array(
				'__objectType' => 'TYPO3.Neos:Text',
				'__value' => NULL,
				'__eelExpression' => NULL
			),
			'object3' => array(
				'__objectType' => 'TYPO3.Schirmchen:Text',
				'__value' => NULL,
				'__eelExpression' => NULL
			),
			'object4' => array(
				'__objectType' => 'TYPO3.Future:Text',
				'__value' => NULL,
				'__eelExpression' => NULL
			),
			'__prototypes' => array (
				'TYPO3.Neos:Foo' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\ArrayImplementation'
					)
				),
				'TYPO3.Neos:Bar' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\ArrayImplementation'
					)
				),
				'TYPO3.Schirmchen:Baz' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\ArrayImplementation'
					)
				),
				'TYPO3.Future:Quux' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\ArrayImplementation'
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 18.');
	}


	/**
	 * Checks if simple values (string, boolean, integer) are parsed correctly
	 *
	 * @test
	 */
	public function parserCorrectlyParsesFixture19() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture19.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'somepath' => array(
				'stringValue' => 'A string value',
				'booleanValueFalse' => FALSE,
				'booleanValueTrue' => TRUE,
				'integerValue' => 42
			),
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 19.');
	}

}
