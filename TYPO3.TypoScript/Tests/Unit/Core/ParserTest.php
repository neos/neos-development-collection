<?php
namespace TYPO3\TypoScript\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
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
class ParserTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Core\Parser
	 */
	protected $parser;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
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

		//$this->mockObjectManager->expects($this->exactly(3))->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$expectedParseTree = array(
			'test' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Hello world!'
			),
			'secondTest' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 23
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
		$this->markTestIncomplete();
		$sourceCode = "namespace: cms=\-notvalid-\TypoScript\Fixtures";
		$this->parser->parse($sourceCode);
	}

	/**
	 * Checks if referring to an unknown namespace throws an exception
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function parserThrowsTypoScriptExceptionWhenReferringToUnknownNamespaceReferenceInProcessorCall() {
		$this->markTestIncomplete();
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = "namespace: default = TYPO3\TypoScript\Fixtures
			foo = Text
			foo.value << 1.unknownNamespace:wrap()";
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
				'value' => "Sorry, we're closed -- double quotes like \" do not need to be escaped."
			),
			'anotherObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'And I said: "Hooray" -- single quotes like \' do not need to be escaped'
			),
			'kaspersObject' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
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
						'value' => 'Espresso is a fine beverage.'
					)
				)
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:ObjectWithArrayProperty',
				'theArray' => array(
					'theKey' => 'theValue'
				)
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:ObjectWithArrayProperty',
				'theArray' => array(
					'theKey' => array(
						'__objectType' => 'TYPO3.TypoScript:Text',
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
				10 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Hello ',
					'__meta' => array(
						'position' => 'after 10'
					)
				),
				20 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'world!'
				),
				30 => array(
					'__objectType' => 'TYPO3.TypoScript:ContentArray',
					20 => array(
						'__objectType' => 'TYPO3.TypoScript:ContentArray',
						10 => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
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
							1 => array(
								'__objectType' => 'TYPO3.TypoScript:Text',
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
				'value' => 'Go outside. The graphics are AMAZING!'
			),
			'firstObject2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Go outside. The graphics are AMAZING!'
			),
			'firstObject3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Overridden value'
			),
			'secondObject' => array(
				'subObject' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => '27°C and a blue sky.'
				)
			),
			'thirdObject' => array(
				'subObject' => array(
					'subSubObject' => array(
						'someMessage' => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
							'value' => 'Fully or hard tail?',
							'value2' => 'I don\'t know.'
						)
					),
					'anotherSubSubObject' => array(
						'someMessage' => array(
							'__objectType' => 'TYPO3.TypoScript:Text',
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
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 06
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture06() {
		$this->markTestIncomplete('Decide what to do with the "Variables" feature');
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture06.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Hello world');
		$expectedObjectTree['object2'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue('Hello world');
		$expectedObjectTree['object3'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue("I didn't have a coffee yet!");
		$expectedObjectTree['object4'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object4']->setValue("Hello, Kasper Skårhøj!");

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 06.');
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
				# TODO: Instead of "NULL" should this just be removed?
				'value' => NULL
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
				'value' => 'Hello world!',
				'foo' => 42
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Hello world!',
				'foo' => 42
			),
			'lib' => array(
				'object3' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Another message'
				),
				'object4' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Another message'
				),
				'object5' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Another message'
				),
				'object6' => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Hello world!',
					'foo' => 21
				),
			),
			'object7' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Hello world!'
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 08.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 09
	 *
	 * @todo Implement lazy rendering support for variable substitutions
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture09() {
		$this->markTestIncomplete('Do not know yet what to do with the reference operator');
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture09.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Quien busca el peligro, perece en él');
		$expectedObjectTree['object2'] = $expectedObjectTree['object1'];
		$expectedObjectTree['object3'] = new \TYPO3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue('Don Quijote dice: "Quien busca el peligro, perece en él"');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 09.');
		$this->assertSame($actualObjectTree['object1'], $actualObjectTree['object2'], 'The two reference objects are not identical after parsing fixture 09.');
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
			'object1' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Hello world!',
				'__processors' => array(
					'value' => array(
						1 => array(
							'prefix' => '<strong>',
							'suffix' => '</strong>',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
						)
					),
					'__all' => array(
						1 => array(
							'prefix' => '<div>',
							'suffix' => '</div>',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
						)
					)
				)
			),
			'__prototypes' => array(
				'TYPO3.TypoScript:Foo' => array(
					'__processors' => array(
						'__all' => array(
							1 => array(
								'prefix' => '<div>',
								'suffix' => '</div>',
								'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
							)
						)
					)
				)
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Bumerang',
				'__processors' => array(
					'value' => array(
						1 => array(
							'prefix' => 'ein ',
							'suffix' => ';',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor',
						),
						2 => array(
							'prefix' => 'einmal (vielleicht auch zweimal) ',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor',
						),
						3 => array(
							'prefix' => 'War ',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
						),
					)
				)
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 345,
				'__processors' => array(
					'value' => array(
						1 => array(
							'prefix' => 2,
							'suffix' => '6',
							'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
						)
					)
				)
			),
			'object4' => array(
				'__objectType' =>'TYPO3.TypoScript:ContentArray',
				10 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'cc',
					'__processors' => array(
						'value' => array(
							1 => array(
								'prefix' => 'su',
								'suffix' => 'ess',
								'__processorClassName' => 'TYPO3\TypoScript\Processors\WrapProcessor'
							)
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 10.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 11
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture11() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture11.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'page' => array(
				'__objectType' => 'TYPO3.TypoScript:Page',
				10 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => 'Hello World!',
					'__processors' => array(
						'value' => array(
							1 => array(
								'start' => 6,
								'length' => 5,
								'__processorClassName' => 'TYPO3\TypoScript\Processors\SubstringProcessor'
							),
							2 => array(
								'start' => -6,
								'__processorClassName' => 'TYPO3\TypoScript\Processors\SubstringProcessor'
							)
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 11.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 12
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function parserCorrectlyParsesFixture12() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture12.ts2', FILE_TEXT);

		$expectedParseTree = array(
			'page' => array(
				'__objectType' => 'TYPO3.TypoScript:Page',
				10 => array(
					'__objectType' => 'TYPO3.TypoScript:Text',
					'value' => '10',
					'__processors' => array(
						'value' => array(
							1 => array(
								'factor' => 1.5,
								'__processorClassName' => 'TYPO3\TypoScript\Processors\MultiplyProcessor'
							)
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 12.');
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
				'value' => chr(10) . '	Some text.' . chr(10)
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => chr(10) . '	Some text.' . chr(10)
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'The text might start' . chr(10) . '	at some line\' and' . chr(10) . '	end at some other line'
			),
			'object4' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'The text might start' . chr(10) . '	at some line "and' . chr(10) . '	end at some other line'
			),
			'object5' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'The text might start' . chr(10) . '	at "some" line and' . chr(10) . '	end at some other line'
			),
			'object6' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
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
				'value' => 'Curly braces like this {} or {that} are ignored.'
			),
			'object2' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Curly braces like this {} or {that} are ignored.'
			),
			'object3' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
				'value' => 'Slashes // or hashes # or /* signs are not interpreted as comments.'
			),
			'object4' => array(
				'__objectType' => 'TYPO3.TypoScript:Text',
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

		$this->markTestIncomplete('This test fails so far...');
		$expectedParseTree = array(
			'foo' => array(
				'__objectType' => 'TYPO3.TypoScript:Bar',
				'prop' => 'myValue'
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 15.');
	}

	/**
	 * @test
	 */
	public function parserCorrectlyParsesFixture16($fixtureNumber = 16) {
		$fixture = __DIR__ . '/Fixtures/ParserTestTypoScriptFixture' . $fixtureNumber . '.ts2';
		$sourceCode = file_get_contents($fixture, FILE_TEXT);

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
								'__eelExpression' => 'my.expression()'
							),
							'blah2' => array(
								'__eelExpression' => "my.expression('asdf')"
							),
							'blah3' => array(
								'__eelExpression' => 'my.expression("asdf")'
							),
						)
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode, $fixture);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture ' . $fixtureNumber);
	}

	/**
	 * @test
	 */
	public function parserCorrectlyParsesFixture17() {
		$this->parserCorrectlyParsesFixture16(17);
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
				'__objectType' => 'TYPO3.Phoenix:Text'
			),
			'object2' => array(
				'__objectType' => 'TYPO3.Phoenix:Text'
			),
			'object3' => array(
				'__objectType' => 'TYPO3.Schirmchen:Text'
			),
			'object4' => array(
				'__objectType' => 'TYPO3.Future:Text'
			),
			'__prototypes' => array (
				'TYPO3.Phoenix:Foo' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\TypoScriptArrayRenderer'
					)
				),
				'TYPO3.Phoenix:Bar' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\TypoScriptArrayRenderer'
					)
				),
				'TYPO3.Schirmchen:Baz' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\TypoScriptArrayRenderer'
					)
				),
				'TYPO3.Future:Quux' => array(
					'__meta' => array(
						'class' => 'TYPO3\TypoScript\TypoScriptObjects\TypoScriptArrayRenderer'
					)
				)
			)
		);

		$actualParseTree = $this->parser->parse($sourceCode);
		$this->assertSame($expectedParseTree, $actualParseTree, 'The parse tree was not as expected after parsing fixture 18.');
	}

}
?>