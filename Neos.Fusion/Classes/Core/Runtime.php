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

use Neos\Eel\Utility as EelUtility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Fusion\Core\Cache\RuntimeContentCache;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Fusion\Exception;
use Neos\Fusion\Exception as Exceptions;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use Neos\Utility\PositionalArraySorter;

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
     * Internal constants defining how evaluate should work in case of an error
     */
    const BEHAVIOR_EXCEPTION = 'Exception';

    const BEHAVIOR_RETURNNULL = 'NULL';

    /**
     * Internal constants defining a status of how evaluate was evaluated
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
     * Reference to the current context
     *
     * @var array
     */
    protected $currentContext = [];

    /**
     * Reference to the current apply value
     *
     * @var array
     */
    protected $currentApplyValues = [];

    /**
     * Fusion global variables like EEL helper definitions {@see FusionGlobals}
     */
    public readonly FusionGlobals $fusionGlobals;

    /**
     * @var array
     */
    protected $runtimeConfiguration;

    /**
     * @deprecated
     */
    protected ControllerContext $controllerContext;

    /**
     * @var array
     */
    protected $settings;

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
     * @internal use {@see RuntimeFactory} for instantiating.
     */
    public function __construct(
        FusionConfiguration $fusionConfiguration,
        FusionGlobals $fusionGlobals
    ) {
        $this->runtimeConfiguration = new RuntimeConfiguration(
            $fusionConfiguration->toArray()
        );
        $this->runtimeContentCache = new RuntimeContentCache($this);
        $this->fusionGlobals = $fusionGlobals;
    }

    /**
     * @deprecated {@see self::getControllerContext()}
     * @internal
     */
    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Returns the context which has been passed by the currently active MVC Controller
     *
     * DEPRECATED CONCEPT. We only implement this as backwards-compatible layer.
     *
     * @deprecated use `Runtime::fusionGlobals->get('request')` instead to get the request. {@see FusionGlobals::get()}
     * @internal
     */
    public function getControllerContext(): ControllerContext
    {
        if (isset($this->controllerContext)) {
            return $this->controllerContext;
        }

        if (!($request = $this->fusionGlobals->get('request')) instanceof ActionRequest) {
            throw new \RuntimeException(sprintf('Expected Fusion variable "request" to be of type ActionRequest, got value of type "%s".', get_debug_type($request)), 1693558026485);
        }

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        return $this->controllerContext = new ControllerContext(
            $request,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        );
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
     * During Fusion rendering the method can be used to add tag dynamicaly for the current cache segment.
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
     * Warning unlike in Fusion's \@context or {@see Runtime::pushContext()},
     * no checks are imposed to prevent overriding Fusion globals like "request".
     * Relying on this behaviour is highly discouraged but leveraged by Neos.Fusion.Form {@see FusionGlobals}.
     *
     * @internal purely internal method, should not be called outside Neos.Fusion.
     * @param array $contextArray
     * @return void
     */
    public function pushContextArray(array $contextArray)
    {
        $this->contextStack[] = $contextArray;
        $this->currentContext = $contextArray;
    }

    /**
     * Push a new context object to the rendering stack.
     * It is disallowed to replace global variables {@see FusionGlobals}.
     *
     * @param string $key the key inside the context
     * @param mixed $context
     * @return void
     */
    public function pushContext($key, $context)
    {
        if ($this->fusionGlobals->has($key)) {
            throw new RuntimeException(sprintf('Overriding Fusion global variable "%s" via @context is not allowed.', $key), 1694284229044);
        }
        $newContext = $this->currentContext;
        $newContext[$key] = $context;
        $this->contextStack[] = $newContext;
        $this->currentContext = $newContext;
    }

    /**
     * Remove the topmost context objects and return them
     *
     * @return array the topmost context objects as associative array
     */
    public function popContext()
    {
        $lastItem = array_pop($this->contextStack);
        $this->currentContext = empty($this->contextStack) ? [] : end($this->contextStack);
        return $lastItem;
    }

    /**
     * Get the current context array.
     * This PHP context api unlike Fusion, doesn't include the Fusion globals {@see FusionGlobals}.
     * The globals can be accessed via {@see Runtime::$fusionGlobals}.
     *
     * @return array the array of current context objects
     */
    public function getCurrentContext()
    {
        return $this->currentContext;
    }

    public function popApplyValues(array $paths): void
    {
        foreach ($paths as $path) {
            unset($this->currentApplyValues[$path]);
        }
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
     * @return mixed
     * @throws \Exception
     * @throws SecurityException
     */
    public function render($fusionPath)
    {
        try {
            $output = $this->evaluate($fusionPath, null, self::BEHAVIOR_EXCEPTION);
            if ($this->debugMode) {
                $output = sprintf(
                    '%1$s<!-- Beginning to render Fusion path "%2$s" (Context: %3$s) -->%4$s%1$s<!-- End to render Fusion path "%2$s" (Context: %3$s) -->',
                    chr(10),
                    $fusionPath,
                    implode(', ', array_keys($this->currentContext)),
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
     * settings specified in Neos.Fusion.rendering.exceptionHandler or {@exceptionHandler}
     *
     * @param string $fusionPath
     * @param \Exception $exception
     * @param boolean $useInnerExceptionHandler
     * @return string
     * @throws Exception
     * @throws InvalidConfigurationException
     * @throws SecurityException
     * @throws StopActionException
     */
    public function handleRenderingException(string $fusionPath, \Exception $exception, bool $useInnerExceptionHandler = false)
    {
        $fusionConfiguration = $this->runtimeConfiguration->forPath($fusionPath);

        $useLocalExceptionHandler = isset($fusionConfiguration['__meta']['exceptionHandler']);
        $exceptionHandlerClass = $useLocalExceptionHandler
            // use local configured @exceptionHandler
            ? $fusionConfiguration['__meta']['exceptionHandler']
            // use global configured exception handler
            : $this->settings['rendering'][$useInnerExceptionHandler ? 'innerExceptionHandler' : 'exceptionHandler'];

        $exceptionHandler = null;
        if ($this->objectManager->isRegistered($exceptionHandlerClass)) {
            $exceptionHandler = $this->objectManager->get($exceptionHandlerClass);
        }

        if ($exceptionHandler instanceof AbstractRenderingExceptionHandler === false) {
            $usedExceptionHandler = $useLocalExceptionHandler
                ? 'property "@exceptionHandler"'
                : 'setting "Neos.Fusion.rendering.exceptionHandler"';

            throw new InvalidConfigurationException(<<<MESSAGE
                The class \"$exceptionHandlerClass\" is not valid for $usedExceptionHandler
                Please specify a fully qualified classname to a subclass of Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler.
                You might implement an own handler or use one of the following:
                Neos\Fusion\Core\ExceptionHandlers\AbsorbingHandler
                Neos\Fusion\Core\ExceptionHandlers\HtmlMessageHandler
                Neos\Fusion\Core\ExceptionHandlers\PlainTextHandler
                Neos\Fusion\Core\ExceptionHandlers\ThrowingHandler
                Neos\Fusion\Core\ExceptionHandlers\XmlCommentHandler
                MESSAGE, 1368788926);
        }

        $exceptionHandler->setRuntime($this);
        if (array_key_exists('__objectType', $fusionConfiguration)) {
            $fusionPath .= "<{$fusionConfiguration['__objectType']}>";
        }
        return $exceptionHandler->handleRenderingException($fusionPath, $exception);
    }

    /**
     * Determine if the given Fusion path is renderable, which means it exists
     * and has an implementation.
     *
     * @param string $fusionPath
     * @return boolean
     * @throws Exception
     */
    public function canRender($fusionPath)
    {
        $fusionConfiguration = $this->runtimeConfiguration->forPath($fusionPath);

        if (isset($fusionConfiguration['__eelExpression']) || isset($fusionConfiguration['__value'])) {
            return true;
        }

        if (isset($fusionConfiguration['__meta']['class']) && isset($fusionConfiguration['__objectType'])) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate an absolute Fusion path and return the result
     *
     * @param string $fusionPath
     * @param mixed $contextObject The object which will be "this" in Eel expressions. ONLY FOR INTERNAL USE!
     * @param (Runtime::BEHAVIOR_EXCEPTION|Runtime::BEHAVIOR_RETURNNULL) $behaviorIfPathNotFound
     * @return mixed
     *
     * @throws StopActionException
     * @throws SecurityException
     * @throws Exception
     * @throws RuntimeException
     * @throws InvalidConfigurationException
     */
    public function evaluate(string $fusionPath, $contextObject = null, string $behaviorIfPathNotFound = self::BEHAVIOR_RETURNNULL)
    {
        $this->lastEvaluationStatus = self::EVALUATION_EXECUTED;

        $fusionConfiguration = $this->runtimeConfiguration->forPath($fusionPath);

        if (isset($this->currentApplyValues[$fusionPath])) {
            // the $fusionPath is an @apply value
            // we evaluate @if and @process
            // when the @apply value is lazy we trigger the evaluation
            // we return directly
            if (isset($fusionConfiguration['__meta']['if']) && $this->evaluateIfCondition($fusionConfiguration, $fusionPath, $contextObject) === false) {
                return null;
            }
            $appliedValue = $this->currentApplyValues[$fusionPath]['value'];
            if (isset($this->currentApplyValues[$fusionPath]['lazy'])) {
                $appliedValue = $appliedValue();
            }
            if (isset($fusionConfiguration['__meta']['process'])) {
                $appliedValue = $this->evaluateProcessors($appliedValue, $fusionConfiguration, $fusionPath, $contextObject);
            }
            return $appliedValue;
        }

        if (isset($fusionConfiguration['__eelExpression']) || isset($fusionConfiguration['__value'])) {
            // fast path for expression or value
            try {
                return $this->evaluateExpressionOrValueInternal($fusionPath, $fusionConfiguration, $contextObject);
            } catch (StopActionException | SecurityException | RuntimeException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                return $this->handleRenderingException($fusionPath, $exception, true);
            }
        }

        // render fusion object
        $cacheContext = $this->runtimeContentCache->enter($fusionConfiguration['__meta']['cache'] ?? [], $fusionPath);
        $needToPopContext = false;
        $applyPathsToPop = [];
        try {
            if (isset($fusionConfiguration['__meta']['class']) === false
                || isset($fusionConfiguration['__objectType']) === false) {
                // fusion object not found / cannot be rendered
                $this->throwExceptionForUnrenderablePathIfNeeded($fusionPath, $fusionConfiguration, $behaviorIfPathNotFound);
                $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;
                return null;
            }

            $applyPathsToPop = $this->prepareApplyValuesForFusionPath($fusionPath, $fusionConfiguration);
            $fusionObject = $this->instantiatefusionObject($fusionPath, $fusionConfiguration, $applyPathsToPop);
            $needToPopContext = $this->prepareContextForFusionObject($fusionObject, $fusionPath, $fusionConfiguration, $cacheContext);
            return $this->evaluateObjectOrRetrieveFromCache($fusionObject, $fusionPath, $fusionConfiguration, $cacheContext);
        } catch (
            StopActionException
            | SecurityException
            | RuntimeException
            | Exception\MissingFusionImplementationException
            | Exception\MissingFusionObjectException
            $exception
        ) {
            throw $exception;
        } catch (\Exception $exception) {
            return $this->handleRenderingException($fusionPath, $exception, true);
        } finally {
            // ends the evaluation of a fusion path
            if ($needToPopContext) {
                $this->popContext();
            }
            if ($applyPathsToPop !== []) {
                $this->popApplyValues($applyPathsToPop);
            }
            $this->runtimeContentCache->leave($cacheContext);
        }
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
        if (isset($fusionConfiguration['__meta']['if']) && $this->evaluateIfCondition($fusionConfiguration, $fusionPath, $fusionObject) === false) {
            $evaluateObject = false;
        }

        if ($evaluateObject) {
            $output = $fusionObject->evaluate();
            $evaluationStatus = self::EVALUATION_EXECUTED;
        }

        $this->lastEvaluationStatus = $evaluationStatus;

        if ($evaluateObject && isset($fusionConfiguration['__meta']['process'])) {
            $output = $this->evaluateProcessors($output, $fusionConfiguration, $fusionPath, $fusionObject);
        }
        $output = $this->runtimeContentCache->postProcess($cacheContext, $fusionObject, $output);
        return $output;
    }

    /**
     * Evaluates an EEL expression or value, checking if conditions first and applying processors.
     *
     * @param string $fusionPath the Fusion path up to now
     * @param array $fusionConfiguration Fusion configuration for the expression or value
     * @param \Neos\Fusion\FusionObjects\AbstractFusionObject $contextObject An optional object for the "this" value inside the context
     * @return mixed The result of the evaluation
     * @throws Exception
     */
    protected function evaluateExpressionOrValueInternal($fusionPath, $fusionConfiguration, $contextObject)
    {
        if (isset($fusionConfiguration['__meta']['if']) && $this->evaluateIfCondition($fusionConfiguration, $fusionPath, $contextObject) === false) {
            $this->lastEvaluationStatus = self::EVALUATION_SKIPPED;

            return null;
        }

        if (isset($fusionConfiguration['__eelExpression'])) {
            $evaluatedValue = $this->evaluateEelExpression($fusionConfiguration['__eelExpression'], $contextObject);
        } else {
            // must be simple type, as this is the only place where this method is called.
            $evaluatedValue = $fusionConfiguration['__value'];
        }

        if (isset($fusionConfiguration['__meta']['process'])) {
            $evaluatedValue = $this->evaluateProcessors($evaluatedValue, $fusionConfiguration, $fusionPath, $contextObject);
        }

        return $evaluatedValue;
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
     * @return array Paths to pop
     * @throws Exception
     * @throws RuntimeException
     * @throws SecurityException
     * @throws StopActionException
     */
    protected function prepareApplyValuesForFusionPath($fusionPath, $fusionConfiguration): array
    {
        $spreadValues = $this->evaluateApplyValues($fusionConfiguration, $fusionPath);
        if ($spreadValues === null) {
            return [];
        }

        foreach ($spreadValues as $path => $entry) {
            $this->currentApplyValues[$path] = $entry;
        }
        return array_keys($spreadValues);
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
            $newContextArray = [];
            foreach ($cacheContext['configuration']['context'] as $contextVariableName) {
                if (isset($this->currentContext[$contextVariableName])) {
                    $newContextArray[$contextVariableName] = $this->currentContext[$contextVariableName];
                }
            }
        }

        if (isset($fusionConfiguration['__meta']['context'])) {
            $newContextArray ??= $this->currentContext;
            foreach ($fusionConfiguration['__meta']['context'] as $contextKey => $contextValue) {
                if ($this->fusionGlobals->has($contextKey)) {
                    throw new RuntimeException(sprintf('Overriding Fusion global variable "%s" via @context is not allowed.', $contextKey), 1694247627130);
                }
                $newContextArray[$contextKey] = $this->evaluate($fusionPath . '/__meta/context/' . $contextKey, $fusionObject, self::BEHAVIOR_EXCEPTION);
            }
        }

        if (isset($newContextArray)) {
            $this->pushContextArray($newContextArray);
            return true;
        }

        return false;
    }

    /**
     * Instantiates a Fusion object specified by the given path and configuration
     *
     * @param string $fusionPath Path to the configuration for this object instance
     * @param array $fusionConfiguration Configuration at the given path
     * @param array $applyValuePaths Apply value paths for this object
     * @return AbstractFusionObject
     * @throws Exception
     */
    protected function instantiateFusionObject($fusionPath, $fusionConfiguration, array $applyValuePaths)
    {
        $fusionObjectType = $fusionConfiguration['__objectType'];

        $fusionObjectClassName = $fusionConfiguration['__meta']['class'] ?? null;

        if (!preg_match('#<[^>]*>$#', $fusionPath)) {
            // Only add Fusion object type to last path part if not already set
            $fusionPath .= '<' . $fusionObjectType . '>';
        }
        if (!class_exists($fusionObjectClassName)) {
            throw new Exception(<<<MESSAGE
                The implementation class "$fusionObjectClassName" defined for Fusion object of type "$fusionObjectType" does not exist.
                Maybe a typo in the "@class" property.
                MESSAGE, 1347952109);
        }

        /** @var $fusionObject AbstractFusionObject */
        $fusionObject = new $fusionObjectClassName($this, $fusionPath, $fusionObjectType);
        if ($this->shouldAssignPropertiesToFusionObject($fusionObject)) {
            /** @var $fusionObject AbstractArrayFusionObject */
            if (isset($fusionConfiguration['__meta']['ignoreProperties'])) {
                $evaluatedIgnores = $this->evaluate($fusionPath . '/__meta/ignoreProperties', $fusionObject);
                $fusionObject->setIgnoreProperties(is_array($evaluatedIgnores) ? $evaluatedIgnores : []);
            }
            $this->assignPropertiesToFusionObject($fusionObject, $fusionConfiguration, $applyValuePaths);
        }
        return $fusionObject;
    }

    /**
     * Is the given object an array like object that should get all properties assigned to iterate or process internally
     *
     * @psalm-assert-if-true AbstractArrayFusionObject $fusionObject
     */
    protected function shouldAssignPropertiesToFusionObject(AbstractFusionObject $fusionObject): bool
    {
        return $fusionObject instanceof AbstractArrayFusionObject;
    }

    /**
     * Assigns paths to the Array-Fusion object
     */
    protected function assignPropertiesToFusionObject(AbstractArrayFusionObject $fusionObject, array $fusionConfiguration, array $applyValuePaths): void
    {
        foreach ($fusionConfiguration as $key => $value) {
            // skip keys which start with __, as they are purely internal.
            if (is_string($key) && $key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                continue;
            }

            ObjectAccess::setProperty($fusionObject, $key, $value);
        }

        if ($applyValuePaths !== []) {
            foreach ($applyValuePaths as $path) {
                $entry = $this->currentApplyValues[$path];
                $key = $entry['key'];
                if (isset($entry['lazy'])) {
                    $valueAst = [
                        '__eelExpression' => null,
                        // Mark this property as not having a simple value in the AST -
                        // the object implementation has to evaluate the key through the Runtime
                        '__objectType' => 'Neos.Fusion:Lazy',
                        '__value' => null
                    ];
                } else {
                    $valueAst = [
                        '__eelExpression' => null,
                        '__objectType' => null,
                        '__value' => $entry['value']
                    ];
                }

                // merge existing meta-configuration to valueAst
                // to preserve @if, @process and @position informations
                if ($meta = Arrays::getValueByPath($fusionConfiguration, [$key, '__meta'])) {
                    $valueAst['__meta'] = $meta;
                }

                ObjectAccess::setProperty($fusionObject, $entry['key'], $valueAst);
            }
        }
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

        $contextVariables = array_merge($this->fusionGlobals->value, $this->currentContext);

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
                if (is_string($key) && $key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                    continue;
                }

                $singleApplyPath = $fusionPath . '/__meta/apply/' . $key;
                if (isset($propertiesConfiguration[$key]['__meta']['if']) && $this->evaluateIfCondition($propertiesConfiguration[$key], $singleApplyPath) === false) {
                    continue;
                }
                if (isset($propertiesConfiguration[$key]['expression'])) {
                    $singleApplyPath .= '/expression';
                }
                $singleApplyValues = $this->evaluate($singleApplyPath, null, self::BEHAVIOR_EXCEPTION);
                if ($singleApplyValues !== null || $this->getLastEvaluationStatus() !== static::EVALUATION_SKIPPED) {
                    if (is_array($singleApplyValues)) {
                        foreach ($singleApplyValues as $key => $value) {
                            // skip keys which start with __, as they are purely internal.
                            if (is_string($key) && $key[0] === '_' && $key[1] === '_' && in_array($key, Parser::$reservedParseTreeKeys, true)) {
                                continue;
                            }

                            $combinedApplyValues[$fusionPath . '/' . $key] = [
                                'key' => $key,
                                'value' => $value
                            ];
                        }
                    } elseif ($singleApplyValues instanceof \Traversable && $singleApplyValues instanceof \ArrayAccess) {
                        for ($singleApplyValues->rewind(); ($key = $singleApplyValues->key()) !== null; $singleApplyValues->next()) {
                            $combinedApplyValues[$fusionPath . '/' . $key] = [
                                'key' => $key,
                                'value' => function () use ($singleApplyValues, $key) {
                                    return $singleApplyValues[$key];
                                },
                                'lazy' => true
                            ];
                        }
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
        $processorConfiguration = $configurationWithEventualProcessors['__meta']['process'];
        $positionalArraySorter = new PositionalArraySorter($processorConfiguration, '__meta.position');
        foreach ($positionalArraySorter->getSortedKeys() as $key) {
            $processorPath = $fusionPath . '/__meta/process/' . $key;
            if (isset($processorConfiguration[$key]['__meta']['if']) && $this->evaluateIfCondition($processorConfiguration[$key], $processorPath, $contextObject) === false) {
                continue;
            }

            # If there is only the internal "__stopInheritanceChain" path set, skip evaluation
            if (isset($processorConfiguration[$key]['__stopInheritanceChain']) && count($processorConfiguration[$key]) === 1) {
                continue;
            }

            if (isset($processorConfiguration[$key]['expression'])) {
                $processorPath .= '/expression';
            }

            $this->pushContext('value', $valueToProcess);
            $result = $this->evaluate($processorPath, $contextObject, self::BEHAVIOR_EXCEPTION);
            if ($result !== null || $this->getLastEvaluationStatus() !== static::EVALUATION_SKIPPED) {
                $valueToProcess = $result;
            }
            $this->popContext();
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
        foreach ($configurationWithEventualIf['__meta']['if'] as $conditionKey => $conditionValue) {
            $conditionValue = $this->evaluate($configurationPath . '/__meta/if/' . $conditionKey, $contextObject, self::BEHAVIOR_EXCEPTION);
            if ((bool)$conditionValue === false) {
                return false;
            }
        }

        return true;
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
            throw new Exceptions\MissingFusionImplementationException(<<<MESSAGE
                The Fusion object "$objectType" cannot be rendered:
                Most likely you mistyped the prototype name or did not define
                the Fusion prototype with "prototype($objectType) < prototype(...)".
                Other possible reasons are a missing parent-prototype or
                a missing "@class" annotation for prototypes without parent.
                It is also possible your Fusion file is not read because
                of a missing "include:" statement.
                MESSAGE, 1332493995);
        }

        if ($behaviorIfPathNotFound === self::BEHAVIOR_EXCEPTION) {
            throw new Exceptions\MissingFusionObjectException(<<<MESSAGE
                No Fusion object found in path "$fusionPath"
                Please make sure to define one in your Fusion configuration.
                MESSAGE, 1332493990);
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
