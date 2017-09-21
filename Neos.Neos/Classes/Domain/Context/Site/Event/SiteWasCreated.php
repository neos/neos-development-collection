<?php
namespace Neos\Neos\Domain\Context\Site\Event;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventInterface;
use Neos\Neos\Domain\ValueObject\NodeName;
use Neos\Neos\Domain\ValueObject\NodeType;
use Neos\Neos\Domain\ValueObject\PackageKey;
use Neos\Neos\Domain\ValueObject\SiteActive;

final class SiteWasCreated implements EventInterface
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
     * @param PackageKey $siteResourcesPackageKey
     * @param NodeType $nodeType
     * @param NodeName $nodeName
     * @param SiteActive $siteActive
     */
    public function __construct(
        NodeName $siteName,
        PackageKey $siteResourcesPackageKey,
        NodeType $nodeType,
        NodeName $nodeName,
        SiteActive $siteActive
    ) {
        $this->siteName = $siteName;
        $this->siteResourcesPackageKey = $siteResourcesPackageKey;
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
