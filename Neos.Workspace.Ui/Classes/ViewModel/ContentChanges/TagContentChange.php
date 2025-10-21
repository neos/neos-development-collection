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

namespace Neos\Workspace\Ui\ViewModel\ContentChanges;

use Neos\Flow\Annotations as Flow;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class TagContentChange
{
    /**
     * @param list<string> $addedTags
     * @param list<string> $removedTags
     */
    public function __construct(
        public array $addedTags,
        public array $removedTags,
    ) {
    }
}
