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

/**
 * A collection of changes
 */
class ChangeCollection
{
    /**
     * Changes in this collection
     *
     * @var array
     */
    protected $changes = [];

    /**
     * Add a change to this collection
     *
     * @param ChangeInterface $change
     * @return void
     */
    public function add(ChangeInterface $change)
    {
        $this->changes[] = $change;
    }

    /**
     * Apply all changes
     *
     * @return void
     */
    public function apply()
    {
        while ($change = array_shift($this->changes)) {
            if ($change->canApply()) {
                $change->apply();
            }
        }
    }

    /**
     * Get the number of changes in this collection
     *
     * @return integer
     */
    public function count()
    {
        return count($this->changes);
    }
}
