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
            $parentNode = $subject->findParentNode();

            $succeedingSibling = null;
            try {
                $succeedingSibling = $parentNode->findChildNodes()->next($subject);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $succeedingSibling is null.
            }

            if ($succeedingSibling) {
                $this->createNode($parentNode, $succeedingSibling->getNodeAggregateIdentifier());
            } else {
                $this->createNode($parentNode, null);
            }

            $this->updateWorkspaceInfo();
        }
    }
}
