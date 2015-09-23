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
 * Renders the exception as HTML.
 */
class HtmlMessageHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Renders the exception in HTML for display
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        if (isset($referenceCode)) {
            $message = sprintf(
                '<div class="neos-rendering-exception"><div class="neos-rendering-exception-title">Exception while rendering</div><div class="neos-typoscript-path"><div>%s:</div></div> <div class="neos-exception-message">%s (%s)</div></div>',
                $this->formatScriptPath($typoScriptPath, '<br/></div><div style="padding-left: 2em">'),
                $exception->getMessage(),
                $referenceCode
            );
        } else {
            $message = sprintf(
                '<div class="neos-rendering-exception">Exception while rendering <div class="neos-typoscript-path"><div>%s:</div></div> <div class="neos-exception-message">%s</div></div>',
                $this->formatScriptPath($typoScriptPath, '<br/></div><div style="padding-left: 2em">'),
                $exception->getMessage()
            );
        }
        $this->systemLogger->logException($exception);
        return $message;
    }
}
