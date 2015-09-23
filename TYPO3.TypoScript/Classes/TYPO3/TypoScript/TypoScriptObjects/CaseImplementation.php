<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Case TypoScript Object
 *
 * The "case" TypoScript object renders its children in order. The first
 * result which is not MATCH_NORESULT is returned.
 *
 * Often, this TypoScript object is used together with the "Matcher" TypoScript
 * object; and all its children are by-default interpreted as "Matcher" TypoScript
 * objects if no others are specified.
 */
class CaseImplementation extends ArrayImplementation
{
    /**
     * This constant should be returned by individual matchers if the matcher
     * did not match.
     *
     * You should not rely on the contents or type of this constant.
     */
    const MATCH_NORESULT = '_____________NO_MATCH_RESULT_____________';

    /**
     * Execute each matcher until the first one matches
     *
     * @return mixed
     */
    public function evaluate()
    {
        $matcherKeys = $this->sortNestedTypoScriptKeys();

        foreach ($matcherKeys as $matcherName) {
            $renderedMatcher = $this->renderMatcher($matcherName);
            if ($this->matcherMatched($renderedMatcher)) {
                return $renderedMatcher;
            }
        }

        return null;
    }

    /**
     * Render the given matcher
     *
     * A result value of MATCH_NORESULT means that the condition of the matcher did not match and the case should
     * continue.
     *
     * @param string $matcherKey
     * @return string
     * @throws \TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException
     */
    protected function renderMatcher($matcherKey)
    {
        $renderedMatcher = null;

        if (isset($this->properties[$matcherKey]['__objectType'])) {
            // object type already set, so no need to set it
            $renderedMatcher = $this->tsRuntime->render(
                sprintf('%s/%s', $this->path, $matcherKey)
            );
            return $renderedMatcher;
        } elseif (!is_array($this->properties[$matcherKey])) {
            throw new \TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no simple values.', 1372668062);
        } elseif (isset($this->properties[$matcherKey]['__eelExpression'])) {
            throw new \TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no Eel expressions.', 1372668077);
        } else {
            // No object type has been set, so we're using TYPO3.TypoScript:Matcher as fallback
            $renderedMatcher = $this->tsRuntime->render(
                sprintf('%s/%s<TYPO3.TypoScript:Matcher>', $this->path, $matcherKey)
            );
            return $renderedMatcher;
        }
    }

    /**
     * Test whether the output of the matcher does not equal the MATCH_NORESULT
     *
     * If the debug mode is enabled, we have to strip the debug output before comparing the rendered result.
     *
     * @param string $renderedMatcher
     * @return boolean
     */
    protected function matcherMatched($renderedMatcher)
    {
        if ($this->tsRuntime->isDebugMode()) {
            $renderedMatcher = preg_replace('/\s*<!--.*?-->\s*/', '', $renderedMatcher);
        }
        return $renderedMatcher !== self::MATCH_NORESULT;
    }
}
