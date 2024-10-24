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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * @api used as part of commands
 */
final readonly class NodeReferencesForName
{
    /**
     * @param ReferenceName $referenceName
     * @param NodeReferenceToWrite[] $references
     */
    private function __construct(
        public ReferenceName $referenceName,
        public array $references
    ) {
        if (!self::isValidReferencesArray($references)) {
            throw new \InvalidArgumentException('References can only contain NodeReferenceToWrite instances.', 1729510972);
        }
    }

    /**
     * @param NodeReferenceToWrite[] $references
     */
    public static function fromNameAndReferences(ReferenceName $name, array $references): self
    {
        return new self($name, $references);
    }

    public static function emptyForName(ReferenceName $name): self
    {
        return new self($name, []);
    }

    /**
     * @param NodeReferenceToWrite[] $references
     */
    private static function isValidReferencesArray(array $references): bool
    {
        foreach ($references as $reference) {
            if (!$reference instanceof NodeReferenceToWrite) {
                return false;
            }
        }

        return true;
    }
}
