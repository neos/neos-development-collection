<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Helpers;

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
use TYPO3\Flow\Exception;
use Neos\FluidAdaptor\Core\Parser\SyntaxTree\TemplateObjectAccessInterface;
use TYPO3\TypoScript\Core\ExceptionHandlers\ContextDependentHandler;
use TYPO3\TypoScript\Exception\UnsupportedProxyMethodException;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * A proxy object representing a TypoScript path inside a Fluid Template. It allows
 * to render arbitrary TypoScript objects or Eel expressions using the already-known
 * property path syntax.
 *
 * It wraps a part of the TypoScript tree which does not contain TypoScript objects or Eel expressions.
 *
 * This class is instantiated inside TemplateImplementation and is never used outside.
 */
class TypoScriptPathProxy implements TemplateObjectAccessInterface, \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Reference to the TypoScript Runtime which controls the whole rendering
     *
     * @var \TYPO3\TypoScript\Core\Runtime
     */
    protected $tsRuntime;

    /**
     * Reference to the "parent" TypoScript object
     *
     * @var TemplateImplementation
     */
    protected $templateImplementation;

    /**
     * The TypoScript path this object proxies
     *
     * @var string
     */
    protected $path;

    /**
     * This is a part of the TypoScript tree built when evaluating $this->path.
     *
     * @var array
     */
    protected $partialTypoScriptTree;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Constructor.
     *
     * @param TemplateImplementation $templateImplementation
     * @param string $path
     * @param array $partialTypoScriptTree
     */
    public function __construct(TemplateImplementation $templateImplementation, $path, array $partialTypoScriptTree)
    {
        $this->templateImplementation = $templateImplementation;
        $this->tsRuntime = $templateImplementation->getTsRuntime();
        $this->path = $path;
        $this->partialTypoScriptTree = $partialTypoScriptTree;
    }

    /**
     * TRUE if a given subpath exists, FALSE otherwise.
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->partialTypoScriptTree[$offset]);
    }

    /**
     * Return the object at $offset; evaluating simple types right away, and
     * wrapping arrays into ourselves again.
     *
     * @param string $offset
     * @return mixed|TypoScriptPathProxy
     */
    public function offsetGet($offset)
    {
        if (!isset($this->partialTypoScriptTree[$offset])) {
            return null;
        }
        if (!is_array($this->partialTypoScriptTree[$offset])) {
            // Simple type; we call "evaluate" nevertheless to make sure processors are applied.
            return $this->tsRuntime->evaluate($this->path . '/' . $offset);
        } else {
            // arbitrary array (could be Eel expression, TypoScript object, nested sub-array) again, so we wrap it with ourselves.
            return new TypoScriptPathProxy($this->templateImplementation, $this->path . '/' . $offset, $this->partialTypoScriptTree[$offset]);
        }
    }

    /**
     * Stub to implement the ArrayAccess interface cleanly
     *
     * @param string $offset
     * @param mixed $value
     * @throws UnsupportedProxyMethodException
     */
    public function offsetSet($offset, $value)
    {
        throw new UnsupportedProxyMethodException('Setting a property of a path proxy not supported. (tried to set: ' . $this->path . ' -- ' . $offset . ')', 1372667221);
    }

    /**
     * Stub to implement the ArrayAccess interface cleanly
     *
     * @param string $offset
     * @throws UnsupportedProxyMethodException
     */
    public function offsetUnset($offset)
    {
        throw new UnsupportedProxyMethodException('Unsetting a property of a path proxy not supported. (tried to unset: ' . $this->path . ' -- ' . $offset . ')', 1372667331);
    }

    /**
     * Post-Processor which is called whenever this object is encountered in a Fluid
     * object access.
     *
     * Evaluates TypoScript objects and eel expressions.
     *
     * @return TypoScriptPathProxy|mixed
     */
    public function objectAccess()
    {
        if (isset($this->partialTypoScriptTree['__objectType'])) {
            try {
                return $this->tsRuntime->evaluate($this->path);
            } catch (\Exception $exception) {
                return $this->tsRuntime->handleRenderingException($this->path, $exception);
            }
        } elseif (isset($this->partialTypoScriptTree['__eelExpression'])) {
            return $this->tsRuntime->evaluate($this->path, $this->templateImplementation);
        } elseif (isset($this->partialTypoScriptTree['__value'])) {
            return $this->partialTypoScriptTree['__value'];
        }

        return $this;
    }

    /**
     * Iterates through all subelements.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $evaluatedArray = array();
        foreach ($this->partialTypoScriptTree as $key => $value) {
            if (!is_array($value)) {
                $evaluatedArray[$key] = $value;
            } elseif (isset($value['__objectType'])) {
                $evaluatedArray[$key] = $this->tsRuntime->evaluate($this->path . '/' . $key);
            } elseif (isset($value['__eelExpression'])) {
                $evaluatedArray[$key] = $this->tsRuntime->evaluate($this->path . '/' . $key, $this->templateImplementation);
            } else {
                $evaluatedArray[$key] = new TypoScriptPathProxy($this->templateImplementation, $this->path . '/' . $key, $this->partialTypoScriptTree[$key]);
            }
        }
        return new \ArrayIterator($evaluatedArray);
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->partialTypoScriptTree);
    }

    /**
     * Finally evaluate the TypoScript path
     *
     * As PHP does not like throwing an exception here, we render any exception using the configured TypoScript exception
     * handler and will also catch and log any exceptions resulting from that as a last resort.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return (string)$this->tsRuntime->evaluate($this->path);
        } catch (\Exception $exception) {
            try {
                return $this->tsRuntime->handleRenderingException($this->path, $exception);
            } catch (\Exception $exceptionHandlerException) {
                try {
                    // Throwing an exception in __toString causes a fatal error, so if that happens we catch them and use the context dependent exception handler instead.
                    $contextDependentExceptionHandler = new ContextDependentHandler();
                    $contextDependentExceptionHandler->setRuntime($this->tsRuntime);
                    return $contextDependentExceptionHandler->handleRenderingException($this->path, $exception);
                } catch (\Exception $contextDepndentExceptionHandlerException) {
                    $this->systemLogger->logException($contextDepndentExceptionHandlerException, array('path' => $this->path));
                    return sprintf(
                        '<!-- Exception while rendering exception in %s: %s (%s) -->',
                        $this->path,
                        $contextDepndentExceptionHandlerException->getMessage(),
                        $contextDepndentExceptionHandlerException instanceof Exception ? 'see reference code ' . $contextDepndentExceptionHandlerException->getReferenceCode() . ' in log' : $contextDepndentExceptionHandlerException->getCode()
                    );
                }
            }
        }
    }
}
