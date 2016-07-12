<?php
namespace TYPO3\Neos\TypoScript\Helper;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
