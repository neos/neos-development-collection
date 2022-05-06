<?php
declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class SiteNodeUtility
{
    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function findSiteNode(NodeInterface $node): NodeInterface
    {
        $previousNode = null;
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                if (is_null($previousNode)) {
                    break;
                }
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $nodeAccessor->findParentNode($node));

        // no Site node found at rootline
        throw new \RuntimeException('No site node found!');
    }
}
