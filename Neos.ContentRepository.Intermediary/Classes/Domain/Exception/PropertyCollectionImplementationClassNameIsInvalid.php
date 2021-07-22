<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Exception;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\PropertyCollectionInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if an invalid property collection implementation class name is attempted to be initialized
 *
 * @Flow\Proxy(false)
 */
final class PropertyCollectionImplementationClassNameIsInvalid extends \DomainException
{
    public static function becauseTheClassDoesNotExist(string $attemptedClassName): self
    {
        return new self('The given property collection implementation class "' . $attemptedClassName . '" does not exist.', 1615416178);
    }

    public static function becauseTheClassDoesNotImplementTheRequiredInterface(string $attemptedClassName): self
    {
        return new self('The given property collection implementation class "' . $attemptedClassName . '" does not implement the required ' . PropertyCollectionInterface::class . '.', 1615416214);
    }
}
