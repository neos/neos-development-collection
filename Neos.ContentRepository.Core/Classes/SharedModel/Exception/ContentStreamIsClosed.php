<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/**
 * The legacy exception stub if a content stream is closed but was tried to be used
 *
 * @deprecated This exception will never be thrown. This implementation is just kept for backwards-compatibility. Remove with Neos 10.0
 * @internal
 */
final class ContentStreamIsClosed extends \DomainException
{
}
