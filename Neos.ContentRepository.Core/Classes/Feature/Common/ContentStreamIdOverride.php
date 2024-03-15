<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal (slightly hacky) implementation details for the workspace command handler
 */
class ContentStreamIdOverride
{
    /**
     * A content stream id that to use instead of the workspace one's {@see ConstraintChecks::requireContentStream()}
     */
    public static ?ContentStreamId $contentStreamIdToUse = null;

    /**
     * @internal
     */
    public static function useContentStreamId(?ContentStreamId $contentStreamIdToUse): void
    {
        self::$contentStreamIdToUse = $contentStreamIdToUse;
    }
}
