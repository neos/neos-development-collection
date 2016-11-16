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
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\TypoScript\Exception\RuntimeException;

/**
 * Wrap the exception to retain the typoScript path at which it was originally thrown
 */
class BubblingHandler extends AbstractRenderingExceptionHandler
{
    /**
     * Handle an Exception thrown while rendering TypoScript
     *
     * @param array $typoScriptPath
     * @param \Exception $exception
     * @return string
     * @throws StopActionException
     * @throws InvalidConfigurationException
     * @throws \Exception
     */
    public function handleRenderingException($typoScriptPath, \Exception $exception)
    {
        if ($exception instanceof RuntimeException) {
            throw $exception;
        } else {
            throw new RuntimeException('TypoScript Rendering Exception, see typoScriptPath and nested Exception for details.', 1401803055, $exception, $typoScriptPath);
        }
    }

    /**
     * Handles an Exception thrown while rendering TypoScript
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return void
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        // nothing to be done here, as this method is normally called in "handleRenderingException()", which was overridden above.
    }
}
