<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class PendingChanges
{
    public int $total;

    public function __construct(
        public int $new,
        public int $changed,
        public int $removed,
    ) {
        $this->total = $this->new + $this->changed + $this->removed;
    }

    public function getNewCountRatio(): float
    {
        return $this->new / $this->total * 100;
    }

    public function getChangedCountRatio(): float
    {
        return $this->changed / $this->total * 100;
    }

    public function getRemovedCountRatio(): float
    {
        return $this->removed / $this->total * 100;
    }
}
