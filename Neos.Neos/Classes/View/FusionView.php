<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View;

use GuzzleHttp\Psr7\Message;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Security\Context;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * A Fusion view for Neos
 */
class FusionView extends AbstractView
{
    use FusionViewI18nTrait;

    /**
     * @Flow\Inject
     * @var SiteNodeUtility
     */
    protected $siteNodeUtility;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * Renders the view
     *
     * @return string|ResponseInterface The rendered view
     * @throws \Exception if no node is given
     * @api
     */
    public function render(): string|ResponseInterface
    {
        $currentNode = $this->getCurrentNode();

        $currentSiteNode = $this->siteNodeUtility->findSiteNode($currentNode);
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $fusionRuntime->pushContextArray([
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode,
            'editPreviewMode' => $this->variables['editPreviewMode'] ?? null
        ]);
        try {
            $output = $fusionRuntime->render($this->fusionPath);
            $output = $this->parsePotentialRawHttpResponse($output);
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious() ?: $exception;
        }
        $fusionRuntime->popContext();

        return $output;
    }

    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array<string,array<int,mixed>>
     */
    protected $supportedOptions = [
        'enableContentCache' => [
            null,
            'Flag to enable content caching inside Fusion (overriding the global setting).',
            'boolean'
        ]
    ];

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * The Fusion path to use for rendering the node given in "value", defaults to "page".
     *
     * @var string
     */
    protected $fusionPath = 'root';

    protected ?Runtime $fusionRuntime;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @param string $output
     * @return string|ResponseInterface If output is a string with a HTTP preamble a ResponseInterface
     *                                  otherwise the original output.
     */
    protected function parsePotentialRawHttpResponse($output)
    {
        if ($this->isRawHttpResponse($output)) {
            return Message::parseResponse($output);
        }

        return $output;
    }

    /**
     * Checks if the mixed input looks like a raw HTTTP response.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isRawHttpResponse($value): bool
    {
        if (is_string($value) && strpos($value, 'HTTP/') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Is it possible to render $node with $his->fusionPath?
     *
     * @return boolean true if $node can be rendered at fusionPath
     *
     * @throws Exception
     */
    public function canRenderWithNodeAndPath()
    {
        $currentSiteNode = $this->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        return $fusionRuntime->canRender($this->fusionPath);
    }

    /**
     * Set the Fusion path to use for rendering the node given in "value"
     *
     * @param string $fusionPath
     * @return void
     */
    public function setFusionPath($fusionPath)
    {
        $this->fusionPath = $fusionPath;
    }

    /**
     * @return string
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    protected function getClosestDocumentNode(Node $node): ?Node
    {
        while ($node !== null && !$node->nodeType->isOfType('Neos.Neos:Document')) {
            $node = $this->nodeAccessorManager->accessorFor(
                $node->subgraphIdentity
            )->findParentNode($node);
        }

        return $node;
    }

    /**
     * @return Node
     * @throws Exception
     */
    protected function getCurrentSiteNode(): Node
    {
        $currentNode = $this->variables['site'] ?? null;
        if (!$currentNode instanceof Node) {
            throw new Exception('FusionView needs a variable \'site\' set with a Node object.', 1538996432);
        }
        return $currentNode;
    }

    /**
     * @return Node
     * @throws Exception
     */
    protected function getCurrentNode(): Node
    {
        $currentNode = $this->variables['value'] ?? null;
        if (!$currentNode instanceof Node) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1329736456);
        }
        return $currentNode;
    }

    /**
     * @param Node $currentSiteNode
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function getFusionRuntime(Node $currentSiteNode)
    {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $this->controllerContext);

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }
        return $this->fusionRuntime;
    }

    /**
     * Clear the cached runtime instance on assignment of variables
     *
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value): AbstractView
    {
        $this->fusionRuntime = null;
        return parent::assign($key, $value);
    }
}
