<?php

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\ContentGraph\Exception;

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if an invalid node implementation class name is attempted to be initialized
 *
 * @Flow\Proxy(false)
 */
final class NodeImplementationClassNameIsInvalid extends \DomainException
{
    public static function becauseTheClassDoesNotExist(string $attemptedClassName): self
    {
        return new self(
            'The given node implementation class "' . $attemptedClassName . '" does not exist.',
            1615415122
        );
    }

    public static function becauseTheClassDoesNotImplementTheRequiredInterface(string $attemptedClassName): self
    {
        return new self(
            'The given node implementation class "' . $attemptedClassName
                . '" does not implement the required ' . Node::class . '.',
            1615415501
        );
    }

    public static function becauseTheClassImplementsTheDeprecatedLegacyInterface(string $attemptedClassName): self
    {
        return new self(
            'The given node implementation class "' . $attemptedClassName
                . '" implements the deprecated legacy Node.',
            1615415586
        );
    }
}
