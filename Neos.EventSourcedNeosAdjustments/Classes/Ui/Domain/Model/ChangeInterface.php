<?php
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * An interface to describe a change
 */
interface ChangeInterface
{

    /**
     * Set the subject
     *
     * @param NodeInterface $subject
     * @return void
     */
    public function setSubject(NodeInterface $subject);

    /**
     * Get the subject
     *
     * @return NodeInterface
     */
    public function getSubject();

    /**
     * Checks whether this change can be applied to the subject
     *
     * @return boolean
     */
    public function canApply();

    /**
     * Applies this change
     *
     * @return void
     */
    public function apply();
}
