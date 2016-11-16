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
 * Creates text representations of the given exceptions.
 */
class PlainTextHandler extends AbstractRenderingExceptionHandler
{
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
        if (isset($referenceCode)) {
            return sprintf(
                'Exception while rendering %s: %s (%s)',
                $this->formatScriptPath($typoScriptPath, "\n\t", false),
                strip_tags($exception->getMessage()),
                $referenceCode
            );
        } else {
            return sprintf(
                'Exception while rendering %s: %s',
                $this->formatScriptPath($typoScriptPath, "\n\t", false),
                strip_tags($exception->getMessage())
            );
        }
    }
}
