<?php
declare(strict_types=1);
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

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;

/**
 * An interface to describe a change
 */
interface ChangeInterface
{

    /**
     * Set the subject
     *
     * @param TraversableNodeInterface $subject
     * @return void
     */
    public function setSubject(TraversableNodeInterface $subject);

    /**
     * Get the subject
     *
     * @return TraversableNodeInterface
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
