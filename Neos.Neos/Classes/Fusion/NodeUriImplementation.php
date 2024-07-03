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
use Neos\Neos\Utility\LegacyNodePathNormalizer;
use Neos\Neos\Utility\NodeAddressNormalizer;
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
     * @Flow\Inject
     * @var NodeAddressNormalizer
     */
    protected $nodeAddressNormalizer;

    /**
     * @Flow\Inject
     * @var LegacyNodePathNormalizer
     */
    protected $legacyNodePathNormalizer;

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
    public function getBaseNodeName(): string
    {
        return $this->fusionValue('baseNodeName') ?: 'documentNode';
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     */
    public function evaluate()
    {
        $node = $this->fusionValue('node');
        if (is_string($node)) {
            $currentContext = $this->runtime->getCurrentContext();
            $baseNode = $currentContext[$this->getBaseNodeName()] ?? null;
            if (!$baseNode instanceof Node) {
                throw new \RuntimeException(sprintf(
                    'If "node" is passed as string a base node in must be set in "%s". Given: %s',
                    $this->getBaseNodeName(),
                    get_debug_type($baseNode)
                ), 1719996392);
            }

            $possibleAbsoluteNodePath = $this->legacyNodePathNormalizer->tryResolveLegacyPathSyntaxToAbsoluteNodePath($node, $baseNode);
            $nodeAddress = $this->nodeAddressNormalizer->resolveNodeAddressFromPath(
                $possibleAbsoluteNodePath ?? $node,
                $baseNode
            );
        } elseif ($node instanceof Node) {
            $nodeAddress = NodeAddress::fromNode($node);
        } else {
            throw new \RuntimeException(sprintf(
                'The "node" argument can only be a string or an instance of `Node`. Given: %s',
                get_debug_type($node)
            ), 1719996456);
        }

        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($possibleRequest);
        } else {
            // unfortunately, the uri-builder always needs a request at hand and cannot build uris without it
            // this will improve with a reformed uri building:
            // https://github.com/neos/flow-development-collection/issues/3354
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest(ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()));
        }

        $options = $this->isAbsolute() ? Options::createForceAbsolute() : Options::createEmpty();
        $format = $this->getFormat() ?: $possibleRequest->getFormat();
        if ($format && $format !== 'html') {
            $options = $options->withCustomFormat($format);
        }
        if ($routingArguments = $this->getAdditionalParams()) {
            $options = $options->withCustomRoutingArguments($routingArguments);
        }

        try {
            $resolvedUri = $nodeUriBuilder->uriFor($nodeAddress, $options);
        } catch (NoMatchingRouteException) {
            // todo log arguments?
            $this->systemLogger->warning(sprintf('Could not resolve "%s" to a node uri.', $nodeAddress->aggregateId->value), LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }

        if ($this->getSection() !== '') {
            $resolvedUri = $resolvedUri->withFragment($this->getSection());
        }

        return (string)$resolvedUri;
    }
}
