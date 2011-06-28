<?php
namespace F3\TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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

/**
 * Testcase for the TypoScript RoundProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RoundProcessorTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\TYPO3\TypoScript\Processors\RoundProcessor
	 */
	protected $roundProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->roundProcessor = new \F3\TYPO3\TypoScript\Processors\RoundProcessor();
	}

	/**
	 * Checks if the round function works as expected when having regular input
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function roundWorksForFloatParameters() {
		$subject = 5.3;
		$expected = 5;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.499999;
		$expected = 41;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.5;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 42;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function works as expected when having an additional precision parameter
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function roundWorksWithPrecisionParameter() {
		$subject = 5.31;
		$this->roundProcessor->setPrecision(1);
		$expected = 5.3;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$this->roundProcessor->setPrecision(-1);
		$expected = 40;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$this->roundProcessor->setPrecision(1);
		$expected = 41.5;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function fails if passed a string
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \F3\TypoScript\Exception
	 */
	public function roundThrowsExceptionOnInvalidParameters() {
		$subject = 'Transition days rock.';
		$this->roundProcessor->process($subject);
	}

	/**
	 * Checks if the round function fails if precision is a string
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \F3\TypoScript\Exception
	 */
	public function roundThrowsExceptionOnInvalidPrecisionParameters() {
		$subject = 'Transition days rock.';
		$this->roundProcessor->setPrecision('invalidValue');
		$this->roundProcessor->process($subject);
	}
}
?>
