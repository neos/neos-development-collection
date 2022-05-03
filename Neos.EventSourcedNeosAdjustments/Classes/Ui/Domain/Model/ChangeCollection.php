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

/**
 * A collection of changes
 */
class ChangeCollection
{
    /**
     * Changes in this collection
     *
     * @var array<int,ChangeInterface>
     */
    protected array $changes = [];

    /**
     * Add a change to this collection
     */
    public function add(ChangeInterface $change): void
    {
        $this->changes[] = $change;
    }

    /**
     * Apply all changes
     */
    public function apply(): void
    {
        while ($change = array_shift($this->changes)) {
            if ($change->canApply()) {
                $change->apply();
            }
        }
    }

    /**
     * Get the number of changes in this collection
     */
    public function count(): int
    {
        return count($this->changes);
    }
}
