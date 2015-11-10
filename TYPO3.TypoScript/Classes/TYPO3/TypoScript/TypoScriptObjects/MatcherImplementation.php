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
