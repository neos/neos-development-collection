<?php
namespace Neos\Neos\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A TypoScript view for Neos
 * @deprecated
 */
class TypoScriptView extends FusionView
{

    /**
     * Set the TypoScript path to use for rendering the node given in "value"
     *
     * @param string $typoScriptPath
     * @return void
     */
    public function setTypoScriptPath($typoScriptPath)
    {
        parent::setFusionPath($typoScriptPath);
    }

    /**
     * @return string
     */
    public function getTypoScriptPath()
    {
        return parent::getFusionPath();
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function getTypoScriptRuntime(NodeInterface $currentSiteNode)
    {
        return parent::getFusionRuntime($currentSiteNode);
    }
}
