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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;

/**
 * Renders the exception as HTML.
 */
class HtmlMessageHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Whether or not to render technical details (i.e. the Fusion stacktrace) in the exception message
     *
     * @var bool
     */
    private $renderTechnicalDetails;

    /**
     * @param bool $renderTechnicalDetails whether or not to render technical details (i.e. the Fusion stacktrace) in the exception message
     */
    public function __construct(bool $renderTechnicalDetails = true)
    {
        $this->renderTechnicalDetails = $renderTechnicalDetails;
    }

    /**
     * Renders the exception in HTML for display
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        $messageBody = sprintf('<p class="neos-message-content">%s</p>', htmlspecialchars($exception->getMessage()));

        if ($this->renderTechnicalDetails) {
            $messageBody .= sprintf('<p class="neos-message-stacktrace"><code>%s</code></p>', $this->formatFusionPath($fusionPath));
        }

        if ($referenceCode) {
            $messageBody .= sprintf('<p class="neos-reference-code">%s</p>', $this->formatErrorCodeMessage($referenceCode));
        }

        $message = sprintf(
            '<div class="neos-message-header"><div class="neos-message-icon"><i class="icon-warning-sign"></i></div><h1>An exception was thrown while Neos tried to render your page</h1></div>' .
            '<div class="neos-message-wrapper">%s</div>',
            $messageBody
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
     * Renders an indented multi-line stack-trace for the given Fusion path.
     *
     * example:
     *
     *     default<Neos.Neos:Page>/body<Neos.Fusion:Template>/content/
     *
     *   is rendered as
     *
     *     default<Neos.Neos:Page>/
     *      body<Neos.Fusion:Template>/
     *       content/
     *
     * @param string $fusionPath
     * @return string Multi-line stack trace for the given Fusion path
     */
    protected function formatFusionPath($fusionPath)
    {
        $pathSegments = array();
        $spacer = '';
        foreach (explode('/', $fusionPath) as $segment) {
            $pathSegments[] = $spacer . $segment . '/';
            $spacer .= ' ';
        }
        return htmlentities(implode("\n", $pathSegments));
    }
}
