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
        $messageArray = array(
            'header' => 'An exception was thrown while Neos tried to render your page',
            'content' => $exception->getMessage(),
            'stacktrace' => $this->formatTypoScriptPath($typoScriptPath),
            'referenceCode' => $this->formatErrorCodeMessage($referenceCode)
        );

        $messageBody = sprintf(
            '<p class="neos-message-content">%s</p>' .
            '<p class="neos-message-stacktrace"><code>%s</code></p>',
            $messageArray['content'], $messageArray['stacktrace']
        );

        if ($referenceCode) {
            $messageBody = sprintf('%s<p class="neos-reference-code">%s</p>', $messageBody, $messageArray['referenceCode']);
        }

        $message = sprintf(
            '<div class="neos-message-header"><div class="neos-message-icon"><i class="icon-warning-sign"></i></div><h1>%s</h1></div>' .
            '<div class="neos-message-wrapper">%s</div>',
            $messageArray['header'], $messageBody
        );

        $this->systemLogger->logException($exception);
        return $message;
    }

    /**
     * Renders a message depicting the user where to find further information
     * for the given reference code.
     *
     * @param integer $referenceCode
     * @return string A rendered message with the reference code containing HTML
     */
    protected function formatErrorCodeMessage($referenceCode)
    {
        return ($referenceCode ? 'For a full stacktrace, open <code>Data/Logs/Exceptions/' . $referenceCode . '.txt</code>' : '');
    }

    /**
     * Renders an indented multi-line stack-trace for the given TypoScript path.
     *
     * example:
     *
     *     default<TYPO3.Neos:Page>/body<TYPO3.TypoScript:Template>/content/
     *
     *   is rendered as
     *
     *     default<TYPO3.Neos:Page>/
     *      body<TYPO3.TypoScript:Template>/
     *       content/
     *
     * @param string $typoScriptPath
     * @return string Multi-line stack trace for the given TypoScript path
     */
    protected function formatTypoScriptPath($typoScriptPath)
    {
        $pathSegments = array();
        $spacer = '';
        foreach (explode('/', $typoScriptPath) as $segment) {
            $pathSegments[] = $spacer . $segment . '/';
            $spacer .= ' ';
        }
        return htmlentities(implode("\n", $pathSegments));
    }
}
