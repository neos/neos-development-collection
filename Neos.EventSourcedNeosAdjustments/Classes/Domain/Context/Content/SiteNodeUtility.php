<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Content;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class SiteNodeUtility
{
    public static function findSiteNode(NodeBasedReadModelInterface $node): NodeBasedReadModelInterface
    {
        $previousNode = null;
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:Sites')) {
                // the Site node is the one one level underneath the "Sites" node.
                return $previousNode;
            }
            $previousNode = $node;
        } while ($node = $node->findParentNode());

        // no Site node found at rootline
        throw new \RuntimeException('No site node found!');
    }
}
