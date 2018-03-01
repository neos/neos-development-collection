<?php
namespace Neos\ContentRepository\Domain\Projection\Changes;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Change Read Model
 */
class Change
{
    /**
     * @var ContentStreamIdentifier
     */
    public $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    public $nodeIdentifier;

    /**
     * @var bool
     */
    public $changed;

    /**
     * @var bool
     */
    public $moved;

    /**
     * Change constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param bool $changed
     * @param bool $moved
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        bool $changed = false,
        bool $moved = false
    )
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->changed = $changed;
        $this->moved = $moved;
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentrepository_projection_change', [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeIdentifier' => (string)$this->nodeIdentifier,
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update('neos_contentrepository_projection_change', [
            'changed' => (int)$this->changed,
            'moved' => (int)$this->moved
        ],
        [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'nodeIdentifier' => (string)$this->nodeIdentifier,
        ]);
    }

    /**
     * @param array $databaseRow
     * @return static
     */
    public static function fromDatabaseRow(array $databaseRow)
    {
        return new static(
            new ContentStreamIdentifier($databaseRow['contentStreamIdentifier']),
            new NodeIdentifier($databaseRow['nodeIdentifier']),
            (bool)$databaseRow['changed'],
            (bool)$databaseRow['moved']
        );
    }
}
