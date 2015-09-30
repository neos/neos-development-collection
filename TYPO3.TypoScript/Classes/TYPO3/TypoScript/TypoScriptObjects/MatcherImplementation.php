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
 * Matcher object for use inside a "Case" statement
 */
class MatcherImplementation extends AbstractTypoScriptObject
{
    /**
     * @return boolean
     */
    public function getCondition()
    {
        return (boolean)$this->tsValue('condition');
    }

    /**
     * The type to render if condition is TRUE
     *
     * @return string
     */
    public function getType()
    {
        return $this->tsValue('type');
    }

    /**
     * A path to a TypoScript configuration
     *
     * @return string
     */
    public function getRenderPath()
    {
        return $this->tsValue('renderPath');
    }

    /**
     * If $condition matches, render $type and return it. Else, return MATCH_NORESULT.
     *
     * @return mixed
     */
    public function evaluate()
    {
        if ($this->getCondition()) {
            $rendererPath = sprintf('%s/renderer', $this->path);
            $canRenderWithRenderer = $this->tsRuntime->canRender($rendererPath);
            $renderPath = $this->getRenderPath();

            if ($canRenderWithRenderer) {
                $renderedElement = $this->tsRuntime->evaluate($rendererPath, $this);
            } elseif ($renderPath !== null) {
                if (substr($renderPath, 0, 1) === '/') {
                    $renderedElement = $this->tsRuntime->render(substr($renderPath, 1));
                } else {
                    $renderedElement = $this->tsRuntime->render($this->path . '/' . str_replace('.', '/', $renderPath));
                }
            } else {
                $renderedElement = $this->tsRuntime->render(
                    sprintf('%s/element<%s>', $this->path, $this->getType())
                );
            }
            return $renderedElement;
        } else {
            return CaseImplementation::MATCH_NORESULT;
        }
    }
}
