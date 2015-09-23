<?php
namespace TYPO3\TypoScript\Exception;

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
