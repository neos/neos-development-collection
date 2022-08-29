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

namespace Neos\Neos\FrontendRouting;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\SharedModel\NodeAddress;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Http\Message\UriInterface;

/**
 * Builds URIs to nodes, taking workspace (live / shared / user) into account.
 * This class can also be used in order to render "preview" URLs to nodes
 * that are not in the live workspace (in the Neos Backend and shared workspaces)
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
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        return new self($uriBuilder);
    }

    public static function fromUriBuilder(UriBuilder $uriBuilder): self
    {
        return new self($uriBuilder);
    }

    /**
     * Renders an URI for the given $nodeAddress
     * If the node belongs to the live workspace, the public URL is generated
     * Otherwise a preview URI is rendered (@see previewUriFor())
     *
     * Note: Shortcut nodes will are resolved in the RoutePartHandler thus the resulting URI will point
     * to the shortcut target (node, asset or external URI)
     *
     * @param NodeAddress $nodeAddress
     * @return UriInterface
     * @throws NoMatchingRouteException | MissingActionNameException | HttpException
     */
    public function uriFor(NodeAddress $nodeAddress): UriInterface
    {
        if (!$nodeAddress->isInLiveWorkspace()) {
            return $this->previewUriFor($nodeAddress);
        }
        return new Uri($this->uriBuilder->uriFor('show', ['node' => $nodeAddress], 'Frontend\Node', 'Neos.Neos'));
    }

    /**
     * Renders a stable "preview" URI for the given $nodeAddress
     * A preview URI is used to display a node that is not public yet (i.e. not in a live workspace).
     *
     * @param NodeAddress $nodeAddress
     * @return UriInterface
     * @throws NoMatchingRouteException | MissingActionNameException | HttpException
     */
    public function previewUriFor(NodeAddress $nodeAddress): UriInterface
    {
        return new Uri($this->uriBuilder->uriFor(
            'preview',
            ['node' => $nodeAddress->serializeForUri()],
            'Frontend\Node',
            'Neos.Neos'
        ));
    }
}
