<?php
namespace TYPO3\TypoScript\Core\ExceptionHandlers;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $this->systemLogger->log('Absorbed Exception: ' . $exception->getMessage(), LOG_DEBUG, array('typoScriptPath' => $typoScriptPath, 'referenceCode' => $referenceCode), 'TYPO3.TypoScript', \TYPO3\TypoScript\Core\ExceptionHandlers\AbsorbingHandler::class);
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
