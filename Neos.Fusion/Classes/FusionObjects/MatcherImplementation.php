<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Matcher object for use inside a "Case" statement
 */
class MatcherImplementation extends RendererImplementation
{
    /**
     * @return boolean
     */
    public function getCondition()
    {
        return (boolean)$this->fusionValue('condition');
    }

    /**
     * The type to render if condition is true
     *
     * @return string
     */
    public function getType()
    {
        return $this->fusionValue('type');
    }

    /**
     * A path to a Fusion configuration
     *
     * @return string
     */
    public function getRenderPath()
    {
        return $this->fusionValue('renderPath');
    }

    /**
     * If $condition matches, render $type and return it. Else, return MATCH_NORESULT.
     *
     * @return mixed
     */
    public function evaluate()
    {
        if ($this->getCondition()) {
            return parent::evaluate();
        } else {
            return CaseImplementation::MATCH_NORESULT;
        }
    }
}
