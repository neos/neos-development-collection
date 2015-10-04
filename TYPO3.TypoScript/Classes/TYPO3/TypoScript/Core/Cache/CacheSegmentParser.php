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

use TYPO3\TypoScript\Exception;

/**
 * A parser which extracts cache segments by searching for start and end markers in the given content.
 */
class CacheSegmentParser
{
    /**
     * @var string
     */
    protected $output;

    /**
     * @var array
     */
    protected $cacheEntries;

    /**
     * Parses the given content and extracts segments by searching for start end end markers. Those segments can later
     * be retrieved via getCacheSegments() and stored in a cache.
     *
     * This method also prepares a cleaned up output which can be retrieved later. See getOutput() for more information.
     *
     * @param string $content The content to process, ie. the rendered content with some segment markers already in place
     * @return string The outer content with placeholders instead of the actual content segments
     * @throws \TYPO3\TypoScript\Exception
     */
    public function extractRenderedSegments($content)
    {
        $this->output = '';
        $this->cacheEntries = array();
        $parts = array(array('content' => ''));

        $currentPosition = 0;
        $level = 0;
        $nextStartPosition = strpos($content, ContentCache::CACHE_SEGMENT_START_TOKEN, $currentPosition);
        $nextEndPosition = strpos($content, ContentCache::CACHE_SEGMENT_END_TOKEN, $currentPosition);

        while (true) {

            // Nothing else to do, all segments are parsed
            if ($nextStartPosition === false && $nextEndPosition === false) {
                $part = substr($content, $currentPosition);
                $parts[0]['content'] .= $part;
                $this->output .= $part;
                break;
            }

            // A cache segment is started and no end token can be found
            if ($nextStartPosition !== false && $nextEndPosition === false) {
                throw new Exception(sprintf('No cache segment end token can be found after position %d', $currentPosition), 1391853500);
            }

            if ($level === 0 && $nextEndPosition !== false && ($nextStartPosition === false || $nextEndPosition < $nextStartPosition)) {
                throw new Exception(sprintf('Exceeding segment end token after position %d', $currentPosition), 1391853689);
            }

            // Either no other segment start was found or we encountered an segment end before the next start
            if ($nextStartPosition === false || $nextEndPosition < $nextStartPosition) {

                // Add everything until end to current level
                $part = substr($content, $currentPosition, $nextEndPosition - $currentPosition);
                $parts[$level]['content'] .= $part;
                $currentLevelPart = &$parts[$level];
                $identifier = $currentLevelPart['identifier'];
                $this->output .= $part;

                if ($currentLevelPart['type'] === ContentCache::SEGMENT_TYPE_CACHED || $currentLevelPart['type'] === ContentCache::SEGMENT_TYPE_SEMICACHED) {
                    $this->cacheEntries[$identifier] = $parts[$level];
                }

                // The end marker ends the current level
                unset($parts[$level]);
                $level--;

                if ($currentLevelPart['type'] === ContentCache::SEGMENT_TYPE_UNCACHED) {
                    $parts[$level]['content'] .= ContentCache::CACHE_SEGMENT_START_TOKEN . $identifier . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . $currentLevelPart['context'] . ContentCache::CACHE_SEGMENT_END_TOKEN;
                } elseif ($currentLevelPart['type'] === ContentCache::SEGMENT_TYPE_SEMICACHED) {
                    $parts[$level]['content'] .= ContentCache::CACHE_SEGMENT_START_TOKEN . 'evalCached=' . $identifier . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . $currentLevelPart['context'] . ContentCache::CACHE_SEGMENT_END_TOKEN;
                } else {
                    $parts[$level]['content'] .= ContentCache::CACHE_SEGMENT_START_TOKEN . $identifier . ContentCache::CACHE_SEGMENT_END_TOKEN;
                }

                $currentPosition = $nextEndPosition + 1;

                $nextEndPosition = strpos($content, ContentCache::CACHE_SEGMENT_END_TOKEN, $currentPosition);
            } else {

                // Push everything until now to the current stack value
                $part = substr($content, $currentPosition, $nextStartPosition - $currentPosition);
                $parts[$level]['content'] .= $part;
                $this->output .= $part;

                // Found opening marker, increase level
                $level++;
                $parts[$level] = array('content' => '');

                $currentPosition = $nextStartPosition + 1;

                $nextStartPosition = strpos($content, ContentCache::CACHE_SEGMENT_START_TOKEN, $currentPosition);

                $nextIdentifierSeparatorPosition = strpos($content, ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN, $currentPosition);
                $nextSecondIdentifierSeparatorPosition = strpos($content, ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN, $nextIdentifierSeparatorPosition + 1);

                if ($nextIdentifierSeparatorPosition === false || $nextSecondIdentifierSeparatorPosition === false
                    || $nextStartPosition !== false && $nextStartPosition < $nextIdentifierSeparatorPosition
                    || $nextEndPosition !== false && $nextEndPosition < $nextIdentifierSeparatorPosition
                    || $nextStartPosition !== false && $nextStartPosition < $nextSecondIdentifierSeparatorPosition
                    || $nextEndPosition !== false && $nextEndPosition < $nextSecondIdentifierSeparatorPosition) {
                    throw new Exception(sprintf('Missing segment separator token after position %d', $currentPosition), 1391855139);
                }

                $identifier = substr($content, $currentPosition, $nextIdentifierSeparatorPosition - $currentPosition);
                $contextOrMetadata = substr($content, $nextIdentifierSeparatorPosition + 1, $nextSecondIdentifierSeparatorPosition - $nextIdentifierSeparatorPosition - 1);

                $parts[$level]['identifier'] = $identifier;
                if (strpos($identifier, 'eval=') === 0) {
                    $parts[$level]['type'] = ContentCache::SEGMENT_TYPE_UNCACHED;
                    $parts[$level]['context'] = $contextOrMetadata;
                } elseif (strpos($identifier, 'evalCached=') === 0) {
                    $parts[$level]['type'] = ContentCache::SEGMENT_TYPE_SEMICACHED;
                    $parts[$level]['identifier'] = substr($identifier, 11);
                    $additionalData = json_decode($contextOrMetadata, true);
                    $parts[$level]['context'] = $contextOrMetadata;
                    $parts[$level]['metadata'] = $additionalData['metadata'];
                } else {
                    $parts[$level]['type'] = ContentCache::SEGMENT_TYPE_CACHED;
                    $parts[$level]['metadata'] = $contextOrMetadata;
                }

                $currentPosition = $nextSecondIdentifierSeparatorPosition + 1;

                $nextStartPosition = strpos($content, ContentCache::CACHE_SEGMENT_START_TOKEN, $currentPosition);
            }
        };

        return $parts[0]['content'];
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
        return $this->cacheEntries;
    }
}
