<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Neos\Service\LinkingService;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Eel helper for the linking service
 */
class LinkHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @param string|Uri $uri
     * @return boolean
     */
    public function hasSupportedScheme($uri)
    {
        return $this->linkingService->hasSupportedScheme($uri);
    }

    /**
     * @param string|Uri $uri
     * @return string
     */
    public function getScheme($uri)
    {
        return $this->linkingService->getScheme($uri);
    }

    /**
     * @param string|Uri $uri
     * @param NodeInterface $contextNode
     * @param ControllerContext $controllerContext
     * @return string
     */
    public function resolveNodeUri($uri, NodeInterface $contextNode, ControllerContext $controllerContext)
    {
        return $this->linkingService->resolveNodeUri($uri, $contextNode, $controllerContext);
    }

    /**
     * @param string|Uri $uri
     * @return string
     */
    public function resolveAssetUri($uri)
    {
        return $this->linkingService->resolveAssetUri($uri);
    }

    /**
     * @param string|Uri $uri
     * @param NodeInterface $contextNode
     * @return NodeInterface|AssetInterface|NULL
     */
    public function convertUriToObject($uri, NodeInterface $contextNode = null)
    {
        return $this->linkingService->convertUriToObject($uri, $contextNode);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
