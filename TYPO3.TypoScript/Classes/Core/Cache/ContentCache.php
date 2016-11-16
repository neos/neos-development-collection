<?php
namespace TYPO3\TypoScript\Core\Cache;

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
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Security\Context;
use TYPO3\TypoScript\Exception;
use Doctrine\ORM\Proxy\Proxy;
use TYPO3\TypoScript\Exception\CacheException;

/**
 * A wrapper around a TYPO3 Flow cache which provides additional functionality for caching partial content (segments)
 * rendered by the TypoScript Runtime.
 *
 * The cache build process generally follows these steps:
 *
 *  - render the whole document as usual (for example a page) but insert special markers before and after the rendered segments
 *  - parse the rendered document and extract segments by the previously added markers
 *
 * This results in two artifacts:
 *
 *  - an array of content segments which are later stored as cache entries (if they may be cached)
 *  - a string called "output" which is the originally rendered output but without the markers
 *
 * We use non-visible ASCII characters as markers / tokens in order to minimize potential conflicts with the actual content.
 *
 * Note: If you choose a different cache backend for this content cache, make sure that it is one implementing
 *       TaggableBackendInterface.
 *
 * @Flow\Scope("singleton")
 */
class ContentCache
{
    const CACHE_SEGMENT_START_TOKEN = "\x02";
    const CACHE_SEGMENT_END_TOKEN = "\x03";
    const CACHE_SEGMENT_SEPARATOR_TOKEN = "\x1f";

    const CACHE_SEGMENT_MARKER = 'CONTENT_CACHE';

    const CACHE_PLACEHOLDER_REGEX = "/\x02CONTENT_CACHE(?P<identifier>[a-f0-9]+)\x03CONTENT_CACHE/";
    const EVAL_PLACEHOLDER_REGEX = "/\x02CONTENT_CACHE(?P<command>[^\x02\x1f\x03]+)\x1fCONTENT_CACHE(?P<data>[^\x02\x1f\x03]+)\x03CONTENT_CACHE/";

    const MAXIMUM_NESTING_LEVEL = 32;

    /**
     * A cache entry tag that will be used by default to flush an entry on "every" change - whatever that means to
     * the application.
     */
    const TAG_EVERYTHING = 'Everything';

    const SEGMENT_TYPE_CACHED = 'cached';
    const SEGMENT_TYPE_UNCACHED = 'uncached';
    const SEGMENT_TYPE_DYNAMICCACHED = 'dynamiccached';

    /**
     * @Flow\Inject
     * @var CacheSegmentParser
     */
    protected $parser;

    /**
     * @var StringFrontend
     * @Flow\Inject
     */
    protected $cache;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @var Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var string
     */
    protected $randomCacheMarker;

    /**
     * ContentCache constructor
     */
    public function __construct()
    {
        $this->randomCacheMarker = uniqid();
    }

    /**
     * Takes the given content and adds markers for later use as a cached content segment.
     *
     * This function will add a start and an end token to the beginning and end of the content and generate a cache
     * identifier based on the current TypoScript path and additional values which were defined in the TypoScript
     * configuration by the site integrator.
     *
     * The whole cache segment (START TOKEN + IDENTIFIER + SEPARATOR TOKEN + original content + END TOKEN) is returned
     * as a string.
     *
     * This method is called by the TypoScript Runtime while rendering a TypoScript object.
     *
     * @param string $content The (partial) content which should potentially be cached later on
     * @param string $typoScriptPath The TypoScript path that rendered the content, for example "page<TYPO3.Neos.NodeTypes:Page>/body<Acme.Demo:DefaultPageTemplate>/parts/breadcrumbMenu"
     * @param array $cacheIdentifierValues The values (simple type or implementing CacheAwareInterface) that should be used to create a cache identifier, will be sorted by keys for consistent ordering
     * @param array $tags Tags to add to the cache entry
     * @param integer $lifetime Lifetime of the cache segment in seconds. NULL for the default lifetime and 0 for unlimited lifetime.
     * @return string The original content, but with additional markers and a cache identifier added
     */
    public function createCacheSegment($content, $typoScriptPath, array $cacheIdentifierValues, array $tags = [], $lifetime = null)
    {
        $cacheIdentifier = $this->renderContentCacheEntryIdentifier($typoScriptPath, $cacheIdentifierValues);
        $metadata = implode(',', $tags);
        if ($lifetime !== null) {
            $metadata .= ';' . $lifetime;
        }
        return self::CACHE_SEGMENT_START_TOKEN . $this->randomCacheMarker . $cacheIdentifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $metadata . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $content . self::CACHE_SEGMENT_END_TOKEN . $this->randomCacheMarker;
    }

