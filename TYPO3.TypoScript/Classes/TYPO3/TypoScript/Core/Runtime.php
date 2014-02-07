<?php
namespace TYPO3\TypoScript\Core;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\TypoScript\Core\Cache\ContentCache;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\TypoScript\Exception as Exceptions;
use TYPO3\TypoScript\Exception;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;
use TYPO3\Eel\FlowQuery\FlowQuery;

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
class Runtime {

	/**
	 * Internal constants defining how evaluateInternal should work in case of an error.
	 */
	const BEHAVIOR_EXCEPTION = 'Exception';
	const BEHAVIOR_RETURNNULL = 'NULL';

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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TypoScript\Core\Cache\ContentCache
	 */
	protected $contentCache;

	/**
	 * @var array
	 */
	protected $configurationOnPathRuntimeCache = array();

	/**
	 * @var boolean
	 */
	protected $debugMode = FALSE;

	/**
	 * @var boolean
	 */
	protected $enableContentCache = FALSE;

	/**
	 * @var boolean
	 */
	protected $inCacheEntryPoint = NULL;

	/**
	 * @var boolean
	 */
	protected $addCacheSegmentMarkersToPlaceholders = FALSE;

	/**
	 * Constructor for the TypoScript Runtime
	 *
	 * @param array $typoScriptConfiguration
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 */
	public function __construct(array $typoScriptConfiguration, \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$this->typoScriptConfiguration = $typoScriptConfiguration;
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Inject settings of this package
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
		if (isset($this->settings['debugMode'])) {
			$this->setDebugMode($this->settings['debugMode'] === TRUE);
		}
		if (isset($this->settings['enableContentCache'])) {
			$this->setEnableContentCache($this->settings['enableContentCache'] === TRUE);
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
	public function pushContextArray(array $contextArray) {
		$this->renderingStack[] = $contextArray;
	}

	/**
	 * Push a new context object to the rendering stack
	 *
	 * @param string $key the key inside the context
	 * @param mixed $context
	 * @return void
	 */
	public function pushContext($key, $context) {
		$newContext = $this->getCurrentContext();
		$newContext[$key] = $context;
		$this->renderingStack[] = $newContext;
	}

	/**
	 * Remove the topmost context objects and return them
	 *
	 * @return array the topmost context objects as associative array
	 */
	public function popContext() {
		return array_pop($this->renderingStack);
	}

	/**
	 * Get the current context array
	 *
	 * @return array the array of current context objects
	 */
	public function getCurrentContext() {
		return $this->renderingStack[count($this->renderingStack) - 1];
	}

	/**
	 * Evaluate an absolute TypoScript path and return the result
	 *
	 * @param string $typoScriptPath
	 * @param object $contextObject the object available as "this" in Eel expressions. ONLY FOR INTERNAL USE!
	 * @return mixed the result of the evaluation, can be a string but also other data types
	 */
	public function evaluate($typoScriptPath, $contextObject = NULL) {
		return $this->evaluateInternal($typoScriptPath, self::BEHAVIOR_RETURNNULL, $contextObject);
	}

	/**
	 * Render an absolute TypoScript path and return the result.
	 *
	 * Compared to $this->evaluate, this adds some more comments helpful for debugging.
	 *
	 * @param string $typoScriptPath
	 * @return string
	 */
	public function render($typoScriptPath) {
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
	 * @return string
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
	 * @throws \Exception|\TYPO3\Flow\Exception
	 */
	public function handleRenderingException($typoScriptPath, \Exception $exception) {
		$typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);

		if (isset($typoScriptConfiguration['__meta']['exceptionHandler'])) {
			$exceptionHandlerClass = $typoScriptConfiguration['__meta']['exceptionHandler'];
			$invalidExceptionHandlerMessage = 'The class "%s" is not valid for property "@exceptionHandler".';
		} else {
			$exceptionHandlerClass = $this->settings['rendering']['exceptionHandler'];
			$invalidExceptionHandlerMessage = 'The class "%s" is not valid for setting "TYPO3.TypoScript.rendering.exceptionHandler".';
		}
		$exceptionHandler = NULL;
		if ($this->objectManager->isRegistered($exceptionHandlerClass)) {
			$exceptionHandler = $this->objectManager->get($exceptionHandlerClass);
		}

		if ($exceptionHandler === NULL || !($exceptionHandler instanceof AbstractRenderingExceptionHandler)) {
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
		$output = $exceptionHandler->handleRenderingException($typoScriptPath, $exception);
		$this->systemLogger->logException($exception);
		return $output;
	}

	/**
	 * Determine if the given TypoScript path is renderable, which means it exists
	 * and has an implementation.
	 *
	 * @param string $typoScriptPath
	 * @return boolean
	 */
	public function canRender($typoScriptPath) {
		$typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);

		return $this->canRenderWithConfiguration($typoScriptConfiguration);
	}

	/**
	 * Internal evaluation if given configuration is renderable.
	 *
	 * @param array $typoScriptConfiguration
	 * @return boolean
	 */
	protected function canRenderWithConfiguration(array $typoScriptConfiguration) {
		if (isset($typoScriptConfiguration['__eelExpression'])) {
			return TRUE;
		}
		if (isset($typoScriptConfiguration['__value'])) {
			return TRUE;
		}

		if (!isset($typoScriptConfiguration['__meta']['class']) || !isset($typoScriptConfiguration['__objectType'])) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Internal evaluation method of absolute $typoScriptPath
	 *
	 * @param string $typoScriptPath
	 * @param string $behaviorIfPathNotFound one of BEHAVIOR_EXCEPTION or BEHAVIOR_RETURNNULL
	 * @param mixed $contextObject the object which will be "this" in Eel expressions, if any.
	 * @return mixed
	 * @throws \TYPO3\TypoScript\Exception\MissingTypoScriptImplementationException
	 * @throws \TYPO3\TypoScript\Exception
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @throws \TYPO3\TypoScript\Exception\RuntimeException
	 * @throws \TYPO3\TypoScript\Exception\MissingTypoScriptObjectException
	 * @throws \TYPO3\TypoScript\Exception\RuntimeException
	 */
	protected function evaluateInternal($typoScriptPath, $behaviorIfPathNotFound, $contextObject = NULL) {
		$typoScriptConfiguration = $this->getConfigurationForPath($typoScriptPath);

		$cacheForPathEnabled = isset($typoScriptConfiguration['__meta']['cache']['mode']) && $typoScriptConfiguration['__meta']['cache']['mode'] === 'cached';
		$cacheForPathDisabled = isset($typoScriptConfiguration['__meta']['cache']['mode']) && $typoScriptConfiguration['__meta']['cache']['mode'] === 'uncached';

		$currentPathIsEntryPoint = FALSE;
		if ($this->enableContentCache && $cacheForPathEnabled) {
			if ($this->inCacheEntryPoint === NULL) {
				$this->inCacheEntryPoint = TRUE;
				$currentPathIsEntryPoint = TRUE;
			}
		}

		if (!$this->canRenderWithConfiguration($typoScriptConfiguration)) {
			if ($behaviorIfPathNotFound === self::BEHAVIOR_EXCEPTION) {
				if (!isset($typoScriptConfiguration['__objectType'])) {
					throw new Exceptions\MissingTypoScriptObjectException('No "' . $typoScriptPath . '" TypoScript object found. Please make sure to define one in your TypoScript configuration.', 1332493990);
				} else {
					throw new Exceptions\MissingTypoScriptImplementationException('The TypoScript object at path "' . $typoScriptPath . '" could not be rendered: Missing implementation class name for "' . $typoScriptConfiguration['__objectType'] . '". Add @class in your TypoScript configuration.', 1332493995);
				}
			} else {
				if ($currentPathIsEntryPoint) {
					$this->inCacheEntryPoint = NULL;
				}
				return NULL;
			}
		}
		if (isset($typoScriptConfiguration['__eelExpression']) || isset($typoScriptConfiguration['__value'])) {
			return $this->evaluateEelExpressionOrSimpleValueWithProcessor($typoScriptPath, $typoScriptConfiguration, $contextObject);
		}

		$tsObject = $this->instantiateTypoScriptObject($typoScriptPath, $typoScriptConfiguration);

			// modify context if @override is specified
		if (isset($typoScriptConfiguration['__meta']['override'])) {
			$contextArray = $this->getCurrentContext();
			foreach ($typoScriptConfiguration['__meta']['override'] as $overrideKey => $overrideValue) {
				$contextArray[$overrideKey] = $this->evaluateInternal($typoScriptPath . '/__meta/override/' . $overrideKey, self::BEHAVIOR_EXCEPTION, $tsObject);
			}
			$this->pushContextArray($contextArray);
		}

		$cacheIdentifierValues = array();
		if ($this->enableContentCache && $cacheForPathEnabled) {
			$cacheIdentifierValues = $this->buildCacheIdentifierValues($typoScriptPath, $typoScriptConfiguration, $tsObject);

			$segment = $this->contentCache->getCachedSegment($this, $typoScriptPath, $cacheIdentifierValues, $this->addCacheSegmentMarkersToPlaceholders);
			if ($segment !== FALSE) {
				if ($currentPathIsEntryPoint) {
					$this->inCacheEntryPoint = NULL;
				}
				return $segment;
			} else {
				$this->addCacheSegmentMarkersToPlaceholders = TRUE;
			}
		}

		try {
			$cacheTags = array();
			if ($this->enableContentCache && $cacheForPathEnabled) {
				$cacheTags = $this->buildCacheTags($typoScriptPath, $typoScriptConfiguration, $tsObject);
			}
			$output = $tsObject->evaluate();
			if ($this->enableContentCache && $cacheForPathEnabled) {
				$output = $this->contentCache->createCacheSegment($output, $typoScriptPath, $cacheIdentifierValues, $cacheTags);
			} elseif ($this->enableContentCache && $cacheForPathDisabled && $this->inCacheEntryPoint) {
				$contextArray = $this->getCurrentContext();
				if (isset($typoScriptConfiguration['__meta']['cache']['context'])) {
					$contextVariables = array();
					foreach ($typoScriptConfiguration['__meta']['cache']['context'] as $contextVariableName) {
						$contextVariables[$contextVariableName] = $contextArray[$contextVariableName];
					}
				} else {
					$contextVariables = $contextArray;
				}
				$output = $this->contentCache->createUncachedSegment($output, $typoScriptPath, $contextVariables);
			}
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $stopActionException) {
			throw $stopActionException;
		} catch (Exceptions\RuntimeException $runtimeException) {
			throw $runtimeException;
		} catch (\Exception $exception) {
			throw new Exceptions\RuntimeException('An exception occurred while rendering "' . $typoScriptPath . '". Please see the nested exception for details.', 1368517488, $exception, $typoScriptPath);
		}

		if (isset($typoScriptConfiguration['__meta']['process'])) {
			$positionalArraySorter = new PositionalArraySorter($typoScriptConfiguration['__meta']['process'], '__meta.position');
			foreach ($positionalArraySorter->getSortedKeys() as $key) {

				$processorPath = $typoScriptPath . '/__meta/process/' . $key;
				if (isset($typoScriptConfiguration['__meta']['process'][$key]['expression'])) {
					$processorPath .= '/expression';
				}

				$this->pushContext('value', $output);
				$output = $this->evaluateInternal($processorPath, self::BEHAVIOR_EXCEPTION, $tsObject);
				$this->popContext();
			}
		}

		if (isset($typoScriptConfiguration['__meta']['override'])) {
			$this->popContext();
		}

		if ($this->enableContentCache && $cacheForPathEnabled && $currentPathIsEntryPoint) {
			$output = $this->contentCache->processCacheSegments($output);
			$this->inCacheEntryPoint = NULL;
			$this->addCacheSegmentMarkersToPlaceholders = FALSE;
		}

		return $output;
	}

	/**
	 * Builds an array of additional key / values which must go into the calculation of the cache entry identifier for
	 * a cached content segment.
	 *
	 * @param string $typoScriptPath Path to the TypoScript object
	 * @param array $typoScriptConfiguration  The TypoScript object's configuration containing the "cache" meta configuration
	 * @param object $tsObject The actual TypoScript object
	 * @return array
	 */
	protected function buildCacheIdentifierValues($typoScriptPath, $typoScriptConfiguration, $tsObject) {
		$cacheIdentifierValues = array();
		if (isset($typoScriptConfiguration['__meta']['cache']['entryIdentifier'])) {
			foreach ($typoScriptConfiguration['__meta']['cache']['entryIdentifier'] as $identifierKey => $identifierValue) {
				$cacheIdentifierValues[$identifierKey] = $this->evaluateInternal($typoScriptPath . '/__meta/cache/entryIdentifier/' . $identifierKey, self::BEHAVIOR_EXCEPTION, $tsObject);
			}
		} else {
			$cacheIdentifierValues = $this->getCurrentContext();
		}
		return $cacheIdentifierValues;
	}

	/**
	 * Builds an array of string which must be used as tags for the cache entry identifier of a specific cached content segment.
	 *
	 * @param string $typoScriptPath Path to the TypoScript object
	 * @param array $typoScriptConfiguration  The TypoScript object's configuration containing the "cache" meta configuration
	 * @param object $tsObject The actual TypoScript object
	 * @return array
	 */
	protected function buildCacheTags($typoScriptPath, $typoScriptConfiguration, $tsObject) {
		$cacheTags = array();
		if (isset($typoScriptConfiguration['__meta']['cache']['entryTags'])) {
			foreach ($typoScriptConfiguration['__meta']['cache']['entryTags'] as $tagKey => $tagValue) {
				$cacheTags[] = $this->evaluateInternal($typoScriptPath . '/__meta/cache/entryTags/' . $tagKey, self::BEHAVIOR_EXCEPTION, $tsObject);
			}
		} else {
			$cacheTags = array(ContentCache::TAG_EVERYTHING);
		}
		return $cacheTags;
	}

	/**
	 * Get the TypoScript Configuration for the given TypoScript path
	 *
	 * @param string $typoScriptPath
	 * @return array
	 * @throws \TYPO3\TypoScript\Exception
	 */
	protected function getConfigurationForPath($typoScriptPath) {
		$pathParts = explode('/', $typoScriptPath);

		$configuration = $this->typoScriptConfiguration;

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
					if (is_array($configuration[$currentPathSegment])) {
						$configuration = $configuration[$currentPathSegment];
					} else {
							// Needed for simple values (which cannot be arrays)
						$configuration = array(
							'__value' => $configuration[$currentPathSegment]
						);
					}
				} else {
					$configuration = array();
				}

				if (isset($configuration['__prototypes'])) {
					$currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverrule($currentPrototypeDefinitions, $configuration['__prototypes']);
				}

				if (isset($matches[3])) {
					$currentPathSegmentType = $matches[3];
				} elseif (isset($configuration['__objectType'])) {
					$currentPathSegmentType = $configuration['__objectType'];
				} else {
					$currentPathSegmentType = NULL;
				}

				if ($currentPathSegmentType !== NULL) {
					if (isset($currentPrototypeDefinitions[$currentPathSegmentType])) {
							// We merge the already flattened prototype with the current configuration (in that order),
							// to make sure that the current configuration (not being defined in the prototype) wins.
						$configuration = Arrays::arrayMergeRecursiveOverrule($currentPrototypeDefinitions[$currentPathSegmentType], $configuration);

							// If context-dependent prototypes are set (such as prototype("foo").prototype("baz")),
							// we update the current prototype definitions.
							// This also takes care of inheritance, as we use the $flattenedPrototype as basis (TODO TESTCASE)
						if (isset($currentPrototypeDefinitions[$currentPathSegmentType]['__prototypes'])) {
							$currentPrototypeDefinitions = Arrays::arrayMergeRecursiveOverrule($currentPrototypeDefinitions, $currentPrototypeDefinitions[$currentPathSegmentType]['__prototypes']);
						}
					}

					$configuration['__objectType'] = $currentPathSegmentType;
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
	protected function instantiateTypoScriptObject($typoScriptPath, $typoScriptConfiguration) {
		$typoScriptObjectType = $typoScriptConfiguration['__objectType'];

		$tsObjectClassName = isset($typoScriptConfiguration['__meta']['class']) ? $typoScriptConfiguration['__meta']['class'] : NULL;

		if (!preg_match('#<[^>]*>$#', $typoScriptPath)) {
				// Only add TypoScript object type to last path part if not already set
			$typoScriptPath .= '<' . $typoScriptObjectType . '>';
		}
		if (!class_exists($tsObjectClassName)) {
			throw new Exception(sprintf('The implementation class "%s" defined for TypoScript object of type "%s" does not exist (defined at %s).', $tsObjectClassName, $typoScriptObjectType, $typoScriptPath), 1347952109);
		}
		/**
		 * @var $typoScriptObject \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject
		 */
		$typoScriptObject = new $tsObjectClassName($this, $typoScriptPath, $typoScriptObjectType);
		$this->setPropertiesOnTypoScriptObject($typoScriptObject, $typoScriptConfiguration);

		return $typoScriptObject;
	}

	/**
	 * Set options on the given TypoScript object
	 *
	 * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject $tsObject
	 * @param array $typoScriptConfiguration
	 * @return void
	 */
	protected function setPropertiesOnTypoScriptObject(AbstractTypoScriptObject $tsObject, array $typoScriptConfiguration) {
		foreach ($typoScriptConfiguration as $key => $value) {
				// skip keys which start with __, as they are purely internal.
			if ($key[0] === '_' && $key[1] === '_') {
				continue;
			}

			ObjectAccess::setProperty($tsObject, $key, $value);
		}
	}

	/**
	 * Evaluate a simple value or eel expression with processors
	 *
	 * @param string $typoScriptPath the TypoScript path up to now
	 * @param array $value TypoScript configuration for the value
	 * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
	 * @return mixed The result of the evaluation
	 */
	protected function evaluateEelExpressionOrSimpleValueWithProcessor($typoScriptPath, array $value, AbstractTypoScriptObject $contextObject = NULL) {
		if (isset($value['__eelExpression'])) {
			$evaluatedValue = $this->evaluateEelExpression($value['__eelExpression'], $contextObject);
		} else {
			// must be simple type, as this is the only place where this method is called.
			$evaluatedValue = $value['__value'];
		}

		if (isset($value['__meta']['process'])) {
			$positionalArraySorter = new PositionalArraySorter($value['__meta']['process'], '__meta.position');
			foreach ($positionalArraySorter->getSortedKeys() as $key) {

				$processorPath = $typoScriptPath . '/__meta/process/' . $key;
				if (isset($value['__meta']['process'][$key]['expression'])) {
					$processorPath .= '/expression';
				}

				$this->pushContext('value', $evaluatedValue);
				$evaluatedValue = $this->evaluateInternal($processorPath, self::BEHAVIOR_EXCEPTION, $contextObject);
				$this->popContext();
			}
		}


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
	protected function evaluateEelExpression($expression, AbstractTypoScriptObject $contextObject = NULL) {
		$contextVariables = array_merge($this->getDefaultContextVariables(), $this->getCurrentContext());
		if (!isset($contextVariables['context']) && isset($contextVariables['node'])) {
			// DEPRECATED since sprint release 10; should be removed lateron.
			$contextVariables['context'] = new FlowQuery(array($contextVariables['node']));
		}
		if (isset($contextVariables['q'])) {
			throw new Exception('Context variable "q" not allowed, as it is already reserved for FlowQuery use.', 1344325040);
		}
		$contextVariables['q'] = function ($element) {
			if (is_array($element) || $element instanceof \Traversable) {
				return new FlowQuery($element);
			} else {
				return new FlowQuery(array($element));
			}
		};
		if (isset($contextVariables['this'])) {
			throw new Exception('Context variable "this" not allowed, as it is already reserved for a pointer to the current TypoScript object.', 1344325044);
		}
		$contextVariables['this'] = $contextObject;

		$context = new \TYPO3\Eel\ProtectedContext($contextVariables);
		$context->whitelist('q');
		$value = $this->eelEvaluator->evaluate($expression, $context);
		return $value;
	}

	/**
	 * Evaluate a TypoScript path with a given context without content caching
	 *
	 * This is used to render uncached segments "out of band" while processing cached segments.
	 *
	 * @internal
	 *
	 * @param string $path
	 * @param array $contextArray
	 * @return mixed
	 *
	 * TODO Find another way of disabling the cache (especially to allow cached content inside uncached content)
	 */
	public function evaluateUncached($path, array $contextArray) {
		$previousEnableContentCache = $this->enableContentCache;
		$this->setEnableContentCache(FALSE);
		$this->pushContextArray($contextArray);
		$result = $this->evaluate($path);
		$this->popContext();
		$this->setEnableContentCache($previousEnableContentCache);
		return $result;
	}

	/**
	 * Returns the context which has been passed by the currently active MVC Controller
	 *
	 * @return \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Get variables from configuration that should be set in the context by default.
	 * For example Eel helpers are made available by this.
	 *
	 * @return array Array with default context variable objects.
	 */
	protected function getDefaultContextVariables() {
		if ($this->defaultContextVariables === NULL) {
			$this->defaultContextVariables = array();
			if (isset($this->settings['defaultContext']) && is_array($this->settings['defaultContext'])) {
				foreach ($this->settings['defaultContext'] as $variableName => $objectType) {
					$currentPathBase = &$this->defaultContextVariables;
					$variablePathNames = explode('.', $variableName);
					foreach ($variablePathNames as $pathName) {
						if (!isset($currentPathBase[$pathName])) {
							$currentPathBase[$pathName] = array();
						}
						$currentPathBase = &$currentPathBase[$pathName];
					}
					$currentPathBase = new $objectType();
				}
			}
		}
		return $this->defaultContextVariables;
	}

	/**
	 * @param boolean $debugMode
	 * @return void
	 */
	public function setDebugMode($debugMode) {
		$this->debugMode = $debugMode;
	}

	/**
	 * @return boolean
	 */
	public function isDebugMode() {
		return $this->debugMode;
	}

	/**
	 * If the TypoScript content cache should be enabled at all
	 *
	 * @param boolean $flag
	 * @return void
	 */
	public function setEnableContentCache($flag) {
		$this->enableContentCache = $flag;
	}

}
