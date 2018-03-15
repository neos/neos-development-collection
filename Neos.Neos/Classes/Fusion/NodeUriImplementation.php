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

use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Domain\Context\Content\NodeAddress;
use Neos\Neos\Domain\Context\Content\NodeAddressService;

/**
 * Create a link to a node
 */
class NodeUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var NodeAddressService
     */
    protected $nodeAddressService;

    /**
     * A node object or a string node path or NULL to resolve the current document node
     *
     * @return mixed
     */
    public function getNode()
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
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->fusionValue('additionalParams');
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = TRUE
     *
     * @return array
     */
    public function getArgumentsToBeExcludedFromQueryString()
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
     * @return ContentSubgraphInterface|null
     */
    public function getSubgraph(): ?ContentSubgraphInterface
    {
        return $this->fusionValue('subgraph') ?: $this->runtime->getCurrentContext()['subgraph'];
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
        $nodeAddress = NodeAddress::fromNode($node);
        if ($node instanceof NodeInterface) {
            $nodeAddress = $nodeAddress->withNodeAggregateIdentifier($node->getNodeAggregateIdentifier());
        } elseif ($node === '~') {
            $nodeAddress = $nodeAddress->withNodeAggregateIdentifier($this->nodeAddressService->findSiteNodeForNodeAddress($nodeAddress)->getNodeAggregateIdentifier());
        } elseif (is_string($node) && substr($node, 0, 7) === 'node://') {
            $nodeAddress = $nodeAddress->withNodeAggregateIdentifier(new NodeAggregateIdentifier(\mb_substr($node, 7)));
        } else {
            return '';
        }
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

        return $uriBuilder->uriFor(
            'show',
            [
                'node' => $nodeAddress
            ],
            'Frontend\\Node',
            'Neos.Neos'
        );
    }
}
