<?php
namespace Neos\Fusion\Core\Cache;

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
use Neos\Utility\Unicode\Functions;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception;

/**
 * Integrate the ContentCache into the Fusion Runtime
 *
 * Holds cache related runtime state.
 */
class RuntimeContentCache
{
    /**
     * @var Runtime
     */
    protected $runtime;

    /**
     * @var boolean
     */
    protected $enableContentCache = false;

    /**
     * @var boolean
     */
    protected $inCacheEntryPoint = null;

    /**
     * @var boolean
     */
    protected $addCacheSegmentMarkersToPlaceholders = false;

    /**
     * Stack of cached segment metadata (lifetime)
     *
     * @var array
     */
    protected $cacheMetadata = [];

    /**
     * @Flow\Inject
     * @var ContentCache
     */
    protected $contentCache;

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var \Neos\Flow\Property\PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @param Runtime $runtime
     */
    public function __construct(Runtime $runtime)
    {
        $this->runtime = $runtime;
    }

    /**
     * Adds a tag built from the given key and value.
     *
     * @param string $key
     * @param string $value
     * @return void
     * @throws Exception
     */
    public function addTag($key, $value)
    {
        $key = trim($key);
        if ($key === '') {
            throw new Exception('Tag Key must not be empty', 1448264366);
        }
        $value = trim($value);
        if ($value === '') {
            throw new Exception('Tag Value must not be empty', 1448264367);
        }
        $tag = Functions::ucfirst($key) . 'DynamicTag_' . $value;
        $this->tags[$tag] = true;
    }

    /**
     * Resets the assigned tags, returning the previously set tags.
     *
     * @return array
     */
    protected function flushTags()
    {
        $tags = array_keys($this->tags);
        $this->tags = [];
        return $tags;
    }

    /**
     * Enter an evaluation
     *
     * Needs to be called right before evaluation of a path starts to check the cache mode and set internal state
     * like the cache entry point.
     *
     * @param array $configuration
     * @param string $fusionPath
     * @return array An evaluate context array that needs to be passed to subsequent calls to pass the current state
     * @throws Exception
     */
    public function enter(array $configuration, $fusionPath)
    {
        $cacheForPathEnabled = isset($configuration['mode']) && ($configuration['mode'] === 'cached' || $configuration['mode'] === 'dynamic');
        $cacheForPathDisabled = isset($configuration['mode']) && ($configuration['mode'] === 'uncached' || $configuration['mode'] === 'dynamic');

        if ($cacheForPathDisabled && (!isset($configuration['context']) || $configuration['context'] === [])) {
            throw new Exception(sprintf('Missing @cache.context configuration for path "%s". An uncached segment must have one or more context variable names configured.', $fusionPath), 1395922119);
        }

        $currentPathIsEntryPoint = false;
        if ($this->enableContentCache && $cacheForPathEnabled) {
            if ($this->inCacheEntryPoint === null) {
                $this->inCacheEntryPoint = true;
                $currentPathIsEntryPoint = true;
            }
        }

        return [
            'configuration' => $configuration,
            'fusionPath' => $fusionPath,
            'cacheForPathEnabled' => $cacheForPathEnabled,
            'cacheForPathDisabled' => $cacheForPathDisabled,
            'currentPathIsEntryPoint' => $currentPathIsEntryPoint
        ];
    }

