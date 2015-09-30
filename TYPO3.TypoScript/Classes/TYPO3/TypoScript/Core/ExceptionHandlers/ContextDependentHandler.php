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
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the context.
 */
class ContextDependentHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Utility\Environment
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
