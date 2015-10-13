<?php
namespace TYPO3\TypoScript\Core;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\TypoScript\Core\Cache\RuntimeContentCache;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\TypoScript\Exception as Exceptions;
use TYPO3\TypoScript\Exception;
use TYPO3\Flow\Security\Exception as SecurityException;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\Utility as EelUtility;

/**
 * TypoScript Runtime
 *
 * TypoScript Rendering Process
 * ============================
 *
 * During rendering, all TypoScript objects form a tree.
 *
 * When a TypoScript object at a certain $typoScriptPath is invoked, it has
 * access to all variables stored in the $context (which is an array).
 *
 * The TypoScript object can then add or replace variables to this context using pushContext()
 * or pushContextArray(), before rendering sub-TypoScript objects. After rendering
 * these, it must call popContext() to reset the context to the last state.
 */
class Runtime
{
    /**
     * Internal constants defining how evaluateInternal should work in case of an error.
     */
    const BEHAVIOR_EXCEPTION = 'Exception';
    const BEHAVIOR_RETURNNULL = 'NULL';

    /**
     * Internal constants defining a status of how evaluateInternal evaluated.
     */
    const EVALUATION_EXECUTED = 'Executed';
    const EVALUATION_SKIPPED = 'Skipped';

    /**
     * @var \TYPO3\Eel\CompilingEvaluator
     * @Flow\Inject
     */
    protected $eelEvaluator;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * Contains list of contexts
     * @var array
     */
    protected $renderingStack = array();

    /**
     * Default context with helper definitions
     * @var array
     */
    protected $defaultContextVariables;

    /**
     * @var array
     */
    protected $typoScriptConfiguration;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $configurationOnPathRuntimeCache = array();

    /**
     * @var boolean
     */
    protected $debugMode = false;

    /**
     * @var RuntimeContentCache
     */
    protected $runtimeContentCache;

    /**
     * @var string
     */
    protected $lastEvaluationStatus;

    /**
     * Constructor for the TypoScript Runtime
     *
     * @param array $typoScriptConfiguration
     * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
     */
    public function __construct(array $typoScriptConfiguration, \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext)
    {
        $this->typoScriptConfiguration = $typoScriptConfiguration;
        $this->controllerContext = $controllerContext;
        $this->runtimeContentCache = new RuntimeContentCache($this);
    }

    /**
     * Inject settings of this package
     *
     * @param array $settings The settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
        if (isset($this->settings['debugMode'])) {
            $this->setDebugMode($this->settings['debugMode'] === true);
        }
        if (isset($this->settings['enableContentCache'])) {
            $this->setEnableContentCache($this->settings['enableContentCache'] === true);
        }
    }

    /**
     * Completely replace the context array with the new $contextArray.
     *
     * Purely internal method, should not be called outside of TYPO3.TypoScript.
     *
     * @param array $contextArray
     * @return void
     */
    public function pushContextArray(array $contextArray)
    {
        $this->renderingStack[] = $contextArray;
    }

    /**
     * Push a new context object to the rendering stack
     *
     * @param string $key the key inside the context
     * @param mixed $context
     * @return void
     */
    public function pushContext($key, $context)
    {
        $newContext = $this->getCurrentContext();
        $newContext[$key] = $context;
        $this->renderingStack[] = $newContext;
    }

    /**
     * Remove the topmost context objects and return them
     *
     * @return array the topmost context objects as associative array
     */
    public function popContext()
    {
        return array_pop($this->renderingStack);
    }

    /**
     * Get the current context array
     *
     * @return array the array of current context objects
     */
    public function getCurrentContext()
    {
        return $this->renderingStack[count($this->renderingStack) - 1];
    }

    /**
     * Evaluate an absolute TypoScript path and return the result
     *
     * @param string $typoScriptPath
     * @param object $contextObject the object available as "this" in Eel expressions. ONLY FOR INTERNAL USE!
     * @return mixed the result of the evaluation, can be a string but also other data types
     */
    public function evaluate($typoScriptPath, $contextObject = null)
    {
        return $this->evaluateInternal($typoScriptPath, self::BEHAVIOR_RETURNNULL, $contextObject);
    }

    /**
     * @return string
     */
    public function getLastEvaluationStatus()
    {
        return $this->lastEvaluationStatus;
    }