    /**
     * Similar to createCacheSegment() creates a content segment with markers added, but in contrast to that function
     * this method is used for rendering a segment which is not supposed to be cached.
     *
     * This method is called by the TypoScript Runtime while rendering a TypoScript object.
     *
     * @param string $content The content rendered by the TypoScript Runtime
     * @param string $typoScriptPath The TypoScript path that rendered the content, for example "page<TYPO3.Neos.NodeTypes:Page>/body<Acme.Demo:DefaultPageTemplate>/parts/breadcrumbMenu"
     * @param array $contextVariables TypoScript context variables which are needed to correctly render the specified TypoScript object
     * @return string The original content, but with additional markers added
     */
    public function createUncachedSegment($content, $typoScriptPath, array $contextVariables)
    {
        $serializedContext = $this->serializeContext($contextVariables);
        return self::CACHE_SEGMENT_START_TOKEN . $this->randomCacheMarker . 'eval=' . $typoScriptPath . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . json_encode(['context' => $serializedContext]) . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $content . self::CACHE_SEGMENT_END_TOKEN . $this->randomCacheMarker;
    }

    /**
     * Similar to createUncachedSegment() creates a content segment with markers added, but in contrast to that function
     * this method is used for rendering a segment which will be evaluated at runtime but can still be cached.
     *
     * This method is called by the TypoScript Runtime while rendering a TypoScript object.
     *
     * @param string $content The content rendered by the TypoScript Runtime
     * @param string $typoScriptPath The TypoScript path that rendered the content, for example "page<TYPO3.Neos.NodeTypes:Page>/body<Acme.Demo:DefaultPageTemplate>/parts/breadcrumbMenu"
     * @param array $contextVariables TypoScript context variables which are needed to correctly render the specified TypoScript object
     * @param array $cacheIdentifierValues
     * @param array $tags Tags to add to the cache entry
     * @param integer $lifetime Lifetime of the cache segment in seconds. NULL for the default lifetime and 0 for unlimited lifetime.
     * @param string $cacheDiscriminator The evaluated cache discriminator value
     * @return string The original content, but with additional markers added
     */
    public function createDynamicCachedSegment($content, $typoScriptPath, array $contextVariables, array $cacheIdentifierValues, array $tags = [], $lifetime = null, $cacheDiscriminator)
    {
        $metadata = implode(',', $tags);
        if ($lifetime !== null) {
            $metadata .= ';' . $lifetime;
        }
        $cacheDiscriminator = md5($cacheDiscriminator);
        $identifier = $this->renderContentCacheEntryIdentifier($typoScriptPath, $cacheIdentifierValues) . '_' . $cacheDiscriminator;
        $segmentData = [
            'path' => $typoScriptPath,
            'metadata' => $metadata,
            'context' => $this->serializeContext($contextVariables),
        ];

        return self::CACHE_SEGMENT_START_TOKEN . $this->randomCacheMarker . 'evalCached=' . $identifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . json_encode($segmentData) . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $content . self::CACHE_SEGMENT_END_TOKEN . $this->randomCacheMarker;
    }

    /**
     * Renders an identifier for a content cache entry
     *
     * @param string $typoScriptPath
     * @param array $cacheIdentifierValues
     * @return string An MD5 hash built from the typoScriptPath and certain elements of the given identifier values
     * @throws CacheException If an invalid entry identifier value is given
     */
    protected function renderContentCacheEntryIdentifier($typoScriptPath, array $cacheIdentifierValues)
    {
        ksort($cacheIdentifierValues);

        $identifierSource = '';
        foreach ($cacheIdentifierValues as $key => $value) {
            if ($value instanceof CacheAwareInterface) {
                $identifierSource .= $key . '=' . $value->getCacheEntryIdentifier() . '&';
            } elseif (is_string($value) || is_bool($value) || is_integer($value)) {
                $identifierSource .= $key . '=' . $value . '&';
            } elseif ($value !== null) {
                throw new CacheException(sprintf('Invalid cache entry identifier @cache.entryIdentifier.%s for path "%s". A entry identifier value must be a string or implement CacheAwareInterface.', $key, $typoScriptPath), 1395846615);
            }
        }
        $identifierSource .= 'securityContextHash=' . $this->securityContext->getContextHash();

        return md5($typoScriptPath . '@' . $identifierSource);
    }

