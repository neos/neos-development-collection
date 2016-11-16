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
use TYPO3\Flow\Utility\Environment;

/**
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the context.
 */
class ContextDependentHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * Handle an exception depending on the context with an HTML message or XML comment
     *
     * @param array $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $context = $this->environment->getContext();
        if ($context->isDevelopment()) {
            $handler = new HtmlMessageHandler();
        } else {
            $handler = new XmlCommentHandler();
        }
        $handler->setRuntime($this->getRuntime());
        return $handler->handleRenderingException($typoScriptPath, $exception);
    }
}
