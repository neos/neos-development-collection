<?php
namespace TYPO3\Neos\Domain\Model\Dto;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Dto\UsageReference;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
    public function __construct(AssetInterface $asset, Site $site, NodeInterface $documentNode = null, NodeInterface $node, $accessible)
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