    /**
     * Takes a string of content which includes cache segment markers, extracts the marked segments, writes those
     * segments which can be cached to the actual cache and returns the cleaned up original content without markers.
     *
     * This method is called by the TypoScript Runtime while rendering a TypoScript object.
     *
     * @param string $content The content with an outer cache segment
     * @param boolean $storeCacheEntries Whether to store extracted cache segments in the cache
     * @return string The (pure) content without cache segment markers
     */
    public function processCacheSegments($content, $storeCacheEntries = true)
    {
        $this->parser->extractRenderedSegments($content, $this->randomCacheMarker);

        if ($storeCacheEntries) {
            $segments = $this->parser->getCacheSegments();

            foreach ($segments as $segment) {
                $metadata = explode(';', $segment['metadata']);
                $tagsValue = $metadata[0] === '' ? [] : ($metadata[0] === '*' ? false : explode(',', $metadata[0]));
                    // FALSE means we do not need to store the cache entry again (because it was previously fetched)
                if ($tagsValue !== false) {
                    $lifetime = isset($metadata[1]) ? (integer)$metadata[1] : null;
                    $this->cache->set($segment['identifier'], $segment['content'], $this->sanitizeTags($tagsValue), $lifetime);
                }
            }
        }

        return $this->parser->getOutput();
    }

    /**
     * Tries to retrieve the specified content segment from the cache – further nested inline segments are retrieved
     * as well and segments which were not cacheable are rendered.
     *
     * @param \Closure $uncachedCommandCallback A callback to process commands in uncached segments
     * @param string $typoScriptPath TypoScript path identifying the TypoScript object to retrieve from the content cache
     * @param array $cacheIdentifierValues Further values which play into the cache identifier hash, must be the same as the ones specified while the cache entry was written
     * @param boolean $addCacheSegmentMarkersToPlaceholders If cache segment markers should be added – this makes sense if the cached segment is about to be included in a not-yet-cached segment
     * @return string|boolean The segment with replaced cache placeholders, or FALSE if a segment was missing in the cache
     * @throws Exception
     */
    public function getCachedSegment($uncachedCommandCallback, $typoScriptPath, $cacheIdentifierValues, $addCacheSegmentMarkersToPlaceholders = false)
    {
        $cacheIdentifier = $this->renderContentCacheEntryIdentifier($typoScriptPath, $cacheIdentifierValues);
        $content = $this->cache->get($cacheIdentifier);

        if ($content === false) {
            return false;
        }

        $i = 0;
        do {
            $replaced = $this->replaceCachePlaceholders($content, $addCacheSegmentMarkersToPlaceholders);
            if ($replaced === false) {
                return false;
            }
            if ($i > self::MAXIMUM_NESTING_LEVEL) {
                throw new Exception('Maximum cache segment level reached', 1391873620);
            }
            $i++;
        } while ($replaced > 0);

        $this->replaceUncachedPlaceholders($uncachedCommandCallback, $content);

        if ($addCacheSegmentMarkersToPlaceholders) {
            return self::CACHE_SEGMENT_START_TOKEN . $this->randomCacheMarker . $cacheIdentifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . '*' . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $content . self::CACHE_SEGMENT_END_TOKEN . $this->randomCacheMarker;
        } else {
            return $content;
        }
    }

