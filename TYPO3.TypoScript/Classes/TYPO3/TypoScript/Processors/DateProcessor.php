<?php
namespace TYPO3\TypoScript\Processors;

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
 * Transforms an UNIX timestamp according to the given format.
 * For the possible format values, look at the php date() function.
 *
 * Please note that the incoming UNIX timestamp is intrinsically considered UTC,
 * if another time zone is intended, the setTimezone() setter has to be used.
 * Using this, the original time will be shifted accordingly, meaning the timestamp
 * 1185279917 representing UTC 2007-07-24 12:25:17 will result into Japan 2007-07-24 21:25:17
 * in case the setTimezone() is set to 'Japan' timezone.
 *
 */
class DateProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * A format string, according to the rules of the PHP date() function
	 * @var string
	 */
	protected $format;

	/**
	 * Timezone string identifier as designated by PHP
	 * @see http://php.net/manual/en/timezones.php
	 * @var string
	 */
	protected $timezone;

	/**
	 * Set the format to use, according to the rules of the php date() function.
	 *
	 * @param string $format format string
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Returns the format to use, according to the rules of the php date() function.
	 *
	 * @return string $format format string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Sets the timezone to apply, see http://php.net/manual/en/timezones.php.
	 *
	 * @param string $timezone
	 * @return void
	 */
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
	}

	/**
	 * Returns the timezone to apply, see http://php.net/manual/en/timezones.php.
	 *
	 * @return string
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * Transforms an UNIX timestamp according to the given format.
	 * For the possible format values, look at the PHP date() function.
	 *
	 * @param mixed $subject The UNIX timestamp to transform, objects are cast to string
	 * @return string The processed timestamp
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function process($subject) {
		if ($subject === '') {
			return '';
		}

		$timestamp = is_object($subject) ? (string)$subject : $subject;
		$format = (string)$this->format;
		if ($timestamp <= 0) {
			throw new \TYPO3\TypoScript\Exception('The given timestamp value was zero or negative, this is not allowed.', 1185282371);
		}

		$dateTime = \DateTime::createFromFormat('U', $timestamp);
		if ($this->timezone !== NULL) {
			try {
				$timezoneArgument = new \DateTimeZone($this->timezone);
			} catch (\Exception $exception) {
				throw new \TYPO3\TypoScript\Exception(sprintf('Attempting to set the given time zone "%s" threw exception "%s".', $this->timezone, $exception->getMessage()), 1343308079, $exception);
			}
			$dateTime->setTimezone($timezoneArgument);
		}

		return $dateTime->format($format);
	}
}
?>