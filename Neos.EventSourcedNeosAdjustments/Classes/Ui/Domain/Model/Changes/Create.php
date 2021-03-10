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

class Create extends AbstractCreate
{

    /**
     * @param string $parentContextPath
     */
    public function setParentContextPath($parentContextPath)
    {
        // this method needs to exist; otherwise the TypeConverter breaks.
    }

    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    public function getMode()
    {
        return 'into';
    }

    /**
     * Check if the new node's node type is allowed in the requested position
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $subject = $this->getSubject();
        $nodeType = $this->getNodeType();

        return NodeInfoHelper::isNodeTypeAllowedAsChildNode($subject, $nodeType);
    }

    /**
     * Create a new node beneath the subject
     *
     * @return void
     */
    public function apply(): void
    {
        if ($this->canApply()) {
            $parentNode = $this->getSubject();
            $this->createNode($parentNode, null);
            $this->updateWorkspaceInfo();
        }
    }
}
