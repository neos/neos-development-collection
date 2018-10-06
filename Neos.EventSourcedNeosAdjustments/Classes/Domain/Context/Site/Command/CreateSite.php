<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Site\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcedNeosAdjustments\Domain\ValueObject\NodeType;
use Neos\EventSourcedNeosAdjustments\Domain\ValueObject\PackageKey;
use Neos\EventSourcedNeosAdjustments\Domain\ValueObject\SiteActive;

final class CreateSite
{
    /**
     * @var NodeName
     */
    private $siteName;

    /**
     * @var PackageKey
     */
    private $siteResourcesPackageKey;

    /**
     * @var NodeType
     */
    private $nodeType;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var SiteActive
     */
    private $siteActive;

    /**
     * CreateSite constructor.
     * @param NodeName $siteName
     * @param PackageKey $packageKey
     * @param NodeType $nodeType
     * @param NodeName $nodeName
     * @param SiteActive $siteActive
     */
    public function __construct(
        NodeName $siteName,
        PackageKey $packageKey,
        NodeType $nodeType,
        NodeName $nodeName,
        SiteActive $siteActive
    ) {
        $this->siteName = $siteName;
        $this->siteResourcesPackageKey = $packageKey;
        $this->nodeType = $nodeType;
        $this->nodeName = $nodeName;
        $this->siteActive = $siteActive;
    }

    /**
     * @return NodeName
     */
    public function getSiteName(): NodeName
    {
        return $this->siteName;
    }

    /**
     * @return PackageKey
     */
    public function getSiteResourcesPackageKey(): PackageKey
    {
        return $this->siteResourcesPackageKey;
    }

    /**
     * @return NodeType
     */
    public function getNodeType(): NodeType
    {
        return $this->nodeType;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    /**
     * @return SiteActive
     */
    public function getSiteActive(): SiteActive
    {
        return $this->siteActive;
    }
}
