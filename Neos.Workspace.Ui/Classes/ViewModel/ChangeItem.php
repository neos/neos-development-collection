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
final readonly class ChangeItem
{
    /** @param list<string> $dimensions */
    public function __construct(
        public string $serializedNodeAddress,
        public bool $hidden,
        public bool $isRemoved,
        public bool $isNew,
        public bool $isMoved,
        public array $dimensions,
        public ?string $lastModificationDateTime,
        public ?string $createdDateTime,
        public string $label,
        public ?string $icon,
        public ContentChangeItems $contentChanges,
    ) {
    }
}
