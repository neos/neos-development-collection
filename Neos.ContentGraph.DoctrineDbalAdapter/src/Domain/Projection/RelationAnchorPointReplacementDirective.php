<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * A collection of relation anchor point replacements:
 * each entry in the $replacement array contains the anchor to be replaced and its replacement
 */
final class RelationAnchorPointReplacementDirective
{
    private function __construct(
        /** @var array<int,array<int,string>> */
        private array $replacements,
    ) {
    }

    /**
     * @param array<int,array<int,string>> $databaseRows
     */
    public static function fromDatabaseRows(array $databaseRows): self
    {
        return new self($databaseRows);
    }

    public function getSelectionStatement(): string
    {
        return 'SELECT ' . implode(
            ' UNION ALL SELECT ',
            array_map(
                fn (array $replacement): string
                => '"' . $replacement['tobereplaced'] . '" AS tobereplaced, "'
                    . $replacement['replacement'] . '" AS replacement',
                $this->replacements
            )
        );
    }

    /**
     * @return array<int,string>
     */
    public function getToBeReplaced(): array
    {
        return array_map(
            fn (array $replacement): string => $replacement['tobereplaced'],
            $this->replacements
        );
    }
}
