<?php
namespace TYPO3\TypoScript\Core\Cache;

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
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\Exception;

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
class ContentCache {

	const CACHE_SEGMENT_START_TOKEN = "\x02";
	const CACHE_SEGMENT_END_TOKEN = "\x03";
	const CACHE_SEGMENT_SEPARATOR_TOKEN = "\x1f";

	const CACHE_PLACEHOLDER_REGEX = "/\x02(?P<identifier>[a-f0-9]+)\x03/";
	const EVAL_PLACEHOLDER_REGEX = "/\x02(?P<command>[^\x02\x1f\x03]+)\x1f(?P<context>[^\x02\x1f\x03]+)\x03/";

	const MAXIMUM_NESTING_LEVEL = 32;

	/**
	 * A cache entry tag that will be used by default to flush an entry on "every" change - whatever that means to
	 * the application.
	 */
	const TAG_EVERYTHING = 'Everything';

	const SEGMENT_TYPE_CACHED = 'cached';
	const SEGMENT_TYPE_UNCACHED = 'uncached';

	/**
	 * @Flow\Inject
	 * @var CacheSegmentParser
	 */
	protected $parser;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 * @Flow\Inject
	 */
	protected $cache;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

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
	 * @param string $typoScriptPath The TypoScript path that rendered the content, for example "page<TYPO3.Neos:Page>/body<Acme.Demo:DefaultPageTemplate>/parts/breadcrumbMenu"
	 * @param array $cacheIdentifierValues The values (simple type or implementing CacheAwareInterface) that should be used to create a cache identifier, will be sorted by keys for consistent ordering
	 * @param array $tags Tags to add to the cache entry
	 * @return string The original content, but with additional markers and a cache identifier added
	 */
	public function createCacheSegment($content, $typoScriptPath, $cacheIdentifierValues, array $tags = array()) {
		$cacheIdentifier = $this->renderContentCacheEntryIdentifier($typoScriptPath, $cacheIdentifierValues);
		return self::CACHE_SEGMENT_START_TOKEN . $cacheIdentifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . implode(',', $tags) . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $content . self::CACHE_SEGMENT_END_TOKEN;
	}

	/**
	 * Similar to createCacheSegment() creates a content segment with markers added, but in contrast to that function
	 * this method is used for rendering a segment which is not supposed to be cached.
	 *
	 * This method is called by the TypoScript Runtime while rendering a TypoScript object.
	 *
	 * @param string $content The content rendered by the TypoScript Runtime
	 * @param string $typoScriptPath The TypoScript path that rendered the content, for example "page<TYPO3.Neos:Page>/body<Acme.Demo:DefaultPageTemplate>/parts/breadcrumbMenu"
	 * @param array $contextVariables TypoScript context variables which are needed to correctly render the specified TypoScript object
	 * @return string The original content, but with additional markers added
	 */
	public function createUncachedSegment($content, $typoScriptPath, array $contextVariables) {
		$serializedContext = $this->serializeContext($contextVariables);
		return self::CACHE_SEGMENT_START_TOKEN . 'eval=' . $typoScriptPath . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $serializedContext . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $content . self::CACHE_SEGMENT_END_TOKEN;
	}

	/**
	 * Renders an identifier for a content cache entry
	 *
	 * @param string $typoScriptPath
	 * @param array $cacheIdentifierValues
	 * @return string An MD5 hash built from the typoScriptPath and certain elements of the given identifier values
	 */
	protected function renderContentCacheEntryIdentifier($typoScriptPath, array $cacheIdentifierValues) {
		ksort($cacheIdentifierValues);

		$identifierSource = '';
		foreach ($cacheIdentifierValues as $key => $value) {
			if ($value instanceof CacheAwareInterface) {
				$identifierSource .= $key . '=' . $value->getCacheEntryIdentifier() . '&';
			} elseif (is_string($value)) {
				$identifierSource .= $key . '=' . $value . '&';
			}
		}

		return md5($typoScriptPath . '@' . rtrim($identifierSource, '&'));
	}

	/**
	 * Takes a string of content which includes cache segment markers, extracts the marked segments, writes those
	 * segments which can be cached to the actual cache and returns the cleaned up original content without markers.
	 *
	 * This method is called by the TypoScript Runtime while rendering a TypoScript object.
	 *
	 * @param string $content The content with an outer cache segment
	 * @return string The (pure) content without cache segment markers
	 */
	public function processCacheSegments($content) {
		$this->parser->extractRenderedSegments($content);

		$segments = $this->parser->getCacheSegments();

		foreach ($segments as $segment) {
				// FALSE means we do not need to store the cache entry again (because it was previously fetched)
			if ($segment['tags'] !== FALSE) {
				$this->cache->set($segment['identifier'], $segment['content'], $this->sanitizeTags($segment['tags']));
			}
		}

		return $this->parser->getOutput();
	}

