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

/**
 * Just rethrows the given exception
 */
class ThrowingHandler extends AbstractRenderingExceptionHandler
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
        throw $exception;
    }

    /**
     * Handles an Exception thrown while rendering TypoScript
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
    }
}
