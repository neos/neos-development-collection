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

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Neos\Domain\Exception;

/**
 * Eel helper for accessing the Site object
 */
class SiteHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     *
     * @throws Exception
     */
    public function findBySiteNode(Node $siteNode): ?Site
    {
        if ($siteNode->nodeName === null) {
            return null;
        }
        $siteNodeName = SiteNodeName::fromNodeName($siteNode->nodeName);
        return $this->siteRepository->findOneByNodeName($siteNodeName);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
