<?php

class F3_TYPO3CR_Storage_Search_LuceneKeywordAnalyser extends Zend_Search_Lucene_Analysis_Analyzer_Common {

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
     * Object constructor
     *
     * @throws Zend_Search_Lucene_Exception
     */
    public function __construct()
    {
        if (@preg_match('/\pL/u', 'a') != 1) {
            // PCRE unicode support is turned off
            require_once 'Zend/Search/Lucene/Exception.php';
            throw new Zend_Search_Lucene_Exception('Utf8Num analyzer needs PCRE unicode support to be enabled.');
        }
    }

    /**
     * Reset token stream
     */
    public function reset()
    {
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
	 * @return Zend_Search_Lucene_Analysis_Token|null
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

			$token = $this->normalize(new Zend_Search_Lucene_Analysis_Token($matchedWord, $startPos, $endPos));
		} while ($token === NULL); // try again if token is skipped

		return $token;
	}

}

?>