    /**
     * Check for cached evaluation and or collect metadata for evaluation
     *
     * Try to get a cached segment for the current path and return that with all uncached segments evaluated if it
     * exists. Otherwise metadata for the cache lifetime is collected (if configured) for nested evaluations (to find the
     * minimum maximumLifetime).
     *
     * @param array $evaluateContext The current evaluation context
     * @param object $fusionObject The current Fusion object (for "this" in evaluations)
     * @return array Cache hit state as boolean and value as mixed
     */
    public function preEvaluate(array &$evaluateContext, $fusionObject)
    {
        if ($this->enableContentCache) {
            if ($evaluateContext['cacheForPathEnabled'] && $evaluateContext['cacheForPathDisabled']) {
                $evaluateContext['cacheDiscriminator'] = $this->runtime->evaluate($evaluateContext['fusionPath'] . '/__meta/cache/entryDiscriminator');
            }
            if ($evaluateContext['cacheForPathEnabled']) {
                $evaluateContext['cacheIdentifierValues'] = $this->buildCacheIdentifierValues($evaluateContext['configuration'], $evaluateContext['fusionPath'], $fusionObject);
                $cacheDiscriminator = isset($evaluateContext['cacheDiscriminator']) ? $evaluateContext['cacheDiscriminator'] : null;
                $self = $this;
                $segment = $this->contentCache->getCachedSegment(function ($command, $additionalData, $cache) use ($self) {
                    if (strpos($command, 'eval=') === 0) {
                        $unserializedContext = $self->unserializeContext($additionalData['context']);
                        $path = substr($command, 5);
                        $result = $self->evaluateUncached($path, $unserializedContext);
                        return $result;
                    } elseif (strpos($command, 'evalCached=') === 0) {
                        /*
                         * Why do we need the following line:
                         * - in "enter" the cache context is decided upon which contains "currentPathIsEntryPoint".
                         * - This can not happen in nested segments as the topmost entry point should be the only one active
                         * - the result of a "currentPathIsEntryPoint" is that on postProcess cache segments are parsed from the content.
                         * - To get "currentPathIsEntryPoint" only on topmost segments, the state "$self->inCacheEntryPoint" is used.
                         *   This state can have two values "true" and "null", in case it's true a topmost segment existed and "currentPathIsEntryPoint" will not be set
                         * - A dynamic cache segment that we resolve here is to be seen independently from the parent cached entry as it is a forking point for content
                         *   It must create cache segment tokens in order to properly cache, but those also need to be removed from the result.
                         *   Therefore a dynamic cache entry must always have "currentPathIsEntryPoint" to make sure the markers are parsed regardless of the caching status of the upper levels
                         *   To make that happen the state "$self->inCacheEntryPoint" must be reset to null.
                         */
                        $previouslyInCacheEntryPoint = $self->inCacheEntryPoint;
                        $self->inCacheEntryPoint = null;

                        $unserializedContext = $self->unserializeContext($additionalData['context']);
                        $this->runtime->pushContextArray($unserializedContext);
                        $result = $this->runtime->evaluate($additionalData['path']);
                        $this->runtime->popContext();
                        $self->inCacheEntryPoint = $previouslyInCacheEntryPoint;
                        return $result;
                    } else {
                        throw new Exception(sprintf('Unknown uncached command "%s"', $command), 1392837596);
                    }
                }, $evaluateContext['fusionPath'], $evaluateContext['cacheIdentifierValues'], $this->addCacheSegmentMarkersToPlaceholders, $cacheDiscriminator);
                if ($segment !== false) {
                    return [true, $segment];
                } else {
                    $this->addCacheSegmentMarkersToPlaceholders = true;
                }

                $this->cacheMetadata[] = ['lifetime' => null];
            }


            if (isset($evaluateContext['configuration']['maximumLifetime'])) {
                $maximumLifetime = $this->runtime->evaluate($evaluateContext['fusionPath'] . '/__meta/cache/maximumLifetime', $fusionObject);

                if ($maximumLifetime !== null && $this->cacheMetadata !== []) {
                    $parentCacheMetadata = &$this->cacheMetadata[count($this->cacheMetadata) - 1];

                    if ($parentCacheMetadata['lifetime'] === null) {
                        $parentCacheMetadata['lifetime'] = (int)$maximumLifetime;
                    } elseif ($maximumLifetime > 0) {
                        $parentCacheMetadata['lifetime'] = min((int)$parentCacheMetadata['lifetime'], (int)$maximumLifetime);
                    }
                }
            }
        }
        return [false, null];
    }

    /**
     * Post process output for caching information
     *
     * The content cache stores cache segments with markers inside the generated content. This method creates cache
     * segments and will process the final outer result (currentPathIsEntryPoint) to remove all cache markers and
     * store cache entries.
     *
     * @param array $evaluateContext The current evaluation context
     * @param object $fusionObject The current Fusion object (for "this" in evaluations)
     * @param mixed $output The generated output after caching information was removed
     * @return mixed The post-processed output with cache segment markers or cleaned for the entry point
     */
    public function postProcess(array $evaluateContext, $fusionObject, $output)
    {
        if ($this->enableContentCache && $evaluateContext['cacheForPathEnabled'] && $evaluateContext['cacheForPathDisabled']) {
            $contextArray = $this->runtime->getCurrentContext();
            if (isset($evaluateContext['configuration']['context'])) {
                $contextVariables = [];
                foreach ($evaluateContext['configuration']['context'] as $contextVariableName) {
                    $contextVariables[$contextVariableName] = $contextArray[$contextVariableName];
                }
            } else {
                $contextVariables = $contextArray;
            }
            $cacheTags = $this->buildCacheTags($evaluateContext['configuration'], $evaluateContext['fusionPath'], $fusionObject);
            $cacheMetadata = array_pop($this->cacheMetadata);
            $output = $this->contentCache->createDynamicCachedSegment($output, $evaluateContext['fusionPath'], $contextVariables, $evaluateContext['cacheIdentifierValues'], $cacheTags, $cacheMetadata['lifetime'], $evaluateContext['cacheDiscriminator']);
        } elseif ($this->enableContentCache && $evaluateContext['cacheForPathEnabled']) {
            $cacheTags = $this->buildCacheTags($evaluateContext['configuration'], $evaluateContext['fusionPath'], $fusionObject);
            $cacheMetadata = array_pop($this->cacheMetadata);
            $output = $this->contentCache->createCacheSegment($output, $evaluateContext['fusionPath'], $evaluateContext['cacheIdentifierValues'], $cacheTags, $cacheMetadata['lifetime']);
        } elseif ($this->enableContentCache && $evaluateContext['cacheForPathDisabled'] && $this->inCacheEntryPoint) {
            $contextArray = $this->runtime->getCurrentContext();
            if (isset($evaluateContext['configuration']['context'])) {
                $contextVariables = [];
                foreach ($evaluateContext['configuration']['context'] as $contextVariableName) {
                    if (isset($contextArray[$contextVariableName])) {
                        $contextVariables[$contextVariableName] = $contextArray[$contextVariableName];
                    } else {
                        $contextVariables[$contextVariableName] = null;
                    }
                }
            } else {
                $contextVariables = $contextArray;
            }
            $output = $this->contentCache->createUncachedSegment($output, $evaluateContext['fusionPath'], $contextVariables);
        }

        if ($evaluateContext['cacheForPathEnabled'] && $evaluateContext['currentPathIsEntryPoint']) {
            $output = $this->contentCache->processCacheSegments($output, $this->enableContentCache);
            $this->inCacheEntryPoint = null;
            $this->addCacheSegmentMarkersToPlaceholders = false;
        }

        return $output;
    }

