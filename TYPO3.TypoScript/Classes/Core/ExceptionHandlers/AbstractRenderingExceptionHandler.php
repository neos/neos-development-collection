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
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Security\Exception as SecurityException;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\Exception as Exceptions;

/**
 * Handles exceptions
 */
abstract class AbstractRenderingExceptionHandler
{
    /**
     * Current TypoScript runtime
     *
     * @var Runtime
     */
    protected $runtime;

    /**
     * Sets the current TypoScript runtime
     *
     * @param Runtime $runtime
     * @return void
     */
    public function setRuntime(Runtime $runtime)
    {
        $this->runtime = $runtime;
    }

    /**
     * Returns current TypoScript runtime
     *
     * @return Runtime
     */
    protected function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * Handle an Exception thrown while rendering TypoScript
     *
     * @param string $typoScriptPath
     * @param \Exception $exception
     * @return string
     * @throws StopActionException|SecurityException
     */
    public function handleRenderingException($typoScriptPath, \Exception $exception)
    {
        if ($exception instanceof StopActionException || $exception instanceof SecurityException) {
            throw $exception;
        }
        if ($exception instanceof Exceptions\RuntimeException) {
            $typoScriptPath = $exception->getTypoScriptPath();
            $exception = $exception->getPrevious();
        }
        if ($this->exceptionDisablesCache($typoScriptPath, $exception)) {
            $this->runtime->setEnableContentCache(false);
        }
        $referenceCode = ($exception instanceof \TYPO3\Flow\Exception) ? $exception->getReferenceCode() : null;
        return $this->handle($typoScriptPath, $exception, $referenceCode);
    }

    /**
     * Handles an Exception thrown while rendering TypoScript
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    abstract protected function handle($typoScriptPath, \Exception $exception, $referenceCode);

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
     * @param string $typoScriptPath path to format
     * @param string $delimiter path element delimiter
     * @param bool $escapeHtml indicates whether to escape html-characters in the given path
     * @return string
     */
    protected function formatScriptPath($typoScriptPath, $delimiter, $escapeHtml = true)
    {
        if ($escapeHtml) {
            $typoScriptPath = htmlspecialchars($typoScriptPath);
        }
        // TODO: hardcoded parsing?! where is the library for that
        $elements = explode('/', $typoScriptPath);

        return implode('/' . $delimiter, $elements);
    }

    /**
     * Can be used to determine if handling the exception should disable the cache or not.
     *
     * @param string $typoScriptPath The typoScriptPath that triggered the Exception
     * @param \Exception $exception
     * @return boolean Should caching be disabled?
     */
    protected function exceptionDisablesCache($typoScriptPath, \Exception $exception)
    {
        return true;
    }
}
