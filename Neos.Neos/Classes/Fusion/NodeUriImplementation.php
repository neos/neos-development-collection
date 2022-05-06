<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Neos\Domain\Service\NodeSiteResolvingService;
use Neos\Neos\EventSourcedRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Create a link to a node
 */
class NodeUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var NodeSiteResolvingService
     */
    protected $nodeSiteResolvingService;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\SharedModel\NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * A node object or a string node path or NULL to resolve the current document node
     */
    public function getNode(): NodeInterface|string|null
    {
        return $this->fusionValue('node');
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->fusionValue('format');
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
     * Arguments to be removed from the URI. Only active if addQueryString = TRUE
     *
     * @return array<int,string>
     */
    public function getArgumentsToBeExcludedFromQueryString(): array
    {
        return $this->fusionValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If TRUE, the current query parameters will be kept in the URI
     *
     * @return boolean
     */
    public function getAddQueryString()
    {
        return (boolean)$this->fusionValue('addQueryString');
    }

    /**
     * If TRUE, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (boolean)$this->fusionValue('absolute');
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
     * @return ContentSubgraphInterface
     */
    public function getSubgraph(): ?ContentSubgraphInterface
    {
        return $this->fusionValue('subgraph');
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function evaluate()
    {
        $node = $this->getNode();
        if ($node instanceof NodeInterface) {
            $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        } else {
            return '';
        }
        /** @todo implement us
        elseif ($node === '~') {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $nodeAddress = $nodeAddress->withNodeAggregateIdentifier(
        $this->nodeSiteResolvingService->findSiteNodeForNodeAddress($nodeAddress)->getNodeAggregateIdentifier()
        );
        } elseif (is_string($node) && substr($node, 0, 7) === 'node://') {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $nodeAddress = $nodeAddress->withNodeAggregateIdentifier(
        NodeAggregateIdentifier::fromString(\mb_substr($node, 7))
        );*/
        if ($this->getSubgraph()) {
            $nodeAddress = $nodeAddress->withDimensionSpacePoint($this->getSubgraph()->getDimensionSpacePoint());
        }

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->runtime->getControllerContext()->getRequest());
        $uriBuilder
            ->setAddQueryString($this->getAddQueryString())
            ->setArguments($this->getAdditionalParams())
            ->setArgumentsToBeExcludedFromQueryString($this->getArgumentsToBeExcludedFromQueryString())
            ->setCreateAbsoluteUri($this->isAbsolute())
            ->setFormat($this->getFormat())
            ->setSection($this->getSection());

        return (string)NodeUriBuilder::fromUriBuilder($uriBuilder)->uriFor($nodeAddress);
    }
}
