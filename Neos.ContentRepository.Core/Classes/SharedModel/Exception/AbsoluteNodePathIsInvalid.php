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

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/**
 * The exception to be thrown if an invalid absolute node path was tried to be initialized
 *
 * @api because this might have to be handled in the application layer
 */
final class AbsoluteNodePathIsInvalid extends \DomainException
{
    public static function becauseItDoesNotMatchTheRequiredPattern(string $attemptedValue): self
    {
        return new self(
            'Absolute node paths must serialized beginning with the pattern "/<My.Package:Root>" ,"'
                . $attemptedValue . '" does not',
            1687207234
        );
    }
}
