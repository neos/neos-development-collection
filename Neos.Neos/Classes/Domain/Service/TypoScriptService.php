<?php
namespace Neos\Neos\Domain\Service;

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
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Files;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;

/**
 * The TypoScript Service. It is replaced by the fusion service and therefore deprecated with 3.0.
 * It will be removed with 4.0.
 *
 * @Flow\Scope("prototype")
 * @api
 * @deprecated
 */
class TypoScriptService extends FusionService
{

    /**
     * Create a runtime for the given site node
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $currentSiteNode
     * @param ControllerContext $controllerContext
     * @return Runtime
     */
    public function createRuntime(NodeInterface $currentSiteNode, ControllerContext $controllerContext)
    {
        return parent::createRuntime($currentSiteNode, $controllerContext);
    }

    /**
     * Returns a merged TypoScript object tree in the context of the given nodes
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $startNode Node marking the starting point
     * @return array The merged object tree as of the given node
     * @throws \Neos\Neos\Domain\Exception
     */
    public function getMergedTypoScriptObjectTree(NodeInterface $startNode)
    {
        return parent::getMergedFusionObjectTree($startNode);
    }

    /**
     * Set the pattern for including the site root TypoScript
     *
     * @param string $siteRootTypoScriptPattern A string for the sprintf format that takes the site package key as a single placeholder
     * @return void
     */
    public function setSiteRootTypoScriptPattern($siteRootTypoScriptPattern)
    {
        parent::setSiteRootFusionPattern($siteRootTypoScriptPattern);
    }

    /**
     * Get the TypoScript resources that are included before the site TypoScript.
     *
     * @return array
     */
    public function getPrependTypoScriptIncludes()
    {
        return parent::getPrependFusionIncludes();
    }

    /**
     * Set TypoScript resources that should be prepended before the site TypoScript,
     * it defaults to the Neos Root.fusion TypoScript.
     *
     * @param array $prependTypoScriptIncludes
     * @return void
     */
    public function setPrependTypoScriptIncludes(array $prependTypoScriptIncludes)
    {
        parent::setPrependFusionIncludes($prependTypoScriptIncludes);
    }

    /**
     * Get TypoScript resources that will be appended after the site TypoScript.
     *
     * @return array
     */
    public function getAppendTypoScriptIncludes()
    {
        return parent::getAppendFusionIncludes();
    }

    /**
     * Set TypoScript resources that should be appended after the site TypoScript,
     * this defaults to an empty array.
     *
     * @param array $appendTypoScriptIncludes An array of TypoScript resource URIs
     * @return void
     */
    public function setAppendTypoScriptIncludes(array $appendTypoScriptIncludes)
    {
        parent::setAppendFusionIncludes($appendTypoScriptIncludes);
    }
}
