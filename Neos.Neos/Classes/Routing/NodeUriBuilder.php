<?php
declare(strict_types=1);
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Psr\Http\Message\UriInterface;

/**
 * Builds URIs to nodes, taking workspace (live / shared / user) into account.
 * This class can also be used in order to render "preview" URLs to nodes that are not in the live workspace (in the Neos Backend and shared workspaces)
 *
 * @Flow\Proxy(false)
 */
final class NodeUriBuilder
{
    private UriBuilder $uriBuilder;

    protected function __construct(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    public static function fromRequest(ActionRequest $request): self
    {
        return new self(UriBuilder::fromRequest($request));
    }

    public static function fromUriBuilder(UriBuilder $uriBuilder): self
    {
        return new self($uriBuilder);
    }

    /**
     * Renders a URI for the given $node
     * If the node belongs to the live workspace, the public URL is generated
     * Otherwise a preview URI is rendered (see previewUriFor())
     *
     * Note: Shortcut nodes will are resolved in the RoutePartHandler thus the resulting URI will point to the shortcut target (node, asset or external URI)
     *
     * @param NodeInterface $node
     * @return UriInterface
     * @throws NoMatchingRouteException
     */
    public function uriFor(NodeInterface $node): UriInterface
    {
        try {
            if ($node->isHidden() || !$node->getContext()->getWorkspace()->isPublicWorkspace()) {
                return $this->previewUriFor($node);
            }
        } catch (IllegalObjectTypeException $e) {
            throw new \RuntimeException($e->getMessage(), 1645455280, $e);
        }
        try {
            return new Uri($this->uriBuilder->uriFor('show', ['node' => $node], 'Frontend\Node', 'Neos.Neos'));
        } catch (MissingActionNameException $e) {
            throw new \RuntimeException($e->getMessage(), 1645455180, $e);
        }
    }

    /**
     * Renders a stable "preview" URI for the given $node
     * A preview URI is used to display a node that is not public yet (i.e. not in a live workspace).
     *
     * @param NodeInterface $node
     * @return UriInterface
     * @throws NoMatchingRouteException
     */
    public function previewUriFor(NodeInterface $node): UriInterface
    {
        try {
            return new Uri($this->uriBuilder->uriFor('preview', ['node' => $node], 'Frontend\Node', 'Neos.Neos'));
        } catch (MissingActionNameException $e) {
            throw new \RuntimeException($e->getMessage(), 1645455200, $e);
        }
    }
}
