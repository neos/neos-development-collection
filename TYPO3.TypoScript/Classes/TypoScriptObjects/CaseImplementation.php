<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

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
use TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException;

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
     * @throws UnsupportedObjectTypeAtPathException
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
            throw new UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no simple values.', 1372668062);
        } elseif (isset($this->properties[$matcherKey]['__eelExpression'])) {
            throw new UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no Eel expressions.', 1372668077);
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
