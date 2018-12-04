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

use Neos\Fusion\Exception;

/**
 * A parser which extracts cache segments by searching for start and end markers in the given content.
 */
class CacheSegmentParser
{
    /**
     * @var string
     */
    protected $randomCacheMarker = '';

    /**
     * @var int
     */
    protected $randomCacheMarkerLength;

    /**
     * @var string
     */
    protected $output = '';

    /**
     * @var string
     */
    protected $outerSegmentContent;

    /**
     * @var array
     */
    protected $cacheSegments = [];

    /**
     * @var integer
     */
    protected $uncachedPartCount = 0;

    /**
     * @var string
     */
    protected $content;

    /**
     * Parses the given content and extracts segments by searching for start end end markers. Those segments can later
     * be retrieved via getCacheSegments() and stored in a cache.
     *
     * This method also prepares a cleaned up output which can be retrieved later. See getOutput() for more information.
     *
     * @param string $content
     * @param string $randomCacheMarker
     * @throws Exception
     */
    public function __construct($content, $randomCacheMarker = '')
    {
        $this->randomCacheMarker = $randomCacheMarker;
        $this->randomCacheMarkerLength = strlen($randomCacheMarker);
        $this->content = $content;
        $this->outerSegmentContent = '';
        $currentPosition = 0;
        $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);
        while ($nextStartPosition !== false) {
            $part = $this->extractContent($currentPosition, $nextStartPosition);
            $this->output .= $part;
            $this->outerSegmentContent .= $part;
            $result = $this->parseSegment($nextStartPosition);
            $this->output .= $result['cleanContent'];
            $this->outerSegmentContent .= $result['embed'];
            $currentPosition = $this->calculateCurrentPosition($result['endPosition']);
            $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);
        }

        $nextEndPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_END_TOKEN);
        if ($nextEndPosition !== false) {
            throw new Exception(sprintf('Exceeding segment end token after position %d', $currentPosition), 1391853689);
        }

        $currentPosition = isset($result['endPosition']) ? $this->calculateCurrentPosition($result['endPosition']) : $currentPosition;
        $part = $this->extractContent($currentPosition);
        $this->output .= $part;
        $this->outerSegmentContent .= $part;
        // we no longer need the content
        unset($this->content);
    }

    /**
     * Parses a segment at current position diving down into nested segments.
     *
     * The returned segmentData array has the following keys:
     * - identifier -> The identifier of this entry for cached segments (or the eval expression for everything else)
     * - type -> The type of segment (one of the ContentCache::SEGMENT_TYPE_* constants)
     * - context -> eventual context information saved for a cached segment (optional)
     * - metadata -> cache entry metadata like tags
     * - content -> the content of this segment including embed code for sub segments
     * - cleanContent -> the raw content without any cache references for this segment and all sub segments
     * - embed -> the placeholder content for this segment to be used in "content" of parent segments
     *
     * @param integer $currentPosition
     * @return array
     * @throws Exception
     */
    protected function parseSegment($currentPosition)
    {
        $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);
        if ($nextStartPosition !== $currentPosition) {
            throw new Exception(sprintf('The current position (%d) is not the start of a segment, next start position %d', $currentPosition, $nextStartPosition), 1472464124);
        }

        $segmentData = [
            'identifier' => '',
            'type' => '',
            'context' => '',
            'metadata' => '',
            'content' => '',
            'cleanContent' => '',
            'embed' => ''
        ];

        $nextEndPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_END_TOKEN);
        $currentPosition = $this->calculateCurrentPosition($nextStartPosition);
        $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);

        $nextIdentifierSeparatorPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN);
        $nextSecondIdentifierSeparatorPosition = $this->calculateNextTokenPosition($nextIdentifierSeparatorPosition + 1, ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN);

        if ($nextIdentifierSeparatorPosition === false || $nextSecondIdentifierSeparatorPosition === false
            || $nextStartPosition !== false && $nextStartPosition < $nextSecondIdentifierSeparatorPosition
            || $nextEndPosition !== false && $nextEndPosition < $nextSecondIdentifierSeparatorPosition
        ) {
            throw new Exception(sprintf('Missing segment separator token after position %d', $currentPosition), 1391855139);
        }

        $identifier = $this->extractContent($currentPosition, $nextIdentifierSeparatorPosition);
        $contextOrMetadata = $this->extractContent($this->calculateCurrentPosition($nextIdentifierSeparatorPosition), $nextSecondIdentifierSeparatorPosition);

        $segmentData['identifier'] = $identifier;
        $segmentData['type'] = ContentCache::SEGMENT_TYPE_CACHED;
        $segmentData['metadata'] = $contextOrMetadata;
        $segmentData['context'] = $contextOrMetadata;

        if (strpos($identifier, 'eval=') === 0) {
            $segmentData['type'] = ContentCache::SEGMENT_TYPE_UNCACHED;
            unset($segmentData['metadata']);
            $this->uncachedPartCount++;
        }

        if (strpos($identifier, 'evalCached=') === 0) {
            $segmentData['type'] = ContentCache::SEGMENT_TYPE_DYNAMICCACHED;
            $segmentData['identifier'] = substr($identifier, 11);
            $additionalData = json_decode($contextOrMetadata, true);
            $segmentData['metadata'] = $additionalData['metadata'];
            $this->uncachedPartCount++;
        }

        $currentPosition = $this->calculateCurrentPosition($nextSecondIdentifierSeparatorPosition);
        $segmentData = $this->extractContentAndSubSegments($currentPosition, $segmentData);

        if ($segmentData['type'] === ContentCache::SEGMENT_TYPE_CACHED || $segmentData['type'] === ContentCache::SEGMENT_TYPE_DYNAMICCACHED) {
            $this->cacheSegments[$identifier] = $this->reduceSegmentDataToCacheRelevantInformation($segmentData);
        }

        return $segmentData;
    }

    /**
     * @param integer $currentPosition
     * @param array $segmentData
     * @return array
     */
    protected function extractContentAndSubSegments($currentPosition, array $segmentData)
    {
        $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);
        $nextEndPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_END_TOKEN);

        $segmentData['content'] = '';
        $segmentData['cleanContent'] = '';
        while ($nextStartPosition !== false && $nextStartPosition < $nextEndPosition) {
            $segmentContent = $this->extractContent($currentPosition, $nextStartPosition);
            $segmentData['content'] .= $segmentContent;
            $segmentData['cleanContent'] .= $segmentContent;

            $nextLevelData = $this->parseSegment($nextStartPosition);
            $segmentData['content'] .= $nextLevelData['embed'];
            $segmentData['cleanContent'] .= $nextLevelData['cleanContent'];

            $currentPosition = $this->calculateCurrentPosition($nextLevelData['endPosition']);
            $nextStartPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_START_TOKEN);
            $nextEndPosition = $this->calculateNextTokenPosition($currentPosition, ContentCache::CACHE_SEGMENT_END_TOKEN);
        }

        $remainingContent = $this->extractContent($currentPosition, $nextEndPosition);
        $segmentData['content'] .= $remainingContent;
        $segmentData['cleanContent'] .= $remainingContent;
        $segmentData['endPosition'] = $nextEndPosition;

        if ($segmentData['type'] === ContentCache::SEGMENT_TYPE_UNCACHED) {
            $segmentData['embed'] = ContentCache::CACHE_SEGMENT_START_TOKEN . ContentCache::CACHE_SEGMENT_MARKER . $segmentData['identifier'] . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ContentCache::CACHE_SEGMENT_MARKER . $segmentData['context'] . ContentCache::CACHE_SEGMENT_END_TOKEN . ContentCache::CACHE_SEGMENT_MARKER;
        } elseif ($segmentData['type'] === ContentCache::SEGMENT_TYPE_DYNAMICCACHED) {
            $segmentData['embed'] = ContentCache::CACHE_SEGMENT_START_TOKEN . ContentCache::CACHE_SEGMENT_MARKER . 'evalCached=' . $segmentData['identifier'] . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ContentCache::CACHE_SEGMENT_MARKER . $segmentData['context'] . ContentCache::CACHE_SEGMENT_END_TOKEN . ContentCache::CACHE_SEGMENT_MARKER;
        } else {
            $segmentData['embed'] = ContentCache::CACHE_SEGMENT_START_TOKEN . ContentCache::CACHE_SEGMENT_MARKER . $segmentData['identifier'] . ContentCache::CACHE_SEGMENT_END_TOKEN . ContentCache::CACHE_SEGMENT_MARKER;
        }

        return $segmentData;
    }

    /**
     * Make sure that we keep only necessary information for caching and strip all internal segment data.
     *
     * @param array $segmentData
     * @return array
     */
    protected function reduceSegmentDataToCacheRelevantInformation(array $segmentData)
    {
        return [
            'identifier' => $segmentData['identifier'],
            'type' => $segmentData['type'],
            'content' => $segmentData['content'],
            'metadata' => $segmentData['metadata']
        ];
    }

    /**
     * @param integer $fromPosition
     * @param integer $toPosition
     * @return string
     */
    protected function extractContent($fromPosition, $toPosition = null)
    {
        // substr behaves differently if the third parameter is not given or if it's null, so we need to take this detour
        if ($toPosition === null) {
            return substr($this->content, $fromPosition);
        }

        return substr($this->content, $fromPosition, ($toPosition - $fromPosition));
    }

    /**
     * Calculates a position assuming that the given position is a token followed by the random cache marker
     *
     * @param int $position
     * @return int
     */
    protected function calculateCurrentPosition($position)
    {
        return $position + 1 + $this->randomCacheMarkerLength;
    }

    /**
     * Find the next position of the given token (one of the ContentCache::CACHE_SEGMENT_*_TOKEN constants) in the parsed content.
     *
     * @param integer $currentPosition The position to start searching from
     * @param string $token the token to search for (will internally be appeneded by the randomCacheMarker)
     * @return integer|boolean Position of the token or false if the token was not found
     */
    protected function calculateNextTokenPosition($currentPosition, $token)
    {
        return strpos($this->content, $token . $this->randomCacheMarker, $currentPosition);
    }

    /**
     * Returns the fully intact content as originally given to extractRenderedSegments() but without the markers. This
     * content is suitable for being used as output for the user.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Returns an array with extracted content segments, including the type (if they can be cached or not) and tags to
     * be used for their entries when the segments are stored in a persistent cache.
     *
     * @return array
     */
    public function getCacheSegments()
    {
        return $this->cacheSegments;
    }

    /**
     * @return integer
     */
    public function getUncachedPartCount()
    {
        return $this->uncachedPartCount;
    }

    /**
     * @return string
     */
    public function getOuterSegmentContent()
    {
        return $this->outerSegmentContent;
    }
}
