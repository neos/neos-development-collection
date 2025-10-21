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

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
readonly class DocumentItem
{
    /** @param list<string> $documentBreadCrumb */
    public function __construct(
        public array $documentBreadCrumb,
        public string $aggregateId,
        public string $documentNodeAddress,
        public ?string $documentIcon
    ) {
    }
}
