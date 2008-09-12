<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Storage::Search;

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
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3::TYPO3CR::Storage::Backend::PDO.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * A keyword analyser that acts non-tokenising
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3::TYPO3CR::Storage::Backend::PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @todo Make it use the PHP6 package or get rid of the iconv dependency in some other way
 */
class LuceneKeywordAnalyser extends ::Zend_Search_Lucene_Analysis_Analyzer_Common {

	/**
	 * Current char position in an UTF-8 stream
	 *
	 * @var integer
	 */
	private $_position;

	/**
	 * Current binary position in an UTF-8 stream
	 *
	 * @var integer
	 */
	private $_bytePosition;

	/**
	 * Reset token stream
	 */
	public function reset() {
		$this->_position     = 0;
		$this->_bytePosition = 0;

			// convert input into UTF-8
		if (strcasecmp($this->_encoding, 'utf8' ) != 0  &&
			strcasecmp($this->_encoding, 'utf-8') != 0 ) {
				$this->_input = iconv($this->_encoding, 'UTF-8', $this->_input);
				$this->_encoding = 'UTF-8';
		}
	}

	/**
	 * Tokenization stream API
	 * Get next token
	 * Returns null at the end of stream
	 *
	 * @return ::Zend_Search_Lucene_Analysis_Token|NULL
	 */
	public function nextToken() {
		if ($this->_input === NULL) {
			return NULL;
		}

		do {
			if (! preg_match('/[\p{L}\p{N}_:]+/u', $this->_input, $match, PREG_OFFSET_CAPTURE, $this->_bytePosition)) {
				return NULL;
			}

				// matched string
			$matchedWord = $match[0][0];

				// binary position of the matched word in the input stream
			$binStartPos = $match[0][1];

				// character position of the matched word in the input stream
			$startPos = $this->_position +
				iconv_strlen(substr($this->_input,
					$this->_bytePosition,
					$binStartPos - $this->_bytePosition),
					'UTF-8');
				// character postion of the end of matched word in the input stream
			$endPos = $startPos + iconv_strlen($matchedWord, 'UTF-8');

			$this->_bytePosition = $binStartPos + strlen($matchedWord);
			$this->_position     = $endPos;

			$token = $this->normalize(new ::Zend_Search_Lucene_Analysis_Token($matchedWord, $startPos, $endPos));
		} while ($token === NULL); // try again if token is skipped

		return $token;
	}

}

?>