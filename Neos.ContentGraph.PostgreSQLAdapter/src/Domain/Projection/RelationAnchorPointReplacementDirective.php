<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/**
 * A collection of relation anchor point replacements:
 * each entry in the $toBeReplaced array is to be replaced by the entry in the $replacements array with the same index
 */
final class RelationAnchorPointReplacementDirective
{
    private function __construct(
        /** @var array<int,string> */
        public readonly array $toBeReplaced,
        /** @var array<int,string> */
        public readonly array $replacements
    ) {
    }

    /**
     * @param array<int,array<int,string>> $databaseRows
     */
    public static function fromDatabaseRows(array $databaseRows): self
    {
        $toBeReplaced = [];
        $replacements = [];
        foreach ($databaseRows as $databaseRow) {
            $toBeReplaced[] = $databaseRow['tobereplaced'];
            $replacements[] = $databaseRow['replacement'];
        }

        return new self($toBeReplaced, $replacements);
    }
}
