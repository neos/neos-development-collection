<?php
namespace Neos\Fusion\Core;

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
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use Neos\Utility\PositionalArraySorter;
use Neos\Fusion\Core\Cache\RuntimeContentCache;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Fusion\Exception as Exceptions;
use Neos\Fusion\Exception;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Fusion\TypoScriptObjects\AbstractArrayTypoScriptObject;
use Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject;
use Neos\Eel\Utility as EelUtility;

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
     * @var \Neos\Eel\CompilingEvaluator
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
     *
     * @var array
     */
    protected $renderingStack = [];

    /**
     * Default context with helper definitions
     *
     * @var array
     */
    protected $defaultContextVariables;

    /**
     * @var array
     */
    protected $typoScriptConfiguration;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $configurationOnPathRuntimeCache = [];

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
     * @var \Closure
     */
    protected $simpleTypeToArrayClosure;

    /**
     * Constructor for the TypoScript Runtime
     *
     * @param array $typoScriptConfiguration
     * @param ControllerContext $controllerContext
     */
    public function __construct(array $typoScriptConfiguration, ControllerContext $controllerContext)
    {
        $this->typoScriptConfiguration = $typoScriptConfiguration;
        $this->controllerContext = $controllerContext;
        $this->runtimeContentCache = new RuntimeContentCache($this);

        $this->simpleTypeToArrayClosure = function ($simpleType) {
            return $simpleType === null ? null : [
                '__eelExpression' => null,
                '__value' => $simpleType,
                '__objectType' => null
            ];
        };
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
     * Add a tag to the current cache segment
     *
     * During TS rendering the method can be used to add tag dynamicaly for the current cache segment.
     *
     * @param string $key
     * @param string $value
     * @return void
     * @api
     */
    public function addCacheTag($key, $value)
    {
        if ($this->runtimeContentCache->getEnableContentCache() === false) {
            return;
        }
        $this->runtimeContentCache->addTag($key, $value);
    }

    /**
     * Completely replace the context array with the new $contextArray.
     *
     * Purely internal method, should not be called outside of Neos.Fusion.
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
        return end($this->renderingStack);
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
     * @throws SecurityException
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
     * settings specified in Neos.Fusion.rendering.exceptionHandler
     *
     * @param array $typoScriptPath
     * @param \Exception $exception
     * @param boolean $useInnerExceptionHandler
     * @return string
     * @throws InvalidConfigurationException
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
            $invalidExceptionHandlerMessage = 'The class "%s" is not valid for setting "Neos.Fusion.rendering.exceptionHandler".';
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
                'Neos\Fusion\Core\ExceptionHandlers'
            );
            throw new InvalidConfigurationException($message, 1368788926);
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
        if ($this->hasExpressionOrValue($typoScriptConfiguration)) {
            return true;
        }

        if (isset($typoScriptConfiguration['__meta']['class']) && isset($typoScriptConfiguration['__objectType'])) {
            return true;
        }

        return false;
    }

    /**
     * Internal evaluation method of absolute $typoScriptPath
     *
     * @param string $typoScriptPath
     * @param string $behaviorIfPathNotFound one of BEHAVIOR_EXCEPTION or BEHAVIOR_RETURNNULL
     * @param mixed $contextObject the object which will be "this" in Eel expressions, if any
     * @return mixed
     *
     * @throws StopActionException
     * @throws SecurityException
     * @throws Exception
     * @throws RuntimeException
     */
    protected function evaluateInternal($typoScriptPath, $behaviorIfPathNotFound, $contextObject = null)
    {
        $needToPopContext = false;
        $this->lastEvaluationStatus = self::EVALUATION_EXECUTED;
        $typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);
        $cacheContext = $this->runtimeContentCache->enter(isset($typoScriptConfiguration['__meta']['cache']) ? $typoScriptConfiguration['__meta']['cache'] : [], $typoScriptPath);

        if (!$this->canRenderWithConfiguration($typoScriptConfiguration)) {
            $this->finalizePathEvaluation($cacheContext);
            $this->throwExceptionForUnrenderablePathIfNeeded($typoScriptPath, $typoScriptConfiguration, $behaviorIfPathNotFound);
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
            return null;
        }

        try {
            if ($this->hasExpressionOrValue($typoScriptConfiguration)) {
                return $this->evaluteExpressionOrValueInternal($typoScriptPath, $typoScriptConfiguration, $cacheContext, $contextObject);
            }

            $typoScriptObject = $this->instantiateTypoScriptObject($typoScriptPath, $typoScriptConfiguration);
            $needToPopContext = $this->prepareContextForTypoScriptObject($typoScriptObject, $typoScriptPath, $typoScriptConfiguration, $cacheContext);
            $output = $this->evaluateObjectOrRetrieveFromCache($typoScriptObject, $typoScriptPath, $typoScriptConfiguration, $cacheContext);
        } catch (StopActionException $stopActionException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext);
            throw $stopActionException;
        } catch (SecurityException $securityException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext);
            throw $securityException;
        } catch (RuntimeException $runtimeException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext);
            throw $runtimeException;
        } catch (\Exception $exception) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext);
            return $this->handleRenderingException($typoScriptPath, $exception, true);
        }

        $this->finalizePathEvaluation($cacheContext, $needToPopContext);
        return $output;
    }

    /**
     * Does the evaluation of a TypoScript instance, first checking the cache and if conditions and afterwards applying processors.
     *
     * @param AbstractTypoScriptObject $typoScriptObject
     * @param string $typoScriptPath
     * @param array $typoScriptConfiguration
     * @param array $cacheContext
     * @return mixed
     */
    protected function evaluateObjectOrRetrieveFromCache($typoScriptObject, $typoScriptPath, $typoScriptConfiguration, $cacheContext)
    {
        $output = null;
        $evaluationStatus = self::EVALUATION_SKIPPED;
        list($cacheHit, $cachedResult) = $this->runtimeContentCache->preEvaluate($cacheContext, $typoScriptObject);
        if ($cacheHit) {
            return $cachedResult;
        }

        $evaluateObject = true;
        if ($this->evaluateIfCondition($typoScriptConfiguration, $typoScriptPath, $typoScriptObject) === false) {
            $evaluateObject = false;
        }

        if ($evaluateObject) {
            $output = $typoScriptObject->evaluate();
            $evaluationStatus = self::EVALUATION_EXECUTED;
        }

        $this->lastEvaluationStatus = $evaluationStatus;

        if ($evaluateObject) {
            $output = $this->evaluateProcessors($output, $typoScriptConfiguration, $typoScriptPath, $typoScriptObject);
        }
        $output = $this->runtimeContentCache->postProcess($cacheContext, $typoScriptObject, $output);
        return $output;
    }

    /**
     * Evaluates an EEL expression or value, checking if conditions first and applying processors.
     *
     * @param string $typoScriptPath
     * @param array $typoScriptConfiguration
     * @param array $cacheContext
     * @param mixed $contextObject
     * @return mixed
     */
    protected function evaluteExpressionOrValueInternal($typoScriptPath, $typoScriptConfiguration, $cacheContext, $contextObject)
    {
        if ($this->evaluateIfCondition($typoScriptConfiguration, $typoScriptPath, $contextObject) === false) {
            $this->finalizePathEvaluation($cacheContext);
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;

            return null;
        }

        $evaluatedExpression = $this->evaluateEelExpressionOrSimpleValueWithProcessor($typoScriptPath, $typoScriptConfiguration, $contextObject);
        $this->finalizePathEvaluation($cacheContext);

        return $evaluatedExpression;
    }

    /**
     * Possibly prepares a new context for the current TypoScriptObject and cache context and pushes it to the stack.
     * Returns if a new context was pushed to the stack or not.
     *
     * @param AbstractTypoScriptObject $typoScriptObject
     * @param string $typoScriptPath
     * @param array $typoScriptConfiguration
     * @param array $cacheContext
     * @return boolean
     */
    protected function prepareContextForTypoScriptObject(AbstractTypoScriptObject $typoScriptObject, $typoScriptPath, $typoScriptConfiguration, $cacheContext)
    {
        if ($cacheContext['cacheForPathDisabled'] === true) {
            $contextArray = $this->getCurrentContext();
            $newContextArray = [];
            foreach ($cacheContext['configuration']['context'] as $contextVariableName) {
                if (isset($contextArray[$contextVariableName])) {
                    $newContextArray[$contextVariableName] = $contextArray[$contextVariableName];
                }
            }
        }

        if (isset($typoScriptConfiguration['__meta']['context'])) {
            $newContextArray = isset($newContextArray) ? $newContextArray : $this->getCurrentContext();
            foreach ($typoScriptConfiguration['__meta']['context'] as $contextKey => $contextValue) {
                $newContextArray[$contextKey] = $this->evaluateInternal($typoScriptPath . '/__meta/context/' . $contextKey, self::BEHAVIOR_EXCEPTION, $typoScriptObject);
            }
        }

        if (isset($newContextArray)) {
            $this->pushContextArray($newContextArray);
            return true;
        }

        return false;
    }

    /**
     * Ends the evaluation of a typoscript path by popping the context stack if needed and leaving the cache context.
     *
     * @param array $cacheContext
     * @param boolean $needToPopContext
     * @return void
     */
    protected function finalizePathEvaluation($cacheContext, $needToPopContext = false)
    {
        if ($needToPopContext) {
            $this->popContext();
        }

        $this->runtimeContentCache->leave($cacheContext);
    }

    /**
     * Get the TypoScript Configuration for the given TypoScript path
     *
     * @param string $typoScriptPath
     * @return array
     * @throws Exception
     */
    protected function getConfigurationForPath($typoScriptPath)
    {
        if (isset($this->configurationOnPathRuntimeCache[$typoScriptPath])) {
            return $this->configurationOnPathRuntimeCache[$typoScriptPath]['c'];
        }

        $pathParts = explode('/', $typoScriptPath);
        $configuration = $this->typoScriptConfiguration;

        $pathUntilNow = '';
        $currentPrototypeDefinitions = array();
        if (isset($configuration['__prototypes'])) {
            $currentPrototypeDefinitions = $configuration['__prototypes'];
        }

        foreach ($pathParts as $pathPart) {
            $pathUntilNow .= '/' . $pathPart;
            if (isset($this->configurationOnPathRuntimeCache[$pathUntilNow])) {
                $configuration = $this->configurationOnPathRuntimeCache[$pathUntilNow]['c'];
                $currentPrototypeDefinitions = $this->configurationOnPathRuntimeCache[$pathUntilNow]['p'];
                continue;
            }

            $configuration = $this->matchCurrentPathPart($pathPart, $configuration, $currentPrototypeDefinitions);
            $this->configurationOnPathRuntimeCache[$pathUntilNow]['c'] = $configuration;
            $this->configurationOnPathRuntimeCache[$pathUntilNow]['p'] = $currentPrototypeDefinitions;
        }

        return $configuration;
    }

    /**
     * Matches the current path segment and prepares the configuration.
     *
     * @param string $pathPart
     * @param array $previousConfiguration
     * @param array $currentPrototypeDefinitions
     * @return array
     * @throws Exception
     */
    protected function matchCurrentPathPart($pathPart, $previousConfiguration, &$currentPrototypeDefinitions)
    {
        if (preg_match('#^([^<]*)(<(.*?)>)?$#', $pathPart, $matches) !== 1) {
            throw new Exception('Path Part ' . $pathPart . ' not well-formed', 1332494645);
        }

        $currentPathSegment = $matches[1];
        $configuration = [];

        if (isset($previousConfiguration[$currentPathSegment])) {
            $configuration = is_array($previousConfiguration[$currentPathSegment]) ? $previousConfiguration[$currentPathSegment] : $this->simpleTypeToArrayClosure->__invoke($previousConfiguration[$currentPathSegment]);
        }

        if (isset($configuration['__prototypes'])) {
            $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeDefinitions, $configuration['__prototypes'], $this->simpleTypeToArrayClosure);
        }

        $currentPathSegmentType = null;
        if (isset($configuration['__objectType'])) {
            $currentPathSegmentType = $configuration['__objectType'];
        }
        if (isset($matches[3])) {
            $currentPathSegmentType = $matches[3];
        }

        if ($currentPathSegmentType !== null) {
            $configuration['__objectType'] = $currentPathSegmentType;
            $configuration = $this->mergePrototypesWithConfigurationForPathSegment($configuration, $currentPrototypeDefinitions);
        }

        if (is_array($configuration) && !isset($configuration['__value']) && !isset($configuration['__eelExpression']) && !isset($configuration['__meta']['class']) && !isset($configuration['__objectType']) && isset($configuration['__meta']['process'])) {
            $configuration['__value'] = '';
        }

        return $configuration;
    }

    /**
     * Merges the prototype chain into the configuration.
     *
     * @param array $configuration
     * @param array $currentPrototypeDefinitions
     * @return array
     * @throws Exception
     */
    protected function mergePrototypesWithConfigurationForPathSegment($configuration, &$currentPrototypeDefinitions)
    {
        $currentPathSegmentType = $configuration['__objectType'];

        if (isset($currentPrototypeDefinitions[$currentPathSegmentType])) {
            $prototypeMergingOrder = [$currentPathSegmentType];
            if (isset($currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'])) {
                $prototypeMergingOrder = array_merge($currentPrototypeDefinitions[$currentPathSegmentType]['__prototypeChain'], $prototypeMergingOrder);
            }

            $currentPrototypeWithInheritanceTakenIntoAccount = [];

            foreach ($prototypeMergingOrder as $prototypeName) {
                if (!array_key_exists($prototypeName, $currentPrototypeDefinitions)) {
                    throw new Exception(sprintf(
                        'The TypoScript object `%s` which you tried to inherit from does not exist.
									Maybe you have a typo on the right hand side of your inheritance statement for `%s`.',
                        $prototypeName, $currentPathSegmentType), 1427134340);
                }

                $currentPrototypeWithInheritanceTakenIntoAccount = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeWithInheritanceTakenIntoAccount, $currentPrototypeDefinitions[$prototypeName], $this->simpleTypeToArrayClosure);
            }

            // We merge the already flattened prototype with the current configuration (in that order),
            // to make sure that the current configuration (not being defined in the prototype) wins.
            $configuration = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeWithInheritanceTakenIntoAccount, $configuration, $this->simpleTypeToArrayClosure);

            // If context-dependent prototypes are set (such as prototype("foo").prototype("baz")),
            // we update the current prototype definitions.
            if (isset($currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'])) {
                $currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverruleWithCallback($currentPrototypeDefinitions, $currentPrototypeWithInheritanceTakenIntoAccount['__prototypes'], $this->simpleTypeToArrayClosure);
            }
        }


        return $configuration;
    }

    /**
     * Instantiates a TypoScript object specified by the given path and configuration
     *
     * @param string $typoScriptPath Path to the configuration for this object instance
     * @param array $typoScriptConfiguration Configuration at the given path
     * @return AbstractTypoScriptObject
     * @throws Exception
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

        /** @var $typoScriptObject AbstractTypoScriptObject */
        $typoScriptObject = new $tsObjectClassName($this, $typoScriptPath, $typoScriptObjectType);
        if ($this->isArrayTypoScriptObject($typoScriptObject)) {
            /** @var $typoScriptObject AbstractArrayTypoScriptObject */
            if (isset($typoScriptConfiguration['__meta']['ignoreProperties'])) {
                $evaluatedIgnores = $this->evaluate($typoScriptPath . '/__meta/ignoreProperties', $typoScriptObject);
                $typoScriptObject->setIgnoreProperties(is_array($evaluatedIgnores) ? $evaluatedIgnores : array());
            }
            $this->setPropertiesOnTypoScriptObject($typoScriptObject, $typoScriptConfiguration);
        }
        return $typoScriptObject;
    }

    /**
     * Check if the given object is an array like object that should get all properties set to iterate or process internally.
     *
     * @param AbstractTypoScriptObject $typoScriptObject
     * @return boolean
     */
    protected function isArrayTypoScriptObject(AbstractTypoScriptObject $typoScriptObject)
    {
        return ($typoScriptObject instanceof AbstractArrayTypoScriptObject);
    }

    /**
     * Does the given TypoScript configuration array hold an EEL expression or simple value.
     *
     * @param array $typoScriptConfiguration
     * @return boolean
     */
    protected function hasExpressionOrValue(array $typoScriptConfiguration)
    {
        return isset($typoScriptConfiguration['__eelExpression']) || isset($typoScriptConfiguration['__value']);
    }

    /**
     * Set options on the given (AbstractArray)TypoScript object
     *
     * @param AbstractArrayTypoScriptObject $typoScriptObject
     * @param array $typoScriptConfiguration
     * @return void
     */
    protected function setPropertiesOnTypoScriptObject(AbstractArrayTypoScriptObject $typoScriptObject, array $typoScriptConfiguration)
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
     * @param \Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
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
     * @param \Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluated Eel expression
     * @throws Exception
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

        if ($this->eelEvaluator instanceof \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy) {
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
     * @return ControllerContext
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
     * Checks and throws an exception for an unrenderable path.
     *
     * @param string $typoScriptPath The TypoScript path that cannot be rendered
     * @param array $typoScriptConfiguration
     * @param string $behaviorIfPathNotFound One of the BEHAVIOR_* constants
     * @throws Exception\MissingTypoScriptImplementationException
     * @throws Exception\MissingTypoScriptObjectException
     */
    protected function throwExceptionForUnrenderablePathIfNeeded($typoScriptPath, $typoScriptConfiguration, $behaviorIfPathNotFound)
    {
        if (isset($typoScriptConfiguration['__objectType'])) {
            $objectType = $typoScriptConfiguration['__objectType'];
            throw new Exceptions\MissingTypoScriptImplementationException(sprintf(
                "The TypoScript object at path `%s` could not be rendered:
					The TypoScript object `%s` is not completely defined (missing property `@class`).
					Most likely you didn't inherit from a basic object.
					For example you could add the following line to your TypoScript:
					`prototype(%s) < prototype(Neos.Fusion:Template)`",
                $typoScriptPath, $objectType, $objectType), 1332493995);
        }

        if ($behaviorIfPathNotFound === self::BEHAVIOR_EXCEPTION) {
            throw new Exceptions\MissingTypoScriptObjectException(sprintf(
                'No TypoScript object found in path "%s"
					Please make sure to define one in your TypoScript configuration.', $typoScriptPath
            ), 1332493990);
        }
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