    /**
     * Render an absolute TypoScript path and return the result.
     *
     * Compared to $this->evaluate, this adds some more comments helpful for debugging.
     *
     * @param string $typoScriptPath
     * @return string
     * @throws \Exception
     * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function render($typoScriptPath)
    {
        try {
            $output = $this->evaluateInternal($typoScriptPath, self::BEHAVIOR_EXCEPTION);
            if ($this->debugMode) {
                $output = sprintf('%1$s<!-- Beginning to render TS path "%2$s" (Context: %3$s) -->%4$s%1$s<!-- End to render TS path "%2$s" (Context: %3$s) -->',
                    chr(10),
                    $typoScriptPath,
                    implode(', ', array_keys($this->getCurrentContext())),
                    $output
                );
            }
        } catch (SecurityException $securityException) {
            throw $securityException;
        } catch (\Exception $exception) {
            $output = $this->handleRenderingException($typoScriptPath, $exception);
        }

        return $output;
    }

    /**
     * Handle an Exception thrown while rendering TypoScript according to
     * settings specified in TYPO3.TypoScript.rendering.exceptionHandler
     *
     * @param array $typoScriptPath
     * @param \Exception $exception
     * @param boolean $useInnerExceptionHandler
     * @return string
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \Exception|\TYPO3\Flow\Exception
     */
    public function handleRenderingException($typoScriptPath, \Exception $exception, $useInnerExceptionHandler = false)
    {
        $typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);

        if (isset($typoScriptConfiguration['__meta']['exceptionHandler'])) {
            $exceptionHandlerClass = $typoScriptConfiguration['__meta']['exceptionHandler'];
            $invalidExceptionHandlerMessage = 'The class "%s" is not valid for property "@exceptionHandler".';
        } else {
            if ($useInnerExceptionHandler === true) {
                $exceptionHandlerClass = $this->settings['rendering']['innerExceptionHandler'];
            } else {
                $exceptionHandlerClass = $this->settings['rendering']['exceptionHandler'];
            }
            $invalidExceptionHandlerMessage = 'The class "%s" is not valid for setting "TYPO3.TypoScript.rendering.exceptionHandler".';
        }
        $exceptionHandler = null;
        if ($this->objectManager->isRegistered($exceptionHandlerClass)) {
            $exceptionHandler = $this->objectManager->get($exceptionHandlerClass);
        }

        if ($exceptionHandler === null || !($exceptionHandler instanceof AbstractRenderingExceptionHandler)) {
            $message = sprintf(
                $invalidExceptionHandlerMessage . "\n" .
                    'Please specify a fully qualified classname to a subclass of %2$s\AbstractRenderingExceptionHandler.' . "\n" .
                    'You might implement an own handler or use one of the following:' . "\n" .
                    '%2$s\AbsorbingHandler' . "\n" .
                    '%2$s\HtmlMessageHandler' . "\n" .
                    '%2$s\PlainTextHandler' . "\n" .
                    '%2$s\ThrowingHandler' . "\n" .
                    '%2$s\XmlCommentHandler',
                $exceptionHandlerClass,
                'TYPO3\TypoScript\Core\ExceptionHandlers'
            );
            throw new \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException($message, 1368788926);
        }

