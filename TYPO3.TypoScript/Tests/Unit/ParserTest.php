<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('Fixtures/Text.php');
require_once('Fixtures/Page.php');
require_once('Fixtures/ContentArray.php');
require_once('Fixtures/ObjectWithArrayProperty.php');
require_once('Fixtures/Processors.php');

/**
 * Testcase for the TypoScript Parser
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TypoScript\Parser
	 */
	protected $parser;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnCallback(array($this, 'objectManagerIsRegisteredCallback')));

		$this->parser = new \F3\TypoScript\Parser($this->mockObjectManager);
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
			case 'F3\TypoScript\Fixtures\Text' :
			case 'F3\TypoScript\Fixtures\Page' :
			case 'F3\TypoScript\Fixtures\ContentArray' :
			case 'F3\TypoScript\Fixtures\ObjectWithArrayProperty' :
			case 'F3\TypoScript\Fixtures\Processors' :
				return TRUE;
			default :
				return FALSE;
		}
	}

	/**
	 * Checks if the TypoScript parser returns an object tree
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserReturnsObjectTreeArray() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture01.ts2', FILE_TEXT);
		$objectTree = $this->parser->parse($sourceCode);
		$this->assertType('array', $objectTree, 'The TypoScript parser did not return an array.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 01
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture01() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture01.ts2', FILE_TEXT);

		$expectedObjectTree['test'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['test']->setValue('Hello world!');
		$expectedObjectTree['secondTest'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['secondTest']->setValue(23);
		$expectedObjectTree['thirdTest'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['thirdTest']->setValue('Fully Qualified Object');

		$this->mockObjectManager->expects($this->exactly(3))->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$actualObjectTree = $this->parser->parse($sourceCode);

		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 01.');
		$this->assertSame($expectedObjectTree['secondTest']->getValue(), $actualObjectTree['secondTest']->getValue(), 'The numeric value was not really the same after parsing fixture 01.');
	}

	/**
	 * Checks if an arbitrary string after a namespace declaration throws an exception
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserThrowsSyntaxExceptionStringAfterNamespaceDeclaration() {
		$sourceCode = "namespace: cms=\F3\TypoScript\Fixtures xyz";
		try {
			$this->parser->parse($sourceCode);
			$this->fail('String after namespace declaration did not throw an exception.');
		} catch (\Exception $exception) {

		}
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 02
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture02() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture02.ts2', FILE_TEXT);

		$expectedObjectTree['myObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['myObject']->setValue("Sorry, we're closed.");
		$expectedObjectTree['anotherObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['anotherObject']->setValue('And I said: "Hooray"');
		$expectedObjectTree['kaspersObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['kaspersObject']->setValue('The end of this line is a backslash\\');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 02.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 03
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture03() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture03.ts2', FILE_TEXT);

		$expectedObjectTree['object1']['mySubObject']['mySubSubObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']['mySubObject']['mySubSubObject']->setValue("Espresso is a fine beverage.");

		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\ObjectWithArrayProperty;
		$expectedObjectTree['object2']->setTheArray(array('theKey' => 'theValue'));

		$textObject3 = new \F3\TypoScript\Fixtures\Text;
		$textObject3->setValue('theValue');
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\ObjectWithArrayProperty;
		$expectedObjectTree['object3']->setTheArray(array('theKey' => $textObject3));

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 03.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 04
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture04() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture04.ts2', FILE_TEXT);

		$expectedObjectTree['myArrayObject'] = new \F3\TypoScript\Fixtures\ContentArray;
		$expectedObjectTree['myArrayObject'][10] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['myArrayObject'][10]->setValue('Hello ');
		$expectedObjectTree['myArrayObject'][20] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['myArrayObject'][20]->setValue('world!');
		$expectedObjectTree['myArrayObject'][30] = new \F3\TypoScript\Fixtures\ContentArray;
		$expectedObjectTree['myArrayObject'][30][20] = new \F3\TypoScript\Fixtures\ContentArray;
		$expectedObjectTree['myArrayObject'][30][20][10] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['myArrayObject'][30][20][10]->setValue('Huh?');
		$expectedObjectTree['anotherObject']['sub1']['sub2']['sub3'] = new \F3\TypoScript\Fixtures\ContentArray;
		$expectedObjectTree['anotherObject']['sub1']['sub2']['sub3'][1] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['anotherObject']['sub1']['sub2']['sub3'][1]->setValue('Yawn');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 04.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 05
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture05() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture05.ts2', FILE_TEXT);

		$expectedObjectTree['firstObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['firstObject']->setValue('Go outside. The graphics are AMAZING!');
		$expectedObjectTree['secondObject']['subObject'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['secondObject']['subObject']->setValue('27°C and a blue sky.');
		$expectedObjectTree['thirdObject']['subObject']['subSubObject']['someMessage'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['thirdObject']['subObject']['subSubObject']['someMessage']->setValue('Fully or hard tail?');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 05.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 06
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture06() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture06.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Hello world');
		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue('Hello world');
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue("I didn't have a coffee yet!");
		$expectedObjectTree['object4'] = new \F3\TypoScript\Fixtures\Text;
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
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture07.ts2', FILE_TEXT);

		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue('');
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue('');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 07.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 08
	 *
	 * @todo Implement lazy rendering support for variable substitutions
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture08() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture08.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Hello world!');
		$expectedObjectTree['object2'] = clone $expectedObjectTree['object1'];
		$expectedObjectTree['lib']['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['lib']['object3']->setValue('Another message');
		$expectedObjectTree['lib']['object4'] = clone $expectedObjectTree['lib']['object3'];
		$expectedObjectTree['lib']['object5'] = clone $expectedObjectTree['lib']['object3'];
		$expectedObjectTree['lib']['object6'] = clone $expectedObjectTree['object1'];
		$expectedObjectTree['object7'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object7']->setValue($expectedObjectTree['object1']->getValue());
		$expectedObjectTree['object8'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object8']->setValue('I say "Hello world!"');

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 08.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 09
	 *
	 * @todo Implement lazy rendering support for variable substitutions
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture09() {
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture09.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Quien busca el peligro, perece en él');
		$expectedObjectTree['object2'] = $expectedObjectTree['object1'];
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
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
		$processors = new \F3\TypoScript\Fixtures\Processors;
		$this->mockObjectManager->expects($this->any())->method('get')->with('F3\TypoScript\Fixtures\Processors')->will($this->returnValue($processors));
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture10.ts2', FILE_TEXT);
		$processorObject = new \F3\TypoScript\Fixtures\Processors;

		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain;
		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue('Hello world!');
		$expectedObjectTree['object1']->setPropertyProcessorChain('value', $propertyProcessorChain);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('<strong>', '</strong>'));
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);

		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain;
		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue('Bumerang');
		$expectedObjectTree['object2']->setPropertyProcessorChain('value', $propertyProcessorChain);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('ein ', ';'));
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('War ', ''));
		$propertyProcessorChain->setProcessorInvocation(3, $processorInvocation);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('einmal (vielleicht auch zweimal) ', ''));
		$propertyProcessorChain->setProcessorInvocation(2, $processorInvocation);

		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain;
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue('345');
		$expectedObjectTree['object3']->setPropertyProcessorChain('value', $propertyProcessorChain);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('2', '6'));
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('1', '789 ...'));
		$propertyProcessorChain->setProcessorInvocation(2, $processorInvocation);

		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain;
		$expectedObjectTree['object4'] = new \F3\TypoScript\Fixtures\ContentArray;
		$expectedObjectTree['object4'][10] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object4'][10]->setValue('cc');
		$expectedObjectTree['object4'][10]->setPropertyProcessorChain('value', $propertyProcessorChain);
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_wrap', array('su', 'ess'));
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 10.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 11
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture11() {
		$processors = new \F3\TypoScript\Fixtures\Processors;
		$this->mockObjectManager->expects($this->any())->method('get')->with('F3\TypoScript\Fixtures\Processors')->will($this->returnValue($processors));
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture11.ts2', FILE_TEXT);

		$processorObject = new \F3\TypoScript\Fixtures\Processors;
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_substring', array(6, 5));
		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain();
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);

		$processorInvocation2 = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_substring', array(-6));
		$propertyProcessorChain->setProcessorInvocation(2, $processorInvocation2);

		$expectedObjectTree = array();
		$expectedObjectTree['page'] = new \F3\TypoScript\Fixtures\Page;
		$expectedObjectTree['page'][10] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['page'][10]->setValue('Hello World!');
		$expectedObjectTree['page'][10]->setPropertyProcessorChain('value', $propertyProcessorChain);

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree);
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 12
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function parserCorrectlyParsesFixture12() {
		$processors = new \F3\TypoScript\Fixtures\Processors;
		$this->mockObjectManager->expects($this->any())->method('get')->with('F3\TypoScript\Fixtures\Processors')->will($this->returnValue($processors));
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture12.ts2', FILE_TEXT);

		$processorObject = new \F3\TypoScript\Fixtures\Processors;
		$processorInvocation = new \F3\TypoScript\ProcessorInvocation($processorObject, 'processor_multiply', array(1.5));
		$propertyProcessorChain = new \F3\TypoScript\ProcessorChain();
		$propertyProcessorChain->setProcessorInvocation(1, $processorInvocation);

		$expectedObjectTree = array();
		$expectedObjectTree['page'] = new \F3\TypoScript\Fixtures\Page;
		$expectedObjectTree['page'][10] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['page'][10]->setValue('10');
		$expectedObjectTree['page'][10]->setPropertyProcessorChain('value', $propertyProcessorChain);

		$actualObjectTree = $this->parser->parse($sourceCode);
		$this->assertEquals($expectedObjectTree, $actualObjectTree);
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 13
	 *
	 * TODO Not implemented yet, see #7552
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture13() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture13.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue("\n\tSome text.\n");
		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue("\n\tSome text.\n");
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue("The text might start\n\tat some line and\n\tend at some other line");
		$expectedObjectTree['object4'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object4']->setValue("The text might start\n\tat some line and\n\tend at some other line");
		$expectedObjectTree['object5'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object5']->setValue("The text might start\n\tat \"some\" line and\n\tend at some other line");
		$expectedObjectTree['object6'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object6']->setValue("The text might start\n\tat 'some' line and\n\tend at some other line");

		$this->mockObjectManager->expects($this->exactly(6))->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$actualObjectTree = $this->parser->parse($sourceCode);

		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 13.');
	}

	/**
	 * checks if the object tree returned by the TypoScript parser reflects source code fixture 14
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserCorrectlyParsesFixture14() {
		$sourceCode = file_get_contents(__DIR__ . '/Fixtures/ParserTestTypoScriptFixture14.ts2', FILE_TEXT);

		$expectedObjectTree['object1'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object1']->setValue("Curly braces like this {} or {that} are ignored.");
		$expectedObjectTree['object2'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object2']->setValue("Curly braces like this {} or {that} are ignored.");
		$expectedObjectTree['object3'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object3']->setValue("Slashes // or hashes # or /* signs are not interpreted as comments.");
		$expectedObjectTree['object4'] = new \F3\TypoScript\Fixtures\Text;
		$expectedObjectTree['object4']->setValue("Slashes // or hashes # or /* signs are not interpreted as comments.");

		$this->mockObjectManager->expects($this->exactly(4))->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$actualObjectTree = $this->parser->parse($sourceCode);

		$this->assertEquals($expectedObjectTree, $actualObjectTree, 'The object tree was not as expected after parsing fixture 14.');
	}

}

?>