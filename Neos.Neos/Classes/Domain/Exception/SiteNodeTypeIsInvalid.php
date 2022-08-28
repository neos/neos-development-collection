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

use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

#[Flow\Proxy(false)]
final class SiteNodeTypeIsInvalid extends \DomainException
{
    public static function becauseItIsNotOfTypeSite(NodeTypeName $attemptedNodeTypeName): self
    {
        return new self(
            'Node type name "' . $attemptedNodeTypeName
                . '" is not of required type "' . NodeTypeNameFactory::forSite() . '"',
            1412372375
        );
    }
}
