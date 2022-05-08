<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Exception;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

#[Flow\Proxy(false)]
final class SitesNodeIsMissing extends \DomainException
{
    public static function butWasRequested(): self
    {
        return new self(
            'The "' . NodeTypeNameFactory::forSites() . '" root node is missing.',
            1651956364
        );
    }
}
