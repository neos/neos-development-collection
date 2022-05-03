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

class CreateAfter extends AbstractCreate
{
    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     */
    public function getMode(): string
    {
        return 'after';
    }

    /**
     * Check if the new node's node type is allowed in the requested position
     */
    public function canApply(): bool
    {
        if (is_null($this->subject)) {
            return false;
        }
        $parent = $this->findParentNode($this->subject);
        $nodeType = $this->getNodeType();

        return $parent && $nodeType && $this->isNodeTypeAllowedAsChildNode($parent, $nodeType);
    }

    /**
     * Create a new node after the subject
     */
    public function apply(): void
    {
        $parentNode = $this->subject ? $this->findParentNode($this->subject) : null;
        $subject = $this->subject;
        if ($this->canApply() && !is_null($subject) && !is_null($parentNode)) {
            $succeedingSibling = null;
            try {
                $succeedingSibling = $this->findChildNodes($parentNode)->next($subject);
            } catch (\InvalidArgumentException $e) {
                // do nothing; $succeedingSibling is null.
            }

            $this->createNode($parentNode, $succeedingSibling?->getNodeAggregateIdentifier());

            $this->updateWorkspaceInfo();
        }
    }
}
