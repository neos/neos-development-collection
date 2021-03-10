<?php
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

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a command cannot be transformed to its serialized form
 *
 * @Flow\Proxy(false)
 */
final class CommandCannotBeTransformedToSerializedForm extends \DomainException
{
    public static function becauseTheNodeTypeDoesNotMatch(string $commandName, NodeTypeName $expectedNodeTypeName, NodeTypeName $actualNodeTypeName): self
    {
        throw new self(
            'The command ' . $commandName . ' cannot be transformed to serialized form because the given node type ' . $actualNodeTypeName . ' does not match the expected ' . $expectedNodeTypeName,
            1615331899
        );
    }
}