    /**
     * Find cache placeholders in a cached segment and return the identifiers
     *
     * @param string $content
     * @param boolean $addCacheSegmentMarkersToPlaceholders
     * @return integer|boolean Number of replaced placeholders or FALSE if a placeholder couldn't be found
     */
    protected function replaceCachePlaceholders(&$content, $addCacheSegmentMarkersToPlaceholders)
    {
        $cache = $this->cache;
        $foundMissingIdentifier = false;
        $content = preg_replace_callback(self::CACHE_PLACEHOLDER_REGEX, function ($match) use ($cache, &$foundMissingIdentifier, $addCacheSegmentMarkersToPlaceholders) {
            $identifier = $match['identifier'];
            $entry = $cache->get($identifier);
            if ($entry !== false) {
                if ($addCacheSegmentMarkersToPlaceholders) {
                    return ContentCache::CACHE_SEGMENT_START_TOKEN . $this->randomCacheMarker . $identifier . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . '*' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . $this->randomCacheMarker . $entry . ContentCache::CACHE_SEGMENT_END_TOKEN . $this->randomCacheMarker;
                } else {
                    return $entry;
                }
            } else {
                $foundMissingIdentifier = true;
                return '';
            }
        }, $content, -1, $count);
        if ($foundMissingIdentifier) {
            return false;
        }
        return $count;
    }

    /**
     * Replace segments which are marked as not-cacheable by their actual content by invoking the TypoScript Runtime.
     *
     * @param \Closure $uncachedCommandCallback
     * @param string $content The content potentially containing not cacheable segments marked by the respective tokens
     * @return string The original content, but with uncached segments replaced by the actual content
     */
    protected function replaceUncachedPlaceholders(\Closure $uncachedCommandCallback, &$content)
    {
        $cache = $this->cache;
        $content = preg_replace_callback(self::EVAL_PLACEHOLDER_REGEX, function ($match) use ($uncachedCommandCallback, $cache) {
            $command = $match['command'];
            $additionalData = json_decode($match['data'], true);

            return $uncachedCommandCallback($command, $additionalData, $cache);
        }, $content);
    }

    /**
     * Generates an array of strings from the given array of context variables
     *
     * @param array $contextVariables
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function serializeContext(array $contextVariables)
    {
        $serializedContextArray = [];
        foreach ($contextVariables as $variableName => $contextValue) {
            // TODO This relies on a converter being available from the context value type to string
            if ($contextValue !== null) {
                $serializedContextArray[$variableName]['type'] = $this->getTypeForContextValue($contextValue);
                $serializedContextArray[$variableName]['value'] = $this->propertyMapper->convert($contextValue, 'string');
            }
        }

        return $serializedContextArray;
    }

    /**
     * TODO: Adapt to Flow change https://review.typo3.org/#/c/33138/
     *
     * @param mixed $contextValue
     * @return string
     */
    protected function getTypeForContextValue($contextValue)
    {
        if (is_object($contextValue)) {
            if ($contextValue instanceof Proxy) {
                $type = get_parent_class($contextValue);
            } else {
                $type = get_class($contextValue);
            }
        } else {
            $type = gettype($contextValue);
        }
        return $type;
    }

    /**
     * Flush content cache entries by tag
     *
     * @param string $tag A tag value that was assigned to a cache entry in TypoScript, for example "Everything", "Node_[…]", "NodeType_[…]", "DescendantOf_[…]" whereas "…" is the node identifier or node type respectively
     * @return integer The number of cache entries which actually have been flushed
     */
    public function flushByTag($tag)
    {
        return $this->cache->flushByTag($this->sanitizeTag($tag));
    }

    /**
     * Flush all content cache entries
     *
     * @return void
     */
    public function flush()
    {
        $this->cache->flush();
    }

    /**
     * Sanitizes the given tag for use with the cache framework
     *
     * @param string $tag A tag which possibly contains non-allowed characters, for example "NodeType_TYPO3.Neos.NodeTypes:Page"
     * @return string A cleaned up tag, for example "NodeType_TYPO3_Neos-Page"
     */
    protected function sanitizeTag($tag)
    {
        return strtr($tag, '.:', '_-');
    }

    /**
     * Sanitizes multiple tags with sanitizeTag()
     *
     * @param array $tags Multiple tags
     * @return array The sanitized tags
     */
    protected function sanitizeTags(array $tags)
    {
        foreach ($tags as $key => $value) {
            $tags[$key] = $this->sanitizeTag($value);
        }
        return $tags;
    }
}
