<?php
namespace TYPO3\TypoScript\Fixtures;

/**
 * subclass to test implementation of abstract class
 *
 * TestAbsorbingHandler
 */
class AbstractRenderingExceptionHandler extends \TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler {

	/**
	 * @var string
	 */
	protected $typoScriptPath;

	/**
	 * @var \Exception
	 */
	protected $exception;

	/**
	 * @var string
	 */
	protected $referenceCode;

	/**
	 * @var string
	 */
	protected $message = 'message';

	/**
	 * received exception
	 *
	 * @return \Exception
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * resulting message
	 *
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return mixed
	 */
	public function getReferenceCode() {
		return $this->referenceCode;
	}

	/**
	 * @return mixed
	 */
	public function getTypoScriptPath() {
		return $this->typoScriptPath;
	}

	/**
	 * dummy implementation of message-generation-stub
	 *
	 * @param string $typoScriptPath path causing the exception
	 * @param \Exception $exception exception to handle
	 * @param integer $referenceCode reference code for the exception
	 * @return string
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode = NULL) {
		$this->typoScriptPath = $typoScriptPath;
		$this->exception = $exception;
		$this->referenceCode = $referenceCode;
		return $this->message;
	}
}
