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

namespace Neos\Neos\Fusion;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Psr\Log\LoggerInterface;

/**
 * Create a link to a node
 */
class NodeUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var NodeUriBuilderFactory
     */
    protected $nodeUriBuilderFactory;

    /**
     * A node object or a string node path or NULL to resolve the current document node
     */
    public function getNode(): Node|string|null
    {
        return $this->fusionValue('node');
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat(): string
    {
        return (string)$this->fusionValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string
     */
    public function getSection()
    {
        return (string)$this->fusionValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array<string,mixed>
     */
    public function getAdditionalParams(): array
    {
        return array_merge($this->fusionValue('additionalParams'), $this->fusionValue('arguments'));
    }

    /**
     * If true, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (bool)$this->fusionValue('absolute');
    }

    /**
     * The name of the base node inside the Fusion context to use for resolving relative paths.
     *
     * @return string
     */
    public function getBaseNodeName()
    {
        return $this->fusionValue('baseNodeName');
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function evaluate()
    {
        $baseNode = null;
        $baseNodeName = $this->getBaseNodeName() ?: 'documentNode';
        $currentContext = $this->runtime->getCurrentContext();
        if (isset($currentContext[$baseNodeName])) {
            $baseNode = $currentContext[$baseNodeName];
        } else {
            throw new \RuntimeException(sprintf('Could not find a node instance in Fusion context with name "%s" and no node instance was given to the node argument. Set a node instance in the Fusion context or pass a node object to resolve the URI.', $baseNodeName), 1373100400);
        }
        $node = $this->getNode();
        if (!$node instanceof Node) {
            throw new \RuntimeException(sprintf('Passing node as %s is not supported yet.', get_debug_type($node)));
        }
        /* TODO implement us see https://github.com/neos/neos-development-collection/issues/4524 {@see \Neos\Neos\ViewHelpers\Uri\NodeViewHelper::resolveNodeAddressFromString} for an example implementation
        elseif ($node === '~') {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $nodeAddress = $nodeAddress->withNodeAggregateId(
        $siteNode->nodeAggregateId
        );
        } elseif (is_string($node) && substr($node, 0, 7) === 'node://') {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $nodeAddress = $nodeAddress->withNodeAggregateId(
        NodeAggregateId::fromString(\mb_substr($node, 7))
        );*/

        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($possibleRequest);
        } else {
            // unfortunately, the uri-builder always needs a request at hand and cannot build uris without it
            // this will improve with a reformed uri building:
            // https://github.com/neos/flow-development-collection/issues/3354
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest(ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()));
        }

        $options = Options::create(forceAbsolute: $this->isAbsolute());
        $format = $this->getFormat() ?: $possibleRequest->getFormat();
        if ($format && $format !== 'html') {
            $options = $options->withCustomFormat($format);
        }
        if ($routingArguments = $this->getAdditionalParams()) {
            $options = $options->withCustomRoutingArguments($routingArguments);
        }

        try {
            $resolvedUri = $nodeUriBuilder->uriFor(NodeAddress::fromNode($node), $options);
        } catch (NoMatchingRouteException) {
            // todo log arguments?
            $this->systemLogger->warning(sprintf('Could not resolve "%s" to a node uri.', $node->aggregateId->value), LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }

        if ($this->getSection() !== '') {
            $resolvedUri = $resolvedUri->withFragment($this->getSection());
        }

        return (string)$resolvedUri;
    }
}