    /**
     * Leave the evaluation of a path
     *
     * Has to be called in the same function calling enter() for every return path.
     *
     * @param array $evaluateContext The current evaluation context
     * @return void
     */
    public function leave(array $evaluateContext)
    {
        if ($evaluateContext['currentPathIsEntryPoint']) {
            $this->inCacheEntryPoint = null;
        }
    }

    /**
     * Evaluate a Fusion path with a given context without content caching
     *
     * This is used to render uncached segments "out of band" in getCachedSegment of ContentCache.
     *
     * @param string $path
     * @param array $contextArray
     * @return mixed
     *
     * TODO Find another way of disabling the cache (especially to allow cached content inside uncached content)
     */
    public function evaluateUncached($path, array $contextArray)
    {
        $previousEnableContentCache = $this->enableContentCache;
        $this->enableContentCache = false;
        $this->runtime->pushContextArray($contextArray);
        $result = $this->runtime->evaluate($path);
        $this->runtime->popContext();
        $this->enableContentCache = $previousEnableContentCache;
        return $result;
    }

    /**
     * Builds an array of additional key / values which must go into the calculation of the cache entry identifier for
     * a cached content segment.
     *
     * @param array $configuration
     * @param string $fusionPath
     * @param object $fusionObject The actual Fusion object
     * @return array
     */
    protected function buildCacheIdentifierValues(array $configuration, $fusionPath, $fusionObject)
    {
        $objectType = '<Neos.Fusion:GlobalCacheIdentifiers>';
        if (isset($configuration['entryIdentifier']['__objectType'])) {
            $objectType = '<' . $configuration['entryIdentifier']['__objectType'] . '>';
        }
        return $this->runtime->evaluate($fusionPath . '/__meta/cache/entryIdentifier' . $objectType, $fusionObject);
    }

    /**
     * Builds an array of string which must be used as tags for the cache entry identifier of a specific cached content segment.
     *
     * @param array $configuration
     * @param string $fusionPath
     * @param object $fusionObject The actual Fusion object
     * @return array
     */
    protected function buildCacheTags(array $configuration, $fusionPath, $fusionObject)
    {
        $cacheTags = [];
        if (isset($configuration['entryTags'])) {
            foreach ($configuration['entryTags'] as $tagKey => $tagValue) {
                $tagValue = $this->runtime->evaluate($fusionPath . '/__meta/cache/entryTags/' . $tagKey, $fusionObject);
                if (is_array($tagValue)) {
                    $cacheTags = array_merge($cacheTags, $tagValue);
                } elseif ((string)$tagValue !== '') {
                    $cacheTags[] = $tagValue;
                }
            }
            foreach ($this->flushTags() as $tagKey => $tagValue) {
                $cacheTags[] = $tagValue;
            }
        } else {
            $cacheTags = [ContentCache::TAG_EVERYTHING];
        }
        return array_unique($cacheTags);
    }

    /**
     * @param array $contextArray
     * @return array
     */
    public function unserializeContext(array $contextArray)
    {
        $unserializedContext = [];
        foreach ($contextArray as $variableName => $typeAndValue) {
            $value = $this->propertyMapper->convert($typeAndValue['value'], $typeAndValue['type']);
            $unserializedContext[$variableName] = $value;
        }

        return $unserializedContext;
    }

    /**
     * @param boolean $enableContentCache
     * @return void
     */
    public function setEnableContentCache($enableContentCache)
    {
        $this->enableContentCache = $enableContentCache;
    }

    /**
     * @return boolean
     */
    public function getEnableContentCache()
    {
        return $this->enableContentCache;
    }
}
