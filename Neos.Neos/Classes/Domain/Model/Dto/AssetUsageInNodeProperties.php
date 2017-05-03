<?php
namespace Neos\Neos\Domain\Model\Dto;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Neos\Domain\Model\Site;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A DTO for storing information related to a usage of an asset in node properties.
 */
class AssetUsageInNodeProperties extends UsageReference
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var NodeInterface
     */
    protected $documentNode;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var boolean
     */
    protected $accessible;

    /**
     * @param AssetInterface $asset
     * @param Site $site
     * @param NodeInterface $documentNode
     * @param NodeInterface $node
     * @param boolean $accessible
     */
    public function __construct(AssetInterface $asset, Site $site = null, NodeInterface $documentNode = null, NodeInterface $node = null, $accessible = false)
    {
        parent::__construct($asset);
        $this->site = $site;
        $this->documentNode = $documentNode;
        $this->node = $node;
        $this->accessible = $accessible;
    }

    /**
     * Returns the Site object of the site where the asset is in use.
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Returns the parent document node of the node where the asset is used.
     *
     * @return NodeInterface
     */
    public function getDocumentNode()
    {
        return $this->documentNode;
    }

    /**
     * Returns the node where the asset is in use.
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Returns true if the node is accessible by the current user.
     *
     * @return boolean
     */
    public function isAccessible()
    {
        return $this->accessible;
    }
}
