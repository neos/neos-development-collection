<?php
namespace Neos\Fusion\Core\ExceptionHandlers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Creates text representations of the given exceptions.
 */
class PlainTextHandler extends AbstractRenderingExceptionHandler
{
    /**
     * Handles an Exception thrown while rendering Fusion
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        if (isset($referenceCode)) {
            return sprintf(
                'Exception while rendering %s: %s (%s)',
                $this->formatScriptPath($fusionPath, "\n\t", false),
                strip_tags($exception->getMessage()),
                $referenceCode
            );
        } else {
            return sprintf(
                'Exception while rendering %s: %s',
                $this->formatScriptPath($fusionPath, "\n\t", false),
                strip_tags($exception->getMessage())
            );
        }
    }
}
