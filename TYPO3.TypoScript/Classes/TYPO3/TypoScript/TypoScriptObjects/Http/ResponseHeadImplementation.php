<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Headers;
use TYPO3\Flow\Http\Response;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Response Head generate a standard HTTP response head
 * @api
 */
class ResponseHeadImplementation extends AbstractTypoScriptObject {

	/**
	 * Get HTTP protocol version
	 *
	 * @return string
	 */
	public function getHttpVersion() {
		$httpVersion = $this->tsValue('httpVersion');
		if ($httpVersion === NULL) {
			$httpVersion = 'HTTP/1.1';
		}
		return trim($httpVersion);
	}

	/**
	 * @return integer
	 */
	public function getStatusCode() {
		$statusCode = $this->tsValue('statusCode');
		if ($statusCode === NULL) {
			$statusCode = 200;
		}
		if (Response::getStatusMessageByCode($statusCode) === 'Unknown Status') {
			throw new \InvalidArgumentException('Unknown HTTP status code', 1412085703);
		}
		return (integer)$statusCode;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		$headers = $this->tsValue('headers');
		if (!is_array($headers)) {
			$headers = array();
		}
		return $headers;
	}

	/**
	 * Just return the processed value
	 *
	 * @return mixed
	 */
	public function evaluate() {
		$httpResponse = new Response();
		$httpResponse->setStatus($this->getStatusCode());
		$httpResponse->setHeaders(new Headers());

		foreach ($this->getHeaders() as $name => $value) {
			$httpResponse->setHeader($name, $value);
		}

		return implode("\r\n", $httpResponse->renderHeaders()) . "\r\n\r\n";
	}
}
