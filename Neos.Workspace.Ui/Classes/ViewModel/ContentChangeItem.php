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
use Neos\Workspace\Ui\ViewModel\ContentChanges\AssetContentChange;
use Neos\Workspace\Ui\ViewModel\ContentChanges\DateTimeContentChange;
use Neos\Workspace\Ui\ViewModel\ContentChanges\ImageContentChange;
use Neos\Workspace\Ui\ViewModel\ContentChanges\TagContentChange;
use Neos\Workspace\Ui\ViewModel\ContentChanges\TextContentChange;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
readonly class ContentChangeItem
{
    public function __construct(
        public ContentChangeProperties $properties,
        public AssetContentChange|DateTimeContentChange|ImageContentChange|TagContentChange|TextContentChange $changes,
    ) {
    }
}
