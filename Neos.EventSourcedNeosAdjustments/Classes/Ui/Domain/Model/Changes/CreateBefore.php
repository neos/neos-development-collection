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

use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;

class CreateBefore extends AbstractCreate
{
    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    public function getMode()
    {
        return 'before';
    }

    /**
     * Check if the new node's node type is allowed in the requested position
     *
     * @return boolean
     */
    public function canApply(): bool
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
    public function apply(): void
    {
        if ($this->canApply()) {
            $subject = $this->getSubject();
            $parent = $subject->findParentNode();
            $this->createNode($parent, $subject->getNodeAggregateIdentifier());
            $this->updateWorkspaceInfo();
        }
    }
}
