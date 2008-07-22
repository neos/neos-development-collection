<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id:F3_FLOW3_Component_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the TypoScript Text object
 *
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id:F3_FLOW3_Component_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_TypoScript_TextTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if a Text object renders a simple content without any processors involved.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textObjectRendersSimpleContentCorrectly() {
		$testString = 'Skårhøj is a nice name for special character testing.';
		$text = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$text->setValue($testString);
		$this->assertEquals($testString, $text->getRenderedContent(), 'The Text object did not return the expected content during the basic check.');
	}

	/**
	 * Checks if a Text object renders a content using a wrap and a crop processor
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textObjectRendersContentWithWrapProcessorCorrectly() {
		$testString = 'Blåbärskaka';
		$text = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$text->setValue($testString);

		$processorChain = $this->componentFactory->getComponent('F3_TypoScript_ProcessorChain');
		$processors = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Processors');

		$processorChain->setProcessorInvocation(1, $this->componentFactory->getComponent('F3_TypoScript_ProcessorInvocation', $processors, 'processor_crop', array(9, ' ...')));
		$processorChain->setProcessorInvocation(2, $this->componentFactory->getComponent('F3_TypoScript_ProcessorInvocation', $processors, 'processor_wrap', array('<strong>', '</strong>')));

		$text->setPropertyProcessorChain('value', $processorChain);

		$expectedResult = '<strong>Blåbärska ...</strong>';
		$this->assertEquals($expectedResult, $text->getRenderedContent(), 'The Text object did not return the expected content during the basic check.');
	}

	/**
	 * Checks if the content of a Text object is a reference to another Text object, the whole thing renders correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textObjectRendersContentOfOtherTextObjectCorrectly() {
		$processors = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Processors');

		$firstText = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$firstText->setValue('first text object');

		$processorChain = $this->componentFactory->getComponent('F3_TypoScript_ProcessorChain');
		$processorChain->setProcessorInvocation(1, $this->componentFactory->getComponent('F3_TypoScript_ProcessorInvocation', $processors, 'processor_wrap', array('<em>', '</em>')));
		$firstText->setPropertyProcessorChain('value', $processorChain);

		$secondText = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$secondText->setValue($firstText);

		$secondProcessorChain = $this->componentFactory->getComponent('F3_TypoScript_ProcessorChain');
		$secondProcessorChain->setProcessorInvocation(1, $this->componentFactory->getComponent('F3_TypoScript_ProcessorInvocation', $processors, 'processor_wrap', array('<strong>', '</strong>')));
		$secondText->setPropertyProcessorChain('value', $secondProcessorChain);

		$expectedResult = '<strong><em>first text object</em></strong>';
		$this->assertEquals($expectedResult, $secondText->getRenderedContent(), 'The Text object did not return the expected content during the referenced content check.');

	}

	/**
	 * Checks if we can render the Text object as a string by simply casting it
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textObjectRendersItselfOnStringCast() {
		$firstText = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$firstText->setValue('first text object');

		$expectedResult = 'first text object';
		$this->assertEquals($expectedResult, (string)$firstText, 'The Text object did not return the expected content while casting it to string.');

		$secondText = $this->componentFactory->getComponent('F3_TYPO3_TypoScript_Text');
		$secondText->setValue(12345343232);

		$expectedResult = '12345343232';
		$this->assertEquals($expectedResult, (string)$secondText, 'The Text object did not return the expected content while casting it to string.');
	}
}
?>