	/**
	 * Tries to retrieve the specified content segment from the cache – further nested inline segments are retrieved
	 * as well and segments which were not cacheable are rendered.
	 *
	 * @param \TYPO3\TypoScript\Core\Runtime $runtime The TypoScript Runtime which is currently used
	 * @param string $typoScriptPath TypoScript path identifying the TypoScript object to retrieve from the content cache
	 * @param array $cacheIdentifierValues Further values which play into the cache identifier hash, must be the same as the ones specified while the cache entry was written
	 * @param boolean $addCacheSegmentMarkersToPlaceholders If cache segment markers should be added – this makes sense if the cached segment is about to be included in a not-yet-cached segment
	 * @return string|boolean The segment with replaced cache placeholders, or FALSE if a segment was missing in the cache
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function getCachedSegment(Runtime $runtime, $typoScriptPath, $cacheIdentifierValues, $addCacheSegmentMarkersToPlaceholders = FALSE) {
		$cacheIdentifier = $this->renderContentCacheEntryIdentifier($typoScriptPath, $cacheIdentifierValues);
		$content = $this->cache->get($cacheIdentifier);

		if ($content === FALSE) {
			return FALSE;
		}

		$i = 0;
		do {
			$replaced = $this->replaceCachePlaceholders($content, $addCacheSegmentMarkersToPlaceholders);
			if ($replaced === FALSE) {
				return FALSE;
			}
			if ($i > self::MAXIMUM_NESTING_LEVEL) {
				throw new Exception('Maximum cache segment level reached', 1391873620);
			}
			$i++;
		} while ($replaced > 0);

		$this->replaceUncachedPlaceholders($runtime, $content);

		if ($addCacheSegmentMarkersToPlaceholders) {
			return self::CACHE_SEGMENT_START_TOKEN . $cacheIdentifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . '*' . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $content . self::CACHE_SEGMENT_END_TOKEN;
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
	public function replaceCachePlaceholders(&$content, $addCacheSegmentMarkersToPlaceholders) {
		$cache = $this->cache;
		$foundMissingIdentifier = FALSE;
		$content = preg_replace_callback(self::CACHE_PLACEHOLDER_REGEX, function($match) use ($cache, &$foundMissingIdentifier, $addCacheSegmentMarkersToPlaceholders) {
			$identifier = $match['identifier'];
			$entry = $cache->get($identifier);
			if ($entry !== FALSE) {
				if ($addCacheSegmentMarkersToPlaceholders) {
					return self::CACHE_SEGMENT_START_TOKEN . $identifier . self::CACHE_SEGMENT_SEPARATOR_TOKEN . '*' . self::CACHE_SEGMENT_SEPARATOR_TOKEN . $entry . self::CACHE_SEGMENT_END_TOKEN;
				} else {
					return $entry;
				}
			} else {
				$foundMissingIdentifier = TRUE;
				return '';
			}
		}, $content, -1, $count);
		if ($foundMissingIdentifier)  {
			return FALSE;
		}
		return $count;
	}

	/**
	 * Replace segments which are marked as not-cacheable by their actual content by invoking the TypoScript Runtime.
	 *
	 * @param Runtime $runtime The currently used TypoScript Runtime
	 * @param string $content The content potentially containing not cacheable segments marked by the respective tokens
	 * @return string The original content, but with uncached segments replaced by the actual content
	 */
	protected function replaceUncachedPlaceholders(Runtime $runtime, &$content) {
		$propertyMapper = $this->propertyMapper;
		$content = preg_replace_callback(self::EVAL_PLACEHOLDER_REGEX, function($match) use ($runtime, $propertyMapper) {
			$command = $match['command'];
			$contextString = $match['context'];

			$unserializedContext = array();
			$serializedContextArray = json_decode($contextString, TRUE);
			foreach ($serializedContextArray as $variableName => $typeAndValue) {
				$value = $propertyMapper->convert($typeAndValue['value'], $typeAndValue['type']);
				$unserializedContext[$variableName] = $value;
			}

			if (strpos($command, 'eval=') === 0) {
				$path = substr($command, 5);
				$result = $runtime->evaluateUncached($path, $unserializedContext);
				return $result;
			} else {
				throw new Exception(sprintf('Unknown uncached command "%s"', $command), 1392837596);
			}
		}, $content);
	}

	/**
	 * Generates a string from the given array of context variables
	 *
	 * @param array $contextVariables
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function serializeContext(array $contextVariables) {
		$serializedContextArray = array();
		foreach ($contextVariables as $variableName => $contextValue) {
			$type = is_object($contextValue) ? get_class($contextValue) : gettype($contextValue);
			$serializedContextArray[$variableName]['type'] = $type;
				// TODO This relies on a converter being available from the context value type to string
			$serializedContextArray[$variableName]['value'] = $this->propertyMapper->convert($contextValue, 'string');
		}
		$serializedContext = json_encode($serializedContextArray);
		return $serializedContext;
	}

	/**
	 * Flush content cache entries by tag
	 *
	 * @param string $tag A tag value that was assigned to a cache entry in TypoScript, for example "Everything", "Node_[…]", "NodeType_[…]", "DescendantOf_[…]" whereas "…" is the node identifier or node type respectively
	 * @return integer The number of cache entries which actually have been flushed
	 */
	public function flushByTag($tag) {
		return $this->cache->flushByTag($this->sanitizeTag($tag));
	}

	/**
	 * Flush all content cache entries
	 *
	 * @return void
	 */
	public function flush() {
		$this->cache->flush();
	}

	/**
	 * Sanitizes the given tag for use with the cache framework
	 *
	 * @param string $tag A tag which possibly contains non-allowed characters, for example "NodeType_TYPO3.Neos:Page"
	 * @return string A cleaned up tag, for example "NodeType_TYPO3_Neos-Page"
	 */
	protected function sanitizeTag($tag) {
		return strtr($tag, '.:', '_-');
	}

	/**
	 * Sanitizes multiple tags with sanitizeTag()
	 *
	 * @param array $tags Multiple tags
	 * @return array The sanitized tags
	 */
	protected function sanitizeTags(array $tags) {
		foreach ($tags as $key => $value) {
			$tags[$key] = $this->sanitizeTag($value);
		}
		return $tags;
	}
}
