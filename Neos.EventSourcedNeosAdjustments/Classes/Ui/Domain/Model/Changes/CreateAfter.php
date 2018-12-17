<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\RelationDistributionStrategy;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

class CreateAfter extends AbstractCreate
{
    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    public function getMode()
    {
        return 'after';
    }

    /**
     * Check if the new node's node type is allowed in the requested position
     *
     * @return boolean
     */
    public function canApply()
    {
        $parent = $this->getSubject()->findParentNode();
        $nodeType = $this->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($parent, $nodeType);
    }

    /**
     * Create a new node after the subject
     *
     * @return void
     */
    public function apply()
    {
        if ($this->canApply()) {
            $subject = $this->getSubject();
            $parentNode = $subject->findParentNode();
            $newlyCreatedNode = $this->createNode($parentNode);

            $this->nodeCommandHandler->handleMoveNode(new MoveNode(
                $newlyCreatedNode->getContentStreamIdentifier(),
                $newlyCreatedNode->getDimensionSpacePoint(),
                $newlyCreatedNode->getNodeAggregateIdentifier(),
                null,
                $subject->getNodeAggregateIdentifier(), // TODO notfully correct I THINK, but for first tests it seems to work.
                RelationDistributionStrategy::gatherAll()
            ));

            $this->updateWorkspaceInfo();
        }
    }
}