        $exceptionHandler->setRuntime($this);
        if (array_key_exists('__objectType', $typoScriptConfiguration)) {
            $typoScriptPath .= sprintf('<%s>', $typoScriptConfiguration['__objectType']);
        }
        $output = $exceptionHandler->handleRenderingException($typoScriptPath, $exception);
        return $output;
    }

    /**
     * Determine if the given TypoScript path is renderable, which means it exists
     * and has an implementation.
     *
     * @param string $typoScriptPath
     * @return boolean
     */
    public function canRender($typoScriptPath)
    {
        $typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);

        return $this->canRenderWithConfiguration($typoScriptConfiguration);
    }

    /**
     * Internal evaluation if given configuration is renderable.
     *
     * @param array $typoScriptConfiguration
     * @return boolean
     */
    protected function canRenderWithConfiguration(array $typoScriptConfiguration)
    {
        if (isset($typoScriptConfiguration['__eelExpression'])) {
            return true;
        }
        if (isset($typoScriptConfiguration['__value'])) {
            return true;
        }

        if (!isset($typoScriptConfiguration['__meta']['class']) || !isset($typoScriptConfiguration['__objectType'])) {
            return false;
        }

        return true;
    }

    /**
     * Internal evaluation method of absolute $typoScriptPath
     *
     * @param string $typoScriptPath
     * @param string $behaviorIfPathNotFound one of BEHAVIOR_EXCEPTION or BEHAVIOR_RETURNNULL
     * @param mixed $contextObject the object which will be "this" in Eel expressions, if any
     * @return mixed
     *
     * @throws \Exception
     * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @throws \TYPO3\Flow\Security\Exception
     * @throws \TYPO3\Flow\Utility\Exception\InvalidPositionException
     * @throws \TYPO3\TypoScript\Exception
     * @throws \TYPO3\TypoScript\Exception\MissingTypoScriptImplementationException
     * @throws \TYPO3\TypoScript\Exception\MissingTypoScriptObjectException
     * @throws \TYPO3\TypoScript\Exception\RuntimeException
     */
    protected function evaluateInternal($typoScriptPath, $behaviorIfPathNotFound, $contextObject = null)
    {
        $needToPopContext = false;
        $this->lastEvaluationStatus = self::EVALUATION_EXECUTED;
        $typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);
        $runtimeContentCache = $this->runtimeContentCache;

        $cacheCtx = $runtimeContentCache->enter(isset($typoScriptConfiguration['__meta']['cache']) ? $typoScriptConfiguration['__meta']['cache'] : array(), $typoScriptPath);

        // A closure that needs to be called for every return path in this method
        $finallyClosure = function ($needToPopContext = false) use ($cacheCtx, $runtimeContentCache) {
            if ($needToPopContext) {
                $this->popContext();
            }
            $runtimeContentCache->leave($cacheCtx);
        };

        if (!$this->canRenderWithConfiguration($typoScriptConfiguration)) {
            $finallyClosure();
            if (isset($typoScriptConfiguration['__objectType'])) {
                $objectType = $typoScriptConfiguration['__objectType'];
                throw new Exceptions\MissingTypoScriptImplementationException(sprintf(
                    "The TypoScript object at path `%s` could not be rendered:
					The TypoScript object `%s` is not completely defined (missing property `@class`).
					Most likely you didn't inherit from a basic object.
					For example you could add the following line to your TypoScript:
					`prototype(%s) < prototype(TYPO3.TypoScript:Template)`",
                    $typoScriptPath, $objectType, $objectType), 1332493995);
            } elseif ($behaviorIfPathNotFound === self::BEHAVIOR_EXCEPTION) {
                throw new Exceptions\MissingTypoScriptObjectException(sprintf(
                    'No TypoScript object found in path "%s"
					Please make sure to define one in your TypoScript configuration.', $typoScriptPath
                ), 1332493990);
            }
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
            return null;
        }

        try {
            if (isset($typoScriptConfiguration['__eelExpression']) || isset($typoScriptConfiguration['__value'])) {
                if ($this->evaluateIfCondition($typoScriptConfiguration, $typoScriptPath, $contextObject) === false) {
                    $finallyClosure();
                    $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
                    return null;
                }

                $evaluatedExpression = $this->evaluateEelExpressionOrSimpleValueWithProcessor($typoScriptPath, $typoScriptConfiguration, $contextObject);
                $finallyClosure();
                return $evaluatedExpression;
            }

            $tsObject = $this->instantiateTypoScriptObject($typoScriptPath, $typoScriptConfiguration);

            if ($cacheCtx['cacheForPathDisabled'] === true) {
                $contextArray = isset($newContextArray) ? $newContextArray : $this->getCurrentContext();
                $newContextArray = array();
                foreach ($cacheCtx['configuration']['context'] as $contextVariableName) {
                    if (isset($contextArray[$contextVariableName])) {
                        $newContextArray[$contextVariableName] = $contextArray[$contextVariableName];
                    }
                }
            }

            if (isset($typoScriptConfiguration['__meta']['context'])) {
                $newContextArray = isset($newContextArray) ? $newContextArray : $this->getCurrentContext();
                foreach ($typoScriptConfiguration['__meta']['context'] as $contextKey => $contextValue) {
                    $newContextArray[$contextKey] = $this->evaluateInternal($typoScriptPath . '/__meta/context/' . $contextKey, self::BEHAVIOR_EXCEPTION, $tsObject);
                }
            }

            if (isset($newContextArray)) {
                $this->pushContextArray($newContextArray);
                $needToPopContext = true;
            }

            list($cacheHit, $cachedResult) = $runtimeContentCache->preEvaluate($cacheCtx, $tsObject);
            if ($cacheHit) {
                $finallyClosure($needToPopContext);
                return $cachedResult;
            }

            $evaluateObject = true;
            if ($this->evaluateIfCondition($typoScriptConfiguration, $typoScriptPath, $tsObject) === false) {
                $evaluateObject = false;
            }

            if ($evaluateObject) {
                $output = $tsObject->evaluate();
                $this->lastEvaluationStatus = self::EVALUATION_EXECUTED;
            } else {
                $output = null;
                $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
            }
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $stopActionException) {
            $finallyClosure($needToPopContext);
            throw $stopActionException;
        } catch (SecurityException $securityException) {
            throw $securityException;
        } catch (Exceptions\RuntimeException $runtimeException) {
            $finallyClosure($needToPopContext);
            throw $runtimeException;
        } catch (\Exception $exception) {
            $finallyClosure($needToPopContext);
            return $this->handleRenderingException($typoScriptPath, $exception, true);
        }

        $output = $this->evaluateProcessors($output, $typoScriptConfiguration, $typoScriptPath, $tsObject);

        $output = $runtimeContentCache->postProcess($cacheCtx, $tsObject, $output);
        $finallyClosure($needToPopContext);

        return $output;
    }

    /**
     * Get the TypoScript Configuration for the given TypoScript path
     *
     * @param string $typoScriptPath
     * @return array
     * @throws \TYPO3\TypoScript\Exception
     */
    protected function getConfigurationForPath($typoScriptPath)
    {
        $pathParts = explode('/', $typoScriptPath);

        $configuration = $this->typoScriptConfiguration;

        $simpleTypeToArrayClosure = function ($simpleType) {
            return $simpleType === null ? null : array(
                '__eelExpression' => null,
                '__value' => $simpleType,
                '__objectType' => null
            );
        };

        $pathUntilNow = '';
        $currentPrototypeDefinitions = array();
        if (isset($configuration['__prototypes'])) {
            $currentPrototypeDefinitions = $configuration['__prototypes'];
        }

            // we also store the configuration on the *last* level such that we are
            // able to add __processors to eel expressions and values.

        foreach ($pathParts as $pathPart) {
            $pathUntilNow .= '/' . $pathPart;
            if (isset($this->configurationOnPathRuntimeCache[$pathUntilNow])) {
                $configuration = $this->configurationOnPathRuntimeCache[$pathUntilNow]['c'];
                $currentPrototypeDefinitions = $this->configurationOnPathRuntimeCache[$pathUntilNow]['p'];
                continue;
            }
            if (preg_match('#^([^<]*)(<(.*?)>)?$#', $pathPart, $matches)) {
                $currentPathSegment = $matches[1];

                if (isset($configuration[$currentPathSegment])) {
                    $configuration = is_array($configuration[$currentPathSegment]) ? $configuration[$currentPathSegment] : $simpleTypeToArrayClosure($configuration[$currentPathSegment]);
                } else {
                    $configuration = array();
                }

                if (isset($configuration['__prototypes'])) {
                    $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeDefinitions, $configuration['__prototypes'], $simpleTypeToArrayClosure);
                }

                if (isset($matches[3])) {
                    $currentPathSegmentType = $matches[3];
                } elseif (isset($configuration['__objectType'])) {
                    $currentPathSegmentType = $configuration['__objectType'];
                } else {
                    $currentPathSegmentType = null;
                }

                if ($currentPathSegmentType !== null) {
                    if (isset($currentPrototypeDefinitions[$currentPathSegmentType])) {
                        if (isset($currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'])) {
                            $prototypeMergingOrder = $currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'];
                            $prototypeMergingOrder[] = $currentPathSegmentType;
                        } else {
                            $prototypeMergingOrder = array($currentPathSegmentType);
                        }

                        $currentPrototypeWithInheritanceTakenIntoAccount = array();

                        foreach ($prototypeMergingOrder as $prototypeName) {
                            if (array_key_exists($prototypeName, $currentPrototypeDefinitions)) {
                                $currentPrototypeWithInheritanceTakenIntoAccount = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeWithInheritanceTakenIntoAccount, $currentPrototypeDefinitions[$prototypeName], $simpleTypeToArrayClosure);
                            } else {
                                throw new Exception(sprintf(
                                    'The TypoScript object `%s` which you tried to inherit from does not exist.
									Maybe you have a typo on the right hand side of your inheritance statement for `%s`.',
                                    $prototypeName, $currentPathSegmentType), 1427134340);
                            }
                        }

                            // We merge the already flattened prototype with the current configuration (in that order),
                            // to make sure that the current configuration (not being defined in the prototype) wins.
                        $configuration = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeWithInheritanceTakenIntoAccount, $configuration, $simpleTypeToArrayClosure);

                            // If context-dependent prototypes are set (such as prototype("foo").prototype("baz")),
                            // we update the current prototype definitions.
                        if (isset($currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'])) {
                            $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeDefinitions, $currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'], $simpleTypeToArrayClosure);
                        }
                    }

                    $configuration['__objectType'] = $currentPathSegmentType;
                }

                if (is_array($configuration) && !isset($configuration['__value']) && !isset($configuration['__eelExpression']) && !isset($configuration['__meta']['class']) && !isset($configuration['__objectType']) && isset($configuration['__meta']['process'])) {
                    $configuration['__value'] = '';
                }

                $this->configurationOnPathRuntimeCache[$pathUntilNow]['c'] = $configuration;
                $this->configurationOnPathRuntimeCache[$pathUntilNow]['p'] = $currentPrototypeDefinitions;
            } else {
                throw new Exception('Path Part ' . $pathPart . ' not well-formed', 1332494645);
            }
        }

        return $configuration;
    }

    /**
     * Instantiates a TypoScript object specified by the given path and configuration
     *
     * @param string $typoScriptPath Path to the configuration for this object instance
     * @param array $typoScriptConfiguration Configuration at the given path
     * @return \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject
     * @throws \TYPO3\TypoScript\Exception
     */
    protected function instantiateTypoScriptObject($typoScriptPath, $typoScriptConfiguration)
    {
        $typoScriptObjectType = $typoScriptConfiguration['__objectType'];

        $tsObjectClassName = isset($typoScriptConfiguration['__meta']['class']) ? $typoScriptConfiguration['__meta']['class'] : null;

        if (!preg_match('#<[^>]*>$#', $typoScriptPath)) {
            // Only add TypoScript object type to last path part if not already set
            $typoScriptPath .= '<' . $typoScriptObjectType . '>';
        }
        if (!class_exists($tsObjectClassName)) {
            throw new Exception(sprintf(
                'The implementation class `%s` defined for TypoScript object of type `%s` does not exist.
				Maybe a typo in the `@class` property.',
                $tsObjectClassName, $typoScriptObjectType), 1347952109);
        }

        /** @var $typoScriptObject \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject */
        $typoScriptObject = new $tsObjectClassName($this, $typoScriptPath, $typoScriptObjectType);
        if ($typoScriptObject instanceof \TYPO3\TypoScript\TypoScriptObjects\AbstractArrayTypoScriptObject) {
            /** @var $typoScriptObject \TYPO3\TypoScript\TypoScriptObjects\AbstractArrayTypoScriptObject */
            if (isset($typoScriptConfiguration['__meta']['ignoreProperties'])) {
                $evaluatedIgnores = $this->evaluate($typoScriptPath . '/__meta/ignoreProperties', $typoScriptObject);
                $typoScriptObject->setIgnoreProperties(is_array($evaluatedIgnores) ? $evaluatedIgnores : array());
            }
            $this->setPropertiesOnTypoScriptObject($typoScriptObject, $typoScriptConfiguration);
        }
        return $typoScriptObject;
    }

    /**
     * Set options on the given (AbstractArray)TypoScript object
     *
     * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject $typoScriptObject
     * @param array $typoScriptConfiguration
     * @return void
     */
    protected function setPropertiesOnTypoScriptObject(AbstractTypoScriptObject $typoScriptObject, array $typoScriptConfiguration)
    {
        foreach ($typoScriptConfiguration as $key => $value) {
            // skip keys which start with __, as they are purely internal.
            if ($key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                continue;
            }

            ObjectAccess::setProperty($typoScriptObject, $key, $value);
        }
    }

    /**
     * Evaluate a simple value or eel expression with processors
     *
     * @param string $typoScriptPath the TypoScript path up to now
     * @param array $valueConfiguration TypoScript configuration for the value
     * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluation
     */
    protected function evaluateEelExpressionOrSimpleValueWithProcessor($typoScriptPath, array $valueConfiguration, AbstractTypoScriptObject $contextObject = null)
    {
        if (isset($valueConfiguration['__eelExpression'])) {
            $evaluatedValue = $this->evaluateEelExpression($valueConfiguration['__eelExpression'], $contextObject);
        } else {
            // must be simple type, as this is the only place where this method is called.
            $evaluatedValue = $valueConfiguration['__value'];
        }

        $evaluatedValue = $this->evaluateProcessors($evaluatedValue, $valueConfiguration, $typoScriptPath, $contextObject);

        return $evaluatedValue;
    }

    /**
     * Evaluate an Eel expression
     *
     * @param string $expression The Eel expression to evaluate
     * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluated Eel expression
     * @throws \TYPO3\TypoScript\Exception
     */
    protected function evaluateEelExpression($expression, AbstractTypoScriptObject $contextObject = null)
    {
        if ($expression[0] !== '$' || $expression[1] !== '{') {
            // We still assume this is an EEL expression and wrap the markers for backwards compatibility.
            $expression = '${' . $expression . '}';
        }

        $contextVariables = array_merge($this->getDefaultContextVariables(), $this->getCurrentContext());

        if (isset($contextVariables['this'])) {
            throw new Exception('Context variable "this" not allowed, as it is already reserved for a pointer to the current TypoScript object.', 1344325044);
        }
        $contextVariables['this'] = $contextObject;

        if ($this->eelEvaluator instanceof \TYPO3\Flow\Object\DependencyInjection\DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }

        return EelUtility::evaluateEelExpression($expression, $this->eelEvaluator, $contextVariables);
    }

    /**
     * Evaluate processors on given value.
     *
     * @param mixed $valueToProcess
     * @param array $configurationWithEventualProcessors
     * @param string $typoScriptPath
     * @param AbstractTypoScriptObject $contextObject
     * @return mixed
     */
    protected function evaluateProcessors($valueToProcess, $configurationWithEventualProcessors, $typoScriptPath, AbstractTypoScriptObject $contextObject = null)
    {
        if (isset($configurationWithEventualProcessors['__meta']['process'])) {
            $processorConfiguration = $configurationWithEventualProcessors['__meta']['process'];
            $positionalArraySorter = new PositionalArraySorter($processorConfiguration, '__meta.position');
            foreach ($positionalArraySorter->getSortedKeys() as $key) {
                $processorPath = $typoScriptPath . '/__meta/process/' . $key;
                if ($this->evaluateIfCondition($processorConfiguration[$key], $processorPath, $contextObject) === false) {
                    continue;
                }
                if (isset($processorConfiguration[$key]['expression'])) {
                    $processorPath .= '/expression';
                }

                $this->pushContext('value', $valueToProcess);
                $result = $this->evaluateInternal($processorPath, self::BEHAVIOR_EXCEPTION, $contextObject);
                if ($this->getLastEvaluationStatus() !== static::EVALUATION_SKIPPED) {
                    $valueToProcess = $result;
                }
                $this->popContext();
            }
        }

        return $valueToProcess;
    }

    /**
     * Evaluate eventually existing meta "@if" conditionals inside the given configuration and path.
     *
     * @param array $configurationWithEventualIf
     * @param string $configurationPath
     * @param AbstractTypoScriptObject $contextObject
     * @return boolean
     */
    protected function evaluateIfCondition($configurationWithEventualIf, $configurationPath, AbstractTypoScriptObject $contextObject = null)
    {
        if (isset($configurationWithEventualIf['__meta']['if'])) {
            foreach ($configurationWithEventualIf['__meta']['if'] as $conditionKey => $conditionValue) {
                $conditionValue = $this->evaluateInternal($configurationPath . '/__meta/if/' . $conditionKey, self::BEHAVIOR_EXCEPTION, $contextObject);
                if ($conditionValue === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the context which has been passed by the currently active MVC Controller
     *
     * @return \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * Get variables from configuration that should be set in the context by default.
     * For example Eel helpers are made available by this.
     *
     * @return array Array with default context variable objects.
     */
    protected function getDefaultContextVariables()
    {
        if ($this->defaultContextVariables === null) {
            $this->defaultContextVariables = array();
            if (isset($this->settings['defaultContext']) && is_array($this->settings['defaultContext'])) {
                $this->defaultContextVariables = EelUtility::getDefaultContextVariables($this->settings['defaultContext']);
            }
            $this->defaultContextVariables['request'] = $this->controllerContext->getRequest();
        }
        return $this->defaultContextVariables;
    }

    /**
     * @param boolean $debugMode
     * @return void
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
    }

    /**
     * @return boolean
     */
    public function isDebugMode()
    {
        return $this->debugMode;
    }

    /**
     * If the TypoScript content cache should be enabled at all
     *
     * @param boolean $flag
     * @return void
     */
    public function setEnableContentCache($flag)
    {
        $this->runtimeContentCache->setEnableContentCache($flag);
    }
}
