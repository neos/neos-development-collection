<?php
namespace TYPO3\TypoScript\Exception;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This exception wraps an inner exception during rendering.
 */
class RuntimeException extends \TYPO3\TypoScript\Exception
{
    /**
     * @var string
     */
    protected $typoScriptPath;

    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     * @param null $typoScriptPath
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $typoScriptPath = null)
    {
        parent::__construct($message, $code, $previous);

        $this->typoScriptPath = $typoScriptPath;
    }

    /**
     * @return null|string
     */
    public function getTypoScriptPath()
    {
        return $this->typoScriptPath;
    }
}
