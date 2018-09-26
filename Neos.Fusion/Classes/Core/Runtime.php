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
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\Utility as EelUtility;

/**
 * Fusion Runtime
 *
 * Fusion Rendering Process
 * ============================
 *
 * During rendering, all Fusion objects form a tree.
 *
 * When a Fusion object at a certain $fusionPath is invoked, it has
 * access to all variables stored in the $context (which is an array).
 *
 * The Fusion object can then add or replace variables to this context using pushContext()
 * or pushContextArray(), before rendering sub-Fusion objects. After rendering
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
     * Stack of evaluated "@context" values
     *
     * @var array
     */
    protected $contextStack = [];

    /**
     * Stack of evaluated "@apply" values
     *
     * @var array
     */
    protected $applyValueStack = [];

    /**
     * Default context with helper definitions
     *
     * @var array
     */
    protected $defaultContextVariables;

    /**
     * @var array
     */
    protected $fusionConfiguration;

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
     * Constructor for the Fusion Runtime
     *
     * @param array $fusionConfiguration
     * @param ControllerContext $controllerContext
     */
    public function __construct(array $fusionConfiguration, ControllerContext $controllerContext)
    {
        $this->fusionConfiguration = $fusionConfiguration;
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
        $this->contextStack[] = $contextArray;
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
        $this->contextStack[] = $newContext;
    }

    /**
     * Remove the topmost context objects and return them
     *
     * @return array the topmost context objects as associative array
     */
    public function popContext()
    {
        return array_pop($this->contextStack);
    }

    /**
     * Get the current context array
     *
     * @return array the array of current context objects
     */
    public function getCurrentContext()
    {
        return end($this->contextStack);
    }

    /**
     * @param null|array $values
     * @return void
     */
    public function pushApplyValues(?array $values)
    {
        $this->applyValueStack[] = $values;
    }

    /**
     * @return null|array the topmost "@apply" values as associative array
     */
    public function popApplyValues()
    {
        return array_pop($this->applyValueStack);
    }

    /**
     * @return null|array the current "@apply"
     */
    public function getCurrentApplyValues()
    {
        return end($this->applyValueStack);
    }

    /**
     * Evaluate an absolute Fusion path and return the result
     *
     * @param string $fusionPath
     * @param object $contextObject the object available as "this" in Eel expressions. ONLY FOR INTERNAL USE!
     * @return mixed the result of the evaluation, can be a string but also other data types
     */
    public function evaluate($fusionPath, $contextObject = null)
    {
        return $this->evaluateInternal($fusionPath, self::BEHAVIOR_RETURNNULL, $contextObject);
    }

    /**
     * @return string
     */
    public function getLastEvaluationStatus()
    {
        return $this->lastEvaluationStatus;
    }

    /**
     * Render an absolute Fusion path and return the result.
     *
     * Compared to $this->evaluate, this adds some more comments helpful for debugging.
     *
     * @param string $fusionPath
     * @return string
     * @throws \Exception
     * @throws SecurityException
     */
    public function render($fusionPath)
    {
        try {
            $output = $this->evaluateInternal($fusionPath, self::BEHAVIOR_EXCEPTION);
            if ($this->debugMode) {
                $output = sprintf('%1$s<!-- Beginning to render TS path "%2$s" (Context: %3$s) -->%4$s%1$s<!-- End to render TS path "%2$s" (Context: %3$s) -->',
                    chr(10),
                    $fusionPath,
                    implode(', ', array_keys($this->getCurrentContext())),
                    $output
                );
            }
        } catch (SecurityException $securityException) {
            throw $securityException;
        } catch (\Exception $exception) {
            $output = $this->handleRenderingException($fusionPath, $exception);
        }

        return $output;
    }

    /**
     * Handle an Exception thrown while rendering Fusion according to
     * settings specified in Neos.Fusion.rendering.exceptionHandler
     *
     * @param string $fusionPath
     * @param \Exception $exception
     * @param boolean $useInnerExceptionHandler
     * @return string
     * @throws InvalidConfigurationException
     */
    public function handleRenderingException($fusionPath, \Exception $exception, $useInnerExceptionHandler = false)
    {
        $fusionConfiguration = $this->getConfigurationForPath($fusionPath);

        if (isset($fusionConfiguration['__meta']['exceptionHandler'])) {
            $exceptionHandlerClass = $fusionConfiguration['__meta']['exceptionHandler'];
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
        if (array_key_exists('__objectType', $fusionConfiguration)) {
            $fusionPath .= sprintf('<%s>', $fusionConfiguration['__objectType']);
        }
        $output = $exceptionHandler->handleRenderingException($fusionPath, $exception);

        return $output;
    }

    /**
     * Determine if the given Fusion path is renderable, which means it exists
     * and has an implementation.
     *
     * @param string $fusionPath
     * @return boolean
     */
    public function canRender($fusionPath)
    {
        $fusionConfiguration = $this->getConfigurationForPath($fusionPath);

        return $this->canRenderWithConfiguration($fusionConfiguration);
    }

    /**
     * Internal evaluation if given configuration is renderable.
     *
     * @param array $fusionConfiguration
     * @return boolean
     */
    protected function canRenderWithConfiguration(array $fusionConfiguration)
    {
        if ($this->hasExpressionOrValue($fusionConfiguration)) {
            return true;
        }

        if (isset($fusionConfiguration['__meta']['class']) && isset($fusionConfiguration['__objectType'])) {
            return true;
        }

        return false;
    }

    /**
     * Internal evaluation method of absolute $fusionPath
     *
     * @param string $fusionPath
     * @param string $behaviorIfPathNotFound one of BEHAVIOR_EXCEPTION or BEHAVIOR_RETURNNULL
     * @param mixed $contextObject the object which will be "this" in Eel expressions, if any
     * @return mixed
     *
     * @throws StopActionException
     * @throws SecurityException
     * @throws Exception
     * @throws RuntimeException
     */
    protected function evaluateInternal($fusionPath, $behaviorIfPathNotFound, $contextObject = null)
    {
        $needToPopContext = false;
        $needToPopApply = false;
        $this->lastEvaluationStatus = self::EVALUATION_EXECUTED;
        $fusionConfiguration = $this->getConfigurationForPath($fusionPath);
        $cacheContext = $this->runtimeContentCache->enter(isset($fusionConfiguration['__meta']['cache']) ? $fusionConfiguration['__meta']['cache'] : [], $fusionPath);

        // check if the current "@apply" contain an entry for the requested fusionPath
        // in which case this value is returned after applying @if and @process rules
        $currentProperties = $this->getCurrentApplyValues();
        if (is_array($currentProperties) && array_key_exists($fusionPath, $currentProperties)) {
            if ($this->evaluateIfCondition($fusionConfiguration, $fusionPath, $contextObject) === false) {
                return null;
            }
            return $this->evaluateProcessors($currentProperties[$fusionPath]['value'], $fusionConfiguration, $fusionPath, $contextObject);
        }

        if (!$this->canRenderWithConfiguration($fusionConfiguration)) {
            $this->finalizePathEvaluation($cacheContext);
            $this->throwExceptionForUnrenderablePathIfNeeded($fusionPath, $fusionConfiguration, $behaviorIfPathNotFound);
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
            return null;
        }

        try {
            if ($this->hasExpressionOrValue($fusionConfiguration)) {
                return $this->evaluteExpressionOrValueInternal($fusionPath, $fusionConfiguration, $cacheContext, $contextObject);
            }
            $needToPopApply = $this->prepareApplyValuesForFusionPath($fusionPath, $fusionConfiguration);
            $fusionObject = $this->instantiatefusionObject($fusionPath, $fusionConfiguration);
            $needToPopContext = $this->prepareContextForFusionObject($fusionObject, $fusionPath, $fusionConfiguration, $cacheContext);
            $output = $this->evaluateObjectOrRetrieveFromCache($fusionObject, $fusionPath, $fusionConfiguration, $cacheContext);
        } catch (StopActionException $stopActionException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext, $needToPopApply);
            throw $stopActionException;
        } catch (SecurityException $securityException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext, $needToPopApply);
            throw $securityException;
        } catch (RuntimeException $runtimeException) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext, $needToPopApply);
            throw $runtimeException;
        } catch (\Exception $exception) {
            $this->finalizePathEvaluation($cacheContext, $needToPopContext, $needToPopApply);
            return $this->handleRenderingException($fusionPath, $exception, true);
        }

        $this->finalizePathEvaluation($cacheContext, $needToPopContext, $needToPopApply);
        return $output;
    }

    /**
     * Does the evaluation of a Fusion instance, first checking the cache and if conditions and afterwards applying processors.
     *
     * @param AbstractFusionObject $fusionObject
     * @param string $fusionPath
     * @param array $fusionConfiguration
     * @param array $cacheContext
     * @return mixed
     */
    protected function evaluateObjectOrRetrieveFromCache($fusionObject, $fusionPath, $fusionConfiguration, $cacheContext)
    {
        $output = null;
        $evaluationStatus = self::EVALUATION_SKIPPED;
        list($cacheHit, $cachedResult) = $this->runtimeContentCache->preEvaluate($cacheContext, $fusionObject);
        if ($cacheHit) {
            return $cachedResult;
        }

        $evaluateObject = true;
        if ($this->evaluateIfCondition($fusionConfiguration, $fusionPath, $fusionObject) === false) {
            $evaluateObject = false;
        }

        if ($evaluateObject) {
            $output = $fusionObject->evaluate();
            $evaluationStatus = self::EVALUATION_EXECUTED;
        }

        $this->lastEvaluationStatus = $evaluationStatus;

        if ($evaluateObject) {
            $output = $this->evaluateProcessors($output, $fusionConfiguration, $fusionPath, $fusionObject);
        }
        $output = $this->runtimeContentCache->postProcess($cacheContext, $fusionObject, $output);
        return $output;
    }

    /**
     * Evaluates an EEL expression or value, checking if conditions first and applying processors.
     *
     * @param string $fusionPath
     * @param array $fusionConfiguration
     * @param array $cacheContext
     * @param mixed $contextObject
     * @return mixed
     */
    protected function evaluteExpressionOrValueInternal($fusionPath, $fusionConfiguration, $cacheContext, $contextObject)
    {
        if ($this->evaluateIfCondition($fusionConfiguration, $fusionPath, $contextObject) === false) {
            $this->finalizePathEvaluation($cacheContext);
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;

            return null;
        }

        $evaluatedExpression = $this->evaluateEelExpressionOrSimpleValueWithProcessor($fusionPath, $fusionConfiguration, $contextObject);
        $this->finalizePathEvaluation($cacheContext);

        return $evaluatedExpression;
    }

    /**
     * Possibly prepares a new "@apply" context for the current fusionPath and pushes it to the stack.
     * Returns true to express that new properties were pushed and have to be popped during finalizePathEvaluation.
     *
     * Since "@apply" are not inherited every call of this method leads to a completely new  "@apply"
     * context, which is null by default.
     *
     * @param string $fusionPath
     * @param array $fusionConfiguration
     * @return boolean
     * @throws Exception
     * @throws RuntimeException
     * @throws SecurityException
     * @throws StopActionException
     */
    protected function prepareApplyValuesForFusionPath($fusionPath, $fusionConfiguration)
    {
        $spreadValues = $this->evaluateApplyValues($fusionConfiguration, $fusionPath);
        $this->pushApplyValues($spreadValues);
        return true;
    }

    /**
     * Possibly prepares a new context for the current FusionObject and cache context and pushes it to the stack.
     * Returns if a new context was pushed to the stack or not.
     *
     * @param AbstractFusionObject $fusionObject
     * @param string $fusionPath
     * @param array $fusionConfiguration
     * @param array $cacheContext
     * @return boolean
     * @throws Exception
     * @throws RuntimeException
     * @throws SecurityException
     * @throws StopActionException
     */
    protected function prepareContextForFusionObject(AbstractFusionObject $fusionObject, $fusionPath, $fusionConfiguration, $cacheContext)
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

        if (isset($fusionConfiguration['__meta']['context'])) {
            $newContextArray = isset($newContextArray) ? $newContextArray : $this->getCurrentContext();
            foreach ($fusionConfiguration['__meta']['context'] as $contextKey => $contextValue) {
                $newContextArray[$contextKey] = $this->evaluateInternal($fusionPath . '/__meta/context/' . $contextKey, self::BEHAVIOR_EXCEPTION, $fusionObject);
            }
        }

        if (isset($newContextArray)) {
            $this->pushContextArray($newContextArray);
            return true;
        }

        return false;
    }

    /**
     * Ends the evaluation of a fusion path by popping the context and property stack if needed and leaving the cache context.
     *
     * @param array $cacheContext
     * @param boolean $needToPopContext
     * @param boolean $needToPopApplyValues
     * @return void
     */
    protected function finalizePathEvaluation($cacheContext, $needToPopContext = false, $needToPopApplyValues = false)
    {
        if ($needToPopContext) {
            $this->popContext();
        }

        if ($needToPopApplyValues) {
            $this->popApplyValues();
        }

        $this->runtimeContentCache->leave($cacheContext);
    }

    /**
     * Get the Fusion Configuration for the given Fusion path
     *
     * @param string $fusionPath
     * @return array
     * @throws Exception
     */
    protected function getConfigurationForPath($fusionPath)
    {
        if (isset($this->configurationOnPathRuntimeCache[$fusionPath])) {
            return $this->configurationOnPathRuntimeCache[$fusionPath]['c'];
        }

        $pathParts = explode('/', $fusionPath);
        $configuration = $this->fusionConfiguration;

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
                        'The Fusion object `%s` which you tried to inherit from does not exist.
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
     * Instantiates a Fusion object specified by the given path and configuration
     *
     * @param string $fusionPath Path to the configuration for this object instance
     * @param array $fusionConfiguration Configuration at the given path
     * @return AbstractFusionObject
     * @throws Exception
     */
    protected function instantiateFusionObject($fusionPath, $fusionConfiguration)
    {
        $fusionObjectType = $fusionConfiguration['__objectType'];

        $fusionObjectClassName = isset($fusionConfiguration['__meta']['class']) ? $fusionConfiguration['__meta']['class'] : null;

        if (!preg_match('#<[^>]*>$#', $fusionPath)) {
            // Only add Fusion object type to last path part if not already set
            $fusionPath .= '<' . $fusionObjectType . '>';
        }
        if (!class_exists($fusionObjectClassName)) {
            throw new Exception(sprintf(
                'The implementation class `%s` defined for Fusion object of type `%s` does not exist.
				Maybe a typo in the `@class` property.',
                $fusionObjectClassName, $fusionObjectType), 1347952109);
        }

        /** @var $fusionObject AbstractFusionObject */
        $fusionObject = new $fusionObjectClassName($this, $fusionPath, $fusionObjectType);
        if ($this->isArrayFusionObject($fusionObject)) {
            /** @var $fusionObject AbstractArrayFusionObject */
            if (isset($fusionConfiguration['__meta']['ignoreProperties'])) {
                $evaluatedIgnores = $this->evaluate($fusionPath . '/__meta/ignoreProperties', $fusionObject);
                $fusionObject->setIgnoreProperties(is_array($evaluatedIgnores) ? $evaluatedIgnores : array());
            }
            $this->setPropertiesOnFusionObject($fusionObject, $fusionConfiguration);
        }
        return $fusionObject;
    }

    /**
     * Check if the given object is an array like object that should get all properties set to iterate or process internally.
     *
     * @param AbstractFusionObject $fusionObject
     * @return boolean
     */
    protected function isArrayFusionObject(AbstractFusionObject $fusionObject)
    {
        return ($fusionObject instanceof AbstractArrayFusionObject);
    }

    /**
     * Does the given Fusion configuration array hold an EEL expression or simple value.
     *
     * @param array $fusionConfiguration
     * @return boolean
     */
    protected function hasExpressionOrValue(array $fusionConfiguration)
    {
        return isset($fusionConfiguration['__eelExpression']) || isset($fusionConfiguration['__value']);
    }

    /**
     * Set options on the given (AbstractArray)Fusion object
     *
     * @param AbstractArrayFusionObject $fusionObject
     * @param array $fusionConfiguration
     * @return void
     */
    protected function setPropertiesOnFusionObject(AbstractArrayFusionObject $fusionObject, array $fusionConfiguration)
    {
        foreach ($fusionConfiguration as $key => $value) {
            // skip keys which start with __, as they are purely internal.
            if ($key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                continue;
            }

            ObjectAccess::setProperty($fusionObject, $key, $value);
        }

        $currentProperties = $this->getCurrentApplyValues();
        if (is_array($currentProperties)) {
            foreach ($currentProperties as $path => $property) {
                $key = $property['key'];
                $valueAst = [
                    '__eelExpression' => null,
                    '__objectType' => null,
                    '__value' => $property['value']
                ];

                // merge existing meta-configuration to valueAst
                // to preserve @if, @process and @position informations
                if ($meta = Arrays::getValueByPath($fusionConfiguration, [$key, '__meta'])) {
                    $valueAst['__meta'] = $meta;
                }

                ObjectAccess::setProperty($fusionObject, $property['key'], $valueAst);
            }
        }
    }

    /**
     * Evaluate a simple value or eel expression with processors
     *
     * @param string $fusionPath the Fusion path up to now
     * @param array $valueConfiguration Fusion configuration for the value
     * @param \Neos\Fusion\FusionObjects\AbstractFusionObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluation
     * @throws Exception
     */
    protected function evaluateEelExpressionOrSimpleValueWithProcessor($fusionPath, array $valueConfiguration, AbstractFusionObject $contextObject = null)
    {
        if (isset($valueConfiguration['__eelExpression'])) {
            $evaluatedValue = $this->evaluateEelExpression($valueConfiguration['__eelExpression'], $contextObject);
        } else {
            // must be simple type, as this is the only place where this method is called.
            $evaluatedValue = $valueConfiguration['__value'];
        }

        $evaluatedValue = $this->evaluateProcessors($evaluatedValue, $valueConfiguration, $fusionPath, $contextObject);

        return $evaluatedValue;
    }

    /**
     * Evaluate an Eel expression
     *
     * @param string $expression The Eel expression to evaluate
     * @param \Neos\Fusion\FusionObjects\AbstractFusionObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluated Eel expression
     * @throws Exception
     */
    protected function evaluateEelExpression($expression, AbstractFusionObject $contextObject = null)
    {
        if ($expression[0] !== '$' || $expression[1] !== '{') {
            // We still assume this is an EEL expression and wrap the markers for backwards compatibility.
            $expression = '${' . $expression . '}';
        }

        $contextVariables = array_merge($this->getDefaultContextVariables(), $this->getCurrentContext());

        if (isset($contextVariables['this'])) {
            throw new Exception('Context variable "this" not allowed, as it is already reserved for a pointer to the current Fusion object.', 1344325044);
        }
        $contextVariables['this'] = $contextObject;

        if ($this->eelEvaluator instanceof \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }

        return EelUtility::evaluateEelExpression($expression, $this->eelEvaluator, $contextVariables);
    }

    /**
     * Evaluate "@apply" for the given fusion key.
     *
     * If apply-definitions are found they are evaluated and the returned keys are combined.
     * The result is returned as array with the following structure:
     *
     * [
     *    'fusionPath/key_1' => ['key' => 'key_1', 'value' => 'evaluated value 1'],
     *    'fusionPath/key_2' => ['key' => 'key_2', 'value' => 'evaluated value 2']
     * ]
     *
     * If no apply-expression is defined null is returned instead.
     *
     * @param array $configurationWithEventualProperties
     * @param string $fusionPath
     * @return array|null
     */
    protected function evaluateApplyValues($configurationWithEventualProperties, $fusionPath): ?array
    {
        if (isset($configurationWithEventualProperties['__meta']['apply'])) {
            $fusionObjectType = $configurationWithEventualProperties['__objectType'];
            if (!preg_match('#<[^>]*>$#', $fusionPath)) {
                // Only add Fusion object type to last path part if not already set
                $fusionPath .= '<' . $fusionObjectType . '>';
            }
            $combinedApplyValues = [];
            $propertiesConfiguration = $configurationWithEventualProperties['__meta']['apply'];
            $positionalArraySorter = new PositionalArraySorter($propertiesConfiguration, '__meta.position');
            foreach ($positionalArraySorter->getSortedKeys() as $key) {
                // skip keys which start with __, as they are purely internal.
                if ($key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                    continue;
                }

                $singleApplyPath = $fusionPath . '/__meta/apply/' . $key;
                if ($this->evaluateIfCondition($propertiesConfiguration[$key], $singleApplyPath) === false) {
                    continue;
                }
                if (isset($propertiesConfiguration[$key]['expression'])) {
                    $singleApplyPath .= '/expression';
                }
                $singleApplyValues = $this->evaluateInternal($singleApplyPath, self::BEHAVIOR_EXCEPTION);
                if ($this->getLastEvaluationStatus() !== static::EVALUATION_SKIPPED && is_array($singleApplyValues)) {
                    foreach ($singleApplyValues as $key => $value) {
                        // skip keys which start with __, as they are purely internal.
                        if ($key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                            continue;
                        }

                        $combinedApplyValues[$fusionPath . '/' . $key] = [
                            'key' => $key,
                            'value' => $value
                        ];
                    }
                }
            }
            return $combinedApplyValues;
        }

        return null;
    }

    /**
     * Evaluate processors on given value.
     *
     * @param mixed $valueToProcess
     * @param array $configurationWithEventualProcessors
     * @param string $fusionPath
     * @param AbstractFusionObject $contextObject
     * @return mixed
     */
    protected function evaluateProcessors($valueToProcess, $configurationWithEventualProcessors, $fusionPath, AbstractFusionObject $contextObject = null)
    {
        if (isset($configurationWithEventualProcessors['__meta']['process'])) {
            $processorConfiguration = $configurationWithEventualProcessors['__meta']['process'];
            $positionalArraySorter = new PositionalArraySorter($processorConfiguration, '__meta.position');
            foreach ($positionalArraySorter->getSortedKeys() as $key) {
                $processorPath = $fusionPath . '/__meta/process/' . $key;
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
     * @param AbstractFusionObject $contextObject
     * @return boolean
     */
    protected function evaluateIfCondition($configurationWithEventualIf, $configurationPath, AbstractFusionObject $contextObject = null)
    {
        if (isset($configurationWithEventualIf['__meta']['if'])) {
            foreach ($configurationWithEventualIf['__meta']['if'] as $conditionKey => $conditionValue) {
                $conditionValue = $this->evaluateInternal($configurationPath . '/__meta/if/' . $conditionKey, self::BEHAVIOR_EXCEPTION, $contextObject);
                if ((bool)$conditionValue === false) {
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
     * @param string $fusionPath The Fusion path that cannot be rendered
     * @param array $fusionConfiguration
     * @param string $behaviorIfPathNotFound One of the BEHAVIOR_* constants
     * @throws Exception\MissingFusionImplementationException
     * @throws Exception\MissingFusionObjectException
     */
    protected function throwExceptionForUnrenderablePathIfNeeded($fusionPath, $fusionConfiguration, $behaviorIfPathNotFound)
    {
        if (isset($fusionConfiguration['__objectType'])) {
            $objectType = $fusionConfiguration['__objectType'];
            throw new Exceptions\MissingFusionImplementationException(sprintf(
                "The Fusion object at path `%s` could not be rendered:
					The Fusion object `%s` is not completely defined (missing property `@class`).
					Most likely you didn't inherit from a basic object.
					For example you could add the following line to your Fusion:
					`prototype(%s) < prototype(Neos.Fusion:Template)`",
                $fusionPath, $objectType, $objectType), 1332493995);
        }

        if ($behaviorIfPathNotFound === self::BEHAVIOR_EXCEPTION) {
            throw new Exceptions\MissingFusionObjectException(sprintf(
                'No Fusion object found in path "%s"
					Please make sure to define one in your Fusion configuration.', $fusionPath
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
     * If the Fusion content cache should be enabled at all
     *
     * @param boolean $flag
     * @return void
     */
    public function setEnableContentCache($flag)
    {
        $this->runtimeContentCache->setEnableContentCache($flag);
    }
}
