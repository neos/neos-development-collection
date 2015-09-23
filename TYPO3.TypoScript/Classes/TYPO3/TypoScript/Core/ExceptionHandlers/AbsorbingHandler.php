<?php
namespace TYPO3\TypoScript\Core\ExceptionHandlers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Renders the element as an empty string
 */
class AbsorbingHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Returns an empty string
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $this->systemLogger->log('Absorbed Exception: ' . $exception->getMessage(), LOG_DEBUG, array('typoScriptPath' => $typoScriptPath, 'referenceCode' => $referenceCode), 'TYPO3.TypoScript', 'TYPO3\TypoScript\Core\ExceptionHandlers\AbsorbingHandler');
        return '';
    }

    /**
     * The absorbing handler is meant to catch loose evaluation errors (like missing assets) in a useful way,
     * therefor caching is desired.
     *
     * @param string $typoScriptPath
     * @param \Exception $exception
     * @return boolean
     */
    protected function exceptionDisablesCache($typoScriptPath, \Exception $exception)
    {
        return false;
    }
}
