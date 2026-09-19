<?php

/*
 * This file is part of the Neos.Restore.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel\Restore;

use Neos\Flow\Annotations as Flow;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class RestoreListItemVariantDetails
{
    /**
     * @param array<int,string> $ancestorLabels
     * @param array<int,string> $dimensionValueLabels
     */
    public function __construct(
        public string $label,
        public array $ancestorLabels,
        public array $dimensionValueLabels,
    ) {
    }
}
