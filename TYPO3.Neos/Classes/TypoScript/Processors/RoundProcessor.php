<?php
declare(ENCODING = 'utf-8');
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
 * Processor that rounds a given float value.
 * If integer given, nothing happens.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RoundProcessor implements \F3\TypoScript\ProcessorInterface {

	/**
	 * Number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 * @var integer
	 */
	protected $precision = NULL;

	/**
	 * @param integer $precision the number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 * @return void
	 */
	public function setPrecision($precision) {
		$this->precision = $precision;
	}

	/**
	 * @return integer the number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Rounds a given float value. If integer given, nothing happens.
	 *
	 * @param float/string $subject The subject to round.
	 * @return float Rounded value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		if (!is_numeric($subject)) throw new \F3\TypoScript\Exception('Expected an integer or float passed, ' . gettype($subject) . ' given.', 1224053300);
		$subject = floatval($subject);
		if ($this->precision != NULL && !is_int($this->precision)) throw new \F3\TypoScript\Exception('Precision must be an integer.');
		return round($subject, $this->precision);
	}
}
?>
