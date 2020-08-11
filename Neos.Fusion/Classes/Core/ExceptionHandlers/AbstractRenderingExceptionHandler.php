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

use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception as Exceptions;

/**
 * Handles exceptions
 */
abstract class AbstractRenderingExceptionHandler
{
    /**
     * Current Fusion runtime
     *
     * @var Runtime
     */
    protected $runtime;

    /**
     * Sets the current Fusion runtime
     *
     * @param Runtime $runtime
     * @return void
     */
    public function setRuntime(Runtime $runtime)
    {
        $this->runtime = $runtime;
    }

    /**
     * Returns current Fusion runtime
     *
     * @return Runtime
     */
    protected function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * Handle an Exception thrown while rendering Fusion
     *
     * @param string $fusionPath
     * @param \Exception $exception
     * @return string
     * @throws StopActionException|SecurityException
     */
    public function handleRenderingException($fusionPath, \Exception $exception)
    {
        if ($exception instanceof StopActionException || $exception instanceof SecurityException) {
            throw $exception;
        }
        if ($exception instanceof Exceptions\RuntimeException) {
            $fusionPath = $exception->getFusionPath();
            $exception = $exception->getPrevious();
        }
        if ($this->exceptionDisablesCache($fusionPath, $exception)) {
            $this->runtime->setEnableContentCache(false);
        }
        $referenceCode = ($exception instanceof \Neos\Flow\Exception) ? $exception->getReferenceCode() : null;
        return $this->handle($fusionPath, $exception, $referenceCode);
    }

    /**
     * Handles an Exception thrown while rendering Fusion
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    abstract protected function handle($fusionPath, \Exception $exception, $referenceCode);

    /**
     * breaks the given path to multiple line to allow a nicer formatted logging
     *
     * example:
     * formatScriptPath('page<Page>/body<Template>/content/main<ContentCollection>', ''):
     * page<Page>/body<Template>/content/main<ContentCollection>
     *
     * formatScriptPath('page<Page>/body<Template>/content/main<ContentCollection>', '\n\t\t'):
     * page<Page>/
     *        body<Template>/
     *        content/
     *        main<ContentCollection>'
     *
     * @param string $fusionPath path to format
     * @param string $delimiter path element delimiter
     * @param bool $escapeHtml indicates whether to escape html-characters in the given path
     * @return string
     */
    protected function formatScriptPath($fusionPath, $delimiter, $escapeHtml = true)
    {
        if ($escapeHtml) {
            $fusionPath = htmlspecialchars($fusionPath);
        }
        // TODO: hardcoded parsing?! where is the library for that
        $elements = explode('/', $fusionPath);

        return implode('/' . $delimiter, $elements);
    }

    /**
     * Can be used to determine if handling the exception should disable the cache or not.
     *
     * @param string $fusionPath The Fusion-Path that triggered the Exception
     * @param \Exception $exception
     * @return boolean Should caching be disabled?
     */
    protected function exceptionDisablesCache($fusionPath, \Exception $exception)
    {
        return true;
    }
}
